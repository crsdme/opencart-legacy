<?php
if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "CLI only\n");
	exit(1);
}

$root = dirname(__DIR__);
chdir($root);

$admin_config = $root . '/admin/config.php';
$catalog_config = $root . '/config.php';

if (is_file($admin_config)) {
	require $admin_config;
} elseif (is_file($catalog_config)) {
	require $catalog_config;
} else {
	fwrite(STDERR, "OpenCart config.php not found\n");
	exit(1);
}

if (!defined('DIR_SYSTEM')) {
	fwrite(STDERR, "Invalid OpenCart config\n");
	exit(1);
}

require_once DIR_SYSTEM . 'startup.php';

$registry = new Registry();
$config = new Config();
$config->load('default');
$registry->set('config', $config);

$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$registry->set('db', $db);

$query = $db->query("SELECT * FROM `" . DB_PREFIX . "setting` WHERE store_id = '0'");

foreach ($query->rows as $setting) {
	$config->set($setting['key'], $setting['serialized'] ? json_decode($setting['value'], true) : $setting['value']);
}

$log_name = $config->get('config_error_filename') ? $config->get('config_error_filename') : 'error.log';
$registry->set('log', new Log($log_name));
$registry->set('event', new Event($registry));
$registry->set('load', new Loader($registry));
$registry->set('cache', new Cache($config->get('cache_engine'), $config->get('cache_expire')));

$command = isset($argv[1]) ? $argv[1] : 'help';
$engine = new \import_export\Engine($registry);
$job = new \import_export\Job($db);

@set_time_limit(0);

switch ($command) {
	case 'template':
		$entity = isset($argv[2]) ? $argv[2] : 'product';
		$format = isset($argv[3]) ? $argv[3] : 'json';
		$file = $engine->template($entity, $format);
		echo $file['content'];
		exit(0);

	case 'export':
		$entity = isset($argv[2]) ? $argv[2] : 'product';
		$format = isset($argv[3]) ? $argv[3] : 'json';
		$file = $engine->export($entity, $format);
		$job->add([
			'trigger' => 'cli',
			'action' => 'export',
			'entity' => $entity,
			'format' => $format,
			'filename' => $file['filename'],
			'status' => 'success',
			'created' => 0,
			'updated' => 0,
			'skipped' => 0,
			'errors' => 0,
			'message' => '',
		]);
		echo $file['content'];
		exit(0);

	case 'import':
		$path = isset($argv[2]) ? $argv[2] : '';
		$dry = in_array('--dry-run', $argv, true);

		if ($path === '' || !is_file($path)) {
			fwrite(STDERR, "Usage: php cli/import_export.php import file.json [--dry-run]\n");
			exit(1);
		}

		$content = file_get_contents($path);
		$format = $engine->detectFormat($path, $content);
		$parsed = $engine->parse($content, $format);

		if ($dry) {
			$preview = $engine->preview($parsed);
			echo 'create=' . $preview['counts']['create'] . ' update=' . $preview['counts']['update'] . ' skip=' . $preview['counts']['skip'] . ' error=' . $preview['counts']['error'] . PHP_EOL;

			foreach ($preview['sample'] as $row) {
				if ($row['action'] === 'error' || $row['error'] !== '') {
					echo $row['action'] . ' ' . $row['identity'] . ' ' . $row['error'] . PHP_EOL;
				}
			}

			exit($preview['counts']['error'] ? 1 : 0);
		}

		$result = $engine->import($parsed);
		$job->add([
			'trigger' => 'cli',
			'action' => 'import',
			'entity' => $parsed['entity'],
			'format' => $format,
			'filename' => basename($path),
			'status' => $result['errors'] ? 'error' : 'success',
			'created' => $result['created'],
			'updated' => $result['updated'],
			'skipped' => $result['skipped'],
			'errors' => $result['errors'],
			'message' => $result['message'],
		]);

		echo 'created=' . $result['created'] . ' updated=' . $result['updated'] . ' skipped=' . $result['skipped'] . ' errors=' . $result['errors'] . PHP_EOL;

		if ($result['message'] !== '') {
			fwrite(STDERR, $result['message'] . PHP_EOL);
		}

		exit($result['errors'] ? 1 : 0);

	case 'help':
	default:
		echo "php cli/import_export.php template [entity|bundle] [json|csv]\n";
		echo "php cli/import_export.php export [entity|bundle] [json|csv]\n";
		echo "php cli/import_export.php import file.json [--dry-run]\n";
		exit($command === 'help' ? 0 : 1);
}
