<?php

namespace Backup;

class Dumper
{
	private $db;
	private $batch = 200;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function dump($path, array $exclude = [])
	{
		$handle = fopen($path, 'w');

		if (!$handle) {
			throw new \RuntimeException('Cannot write database dump.');
		}

		fwrite($handle, "-- OpenTail auto backup " . date('c') . "\n");
		fwrite($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

		$exclude_map = [];

		foreach ($exclude as $name) {
			$exclude_map[strtolower($name)] = true;
			$exclude_map[strtolower(DB_PREFIX . $name)] = true;
		}

		$query = $this->db->query("SHOW TABLES FROM `" . DB_DATABASE . "`");

		foreach ($query->rows as $row) {
			$table = reset($row);

			if (!$table || utf8_substr($table, 0, strlen(DB_PREFIX)) !== DB_PREFIX) {
				continue;
			}

			$short = utf8_substr($table, strlen(DB_PREFIX));

			if (isset($exclude_map[strtolower($table)]) || isset($exclude_map[strtolower($short)])) {
				continue;
			}

			$this->dumpTable($handle, $table);
		}

		fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
		fclose($handle);
	}

	private function dumpTable($handle, $table)
	{
		$create = $this->db->query("SHOW CREATE TABLE `" . $table . "`");
		$sql = isset($create->row['Create Table']) ? $create->row['Create Table'] : '';

		if ($sql === '') {
			return;
		}

		fwrite($handle, "DROP TABLE IF EXISTS `" . $table . "`;\n");
		fwrite($handle, $sql . ";\n\n");

		$offset = 0;

		while (true) {
			$rows = $this->db->query(
				"SELECT * FROM `" . $table . "` LIMIT " . (int) $offset . "," . (int) $this->batch
			);

			if (!$rows->num_rows) {
				break;
			}

			foreach ($rows->rows as $result) {
				$fields = [];
				$values = [];

				foreach ($result as $field => $value) {
					$fields[] = '`' . $field . '`';

					if ($value === null) {
						$values[] = 'NULL';
					} else {
						$values[] = "'" . $this->db->escape($value) . "'";
					}
				}

				fwrite(
					$handle,
					"INSERT INTO `" . $table . "` (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ");\n"
				);
			}

			$offset += $this->batch;
		}

		fwrite($handle, "\n");
	}
}
