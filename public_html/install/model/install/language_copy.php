<?php

/**
 * Known install language packs (code => fixed language_id in languages/{code}.sql).
 * English is always language_id = 1 from system SQL.
 */
function install_language_packs()
{
	return array(
		'ua' => 2,
		'ru' => 3,
	);
}

function install_copy_language($db, $prefix, $from_id, $to_id, $keyword_prefix)
{
	$from_id = (int)$from_id;
	$to_id = (int)$to_id;
	$keyword_prefix = preg_replace('/[^a-z0-9\-]/i', '', $keyword_prefix);

	$tables = array(
		'attribute_description' => array('attribute_id'),
		'attribute_group_description' => array('attribute_group_id'),
		'category_description' => array('category_id'),
		'customer_group_description' => array('customer_group_id'),
		'custom_field_description' => array('custom_field_id'),
		'custom_field_value_description' => array('custom_field_value_id'),
		'download_description' => array('download_id'),
		'filter_description' => array('filter_id'),
		'filter_group_description' => array('filter_group_id'),
		'information_description' => array('information_id'),
		'length_class_description' => array('length_class_id'),
		'option_description' => array('option_id'),
		'option_value_description' => array('option_value_id'),
		'order_status' => array('order_status_id'),
		'product_attribute' => array('product_id', 'attribute_id'),
		'product_description' => array('product_id'),
		'recurring_description' => array('recurring_id'),
		'return_action' => array('return_action_id'),
		'return_reason' => array('return_reason_id'),
		'return_status' => array('return_status_id'),
		'stock_status' => array('stock_status_id'),
		'voucher_theme_description' => array('voucher_theme_id'),
		'weight_class_description' => array('weight_class_id'),
		'blog_category_description' => array('blog_category_id'),
		'article_description' => array('article_id'),
		'manufacturer_description' => array('manufacturer_id'),
	);

	foreach ($tables as $table => $keys) {
		$name = $prefix . $table;
		$exists = $db->query("SHOW TABLES LIKE '" . $db->escape($name) . "'");

		if (!$exists->num_rows) {
			continue;
		}

		$query = $db->query("SELECT * FROM `" . $name . "` WHERE `language_id` = '" . $from_id . "'");

		foreach ($query->rows as $row) {
			$where = array("`language_id` = '" . $to_id . "'");

			foreach ($keys as $key) {
				$where[] = "`" . $key . "` = '" . $db->escape($row[$key]) . "'";
			}

			$found = $db->query("SELECT 1 FROM `" . $name . "` WHERE " . implode(' AND ', $where) . " LIMIT 1");

			if ($found->num_rows) {
				continue;
			}

			$row['language_id'] = $to_id;
			$fields = array();
			$values = array();

			foreach ($row as $column => $value) {
				$fields[] = '`' . $column . '`';
				$values[] = "'" . $db->escape($value) . "'";
			}

			$db->query("INSERT INTO `" . $name . "` (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")");
		}
	}

	$seo = $prefix . 'seo_url';
	$query = $db->query("SELECT * FROM `" . $seo . "` WHERE `language_id` = '" . $from_id . "'");

	foreach ($query->rows as $row) {
		$found = $db->query("SELECT 1 FROM `" . $seo . "` WHERE `store_id` = '" . (int)$row['store_id'] . "' AND `language_id` = '" . $to_id . "' AND `query` = '" . $db->escape($row['query']) . "' LIMIT 1");

		if ($found->num_rows) {
			continue;
		}

		// Same slug across languages is OK — router resolves by language_id.
		// Empty home keyword for non-main becomes the language code.
		$keyword = $row['keyword'] === '' ? $keyword_prefix : $row['keyword'];

		$db->query("INSERT INTO `" . $seo . "` SET `store_id` = '" . (int)$row['store_id'] . "', `language_id` = '" . $to_id . "', `query` = '" . $db->escape($row['query']) . "', `keyword` = '" . $db->escape($keyword) . "'");
	}

	$banner = $prefix . 'banner_image';
	$exists = $db->query("SHOW TABLES LIKE '" . $db->escape($banner) . "'");

	if (!$exists->num_rows) {
		return;
	}

	$query = $db->query("SELECT * FROM `" . $banner . "` WHERE `language_id` = '" . $from_id . "'");

	foreach ($query->rows as $row) {
		$found = $db->query("SELECT 1 FROM `" . $banner . "` WHERE `banner_id` = '" . (int)$row['banner_id'] . "' AND `language_id` = '" . $to_id . "' AND `image` = '" . $db->escape($row['image']) . "' AND `sort_order` = '" . (int)$row['sort_order'] . "' LIMIT 1");

		if ($found->num_rows) {
			continue;
		}

		$db->query("INSERT INTO `" . $banner . "` SET `banner_id` = '" . (int)$row['banner_id'] . "', `language_id` = '" . $to_id . "', `title` = '" . $db->escape($row['title']) . "', `link` = '" . $db->escape($row['link']) . "', `image` = '" . $db->escape($row['image']) . "', `sort_order` = '" . (int)$row['sort_order'] . "'");
	}
}

/**
 * Apply selected language packs (or language_copy fallback) and set main language.
 *
 * @param object $db
 * @param string $prefix
 * @param callable $apply_sql function($db, $file, $prefix)
 * @param string $sql_dir absolute path to install/sql/
 * @param array $codes extra language codes (e.g. ua, ru); en is always present
 * @param string $main_code main storefront language code
 */
