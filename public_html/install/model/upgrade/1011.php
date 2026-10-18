<?php
class ModelUpgrade1011 extends Model {
	public function upgrade() {
		$query = $this->db->query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . $this->db->escape(DB_DATABASE) . "' AND TABLE_NAME = '" . DB_PREFIX . "language' AND COLUMN_NAME = 'hreflang'");

		if (!$query->num_rows) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "language` ADD `hreflang` VARCHAR(16) NOT NULL DEFAULT '' AFTER `locale`");
		}

		// Fill empty hreflang from locale (first token), any language — no per-code map.
		$languages = $this->db->query("SELECT language_id, locale, code, hreflang FROM `" . DB_PREFIX . "language`");

		foreach ($languages->rows as $language) {
			if (!empty($language['hreflang'])) {
				continue;
			}

			$hreflang = $this->tagFromLocale($language['locale']);

			if ($hreflang === '') {
				$hreflang = $this->tagFromLocale($language['code']);
			}

			if ($hreflang === '') {
				continue;
			}

			$this->db->query("UPDATE `" . DB_PREFIX . "language` SET `hreflang` = '" . $this->db->escape($hreflang) . "' WHERE language_id = '" . (int) $language['language_id'] . "'");
		}

		$this->cache->delete('catalog.language');
		$this->cache->delete('admin.language');
	}

	private function tagFromLocale($value) {
		$value = trim((string) $value);

		if ($value === '') {
			return '';
		}

		$first = trim(explode(',', $value)[0]);
		$first = preg_replace('/\..*$/', '', $first);
		$parts = preg_split('/[-_]/', $first);

		if (!$parts || $parts[0] === '') {
			return '';
		}

		$language = strtolower($parts[0]);

		if (isset($parts[1]) && $parts[1] !== '' && preg_match('/^[A-Za-z]{2,4}$/', $parts[1])) {
			return $language . '-' . strtoupper($parts[1]);
		}

		return $language;
	}
}
