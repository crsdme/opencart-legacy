<?php

class ModelExtensionRedirectManagerRule extends Model {
	public function install() {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "redirect_manager_rule` (
			`redirect_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`store_id` INT NOT NULL DEFAULT 0,
			`language_id` INT NOT NULL DEFAULT 0,
			`source_url` VARCHAR(2048) NOT NULL,
			`source_hash` CHAR(64) NOT NULL,
			`target_url` VARCHAR(2048) DEFAULT NULL,
			`action` VARCHAR(16) NOT NULL DEFAULT 'redirect',
			`http_code` SMALLINT UNSIGNED NOT NULL,
			`query_mode` VARCHAR(16) NOT NULL DEFAULT 'ignore',
			`source_type` VARCHAR(32) NOT NULL DEFAULT 'manual',
			`source_entity_id` INT UNSIGNED DEFAULT NULL,
			`target_type` VARCHAR(32) DEFAULT NULL,
			`target_entity_id` INT UNSIGNED DEFAULT NULL,
			`batch_id` INT UNSIGNED DEFAULT NULL,
			`enabled` TINYINT(1) NOT NULL DEFAULT 1,
			`priority` INT NOT NULL DEFAULT 0,
			`broken_target` TINYINT(1) NOT NULL DEFAULT 0,
			`date_added` DATETIME NOT NULL,
			`date_modified` DATETIME NOT NULL,
			PRIMARY KEY (`redirect_id`),
			UNIQUE KEY `uniq_source` (`store_id`, `language_id`, `source_hash`),
			KEY `idx_source_url` (`source_url`(191)),
			KEY `idx_target_url` (`target_url`(191)),
			KEY `idx_enabled` (`enabled`),
			KEY `idx_action_code` (`action`, `http_code`),
			KEY `idx_source_entity` (`source_type`, `source_entity_id`),
			KEY `idx_target_entity` (`target_type`, `target_entity_id`),
			KEY `idx_batch` (`batch_id`),
			KEY `idx_broken` (`broken_target`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "redirect_manager_batch` (
			`batch_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`store_id` INT NOT NULL DEFAULT 0,
			`type` VARCHAR(32) NOT NULL,
			`title` VARCHAR(255) NOT NULL,
			`payload` MEDIUMTEXT,
			`rules_count` INT UNSIGNED NOT NULL DEFAULT 0,
			`user_id` INT UNSIGNED NOT NULL DEFAULT 0,
			`status` VARCHAR(16) NOT NULL DEFAULT 'applied',
			`date_added` DATETIME NOT NULL,
			PRIMARY KEY (`batch_id`),
			KEY `idx_type_status` (`type`, `status`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "redirect_manager_url_history` (
			`history_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`store_id` INT NOT NULL,
			`language_id` INT NOT NULL,
			`entity_type` VARCHAR(32) NOT NULL,
			`entity_id` INT UNSIGNED NOT NULL,
			`url` VARCHAR(2048) NOT NULL,
			`url_hash` CHAR(64) NOT NULL,
			`date_added` DATETIME NOT NULL,
			`date_removed` DATETIME DEFAULT NULL,
			`active` TINYINT(1) NOT NULL DEFAULT 1,
			PRIMARY KEY (`history_id`),
			KEY `idx_entity` (`entity_type`, `entity_id`),
			KEY `idx_active` (`active`),
			KEY `idx_hash` (`store_id`, `language_id`, `url_hash`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "redirect_manager_sync` (
			`sync_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`backend` VARCHAR(16) NOT NULL,
			`store_id` INT NOT NULL DEFAULT 0,
			`status` VARCHAR(16) NOT NULL DEFAULT 'idle',
			`rules_hash` CHAR(64) DEFAULT NULL,
			`file_hash` CHAR(64) DEFAULT NULL,
			`file_path` VARCHAR(512) DEFAULT NULL,
			`rules_count` INT UNSIGNED NOT NULL DEFAULT 0,
			`operation_id` VARCHAR(128) DEFAULT NULL,
			`error_message` TEXT,
			`date_generated` DATETIME DEFAULT NULL,
			`date_synced` DATETIME DEFAULT NULL,
			PRIMARY KEY (`sync_id`),
			UNIQUE KEY `uniq_backend_store` (`backend`, `store_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "redirect_manager_log` (
			`log_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`date_added` DATETIME NOT NULL,
			`user_id` INT UNSIGNED NOT NULL DEFAULT 0,
			`action` VARCHAR(64) NOT NULL,
			`entity` VARCHAR(64) DEFAULT NULL,
			`entity_id` INT UNSIGNED DEFAULT NULL,
			`before_json` MEDIUMTEXT,
			`after_json` MEDIUMTEXT,
			`backend` VARCHAR(32) DEFAULT NULL,
			`result` VARCHAR(16) NOT NULL DEFAULT 'ok',
			`message` VARCHAR(512) DEFAULT NULL,
			PRIMARY KEY (`log_id`),
			KEY `idx_date` (`date_added`),
			KEY `idx_action` (`action`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$engine = new \redirect_manager\Engine($this->registry);
		$root = $engine->storageRoot();

		foreach (array($root, $root . '/apache', $root . '/nginx', $root . '/cloudflare', $root . '/php', $root . '/logs') as $dir) {
			if (!is_dir($dir)) {
				mkdir($dir, 0755, true);
			}

			if (!is_file($dir . '/index.html')) {
				file_put_contents($dir . '/index.html', '');
			}
		}

		if (!is_file($root . '/.htaccess')) {
			file_put_contents($root . '/.htaccess', "Deny from all\n");
		}

		$this->ensureErrorLayouts();
	}

	public function ensureErrorLayouts() {
		$pages = array(
			array('name' => '404', 'route' => 'error/not_found'),
			array('name' => '410', 'route' => 'error/gone')
		);

		foreach ($pages as $page) {
			$exists = $this->db->query("SELECT layout_route_id FROM `" . DB_PREFIX . "layout_route` WHERE route = '" . $this->db->escape($page['route']) . "' LIMIT 1");

			if ($exists->num_rows) {
				continue;
			}

			$this->db->query("INSERT INTO `" . DB_PREFIX . "layout` SET name = '" . $this->db->escape($page['name']) . "'");
			$layout_id = $this->db->getLastId();
			$this->db->query("INSERT INTO `" . DB_PREFIX . "layout_route` SET layout_id = '" . (int)$layout_id . "', store_id = '0', route = '" . $this->db->escape($page['route']) . "'");
		}
	}

	public function uninstall($delete_data) {
		if (!$delete_data) {
			return;
		}

		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "redirect_manager_rule`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "redirect_manager_batch`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "redirect_manager_url_history`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "redirect_manager_sync`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "redirect_manager_log`");
	}

	private function engine() {
		return new \redirect_manager\Engine($this->registry);
	}

	public function addRule($data) {
		$engine = $this->engine();
		$source = $engine->normalize($data['source_url']);
		$target = ($data['action'] === 'redirect') ? $engine->normalize($data['target_url']) : array('storage' => '');
		$hash = $engine->sourceHash($data['store_id'], $data['language_id'], $source['storage']);

		$this->db->query("INSERT INTO `" . DB_PREFIX . "redirect_manager_rule` SET
			store_id = '" . (int)$data['store_id'] . "',
			language_id = '" . (int)$data['language_id'] . "',
			source_url = '" . $this->db->escape($source['storage']) . "',
			source_hash = '" . $this->db->escape($hash) . "',
			target_url = '" . $this->db->escape($target['storage']) . "',
			action = '" . $this->db->escape($data['action']) . "',
			http_code = '" . (int)$data['http_code'] . "',
			query_mode = '" . $this->db->escape(isset($data['query_mode']) ? $data['query_mode'] : 'ignore') . "',
			source_type = '" . $this->db->escape(isset($data['source_type']) ? $data['source_type'] : 'manual') . "',
			source_entity_id = " . $this->nullableInt(isset($data['source_entity_id']) ? $data['source_entity_id'] : null) . ",
			target_type = '" . $this->db->escape(isset($data['target_type']) ? $data['target_type'] : 'custom') . "',
			target_entity_id = " . $this->nullableInt(isset($data['target_entity_id']) ? $data['target_entity_id'] : null) . ",
			batch_id = " . $this->nullableInt(isset($data['batch_id']) ? $data['batch_id'] : null) . ",
			enabled = '" . (int)$data['enabled'] . "',
			priority = '" . (int)(isset($data['priority']) ? $data['priority'] : 0) . "',
			broken_target = '0',
			date_added = NOW(),
			date_modified = NOW()");

		$this->markDirty();

		return $this->db->getLastId();
	}

	public function editRule($redirect_id, $data) {
		$engine = $this->engine();
		$source = $engine->normalize($data['source_url']);
		$target = ($data['action'] === 'redirect') ? $engine->normalize($data['target_url']) : array('storage' => '');
		$hash = $engine->sourceHash($data['store_id'], $data['language_id'], $source['storage']);

		$this->db->query("UPDATE `" . DB_PREFIX . "redirect_manager_rule` SET
			store_id = '" . (int)$data['store_id'] . "',
			language_id = '" . (int)$data['language_id'] . "',
			source_url = '" . $this->db->escape($source['storage']) . "',
			source_hash = '" . $this->db->escape($hash) . "',
			target_url = '" . $this->db->escape($target['storage']) . "',
			action = '" . $this->db->escape($data['action']) . "',
			http_code = '" . (int)$data['http_code'] . "',
			query_mode = '" . $this->db->escape(isset($data['query_mode']) ? $data['query_mode'] : 'ignore') . "',
			source_type = '" . $this->db->escape(isset($data['source_type']) ? $data['source_type'] : 'manual') . "',
			source_entity_id = " . $this->nullableInt(isset($data['source_entity_id']) ? $data['source_entity_id'] : null) . ",
			target_type = '" . $this->db->escape(isset($data['target_type']) ? $data['target_type'] : 'custom') . "',
			target_entity_id = " . $this->nullableInt(isset($data['target_entity_id']) ? $data['target_entity_id'] : null) . ",
			enabled = '" . (int)$data['enabled'] . "',
			priority = '" . (int)(isset($data['priority']) ? $data['priority'] : 0) . "',
			date_modified = NOW()
			WHERE redirect_id = '" . (int)$redirect_id . "'");

		$this->markDirty();
	}

	public function deleteRule($redirect_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "redirect_manager_rule` WHERE redirect_id = '" . (int)$redirect_id . "'");
		$this->markDirty();
	}

	public function getRule($redirect_id) {
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "redirect_manager_rule` WHERE redirect_id = '" . (int)$redirect_id . "'")->row;
	}

	public function getRuleBySource($store_id, $language_id, $source_url) {
		$engine = $this->engine();
		$source = $engine->normalize($source_url);
		$hash = $engine->sourceHash($store_id, $language_id, $source['storage']);

		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "redirect_manager_rule` WHERE store_id = '" . (int)$store_id . "' AND language_id = '" . (int)$language_id . "' AND source_hash = '" . $this->db->escape($hash) . "'")->row;
	}

	public function getRules($data = array()) {
		$sql = "SELECT * FROM `" . DB_PREFIX . "redirect_manager_rule` WHERE 1" . $this->filterSql($data);
		$sort = array('redirect_id', 'source_url', 'target_url', 'http_code', 'action', 'enabled', 'date_added', 'date_modified');
		$sql .= " ORDER BY `" . ((isset($data['sort']) && in_array($data['sort'], $sort, true)) ? $data['sort'] : 'redirect_id') . "`";
		$sql .= (isset($data['order']) && $data['order'] === 'ASC') ? " ASC" : " DESC";

		if (isset($data['start']) || isset($data['limit'])) {
			$start = max(0, isset($data['start']) ? (int)$data['start'] : 0);
			$limit = max(1, isset($data['limit']) ? (int)$data['limit'] : 20);
			$sql .= " LIMIT " . $start . "," . $limit;
		}

		return $this->db->query($sql)->rows;
	}

	public function getTotalRules($data = array()) {
		return (int)$this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "redirect_manager_rule` WHERE 1" . $this->filterSql($data))->row['total'];
	}

