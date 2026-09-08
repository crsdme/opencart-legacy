<?php
class ModelBlogAuthor extends Model
{
	public function install()
	{
		static $done = false;

		if ($done) {
			return;
		}

		$done = true;

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "blog_author` (
			`author_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`image` VARCHAR(255) NOT NULL DEFAULT '',
			`status` TINYINT(1) NOT NULL DEFAULT 1,
			`noindex` TINYINT(1) NOT NULL DEFAULT 1,
			`sort_order` INT NOT NULL DEFAULT 0,
			PRIMARY KEY (`author_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "blog_author_description` (
			`author_id` INT UNSIGNED NOT NULL,
			`language_id` INT UNSIGNED NOT NULL,
			`name` VARCHAR(255) NOT NULL DEFAULT '',
			`description` TEXT NOT NULL,
			`meta_title` VARCHAR(255) NOT NULL DEFAULT '',
			`meta_h1` VARCHAR(255) NOT NULL DEFAULT '',
			`meta_description` TEXT NOT NULL,
			`meta_keyword` VARCHAR(255) NOT NULL DEFAULT '',
			PRIMARY KEY (`author_id`, `language_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");

		$this->addColumnIfMissing('blog_author', 'noindex', "TINYINT(1) NOT NULL DEFAULT 1 AFTER `status`");
		$this->addColumnIfMissing('blog_author_description', 'meta_title', "VARCHAR(255) NOT NULL DEFAULT '' AFTER `description`");
		$this->addColumnIfMissing('blog_author_description', 'meta_h1', "VARCHAR(255) NOT NULL DEFAULT '' AFTER `meta_title`");
		$this->addColumnIfMissing('blog_author_description', 'meta_description', "TEXT NOT NULL AFTER `meta_h1`");
		$this->addColumnIfMissing('blog_author_description', 'meta_keyword', "VARCHAR(255) NOT NULL DEFAULT '' AFTER `meta_description`");

		$column = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "article` LIKE 'author_id'");

		if (!$column->num_rows) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "article` ADD `author_id` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `article_id`");
		}

		$query = $this->db->query("SELECT user_group_id, permission FROM `" . DB_PREFIX . "user_group`");

