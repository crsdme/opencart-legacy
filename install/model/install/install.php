<?php
class ModelInstallInstall extends Model {
	public function database($data) {
		$db = new DB($data['db_driver'], htmlspecialchars_decode($data['db_hostname']), htmlspecialchars_decode($data['db_username']), htmlspecialchars_decode($data['db_password']), htmlspecialchars_decode($data['db_database']), $data['db_port']);

		$prefix = $data['db_prefix'];
		$sql_dir = DIR_APPLICATION . 'sql/';

		$this->applySqlFile($db, $sql_dir . 'schema.sql', $prefix);

		foreach ($this->sqlFiles($sql_dir . 'system') as $file) {
			$this->applySqlFile($db, $file, $prefix);
		}

		$sample_data = !empty($data['sample_data']);

		if ($sample_data) {
			foreach ($this->sqlFiles($sql_dir . 'demo') as $file) {
				$this->applySqlFile($db, $file, $prefix);
			}
		} else {
			$db->query("UPDATE `" . $prefix . "setting` SET `value` = '0' WHERE `key` = 'configblog_blog_menu'");
			$db->query("DELETE FROM `" . $prefix . "setting` WHERE `key` = 'config_pages_blog'");
			$db->query("INSERT INTO `" . $prefix . "setting` SET `store_id` = '0', `code` = 'config', `key` = 'config_pages_blog', `value` = '0', `serialized` = '0'");
			$db->query("UPDATE `" . $prefix . "modification` SET `status` = '0'");
		}

		$db->query("SET CHARACTER SET utf8");

		$db->query("SET @@session.sql_mode = ''");

		$db->query("DELETE FROM `" . $prefix . "user` WHERE user_id = '1'");

		$db->query("INSERT INTO `" . $prefix . "user` SET user_id = '1', user_group_id = '1', username = '" . $db->escape($data['username']) . "', salt = '" . $db->escape($salt = token(9)) . "', password = '" . $db->escape(sha1($salt . sha1($salt . sha1($data['password'])))) . "', firstname = 'John', lastname = 'Doe', email = '" . $db->escape($data['email']) . "', status = '1', date_added = NOW()");

		$db->query("DELETE FROM `" . $prefix . "setting` WHERE `key` = 'config_email'");
		$db->query("INSERT INTO `" . $prefix . "setting` SET `code` = 'config', `key` = 'config_email', value = '" . $db->escape($data['email']) . "'");

		$db->query("DELETE FROM `" . $prefix . "setting` WHERE `key` = 'config_encryption'");
		$db->query("INSERT INTO `" . $prefix . "setting` SET `code` = 'config', `key` = 'config_encryption', value = '" . $db->escape(token(1024)) . "'");

		$db->query("UPDATE `" . $prefix . "product` SET `viewed` = '0'");

		$db->query("INSERT INTO `" . $prefix . "api` SET username = 'Default', `key` = '" . $db->escape(token(256)) . "', status = 1, date_added = NOW(), date_modified = NOW()");

		$api_id = $db->getLastId();

		$db->query("DELETE FROM `" . $prefix . "setting` WHERE `key` = 'config_api_id'");
		$db->query("INSERT INTO `" . $prefix . "setting` SET `code` = 'config', `key` = 'config_api_id', value = '" . (int)$api_id . "'");

		require_once DIR_APPLICATION . 'model/install/language_copy.php';

		$ips = array('127.0.0.1', '::1');

		if (!empty($this->request->server['REMOTE_ADDR'])) {
			$ips[] = $this->request->server['REMOTE_ADDR'];
		}

		install_api_ips($db, $prefix, $api_id, $ips);
		install_copy_language($db, $prefix, 1, 3, 'ru');

		// set the current years prefix
		$db->query("UPDATE `" . $prefix . "setting` SET `value` = 'INV-" . date('Y') . "-00' WHERE `key` = 'config_invoice_prefix'");
	}

	private function sqlFiles($directory) {
		$files = glob(rtrim($directory, '/\\') . '/*.sql');

		if (!$files) {
			return array();
		}

		sort($files, SORT_STRING);

		return $files;
	}

	private function applySqlFile($db, $file, $prefix) {
		if (!is_file($file)) {
			exit('Could not load sql file: ' . $file);
		}

		$lines = file($file);

		if (!$lines) {
			return;
		}

		$db->query("SET sql_mode = ''");

		$sql = '';

		foreach ($lines as $line) {
			if ($line && (substr($line, 0, 2) != '--') && (substr($line, 0, 1) != '#')) {
				$sql .= $line;

				if (preg_match('/;\s*$/', $line)) {
					$sql = str_replace("DROP TABLE IF EXISTS `oc_", "DROP TABLE IF EXISTS `" . $prefix, $sql);
					$sql = str_replace("CREATE TABLE `oc_", "CREATE TABLE `" . $prefix, $sql);
					$sql = str_replace("INSERT INTO `oc_", "INSERT INTO `" . $prefix, $sql);

					$db->query($sql);

					$sql = '';
				}
			}
		}
	}
}
