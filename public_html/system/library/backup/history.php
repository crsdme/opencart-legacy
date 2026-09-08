<?php

namespace Backup;

class History
{
	private $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function install()
	{
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "auto_backup` (
			`backup_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`trigger` VARCHAR(16) NOT NULL DEFAULT 'cron',
			`status` VARCHAR(16) NOT NULL DEFAULT 'running',
			`destination` VARCHAR(32) NOT NULL DEFAULT 'local',
			`filename` VARCHAR(255) NOT NULL DEFAULT '',
			`filesize` BIGINT UNSIGNED NOT NULL DEFAULT 0,
			`remote_id` VARCHAR(255) NOT NULL DEFAULT '',
			`error` TEXT,
			`date_start` DATETIME NOT NULL,
			`date_end` DATETIME DEFAULT NULL,
			PRIMARY KEY (`backup_id`),
			KEY `idx_status` (`status`, `date_start`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	}

	public function uninstall($delete_data = false)
	{
		if (!$delete_data) {
			return;
		}

		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "auto_backup`");
	}

	public function add($data)
	{
		$this->db->query(
			"INSERT INTO `" . DB_PREFIX . "auto_backup` SET
				`trigger` = '" . $this->db->escape($data['trigger']) . "',
				`status` = '" . $this->db->escape($data['status']) . "',
				`destination` = '" . $this->db->escape($data['destination']) . "',
				`filename` = '',
				`filesize` = '0',
				`remote_id` = '',
				`error` = '',
				`date_start` = NOW()"
		);

		return (int) $this->db->getLastId();
	}

	public function finish($backup_id, $data)
	{
		$this->db->query(
			"UPDATE `" . DB_PREFIX . "auto_backup` SET
				`status` = '" . $this->db->escape($data['status']) . "',
				`filename` = '" . $this->db->escape($data['filename']) . "',
				`filesize` = '" . (int) $data['filesize'] . "',
				`remote_id` = '" . $this->db->escape($data['remote_id']) . "',
				`error` = '" . $this->db->escape($data['error']) . "',
				`date_end` = NOW()
			WHERE `backup_id` = '" . (int) $backup_id . "'"
		);
	}

	public function get($backup_id)
	{
		$query = $this->db->query(
			"SELECT * FROM `" . DB_PREFIX . "auto_backup` WHERE `backup_id` = '" . (int) $backup_id . "'"
		);

		return $query->row;
	}

	public function getJobs($start, $limit)
	{
		$query = $this->db->query(
			"SELECT * FROM `" . DB_PREFIX . "auto_backup`
			ORDER BY `backup_id` DESC
			LIMIT " . (int) $start . "," . (int) $limit
		);

		return $query->rows;
	}

	public function getTotal()
	{
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "auto_backup`");

		return (int) $query->row['total'];
	}

	public function delete($backup_id)
	{
		$row = $this->get($backup_id);

		if ($row) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "auto_backup` WHERE `backup_id` = '" . (int) $backup_id . "'");
		}

		return $row;
	}
}
