<?php

class ModelExtensionShippingNovaposhta extends Model
{
	public function install()
	{
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "novaposhta_regions` (
			`ref` varchar(36) NOT NULL,
			`description` varchar(50) NOT NULL,
			`description_ru` varchar(50) NOT NULL,
			PRIMARY KEY (`ref`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "novaposhta_cities` (
			`ref` varchar(36) NOT NULL,
			`description` varchar(200) NOT NULL,
			`description_ru` varchar(200) NOT NULL,
			`area` varchar(36) NOT NULL,
			PRIMARY KEY (`ref`),
			KEY `description` (`description`(20)),
			KEY `description_ru` (`description_ru`(20))
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "novaposhta_departments` (
			`ref` varchar(36) NOT NULL,
			`description` varchar(500) NOT NULL,
			`description_ru` varchar(500) NOT NULL,
			`city_ref` varchar(36) NOT NULL,
			`city_description` varchar(200) NOT NULL,
			`city_description_ru` varchar(200) NOT NULL,
			`category` varchar(20) NOT NULL,
			`number` int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY (`ref`),
			KEY `city_ref` (`city_ref`),
			KEY `category` (`category`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "novaposhta_meta` (
			`type` varchar(32) NOT NULL,
			`updated_at` datetime NOT NULL,
			`amount` int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY (`type`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
	}

	public function uninstall()
	{
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "novaposhta_regions`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "novaposhta_cities`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "novaposhta_departments`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "novaposhta_meta`");
	}
}
