<?php
class ModelDesignSeoUrl extends Model {
	public function addSeoUrl($data) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "seo_url` SET store_id = '" . (int)$data['store_id'] . "', language_id = '" . (int)$data['language_id'] . "', query = '" . $this->db->escape($data['query']) . "', keyword = '" . $this->db->escape($data['keyword']) . "'");
	}

	public function editSeoUrl($seo_url_id, $data) {
		$this->db->query("UPDATE `" . DB_PREFIX . "seo_url` SET store_id = '" . (int)$data['store_id'] . "', language_id = '" . (int)$data['language_id'] . "', query = '" . $this->db->escape($data['query']) . "', keyword = '" . $this->db->escape($data['keyword']) . "' WHERE seo_url_id = '" . (int)$seo_url_id . "'");
	}

	public function deleteSeoUrl($seo_url_id) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE seo_url_id = '" . (int)$seo_url_id . "'");
	}
	
	public function getSeoUrl($seo_url_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "seo_url` WHERE seo_url_id = '" . (int)$seo_url_id . "'");

		return $query->row;
	}

	public function getSeoUrls($data = array()) {
		$sql = "SELECT *, (SELECT `name` FROM `" . DB_PREFIX . "store` s WHERE s.store_id = su.store_id) AS store, (SELECT `name` FROM `" . DB_PREFIX . "language` l WHERE l.language_id = su.language_id) AS language FROM `" . DB_PREFIX . "seo_url` su";

		$implode = array();

		if (!empty($data['filter_query'])) {
			$implode[] = "`query` LIKE '" . $this->db->escape($data['filter_query']) . "'";
		}
		
		if (!empty($data['filter_keyword'])) {
			$implode[] = "`keyword` LIKE '" . $this->db->escape($data['filter_keyword']) . "'";
		}
		
		if (isset($data['filter_store_id']) && $data['filter_store_id'] !== '') {
			$implode[] = "`store_id` = '" . (int)$data['filter_store_id'] . "'";
		}
				
		if (!empty($data['filter_language_id']) && $data['filter_language_id'] !== '') {
			$implode[] = "`language_id` = '" . (int)$data['filter_language_id'] . "'";
		}
		
		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}	
		
		$sort_data = array(
			'query',
			'keyword',
			'language_id',
			'store_id'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY query";
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

	public function getTotalSeoUrls($data = array()) {
		$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "seo_url`";
		
		$implode = array();

		if (!empty($data['filter_query'])) {
			$implode[] = "query LIKE '" . $this->db->escape($data['filter_query']) . "'";
		}
		
		if (!empty($data['filter_keyword'])) {
			$implode[] = "keyword LIKE '" . $this->db->escape($data['filter_keyword']) . "'";
		}
		
		if (!empty($data['filter_store_id']) && $data['filter_store_id'] !== '') {
			$implode[] = "store_id = '" . (int)$data['filter_store_id'] . "'";
		}
				
		if (!empty($data['filter_language_id']) && $data['filter_language_id'] !== '') {
			$implode[] = "language_id = '" . (int)$data['filter_language_id'] . "'";
		}
		
		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}		
		
		$query = $this->db->query($sql);

		return $query->row['total'];
	}
	
	public function getSeoUrlsByKeyword($keyword) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "seo_url` WHERE keyword = '" . $this->db->escape(trim($keyword)) . "'");

		return $query->rows;
	}	
	
	public function getSeoUrlsByQuery($keyword) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "seo_url` WHERE keyword = '" . $this->db->escape(trim($keyword)) . "'");

		return $query->rows;
	}

	/**
	 * Same keyword is allowed across languages.
	 * Catalog and blog categories may share a keyword when parent_id differs.
	 * Same parent (two roots included) conflicts. A non-category query conflicts with any other use in that store and language.
	 *
	 * @param string|null $scope category_id or blog_category_id when this row is a category; null for every other query
	 */
	public function keywordConflicts($keyword, $store_id, $language_id, $query, $scope = null, $parent_id = 0) {
		$keyword = trim((string)$keyword);

		if ($keyword === '') {
			return false;
		}

		$store_id = (int)$store_id;
		$language_id = (int)$language_id;
		$query = (string)$query;
		$parent_id = (int)$parent_id;

		foreach ($this->getSeoUrlsByKeyword($keyword) as $row) {
			if ((int)$row['store_id'] !== $store_id || (int)$row['language_id'] !== $language_id) {
				continue;
			}

			if ((string)$row['query'] === $query) {
				continue;
			}

			if ($scope && $this->categoryKeywordAllowed($scope, $parent_id, $row['query'])) {
				continue;
			}

			return true;
		}

		return false;
	}

	public function entityParentId($scope, $id) {
		if ($scope === 'category_id') {
			$table = 'category';
			$column = 'category_id';
		} elseif ($scope === 'blog_category_id') {
			$table = 'blog_category';
			$column = 'blog_category_id';
		} else {
			return null;
		}

		$query = $this->db->query("SELECT parent_id FROM `" . DB_PREFIX . $table . "` WHERE `" . $column . "` = '" . (int)$id . "'");

		if (!$query->num_rows) {
			return null;
		}

		return (int)$query->row['parent_id'];
	}

	private function categoryKeywordAllowed($scope, $parent_id, $other_query) {
		if (!preg_match('/^(category_id|blog_category_id)=(\d+)$/', (string)$other_query, $match)) {
			return false;
		}

		if ($match[1] !== $scope) {
			return false;
		}

		$other_parent = $this->entityParentId($scope, (int)$match[2]);

		if ($other_parent === null) {
			return false;
		}

		return (int)$other_parent !== (int)$parent_id;
	}
}