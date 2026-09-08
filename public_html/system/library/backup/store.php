<?php

namespace Backup;

class Store
{
	const TOKEN_MASK = '********';
	const CODE = 'module_auto_backup';

	private $registry;
	private $secrets = [
		'module_auto_backup_ftp_password',
		'module_auto_backup_google_client_secret',
		'module_auto_backup_google_refresh_token',
	];

	public function __construct($registry)
	{
		$this->registry = $registry;
	}

	public function get($key, $default = '')
	{
		$value = $this->registry->get('config')->get($key);

		if ($value === null || $value === false) {
			return $default;
		}

		return $value;
	}

	public function getSecret($key)
	{
		return $this->decryptValue((string) $this->get($key, ''));
	}

	public function enabled()
	{
		return (int) $this->get('module_auto_backup_status', 0) === 1;
	}

	public function storageDir()
	{
		$dir = rtrim(DIR_STORAGE, '/\\') . '/backup';

		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}

		return $dir;
	}

	public function catalogUrl($route, $query = '')
	{
		$base = defined('HTTPS_CATALOG') ? HTTPS_CATALOG : (defined('HTTPS_SERVER') ? HTTPS_SERVER : HTTP_SERVER);
		$url = rtrim($base, '/') . '/index.php?route=' . $route;

		if ($query !== '') {
			$url .= '&' . ltrim($query, '&');
		}

		return $url;
	}

	public function cronUrl()
	{
		$token = (string) $this->get('module_auto_backup_cron_token', '');

		return $this->catalogUrl('extension/auto_backup/cron', 'cron_token=' . rawurlencode($token));
	}

	public function oauthRedirectUri()
	{
		return $this->catalogUrl('extension/auto_backup/oauth');
	}

	public function googleAuthUrl($state)
	{
		$params = [
			'client_id' => (string) $this->get('module_auto_backup_google_client_id', ''),
			'redirect_uri' => $this->oauthRedirectUri(),
			'response_type' => 'code',
			'scope' => 'https://www.googleapis.com/auth/drive.file',
			'access_type' => 'offline',
			'prompt' => 'consent',
			'state' => $state,
		];

		return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
	}

	public function maskToken($value)
	{
		$value = (string) $value;

		if ($value === '') {
			return '';
		}

		return self::TOKEN_MASK;
	}

	public function encryptValue($value)
	{
		if ($value === '' || $value === self::TOKEN_MASK) {
			return $value;
		}

		$key = (string) $this->registry->get('config')->get('config_encryption');

		if ($key === '') {
			return base64_encode($value);
		}

		$encryption = new \Encryption();

		return $encryption->encrypt($key, $value);
	}

	public function decryptValue($value)
	{
		$value = (string) $value;

		if ($value === '' || $value === self::TOKEN_MASK) {
			return '';
		}

		$key = (string) $this->registry->get('config')->get('config_encryption');

		if ($key === '') {
			$decoded = base64_decode($value, true);

			return ($decoded !== false) ? $decoded : $value;
		}

		$encryption = new \Encryption();
		$decrypted = $encryption->decrypt($key, $value);

		return ($decrypted !== false && $decrypted !== null) ? $decrypted : '';
	}

	public function saveKey($key, $value)
	{
		$db = $this->registry->get('db');
		$serialized = is_array($value) ? 1 : 0;
		$stored = $serialized ? json_encode($value) : (string) $value;

		$query = $db->query(
			"SELECT setting_id FROM `" . DB_PREFIX . "setting`
			WHERE `store_id` = '0' AND `code` = '" . $db->escape(self::CODE) . "'
				AND `key` = '" . $db->escape($key) . "'"
		);

		if ($query->num_rows) {
			$db->query(
				"UPDATE `" . DB_PREFIX . "setting` SET `value` = '" . $db->escape($stored) . "',
					`serialized` = '" . (int) $serialized . "'
				WHERE `setting_id` = '" . (int) $query->row['setting_id'] . "'"
			);
		} else {
			$db->query(
				"INSERT INTO `" . DB_PREFIX . "setting` SET
					`store_id` = '0',
					`code` = '" . $db->escape(self::CODE) . "',
					`key` = '" . $db->escape($key) . "',
					`value` = '" . $db->escape($stored) . "',
					`serialized` = '" . (int) $serialized . "'"
			);
		}

		$this->registry->get('config')->set($key, $value);
	}

	public function saveAll($post)
	{
		$current = $this->all();
		$out = [];

		foreach ($this->defaults() as $key => $default) {
			if (!array_key_exists($key, $post) && in_array($key, $this->secrets, true)) {
				$out[$key] = isset($current[$key]) ? $current[$key] : '';
				continue;
			}

			$value = array_key_exists($key, $post) ? $post[$key] : $default;

			if ($key === 'module_auto_backup_cron_token') {
				if ($value === '' || !array_key_exists($key, $post)) {
					$out[$key] = isset($current[$key]) && $current[$key] !== '' ? $current[$key] : $default;
					continue;
				}
			}

			if (in_array($key, $this->secrets, true)) {
				if ($value === '' || $value === self::TOKEN_MASK) {
					$out[$key] = isset($current[$key]) ? $current[$key] : '';
				} else {
					$out[$key] = $this->encryptValue($value);
				}
			} else {
				$out[$key] = $value;
			}
		}

		$keep = [
			'module_auto_backup_google_refresh_token',
			'module_auto_backup_google_folder_id',
			'module_auto_backup_last_success',
			'module_auto_backup_oauth_state',
		];

		foreach ($keep as $key) {
			if (!array_key_exists($key, $post) && isset($current[$key])) {
				$out[$key] = $current[$key];
			}
		}

		$this->registry->get('load')->model('setting/setting');
		$this->registry->get('model_setting_setting')->editSetting(self::CODE, $out);
	}

	public function defaults()
	{
		return [
			'module_auto_backup_status' => 1,
			'module_auto_backup_cron_token' => bin2hex(random_bytes(16)),
			'module_auto_backup_interval' => 24,
			'module_auto_backup_include_database' => 1,
			'module_auto_backup_include_images' => 1,
			'module_auto_backup_include_downloads' => 0,
			'module_auto_backup_include_config' => 0,
			'module_auto_backup_exclude_tables' => "session\ncustomer_online",
			'module_auto_backup_exclude_paths' => '',
			'module_auto_backup_keep' => 7,
			'module_auto_backup_destination' => 'local',
			'module_auto_backup_keep_local' => 1,
			'module_auto_backup_email_status' => 1,
			'module_auto_backup_email' => '',
			'module_auto_backup_ftp_host' => '',
			'module_auto_backup_ftp_port' => 21,
			'module_auto_backup_ftp_user' => '',
			'module_auto_backup_ftp_password' => '',
			'module_auto_backup_ftp_path' => '/backups',
			'module_auto_backup_ftp_ssl' => 0,
			'module_auto_backup_google_client_id' => '',
			'module_auto_backup_google_client_secret' => '',
			'module_auto_backup_google_refresh_token' => '',
			'module_auto_backup_google_folder_id' => '',
			'module_auto_backup_last_success' => '',
			'module_auto_backup_oauth_state' => '',
			'module_auto_backup_delete_data_on_uninstall' => 0,
		];
	}

	public function all()
	{
		$this->registry->get('load')->model('setting/setting');

		return $this->registry->get('model_setting_setting')->getSetting(self::CODE);
	}

	public function lines($key)
	{
		$raw = (string) $this->get($key, '');
		$lines = preg_split('/\r\n|\r|\n/', $raw);
		$out = [];

		foreach ($lines as $line) {
			$line = trim($line);

			if ($line !== '' && $line[0] !== '#') {
				$out[] = $line;
			}
		}

		return $out;
	}

	public function destination()
	{
		$code = (string) $this->get('module_auto_backup_destination', 'local');

		if ($code === 'ftp') {
			return new Destination\Ftp($this);
		}

		if ($code === 'google_drive') {
			return new Destination\GoogleDrive($this);
		}

		return new Destination\Local($this);
	}
}
