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

$command = isset($argv[1]) ? $argv[1] : 'status';

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

$registry->set('load', new Loader($registry));
$registry->set('cache', new Cache($config->get('cache_engine'), $config->get('cache_expire')));

require_once DIR_APPLICATION . 'model/extension/redirect_manager/rule.php';

if (!class_exists('ModelExtensionRedirectManagerRule', false)) {
	$admin_model = $root . '/admin/model/extension/redirect_manager/rule.php';

	if (is_file($admin_model)) {
		require_once $admin_model;
	}
}

if (!class_exists('ModelExtensionRedirectManagerRule')) {
	fwrite(STDERR, "Redirect Manager is not installed\n");
	exit(1);
}

$model = new ModelExtensionRedirectManagerRule($registry);
$engine = new \redirect_manager\Engine($registry);
$backend_name = (string)$config->get('module_redirect_manager_backend');

if ($backend_name === '') {
	$backend_name = 'apache';
}

$backend = new \redirect_manager\Backend($registry, $backend_name);
$rules = $model->getEnabledRules();
$hash = $engine->rulesHash($rules);
$settings = array(
	'apache_mode'           => $config->get('module_redirect_manager_apache_mode'),
	'apache_file'           => $config->get('module_redirect_manager_apache_file'),
	'nginx_file'            => $config->get('module_redirect_manager_nginx_file'),
	'allow_reload'          => (int)$config->get('module_redirect_manager_allow_reload'),
	'cloudflare_token'      => $config->get('module_redirect_manager_cloudflare_token'),
	'cloudflare_account_id' => $config->get('module_redirect_manager_cloudflare_account_id'),
	'cloudflare_list_id'    => $config->get('module_redirect_manager_cloudflare_list_id'),
	'cloudflare_domain'     => $config->get('module_redirect_manager_cloudflare_domain')
);

switch ($command) {
	case 'validate':
		$errors = $backend->validateRules($rules);
		$loops = $engine->detectLoops($rules);
		echo 'Rules: ' . count($rules) . PHP_EOL;
		echo 'Errors: ' . count($errors) . PHP_EOL;
		echo 'Loops: ' . count($loops) . PHP_EOL;
		exit($errors || $loops ? 1 : 0);

	case 'generate':
	case 'sync':
	case 'cloudflare-sync':
		$sync = $model->getSync($backend_name);

		if ($command !== 'cloudflare-sync' && $sync && $sync['rules_hash'] === $hash && $sync['status'] === 'synced') {
			echo "Hash unchanged, skip\n";
			exit(0);
		}

		$result = $backend->deploy($rules, $settings);

		if (!empty($result['ok'])) {
			$model->saveSync(array(
				'backend'        => $backend_name,
				'status'         => 'synced',
				'rules_hash'     => $hash,
				'file_hash'      => isset($result['hash']) ? $result['hash'] : '',
				'file_path'      => isset($result['path']) ? $result['path'] : '',
				'rules_count'    => isset($result['count']) ? $result['count'] : 0,
				'operation_id'   => isset($result['operation_id']) ? $result['operation_id'] : '',
				'date_generated' => 1,
				'date_synced'    => 1
			));
			$model->markClean($hash);
			echo 'OK ' . (isset($result['count']) ? $result['count'] : 0) . ' rules -> ' . (isset($result['path']) ? $result['path'] : '') . PHP_EOL;
			exit(0);
		}

		fwrite(STDERR, "Failed\n");
		print_r(isset($result['errors']) ? $result['errors'] : array());
		exit(1);

	case 'status':
	default:
		$sync = $model->getSync($backend_name);
		echo 'Backend: ' . $backend_name . PHP_EOL;
		echo 'Rules: ' . count($rules) . PHP_EOL;
		echo 'Hash: ' . $hash . PHP_EOL;
		echo 'Dirty: ' . (int)$config->get('module_redirect_manager_configuration_dirty') . PHP_EOL;
		echo 'Status: ' . (isset($sync['status']) ? $sync['status'] : 'idle') . PHP_EOL;
		echo 'File: ' . (isset($sync['file_path']) ? $sync['file_path'] : '') . PHP_EOL;
		exit(0);
}
