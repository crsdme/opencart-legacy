<?php

namespace import_export;

class Context
{
	private $registry;
	private $languages;
	private $aliases;

	public function __construct($registry)
	{
		$this->registry = $registry;
	}

	public function registry()
	{
		return $this->registry;
	}

	public function db()
	{
		return $this->registry->get('db');
	}

	public function config()
	{
		return $this->registry->get('config');
	}

	public function load()
	{
		return $this->registry->get('load');
	}

	public function model($route)
	{
		$this->load()->model($route);

		return $this->registry->get('model_' . str_replace('/', '_', $route));
	}

	public function setting($key, $default = '')
	{
		$value = $this->config()->get('module_import_export_' . $key);

		return $value !== null && $value !== '' ? $value : $default;
	}

	public function productKey()
	{
		$key = $this->setting('product_key', 'sku');

		return $key === 'model' ? 'model' : 'sku';
	}

	public function onMissing()
	{
		$mode = $this->setting('on_missing_ref', 'create');

		if (in_array($mode, ['create', 'error', 'skip'], true)) {
			return $mode;
		}

		return 'create';
	}

	public function languages()
	{
		if ($this->languages !== null) {
			return $this->languages;
		}

		$query = $this->db()->query("SELECT language_id, name, code FROM `" . DB_PREFIX . "language` WHERE status = '1' ORDER BY sort_order, language_id");
		$default_id = (int) $this->config()->get('config_language_id');
		$this->languages = [];
		$this->aliases = [];

		foreach ($query->rows as $row) {
			$code = strtolower(trim($row['code']));
			$short = strtolower(substr($code, 0, 2));
			$item = [
				'language_id' => (int) $row['language_id'],
				'name' => $row['name'],
				'code' => $code,
				'short' => $short,
				'default' => (int) $row['language_id'] === $default_id,
			];
			$this->languages[] = $item;
			$this->aliases[$code] = $item['language_id'];

			if (!isset($this->aliases[$short])) {
				$this->aliases[$short] = $item['language_id'];
			}
		}

		return $this->languages;
	}

	public function languageId($code)
	{
		$this->languages();
		$code = strtolower(trim((string) $code));

		if ($code === '' || $code === 'default') {
			return $this->defaultLanguageId();
		}

		return isset($this->aliases[$code]) ? (int) $this->aliases[$code] : 0;
	}

	public function languageCode($language_id)
	{
		foreach ($this->languages() as $language) {
			if ($language['language_id'] === (int) $language_id) {
				return $language['short'];
			}
		}

		return '';
	}

	public function defaultLanguageId()
	{
		$id = (int) $this->config()->get('config_language_id');

		if ($id) {
			return $id;
		}

		$languages = $this->languages();

		return $languages ? (int) $languages[0]['language_id'] : 1;
	}

	public function downloadImages()
	{
		return (int) $this->setting('download_images', 1) === 1;
	}

	public function storeIds()
	{
		return [0];
	}

	public function stockStatusId()
	{
		$id = (int) $this->config()->get('config_stock_status_id');

		if ($id) {
			return $id;
		}

		$query = $this->db()->query("SELECT stock_status_id FROM `" . DB_PREFIX . "stock_status` ORDER BY stock_status_id ASC LIMIT 1");

		return $query->num_rows ? (int) $query->row['stock_status_id'] : 1;
	}

	public function weightClassId()
	{
		$id = (int) $this->config()->get('config_weight_class_id');

		return $id ? $id : 1;
	}

	public function lengthClassId()
	{
		$id = (int) $this->config()->get('config_length_class_id');

		return $id ? $id : 1;
	}

	public function storageDir()
	{
		$dir = rtrim(DIR_STORAGE, '/\\') . '/import_export';

		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}

		return $dir;
	}

	public function emptyDescription($name = '')
	{
		$out = [];

		foreach ($this->languages() as $language) {
			$out[$language['language_id']] = [
				'name' => $name,
				'description' => '',
				'tag' => '',
				'meta_title' => '',
				'meta_h1' => '',
				'meta_description' => '',
				'meta_keyword' => '',
			];
		}

		return $out;
	}
}
