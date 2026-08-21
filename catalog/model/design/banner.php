<?php
class ModelDesignBanner extends Model {
	public function getBanner($banner_id) {
		$this->ensureMobileImageColumn();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "banner b LEFT JOIN " . DB_PREFIX . "banner_image bi ON (b.banner_id = bi.banner_id) WHERE b.banner_id = '" . (int)$banner_id . "' AND b.status = '1' AND bi.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY bi.sort_order ASC");
		return $query->rows;
	}

	private function ensureMobileImageColumn() {
		static $checked = false;

		if ($checked) {
			return;
		}

		$checked = true;

		$query = $this->db->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . $this->db->escape(DB_DATABASE) . "' AND TABLE_NAME = '" . DB_PREFIX . "banner_image' AND COLUMN_NAME = 'mobile_image'");

		if (!$query->num_rows) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "banner_image` ADD `mobile_image` VARCHAR(255) NOT NULL DEFAULT '' AFTER `image`");
		}
	}
}