	public function getEnabledRules() {
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "redirect_manager_rule` WHERE enabled = '1' ORDER BY priority ASC, redirect_id ASC")->rows;
	}

	public function addRulesBatch($rows, $chunk_size = 400) {
		$engine = $this->engine();
		$inserted = 0;
		$conflicts = array();

		foreach (array_chunk($rows, $chunk_size) as $chunk) {
			$values = array();

			foreach ($chunk as $data) {
				$source = $engine->normalize($data['source_url']);
				$target = ($data['action'] === 'redirect') ? $engine->normalize($data['target_url']) : array('storage' => '');
				$existing = $this->getRuleBySource($data['store_id'], $data['language_id'], $source['storage']);

				if ($existing) {
					$conflicts[] = array(
						'source_url'     => $source['storage'],
						'current_target' => $existing['target_url'],
						'new_target'     => $target['storage'],
						'redirect_id'    => $existing['redirect_id']
					);

					if (!empty($data['replace'])) {
						$this->editRule($existing['redirect_id'], $data);
						$inserted++;
					}

					continue;
				}

				$hash = $engine->sourceHash($data['store_id'], $data['language_id'], $source['storage']);
				$values[] = "('" . (int)$data['store_id'] . "', '" . (int)$data['language_id'] . "', '" . $this->db->escape($source['storage']) . "', '" . $this->db->escape($hash) . "', '" . $this->db->escape($target['storage']) . "', '" . $this->db->escape($data['action']) . "', '" . (int)$data['http_code'] . "', '" . $this->db->escape(isset($data['query_mode']) ? $data['query_mode'] : 'ignore') . "', '" . $this->db->escape(isset($data['source_type']) ? $data['source_type'] : 'manual') . "', " . $this->nullableInt(isset($data['source_entity_id']) ? $data['source_entity_id'] : null) . ", '" . $this->db->escape(isset($data['target_type']) ? $data['target_type'] : 'custom') . "', " . $this->nullableInt(isset($data['target_entity_id']) ? $data['target_entity_id'] : null) . ", " . $this->nullableInt(isset($data['batch_id']) ? $data['batch_id'] : null) . ", '" . (int)$data['enabled'] . "', '0', '0', NOW(), NOW())";
			}

			if ($values) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "redirect_manager_rule` (store_id, language_id, source_url, source_hash, target_url, action, http_code, query_mode, source_type, source_entity_id, target_type, target_entity_id, batch_id, enabled, priority, broken_target, date_added, date_modified) VALUES " . implode(',', $values));
				$inserted += count($values);
			}
		}

		if ($inserted) {
			$this->markDirty();
		}

		return array('inserted' => $inserted, 'conflicts' => $conflicts);
	}

	public function bulkUpdate($ids, $fields) {
		if (!$ids) {
			return;
		}

		$set = array();

		if (isset($fields['enabled'])) {
			$set[] = "enabled = '" . (int)$fields['enabled'] . "'";
		}

		if (isset($fields['http_code'])) {
			$set[] = "http_code = '" . (int)$fields['http_code'] . "'";
		}

		if (isset($fields['action'])) {
			$set[] = "action = '" . $this->db->escape($fields['action']) . "'";
		}

		if (isset($fields['target_url'])) {
			$target = $this->engine()->normalize($fields['target_url']);
			$set[] = "target_url = '" . $this->db->escape($target['storage']) . "'";
		}

		if (!$set) {
			return;
		}

		$set[] = "date_modified = NOW()";
		$this->db->query("UPDATE `" . DB_PREFIX . "redirect_manager_rule` SET " . implode(', ', $set) . " WHERE redirect_id IN (" . $this->idList($ids) . ")");
		$this->markDirty();
	}

	public function bulkDelete($ids) {
		if (!$ids) {
			return;
		}

		$this->db->query("DELETE FROM `" . DB_PREFIX . "redirect_manager_rule` WHERE redirect_id IN (" . $this->idList($ids) . ")");
		$this->markDirty();
	}

	public function addBatch($data) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "redirect_manager_batch` SET
			store_id = '" . (int)$data['store_id'] . "',
			type = '" . $this->db->escape($data['type']) . "',
			title = '" . $this->db->escape($data['title']) . "',
			payload = '" . $this->db->escape(isset($data['payload']) ? json_encode($data['payload']) : '') . "',
			rules_count = '" . (int)$data['rules_count'] . "',
			user_id = '" . (int)$data['user_id'] . "',
			status = 'applied',
			date_added = NOW()");

		return $this->db->getLastId();
	}

	public function getBatches($start = 0, $limit = 20) {
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "redirect_manager_batch` ORDER BY batch_id DESC LIMIT " . (int)$start . "," . (int)$limit)->rows;
	}

	public function getBatch($batch_id) {
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "redirect_manager_batch` WHERE batch_id = '" . (int)$batch_id . "'")->row;
	}

	public function undoBatch($batch_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "redirect_manager_rule` WHERE batch_id = '" . (int)$batch_id . "'");
		$this->db->query("UPDATE `" . DB_PREFIX . "redirect_manager_batch` SET status = 'undone' WHERE batch_id = '" . (int)$batch_id . "'");
		$this->markDirty();
	}

	public function setBatchEnabled($batch_id, $enabled) {
		$this->db->query("UPDATE `" . DB_PREFIX . "redirect_manager_rule` SET enabled = '" . (int)$enabled . "', date_modified = NOW() WHERE batch_id = '" . (int)$batch_id . "'");
		$this->markDirty();
	}

	public function addLog($data) {
		$user_id = !empty($data['user_id']) ? (int)$data['user_id'] : 0;

		if (!$user_id && $this->registry->has('user')) {
			$user = $this->registry->get('user');

			if (is_object($user) && method_exists($user, 'getId')) {
				$user_id = (int)$user->getId();
			}
		}

		$message = isset($data['message']) ? $data['message'] : '';

		if (function_exists('mb_substr')) {
			$message = mb_substr($message, 0, 512);
		} else {
			$message = substr($message, 0, 512);
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "redirect_manager_log` SET
			date_added = NOW(),
			user_id = '" . (int)$user_id . "',
			action = '" . $this->db->escape($data['action']) . "',
			entity = '" . $this->db->escape(isset($data['entity']) ? $data['entity'] : '') . "',
			entity_id = " . $this->nullableInt(isset($data['entity_id']) ? $data['entity_id'] : null) . ",
			before_json = '" . $this->db->escape(isset($data['before']) ? json_encode($data['before']) : '') . "',
			after_json = '" . $this->db->escape(isset($data['after']) ? json_encode($data['after']) : '') . "',
			backend = '" . $this->db->escape(isset($data['backend']) ? $data['backend'] : '') . "',
			result = '" . $this->db->escape(isset($data['result']) ? $data['result'] : 'ok') . "',
			message = '" . $this->db->escape($message) . "'");
	}

	public function getLogs($start = 0, $limit = 50) {
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "redirect_manager_log` ORDER BY log_id DESC LIMIT " . (int)$start . "," . (int)$limit)->rows;
	}

	public function getTotalLogs() {
		return (int)$this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "redirect_manager_log`")->row['total'];
	}

	public function getSync($backend) {
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "redirect_manager_sync` WHERE backend = '" . $this->db->escape($backend) . "' AND store_id = '0'")->row;
	}

	public function saveSync($data) {
		$existing = $this->getSync($data['backend']);
		$generated = !empty($data['date_generated']) ? 'NOW()' : ($existing ? 'date_generated' : 'NULL');
		$synced = !empty($data['date_synced']) ? 'NOW()' : ($existing ? 'date_synced' : 'NULL');

		if ($existing) {
			$this->db->query("UPDATE `" . DB_PREFIX . "redirect_manager_sync` SET
				status = '" . $this->db->escape($data['status']) . "',
				rules_hash = '" . $this->db->escape(isset($data['rules_hash']) ? $data['rules_hash'] : '') . "',
				file_hash = '" . $this->db->escape(isset($data['file_hash']) ? $data['file_hash'] : '') . "',
				file_path = '" . $this->db->escape(isset($data['file_path']) ? $data['file_path'] : '') . "',
				rules_count = '" . (int)(isset($data['rules_count']) ? $data['rules_count'] : 0) . "',
				operation_id = '" . $this->db->escape(isset($data['operation_id']) ? $data['operation_id'] : '') . "',
				error_message = '" . $this->db->escape(isset($data['error_message']) ? $data['error_message'] : '') . "',
				date_generated = " . $generated . ",
				date_synced = " . $synced . "
				WHERE sync_id = '" . (int)$existing['sync_id'] . "'");
		} else {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "redirect_manager_sync` SET
				backend = '" . $this->db->escape($data['backend']) . "',
				store_id = '0',
				status = '" . $this->db->escape($data['status']) . "',
				rules_hash = '" . $this->db->escape(isset($data['rules_hash']) ? $data['rules_hash'] : '') . "',
				file_hash = '" . $this->db->escape(isset($data['file_hash']) ? $data['file_hash'] : '') . "',
				file_path = '" . $this->db->escape(isset($data['file_path']) ? $data['file_path'] : '') . "',
				rules_count = '" . (int)(isset($data['rules_count']) ? $data['rules_count'] : 0) . "',
				operation_id = '" . $this->db->escape(isset($data['operation_id']) ? $data['operation_id'] : '') . "',
				error_message = '" . $this->db->escape(isset($data['error_message']) ? $data['error_message'] : '') . "',
				date_generated = " . ($generated === 'date_generated' ? 'NULL' : $generated) . ",
				date_synced = " . ($synced === 'date_synced' ? 'NULL' : $synced));
		}
	}

	public function markDirty() {
		$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '1' WHERE `code` = 'module_redirect_manager' AND `key` = 'module_redirect_manager_configuration_dirty'");

		$backend = (string)$this->config->get('module_redirect_manager_backend');

		if ($backend === '') {
			$backend = 'apache';
		}

		if ($this->getSync($backend)) {
			$this->db->query("UPDATE `" . DB_PREFIX . "redirect_manager_sync` SET status = 'dirty' WHERE backend = '" . $this->db->escape($backend) . "' AND store_id = '0'");
		} else {
			$this->saveSync(array('backend' => $backend, 'status' => 'dirty'));
		}
	}

	public function markClean($hash) {
		$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '0' WHERE `code` = 'module_redirect_manager' AND `key` = 'module_redirect_manager_configuration_dirty'");
		$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '" . $this->db->escape($hash) . "' WHERE `code` = 'module_redirect_manager' AND `key` = 'module_redirect_manager_rules_hash'");
	}

	public function getDashboard() {
		$stats = $this->db->query("SELECT
			COUNT(*) AS total,
			SUM(action = 'redirect') AS redirects,
			SUM(action = 'status' AND http_code = 410) AS gone,
			SUM(http_code = 301) AS c301,
			SUM(http_code = 302) AS c302,
			SUM(enabled = 1) AS enabled,
			SUM(enabled = 0) AS disabled,
			SUM(broken_target = 1) AS broken
			FROM `" . DB_PREFIX . "redirect_manager_rule`")->row;

		$engine = $this->engine();
		$rules = $this->getEnabledRules();

		return array(
			'stats'  => $stats,
			'loops'  => $engine->detectLoops($rules),
			'chains' => $engine->detectChains($rules)
		);
	}

	public function refreshBrokenTargets() {
		$this->db->query("UPDATE `" . DB_PREFIX . "redirect_manager_rule` SET broken_target = '0'");
		$this->db->query("UPDATE `" . DB_PREFIX . "redirect_manager_rule` r LEFT JOIN `" . DB_PREFIX . "product` p ON (r.target_entity_id = p.product_id) SET r.broken_target = '1' WHERE r.target_type = 'product' AND r.target_entity_id IS NOT NULL AND p.product_id IS NULL");
		$this->db->query("UPDATE `" . DB_PREFIX . "redirect_manager_rule` r LEFT JOIN `" . DB_PREFIX . "category` c ON (r.target_entity_id = c.category_id) SET r.broken_target = '1' WHERE r.target_type = 'category' AND r.target_entity_id IS NOT NULL AND c.category_id IS NULL");
		$this->db->query("UPDATE `" . DB_PREFIX . "redirect_manager_rule` r LEFT JOIN `" . DB_PREFIX . "information` i ON (r.target_entity_id = i.information_id) SET r.broken_target = '1' WHERE r.target_type = 'information' AND r.target_entity_id IS NOT NULL AND i.information_id IS NULL");
		$this->db->query("UPDATE `" . DB_PREFIX . "redirect_manager_rule` r LEFT JOIN `" . DB_PREFIX . "manufacturer` m ON (r.target_entity_id = m.manufacturer_id) SET r.broken_target = '1' WHERE r.target_type = 'manufacturer' AND r.target_entity_id IS NOT NULL AND m.manufacturer_id IS NULL");
	}

	public function addHistory($data) {
		$hash = hash('sha256', (int)$data['store_id'] . '|' . (int)$data['language_id'] . '|' . $data['url']);
		$exists = $this->db->query("SELECT history_id FROM `" . DB_PREFIX . "redirect_manager_url_history` WHERE store_id = '" . (int)$data['store_id'] . "' AND language_id = '" . (int)$data['language_id'] . "' AND entity_type = '" . $this->db->escape($data['entity_type']) . "' AND entity_id = '" . (int)$data['entity_id'] . "' AND url_hash = '" . $this->db->escape($hash) . "' AND active = '1'");

		if ($exists->num_rows) {
			return (int)$exists->row['history_id'];
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "redirect_manager_url_history` SET
			store_id = '" . (int)$data['store_id'] . "',
			language_id = '" . (int)$data['language_id'] . "',
			entity_type = '" . $this->db->escape($data['entity_type']) . "',
			entity_id = '" . (int)$data['entity_id'] . "',
			url = '" . $this->db->escape($data['url']) . "',
			url_hash = '" . $this->db->escape($hash) . "',
			date_added = NOW(),
			active = '1'");

		return $this->db->getLastId();
	}

	public function deactivateHistory($entity_type, $entity_id, $store_id, $language_id, $url) {
		$hash = hash('sha256', (int)$store_id . '|' . (int)$language_id . '|' . $url);
		$this->db->query("UPDATE `" . DB_PREFIX . "redirect_manager_url_history` SET active = '0', date_removed = NOW() WHERE entity_type = '" . $this->db->escape($entity_type) . "' AND entity_id = '" . (int)$entity_id . "' AND store_id = '" . (int)$store_id . "' AND language_id = '" . (int)$language_id . "' AND url_hash = '" . $this->db->escape($hash) . "' AND active = '1'");
	}

	public function getEntityUrls($entity_type, $entity_id) {
		$map = array('product' => 'product_id=', 'category' => 'category_id=', 'information' => 'information_id=', 'manufacturer' => 'manufacturer_id=');

		if (!isset($map[$entity_type])) {
			return array();
		}

		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "seo_url` WHERE `query` = '" . $this->db->escape($map[$entity_type] . (int)$entity_id) . "'")->rows;
	}

	public function getCategoryPreview($category_id, $language_id, $store_id, $options) {
		$engine = $this->engine();
		$category_url = $engine->getCategoryUrl($category_id, $language_id, $store_id);
		$direct_products = $this->db->query("SELECT p.product_id FROM `" . DB_PREFIX . "product_to_category` p2c LEFT JOIN `" . DB_PREFIX . "product` p ON (p2c.product_id = p.product_id) WHERE p2c.category_id = '" . (int)$category_id . "' GROUP BY p.product_id")->rows;
		$all_products = $this->db->query("SELECT p.product_id FROM `" . DB_PREFIX . "product_to_category` p2c INNER JOIN `" . DB_PREFIX . "category_path` cp ON (p2c.category_id = cp.category_id) LEFT JOIN `" . DB_PREFIX . "product` p ON (p2c.product_id = p.product_id) WHERE cp.path_id = '" . (int)$category_id . "' GROUP BY p.product_id")->rows;
		$subcategories = $this->db->query("SELECT cp.category_id FROM `" . DB_PREFIX . "category_path` cp WHERE cp.path_id = '" . (int)$category_id . "' AND cp.category_id != '" . (int)$category_id . "'")->rows;
		$direct_ids = array();

		foreach ($direct_products as $row) {
			$direct_ids[] = (int)$row['product_id'];
		}

		$also_other = 0;

		if ($direct_ids) {
			$also_other = $this->db->query("SELECT product_id FROM `" . DB_PREFIX . "product_to_category` WHERE product_id IN (" . implode(',', $direct_ids) . ") GROUP BY product_id HAVING COUNT(*) > 1")->num_rows;
		}

		$urls = array();

		if (!empty($options['include_category']) && $category_url !== '') {
			$urls[] = array('type' => 'category', 'entity_id' => (int)$category_id, 'url' => $category_url);
		}

		if (!empty($options['include_products'])) {
			foreach ($direct_products as $row) {
				$url = $engine->getProductUrl((int)$row['product_id'], $language_id, $store_id);

				if ($url !== '') {
					$urls[] = array('type' => 'product', 'entity_id' => (int)$row['product_id'], 'url' => $url);
				}
			}
		}

		if (!empty($options['include_subcategories'])) {
			foreach ($subcategories as $row) {
				$url = $engine->getCategoryUrl((int)$row['category_id'], $language_id, $store_id);

				if ($url !== '') {
					$urls[] = array('type' => 'category', 'entity_id' => (int)$row['category_id'], 'url' => $url);
				}
			}
		}

		if (!empty($options['include_sub_products'])) {
			$direct_map = array_flip($direct_ids);

			foreach ($all_products as $row) {
				$id = (int)$row['product_id'];

				if (isset($direct_map[$id])) {
					continue;
				}

				$url = $engine->getProductUrl($id, $language_id, $store_id);

				if ($url !== '') {
					$urls[] = array('type' => 'product', 'entity_id' => $id, 'url' => $url);
				}
			}
		}

		$name_q = $this->db->query("SELECT name FROM `" . DB_PREFIX . "category_description` WHERE category_id = '" . (int)$category_id . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return array(
			'category_id'     => (int)$category_id,
			'category_name'   => $name_q->num_rows ? $name_q->row['name'] : '',
			'category_url'    => $category_url,
			'direct_products' => count($direct_products),
			'all_products'    => count($all_products),
			'subcategories'   => count($subcategories),
			'also_other'      => $also_other,
			'urls'            => $urls,
			'total'           => count($urls)
		);
	}

	public function getProductSelection($data, $language_id, $store_id) {
		$engine = $this->engine();
		$sql = "SELECT DISTINCT p.product_id FROM `" . DB_PREFIX . "product` p LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')";

		if (!empty($data['category_id'])) {
			$sql .= " LEFT JOIN `" . DB_PREFIX . "product_to_category` p2c ON (p.product_id = p2c.product_id)";

			if (!empty($data['sub_category'])) {
				$sql .= " LEFT JOIN `" . DB_PREFIX . "category_path` cp ON (p2c.category_id = cp.category_id)";
			}
		}

		$sql .= " WHERE 1";

		if (!empty($data['product_ids']) && is_array($data['product_ids'])) {
			$sql .= " AND p.product_id IN (" . $this->idList($data['product_ids']) . ")";
		}

		if (!empty($data['category_id'])) {
			$sql .= !empty($data['sub_category']) ? " AND cp.path_id = '" . (int)$data['category_id'] . "'" : " AND p2c.category_id = '" . (int)$data['category_id'] . "'";
		}

		if (!empty($data['manufacturer_id'])) {
			$sql .= " AND p.manufacturer_id = '" . (int)$data['manufacturer_id'] . "'";
		}

		if (!empty($data['disabled'])) {
			$sql .= " AND p.status = '0'";
		}

		if (!empty($data['out_of_stock'])) {
			$sql .= " AND p.quantity <= '0'";
		}

		if (!empty($data['filter_name'])) {
			$sql .= " AND pd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		$urls = array();

		foreach ($this->db->query($sql)->rows as $row) {
			$url = $engine->getProductUrl((int)$row['product_id'], $language_id, $store_id);

			if ($url !== '') {
				$urls[] = array('type' => 'product', 'entity_id' => (int)$row['product_id'], 'url' => $url);
			}
		}

		return $urls;
	}

	private function filterSql($data) {
		$sql = '';

		if (!empty($data['filter_source'])) {
			$sql .= " AND source_url LIKE '%" . $this->db->escape($data['filter_source']) . "%'";
		}

		if (!empty($data['filter_target'])) {
			$sql .= " AND target_url LIKE '%" . $this->db->escape($data['filter_target']) . "%'";
		}

		if (isset($data['filter_http_code']) && $data['filter_http_code'] !== '') {
			$sql .= " AND http_code = '" . (int)$data['filter_http_code'] . "'";
		}

		if (!empty($data['filter_action'])) {
			$sql .= " AND action = '" . $this->db->escape($data['filter_action']) . "'";
		}

		if (!empty($data['filter_source_type'])) {
			$sql .= " AND source_type = '" . $this->db->escape($data['filter_source_type']) . "'";
		}

		if (isset($data['filter_enabled']) && $data['filter_enabled'] !== '') {
			$sql .= " AND enabled = '" . (int)$data['filter_enabled'] . "'";
		}

		if (!empty($data['filter_broken'])) {
			$sql .= " AND broken_target = '1'";
		}

		if (isset($data['filter_store_id']) && $data['filter_store_id'] !== '') {
			$sql .= " AND store_id = '" . (int)$data['filter_store_id'] . "'";
		}

		if (!empty($data['filter_batch_id'])) {
			$sql .= " AND batch_id = '" . (int)$data['filter_batch_id'] . "'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		if (!empty($data['filter_date_modified'])) {
			$sql .= " AND DATE(date_modified) = DATE('" . $this->db->escape($data['filter_date_modified']) . "')";
		}

		if (!empty($data['filter_entity_type']) && !empty($data['filter_entity_id'])) {
			$sql .= " AND source_type = '" . $this->db->escape($data['filter_entity_type']) . "' AND source_entity_id = '" . (int)$data['filter_entity_id'] . "'";
		}

		return $sql;
	}

	private function nullableInt($value) {
		if ($value === '' || $value === null) {
			return 'NULL';
		}

		return "'" . (int)$value . "'";
	}

	private function idList($ids) {
		$clean = array();

		foreach ($ids as $id) {
			$clean[] = (int)$id;
		}

		return $clean ? implode(',', $clean) : '0';
	}
}
