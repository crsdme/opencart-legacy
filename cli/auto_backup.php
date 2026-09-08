<?php
if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "CLI only\n");
	exit(1);
}

$root = dirname(__DIR__);
chdir($root);

$catalog_config = $root . '/config.php';
$admin_config = $root . '/admin/config.php';

if (is_file($catalog_config)) {
	require $catalog_config;
} elseif (is_file($admin_config)) {
	require $admin_config;
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
$registry->set('load', new Loader($registry));

@set_time_limit(0);

$job = new \Backup\Job($registry);
$force = in_array('force', $argv, true);
$result = $job->run('cron', $force);
$prefix = !empty($result['ok']) ? 'OK' : 'ERROR';

echo $prefix . ': ' . $result['message'] . PHP_EOL;
exit(!empty($result['ok']) ? 0 : 1);