function install_apply_languages($db, $prefix, $apply_sql, $sql_dir, $codes, $main_code)
{
	$packs = install_language_packs();
	$codes = array_values(array_unique(array_filter(array_map('strtolower', (array)$codes))));
	$main_code = strtolower((string)$main_code);

	if ($main_code === '' || ($main_code !== 'en' && !in_array($main_code, $codes, true))) {
		$main_code = 'en';
	}

	$installed = array('en' => 1);

	foreach ($codes as $code) {
		if ($code === 'en') {
			continue;
		}

		$pack = rtrim($sql_dir, '/\\') . '/languages/' . $code . '.sql';

		if (is_file($pack)) {
			$apply_sql($db, $pack, $prefix);

			if (isset($packs[$code])) {
				$installed[$code] = (int)$packs[$code];
			} else {
				$query = $db->query("SELECT language_id FROM `" . $prefix . "language` WHERE `code` = '" . $db->escape($code) . "' LIMIT 1");
				$installed[$code] = $query->num_rows ? (int)$query->row['language_id'] : 0;
			}
		} else {
			$db->query("INSERT INTO `" . $prefix . "language` SET name = '" . $db->escape(strtoupper($code)) . "', code = '" . $db->escape($code) . "', locale = '" . $db->escape($code) . "', hreflang = '" . $db->escape($code) . "', image = '" . $db->escape($code . '.png') . "', directory = '" . $db->escape($code) . "', sort_order = '10', status = '1'");
			$to_id = (int)$db->getLastId();
			install_copy_language($db, $prefix, 1, $to_id, $code);
			$installed[$code] = $to_id;
		}
	}

	return array($installed, $main_code);
}

/**
 * Demo SQL historically used language_id 1 = Ukrainian, 2 = English.
 * After EN-first system install, swap entity rows to 1 = English, 2 = Ukrainian.
 */
function install_remap_demo_languages($db, $prefix, $has_ua)
{
	$tables = array(
		'attribute_description',
		'attribute_group_description',
		'category_description',
		'filter_description',
		'filter_group_description',
		'option_description',
		'option_value_description',
		'product_description',
		'product_attribute',
		'manufacturer_description',
		'blog_category_description',
		'article_description',
		'banner_image',
	);

	foreach ($tables as $table) {
		$name = $prefix . $table;
		$exists = $db->query("SHOW TABLES LIKE '" . $db->escape($name) . "'");

		if (!$exists->num_rows) {
			continue;
		}

		$db->query("UPDATE `" . $name . "` SET `language_id` = '9001' WHERE `language_id` = '1'");
		$db->query("UPDATE `" . $name . "` SET `language_id` = '1' WHERE `language_id` = '2'");
		$db->query("UPDATE `" . $name . "` SET `language_id` = '2' WHERE `language_id` = '9001'");

		if (!$has_ua) {
			$db->query("DELETE FROM `" . $name . "` WHERE `language_id` = '2'");
		}
	}

	$seo = $prefix . 'seo_url';
	// Drop old English-prefixed demo duplicates (language_id = 2).
	$db->query("DELETE FROM `" . $seo . "` WHERE `language_id` = '2' AND (`query` LIKE 'product_id=%' OR `query` LIKE 'category_id=%' OR `query` LIKE 'manufacturer_id=%' OR `query` LIKE 'article_id=%' OR `query` LIKE 'blog_category_id=%' OR `query` LIKE 'author_id=%')");
}

/**
 * Point common/home keywords at the chosen main language (empty slug).
 */
function install_set_main_language($db, $prefix, $installed, $main_code)
{
	$db->query("UPDATE `" . $prefix . "setting` SET `value` = '" . $db->escape($main_code) . "' WHERE `key` = 'config_language'");

	$admin = ($main_code === 'ua' || $main_code === 'en') ? $main_code : 'en';
	$db->query("UPDATE `" . $prefix . "setting` SET `value` = '" . $db->escape($admin) . "' WHERE `key` = 'config_admin_language'");

	$seo = $prefix . 'seo_url';

	foreach ($installed as $code => $language_id) {
		$language_id = (int)$language_id;

		if (!$language_id) {
			continue;
		}

		$keyword = ($code === $main_code) ? '' : $code;
		$found = $db->query("SELECT seo_url_id FROM `" . $seo . "` WHERE `query` = 'common/home' AND `language_id` = '" . $language_id . "' AND `store_id` = '0' LIMIT 1");

		if ($found->num_rows) {
			$db->query("UPDATE `" . $seo . "` SET `keyword` = '" . $db->escape($keyword) . "' WHERE `seo_url_id` = '" . (int)$found->row['seo_url_id'] . "'");
		} else {
			$db->query("INSERT INTO `" . $seo . "` SET `store_id` = '0', `language_id` = '" . $language_id . "', `query` = 'common/home', `keyword` = '" . $db->escape($keyword) . "'");
		}
	}
}

function install_api_ips($db, $prefix, $api_id, $ips)
{
	$seen = array();

	foreach ($ips as $ip) {
		$ip = trim((string)$ip);

		if ($ip === '' || isset($seen[$ip])) {
			continue;
		}

		$seen[$ip] = true;
		$db->query("INSERT INTO `" . $prefix . "api_ip` SET `api_id` = '" . (int)$api_id . "', `ip` = '" . $db->escape($ip) . "'");
	}
}
