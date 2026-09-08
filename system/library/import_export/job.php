<?php

namespace import_export;

class Job
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function install()
	{
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "import_export_job` (
			`job_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`trigger` VARCHAR(16) NOT NULL DEFAULT 'admin',
			`action` VARCHAR(16) NOT NULL DEFAULT 'import',
			`entity` VARCHAR(64) NOT NULL DEFAULT '',
			`format` VARCHAR(16) NOT NULL DEFAULT '',
			`filename` VARCHAR(255) NOT NULL DEFAULT '',
			`status` VARCHAR(16) NOT NULL DEFAULT 'success',
			`created` INT UNSIGNED NOT NULL DEFAULT 0,
			`updated` INT UNSIGNED NOT NULL DEFAULT 0,
			`skipped` INT UNSIGNED NOT NULL DEFAULT 0,
			`errors` INT UNSIGNED NOT NULL DEFAULT 0,
			`message` TEXT,
			`date_added` DATETIME NOT NULL,
			PRIMARY KEY (`job_id`),
			KEY `idx_date` (`date_added`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	}

	public function uninstall($delete_data = false)
	{
		if (!$delete_data) {
			return;
		}

		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "import_export_job`");
	}

	public function add($data)
	{
		$this->db->query(
			"INSERT INTO `" . DB_PREFIX . "import_export_job` SET
				`trigger` = '" . $this->db->escape($data['trigger']) . "',
				`action` = '" . $this->db->escape($data['action']) . "',
				`entity` = '" . $this->db->escape($data['entity']) . "',
				`format` = '" . $this->db->escape($data['format']) . "',
				`filename` = '" . $this->db->escape($data['filename']) . "',
				`status` = '" . $this->db->escape($data['status']) . "',
				`created` = '" . (int) $data['created'] . "',
				`updated` = '" . (int) $data['updated'] . "',
				`skipped` = '" . (int) $data['skipped'] . "',
				`errors` = '" . (int) $data['errors'] . "',
				`message` = '" . $this->db->escape($data['message']) . "',
				`date_added` = NOW()"
		);

		return (int) $this->db->getLastId();
	}

	public function get($job_id)
	{
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "import_export_job` WHERE `job_id` = '" . (int) $job_id . "'");

		return $query->row;
	}

	public function getJobs($start = 0, $limit = 20)
	{
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "import_export_job` ORDER BY `job_id` DESC LIMIT " . (int) $start . "," . (int) $limit);

		return $query->rows;
	}

	public function getTotal()
	{
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "import_export_job`");

		return (int) $query->row['total'];
	}

	public function delete($job_id)
	{
		$this->db->query("DELETE FROM `" . DB_PREFIX . "import_export_job` WHERE `job_id` = '" . (int) $job_id . "'");
	}

	public function recent($limit = 8)
	{
		return $this->getJobs(0, $limit);
	}
}
