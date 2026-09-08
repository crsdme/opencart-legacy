<?php

class ModelExtensionFaqFaq extends Model
{
	const TYPES = ['product', 'category', 'manufacturer'];

	public function install()
	{
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "faq` (
			`faq_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`entity_type` VARCHAR(32) NOT NULL,
			`entity_id` INT UNSIGNED NOT NULL DEFAULT 0,
			`scope` VARCHAR(16) NOT NULL DEFAULT 'page',
			`sort_order` INT NOT NULL DEFAULT 0,
			`status` TINYINT(1) NOT NULL DEFAULT 1,
			PRIMARY KEY (`faq_id`),
			KEY `idx_entity` (`entity_type`, `entity_id`, `scope`, `status`, `sort_order`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "faq_description` (
			`faq_id` INT UNSIGNED NOT NULL,
			`language_id` INT UNSIGNED NOT NULL,
			`question` TEXT NOT NULL,
			`answer` TEXT NOT NULL,
			PRIMARY KEY (`faq_id`, `language_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "faq_page` (
			`entity_type` VARCHAR(32) NOT NULL,
			`entity_id` INT UNSIGNED NOT NULL,
			`status` TINYINT(1) NOT NULL DEFAULT 1,
			PRIMARY KEY (`entity_type`, `entity_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->deleteProductScope();
	}

	public function uninstall($delete_data = false)
	{
		if (!$delete_data) {
			return;
		}

		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "faq_description`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "faq`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "faq_page`");
	}

	public function getItems($type, $entity_id)
	{
		$items = [];

		if (!$this->isType($type)) {
			return $items;
		}

		$query = $this->db->query(
			"SELECT * FROM `" . DB_PREFIX . "faq`
			WHERE `entity_type` = '" . $this->db->escape($type) . "'
				AND `entity_id` = '" . (int) $entity_id . "'
				AND `scope` = 'page'
			ORDER BY `sort_order` ASC, `faq_id` ASC"
		);

		foreach ($query->rows as $row) {
			$faq_data = [];
			$descriptions = $this->db->query(
				"SELECT * FROM `" . DB_PREFIX . "faq_description` WHERE `faq_id` = '" . (int) $row['faq_id'] . "'"
			);

			foreach ($descriptions->rows as $description) {
				$faq_data[(int) $description['language_id']] = [
					'question' => $description['question'],
					'answer' => $description['answer'],
				];
			}

			$items[] = [
				'faq_id' => (int) $row['faq_id'],
				'sort_order' => (int) $row['sort_order'],
				'status' => (int) $row['status'],
				'faq_data' => $faq_data,
			];
		}

		return $items;
	}

	public function saveItems($type, $entity_id, $items)
	{
		if (!$this->isType($type)) {
			return;
		}

		$entity_id = (int) $entity_id;
		$existing = $this->db->query(
			"SELECT `faq_id` FROM `" . DB_PREFIX . "faq`
			WHERE `entity_type` = '" . $this->db->escape($type) . "'
				AND `entity_id` = '" . $entity_id . "'
				AND `scope` = 'page'"
		);

		foreach ($existing->rows as $row) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "faq_description` WHERE `faq_id` = '" . (int) $row['faq_id'] . "'");
		}

		$this->db->query(
			"DELETE FROM `" . DB_PREFIX . "faq`
			WHERE `entity_type` = '" . $this->db->escape($type) . "'
				AND `entity_id` = '" . $entity_id . "'
				AND `scope` = 'page'"
		);

		if (!is_array($items)) {
			return;
		}

		foreach ($items as $item) {
			$sort_order = isset($item['sort_order']) ? (int) $item['sort_order'] : 0;
			$status = isset($item['status']) ? (int) $item['status'] : 1;

			$this->db->query(
				"INSERT INTO `" . DB_PREFIX . "faq`
				SET `entity_type` = '" . $this->db->escape($type) . "',
					`entity_id` = '" . $entity_id . "',
					`scope` = 'page',
					`sort_order` = '" . $sort_order . "',
					`status` = '" . $status . "'"
			);

			$faq_id = (int) $this->db->getLastId();
			$faq_data = isset($item['faq_data']) && is_array($item['faq_data']) ? $item['faq_data'] : [];

			foreach ($faq_data as $language_id => $value) {
				$question = isset($value['question']) ? $value['question'] : '';
				$answer = isset($value['answer']) ? $value['answer'] : '';

				$this->db->query(
					"INSERT INTO `" . DB_PREFIX . "faq_description`
					SET `faq_id` = '" . $faq_id . "',
						`language_id` = '" . (int) $language_id . "',
						`question` = '" . $this->db->escape($question) . "',
						`answer` = '" . $this->db->escape($answer) . "'"
				);
			}
		}
	}

	public function getPageStatus($type, $entity_id)
	{
		if (!$this->isType($type) || !(int) $entity_id) {
			return 1;
		}

		$query = $this->db->query(
			"SELECT `status` FROM `" . DB_PREFIX . "faq_page`
			WHERE `entity_type` = '" . $this->db->escape($type) . "'
				AND `entity_id` = '" . (int) $entity_id . "'"
		);

		if ($query->num_rows) {
			return (int) $query->row['status'];
		}

		return 1;
	}

	public function savePageStatus($type, $entity_id, $status)
	{
		if (!$this->isType($type) || !(int) $entity_id) {
			return;
		}

		$this->db->query(
			"REPLACE INTO `" . DB_PREFIX . "faq_page`
			SET `entity_type` = '" . $this->db->escape($type) . "',
				`entity_id` = '" . (int) $entity_id . "',
				`status` = '" . ((int) $status ? 1 : 0) . "'"
		);
	}

	public function deleteEntity($type, $entity_id)
	{
		if (!$this->isType($type) || !(int) $entity_id) {
			return;
		}

		$query = $this->db->query(
			"SELECT `faq_id` FROM `" . DB_PREFIX . "faq`
			WHERE `entity_type` = '" . $this->db->escape($type) . "'
				AND `entity_id` = '" . (int) $entity_id . "'"
		);

		foreach ($query->rows as $row) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "faq_description` WHERE `faq_id` = '" . (int) $row['faq_id'] . "'");
		}

		$this->db->query(
			"DELETE FROM `" . DB_PREFIX . "faq`
			WHERE `entity_type` = '" . $this->db->escape($type) . "'
				AND `entity_id` = '" . (int) $entity_id . "'"
		);

		$this->db->query(
			"DELETE FROM `" . DB_PREFIX . "faq_page`
			WHERE `entity_type` = '" . $this->db->escape($type) . "'
				AND `entity_id` = '" . (int) $entity_id . "'"
		);
	}

	public function saveEntityFromPost($type, $entity_id, array $data)
	{
		if (empty($data['faq_form']) || !$this->isType($type)) {
			return;
		}

		$this->saveItems($type, $entity_id, isset($data['faq']) ? $data['faq'] : []);

		if ((int) $entity_id) {
			$this->savePageStatus($type, $entity_id, isset($data['faq_page_status']) ? $data['faq_page_status'] : 1);
		}
	}

	public function getPlaceholders($type)
	{
		$common = ['{name}', '{heading_title}', '{meta_title}', '{month}', '{year}'];

		if ($type === 'product') {
			return [
				'{name}',
				'{product_name}',
				'{heading_title}',
				'{meta_title}',
				'{price}',
				'{product_price}',
				'{manufacturer}',
				'{model}',
				'{sku}',
				'{category}',
				'{category_name}',
				'{month}',
				'{year}',
			];
		}

		if ($type === 'category') {
			return ['{name}', '{category_name}', '{heading_title}', '{meta_title}', '{month}', '{year}'];
		}

		if ($type === 'manufacturer') {
			return ['{name}', '{manufacturer_name}', '{heading_title}', '{meta_title}', '{month}', '{year}'];
		}

		return $common;
	}

	public function deleteProductScope()
	{
		$query = $this->db->query(
			"SELECT `faq_id` FROM `" . DB_PREFIX . "faq` WHERE `scope` = 'products'"
		);

		foreach ($query->rows as $row) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "faq_description` WHERE `faq_id` = '" . (int) $row['faq_id'] . "'");
		}

		$this->db->query("DELETE FROM `" . DB_PREFIX . "faq` WHERE `scope` = 'products'");
	}

	private function isType($type)
	{
		return in_array($type, self::TYPES, true);
	}
}
