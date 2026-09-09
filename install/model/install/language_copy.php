<?php

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

		$keyword = $row['keyword'] === '' ? $keyword_prefix : $keyword_prefix . '-' . $row['keyword'];

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