		foreach ($query->rows as $row) {
			$permission = json_decode($row['permission'], true);

			if (!is_array($permission)) {
				continue;
			}

			$changed = false;

			foreach (array('access', 'modify') as $type) {
				if (empty($permission[$type]) || !is_array($permission[$type])) {
					continue;
				}

				if (in_array('blog/article', $permission[$type]) && !in_array('blog/author', $permission[$type])) {
					$permission[$type][] = 'blog/author';
					$changed = true;
				}
			}

			if ($changed) {
				$this->db->query("UPDATE `" . DB_PREFIX . "user_group` SET permission = '" . $this->db->escape(json_encode($permission)) . "' WHERE user_group_id = '" . (int)$row['user_group_id'] . "'");
			}
		}
	}

	public function addAuthor($data)
	{
		$this->db->query("INSERT INTO `" . DB_PREFIX . "blog_author` SET image = '" . $this->db->escape($data['image'] ?? '') . "', status = '" . (int)$data['status'] . "', noindex = '" . (int)($data['noindex'] ?? 1) . "', sort_order = '" . (int)$data['sort_order'] . "'");

		$author_id = $this->db->getLastId();

		foreach ($data['author_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "blog_author_description` SET author_id = '" . (int)$author_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name'] ?? '') . "', description = '" . $this->db->escape($value['description'] ?? '') . "', meta_title = '" . $this->db->escape($value['meta_title'] ?? '') . "', meta_h1 = '" . $this->db->escape($value['meta_h1'] ?? '') . "', meta_description = '" . $this->db->escape($value['meta_description'] ?? '') . "', meta_keyword = '" . $this->db->escape($value['meta_keyword'] ?? '') . "'");
		}

		$this->saveAuthorSeoUrls($author_id, $data);

		if ($this->config->get('config_seo_pro')) {
			$this->cache->delete('seopro');
		}

		return $author_id;
	}

	public function editAuthor($author_id, $data)
	{
		$this->db->query("UPDATE `" . DB_PREFIX . "blog_author` SET image = '" . $this->db->escape($data['image'] ?? '') . "', status = '" . (int)$data['status'] . "', noindex = '" . (int)($data['noindex'] ?? 1) . "', sort_order = '" . (int)$data['sort_order'] . "' WHERE author_id = '" . (int)$author_id . "'");

		$this->db->query("DELETE FROM `" . DB_PREFIX . "blog_author_description` WHERE author_id = '" . (int)$author_id . "'");

		foreach ($data['author_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "blog_author_description` SET author_id = '" . (int)$author_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name'] ?? '') . "', description = '" . $this->db->escape($value['description'] ?? '') . "', meta_title = '" . $this->db->escape($value['meta_title'] ?? '') . "', meta_h1 = '" . $this->db->escape($value['meta_h1'] ?? '') . "', meta_description = '" . $this->db->escape($value['meta_description'] ?? '') . "', meta_keyword = '" . $this->db->escape($value['meta_keyword'] ?? '') . "'");
		}

		$this->saveAuthorSeoUrls($author_id, $data);

		if ($this->config->get('config_seo_pro')) {
			$this->cache->delete('seopro');
		}
	}

	public function deleteAuthor($author_id)
	{
		$this->db->query("DELETE FROM `" . DB_PREFIX . "blog_author` WHERE author_id = '" . (int)$author_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "blog_author_description` WHERE author_id = '" . (int)$author_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE query = 'author_id=" . (int)$author_id . "'");
		$this->db->query("UPDATE `" . DB_PREFIX . "article` SET author_id = '0' WHERE author_id = '" . (int)$author_id . "'");

		if ($this->config->get('config_seo_pro')) {
			$this->cache->delete('seopro');
		}
	}

	public function getAuthor($author_id)
	{
		$query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "blog_author` a LEFT JOIN `" . DB_PREFIX . "blog_author_description` ad ON (a.author_id = ad.author_id) WHERE a.author_id = '" . (int)$author_id . "' AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	public function getAuthors($data = array())
	{
		$sql = "SELECT * FROM `" . DB_PREFIX . "blog_author` a LEFT JOIN `" . DB_PREFIX . "blog_author_description` ad ON (a.author_id = ad.author_id) WHERE ad.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		$sort_data = array('ad.name', 'a.sort_order', 'a.status');

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY ad.name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getAuthorDescriptions($author_id)
	{
		$author_description_data = array();

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "blog_author_description` WHERE author_id = '" . (int)$author_id . "'");

		foreach ($query->rows as $result) {
			$author_description_data[$result['language_id']] = array(
				'name' => $result['name'],
				'description' => $result['description'],
				'meta_title' => $result['meta_title'] ?? '',
				'meta_h1' => $result['meta_h1'] ?? '',
				'meta_description' => $result['meta_description'] ?? '',
				'meta_keyword' => $result['meta_keyword'] ?? ''
			);
		}

		return $author_description_data;
	}

	public function getTotalAuthors()
	{
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "blog_author`");

		return $query->row['total'];
	}

	public function getAuthorSeoUrls($author_id)
	{
		$author_seo_url_data = array();

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "seo_url` WHERE query = 'author_id=" . (int)$author_id . "'");

		foreach ($query->rows as $result) {
			$author_seo_url_data[$result['store_id']][$result['language_id']] = $result['keyword'];
		}

		return $author_seo_url_data;
	}

	private function saveAuthorSeoUrls($author_id, $data)
	{
		$this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE query = 'author_id=" . (int)$author_id . "'");

		if (empty($data['author_seo_url']) || !is_array($data['author_seo_url'])) {
			return;
		}

		foreach ($data['author_seo_url'] as $store_id => $language) {
			foreach ($language as $language_id => $keyword) {
				if (!empty($keyword)) {
					$this->db->query("INSERT INTO `" . DB_PREFIX . "seo_url` SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'author_id=" . (int)$author_id . "', keyword = '" . $this->db->escape(trim($keyword)) . "'");
				}
			}
		}
	}

	private function addColumnIfMissing($table, $column, $definition)
	{
		$check = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . $table . "`");
		$exists = false;

		foreach ($check->rows as $row) {
			if (isset($row['Field']) && strcasecmp($row['Field'], $column) === 0) {
				$exists = true;
				break;
			}
		}

		if ($exists) {
			return;
		}

		try {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . $table . "` ADD `" . $column . "` " . $definition);
		} catch (Exception $e) {
			if (strpos($e->getMessage(), 'Duplicate column') === false) {
				throw $e;
			}
		}
	}
}
