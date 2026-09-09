<?php

namespace import_export;

class Resolver
{
	private $ctx;
	private $mapper;
	private $cache = [];

	public function __construct(Context $ctx, Mapper $mapper)
	{
		$this->ctx = $ctx;
		$this->mapper = $mapper;
	}

	public function reset()
	{
		$this->cache = [];
	}

	public function declareLocal($table, $id)
	{
		$id = (int) $id;

		if ($id < 0) {
			$this->cache['local:' . $table . ':' . $id] = true;
		}
	}

	public function isLocal($table, $id)
	{
		$id = (int) $id;

		return $id < 0 && !empty($this->cache['local:' . $table . ':' . $id]);
	}

	public function alias($table, $id)
	{
		$id = (int) $id;

		if ($id >= 0) {
			return 0;
		}

		$key = 'alias:' . $table . ':' . $id;

		return isset($this->cache[$key]) ? (int) $this->cache[$key] : 0;
	}

	public function rememberAlias($table, $local_id, $real_id)
	{
		$local_id = (int) $local_id;
		$real_id = (int) $real_id;

		if ($local_id >= 0 || $real_id < 1) {
			return;
		}

		$this->cache['alias:' . $table . ':' . $local_id] = $real_id;
		$column = $this->idColumn($table);

		if ($column) {
			$this->cache['ex:' . $table . ':' . $real_id] = true;
		}
	}

	public function resolveRef($table, $id, $allow_local = false)
	{
		$id = (int) $id;

		if ($id > 0) {
			$column = $this->idColumn($table);

			return $column && $this->exists($table, $column, $id) ? $id : 0;
		}

		if ($id < 0) {
			$real = $this->alias($table, $id);

			if ($real) {
				return $real;
			}

			return $allow_local && $this->isLocal($table, $id) ? $id : 0;
		}

		return 0;
	}

	private function idColumn($table)
	{
		$allowed = [
			'product' => 'product_id',
			'category' => 'category_id',
			'manufacturer' => 'manufacturer_id',
			'attribute' => 'attribute_id',
			'attribute_group' => 'attribute_group_id',
		];

		return isset($allowed[$table]) ? $allowed[$table] : '';
	}

	public function exists($table, $column, $id)
	{
		$id = (int) $id;

		if ($id < 1) {
			return false;
		}

		if ($this->idColumn($table) !== $column) {
			return false;
		}

		$key = 'ex:' . $table . ':' . $id;

		if (isset($this->cache[$key])) {
			return $this->cache[$key];
		}

		$db = $this->ctx->db();
		$query = $db->query("SELECT `" . $column . "` FROM `" . DB_PREFIX . $table . "` WHERE `" . $column . "` = '" . $id . "' LIMIT 1");

		return $this->cache[$key] = (bool) $query->num_rows;
	}

	public function manufacturerId($name, $create = false)
	{
		$name = trim((string) $name);

		if ($name === '') {
			return 0;
		}

		$key = 'm:' . mb_strtolower($name);

		if (isset($this->cache[$key])) {
			return $this->cache[$key];
		}

		$db = $this->ctx->db();
		$query = $db->query("SELECT manufacturer_id FROM `" . DB_PREFIX . "manufacturer` WHERE name = '" . $db->escape($name) . "' LIMIT 1");

		if ($query->num_rows) {
			return $this->cache[$key] = (int) $query->row['manufacturer_id'];
		}

		$query = $db->query("SELECT query FROM `" . DB_PREFIX . "seo_url` WHERE keyword = '" . $db->escape($name) . "' AND query LIKE 'manufacturer_id=%' LIMIT 1");

		if ($query->num_rows && preg_match('/manufacturer_id=(\d+)/', $query->row['query'], $match)) {
			return $this->cache[$key] = (int) $match[1];
		}

		if (!$create) {
			return 0;
		}

		$model = $this->ctx->model('catalog/manufacturer');
		$description = [];

		foreach ($this->ctx->languages() as $language) {
			$description[$language['language_id']] = [
				'description' => '',
				'meta_title' => '',
				'meta_h1' => '',
				'meta_description' => '',
				'meta_keyword' => '',
			];
		}

		$id = (int) $model->addManufacturer([
			'name' => $name,
			'sort_order' => 0,
			'noindex' => 0,
			'image' => '',
			'manufacturer_description' => $description,
			'manufacturer_store' => $this->ctx->storeIds(),
		]);

		return $this->cache[$key] = $id;
	}

	public function attributeGroupId($name, $create = false)
	{
		$name = trim((string) $name);

		if ($name === '') {
			return 0;
		}

		$key = 'ag:' . mb_strtolower($name);

		if (isset($this->cache[$key])) {
			return $this->cache[$key];
		}

		$db = $this->ctx->db();
		$query = $db->query("SELECT attribute_group_id FROM `" . DB_PREFIX . "attribute_group_description` WHERE name = '" . $db->escape($name) . "' LIMIT 1");

		if ($query->num_rows) {
			return $this->cache[$key] = (int) $query->row['attribute_group_id'];
		}

		if (!$create) {
			return 0;
		}

		$model = $this->ctx->model('catalog/attribute_group');
		$description = [];

		foreach ($this->ctx->languages() as $language) {
			$description[$language['language_id']] = ['name' => $name];
		}

		$id = (int) $model->addAttributeGroup([
			'sort_order' => 0,
			'attribute_group_description' => $description,
		]);

		return $this->cache[$key] = $id;
	}

	public function attributeId($name, $group = '', $create = false)
	{
		$name = trim((string) $name);
		$group = trim((string) $group);

		if ($name === '') {
			return 0;
		}

		$key = 'a:' . mb_strtolower($group) . ':' . mb_strtolower($name);

		if (isset($this->cache[$key])) {
			return $this->cache[$key];
		}

		$db = $this->ctx->db();
		$sql = "SELECT a.attribute_id FROM `" . DB_PREFIX . "attribute` a LEFT JOIN `" . DB_PREFIX . "attribute_description` ad ON (a.attribute_id = ad.attribute_id) WHERE ad.name = '" . $db->escape($name) . "'";

		if ($group !== '') {
			$group_id = $this->attributeGroupId($group, $create);

			if (!$group_id) {
				return 0;
			}

			$sql .= " AND a.attribute_group_id = '" . (int) $group_id . "'";
		}

		$sql .= " LIMIT 1";
		$query = $db->query($sql);

		if ($query->num_rows) {
			return $this->cache[$key] = (int) $query->row['attribute_id'];
		}

		if (!$create) {
			return 0;
		}

		$group_id = $this->attributeGroupId($group !== '' ? $group : 'General', true);

		if (!$group_id) {
			return 0;
		}

		$model = $this->ctx->model('catalog/attribute');
		$description = [];

		foreach ($this->ctx->languages() as $language) {
			$description[$language['language_id']] = ['name' => $name];
		}

		$id = (int) $model->addAttribute([
			'attribute_group_id' => $group_id,
			'sort_order' => 0,
			'attribute_description' => $description,
		]);

		return $this->cache[$key] = $id;
	}

	public function categoryIdByPath($path, $create = false)
	{
		$path = html_entity_decode(trim((string) $path), ENT_QUOTES, 'UTF-8');
		$path = str_replace(["\xC2\xA0", '&nbsp;'], ' ', $path);
		$path = preg_replace('/\s*>\s*/', '>', $path);
		$path = trim($path, " >");

		if ($path === '') {
			return 0;
		}

		$key = 'c:' . mb_strtolower($path);

		if (isset($this->cache[$key])) {
			return $this->cache[$key];
		}

		$parts = array_values(array_filter(array_map('trim', explode('>', $path)), 'strlen'));
		$parent_id = 0;
		$walked = [];

		foreach ($parts as $name) {
			$walked[] = $name;
			$walk_key = 'c:' . mb_strtolower(implode(' > ', $walked));

			if (isset($this->cache[$walk_key])) {
				$parent_id = $this->cache[$walk_key];
				continue;
			}

			$id = $this->categoryByName($name, $parent_id);

			if (!$id && $create) {
				$id = $this->createCategory($name, $parent_id);
			}

			if (!$id) {
				return 0;
			}

			$this->cache[$walk_key] = $id;
			$parent_id = $id;
		}

		return $this->cache[$key] = $parent_id;
	}

	public function categoryIdByKeyword($keyword)
	{
		$keyword = trim((string) $keyword);

		if ($keyword === '') {
			return 0;
		}

		$db = $this->ctx->db();
		$query = $db->query("SELECT query FROM `" . DB_PREFIX . "seo_url` WHERE keyword = '" . $db->escape($keyword) . "' AND query LIKE 'category_id=%' LIMIT 1");

		if ($query->num_rows && preg_match('/category_id=(\d+)/', $query->row['query'], $match)) {
			return (int) $match[1];
		}

		return 0;
	}

	public function productId($value, $field = 'sku')
	{
		$value = trim((string) $value);

		if ($value === '') {
			return 0;
		}

		$column = $field === 'model' ? 'model' : 'sku';
		$key = 'p:' . $column . ':' . mb_strtolower($value);

		if (isset($this->cache[$key])) {
			return $this->cache[$key];
		}

		$db = $this->ctx->db();
		$query = $db->query("SELECT product_id FROM `" . DB_PREFIX . "product` WHERE `" . $column . "` = '" . $db->escape($value) . "' LIMIT 1");

		if ($query->num_rows) {
			return $this->cache[$key] = (int) $query->row['product_id'];
		}

		return 0;
	}

	public function rememberProduct($field, $value, $id)
	{
		$column = $field === 'model' ? 'model' : 'sku';
		$this->cache['p:' . $column . ':' . mb_strtolower(trim((string) $value))] = (int) $id;
	}

	public function categoryPath($category_id)
	{
		$names = [];
		$id = (int) $category_id;
		$guard = 0;

		while ($id && $guard < 20) {
			$model = $this->ctx->model('catalog/category');
			$row = $model->getCategory($id);

			if (!$row) {
				break;
			}

			$names[] = html_entity_decode($row['name'], ENT_QUOTES, 'UTF-8');
			$id = (int) $row['parent_id'];
			$guard++;
		}

		return implode(' > ', array_reverse($names));
	}

	private function categoryByName($name, $parent_id)
	{
		$db = $this->ctx->db();
		$query = $db->query("SELECT c.category_id FROM `" . DB_PREFIX . "category` c LEFT JOIN `" . DB_PREFIX . "category_description` cd ON (c.category_id = cd.category_id) WHERE c.parent_id = '" . (int) $parent_id . "' AND cd.name = '" . $db->escape($name) . "' LIMIT 1");

		return $query->num_rows ? (int) $query->row['category_id'] : 0;
	}

	private function createCategory($name, $parent_id)
	{
		$model = $this->ctx->model('catalog/category');
		$description = $this->ctx->emptyDescription($name);

		return (int) $model->addCategory([
			'parent_id' => (int) $parent_id,
			'top' => $parent_id ? 0 : 1,
			'column' => 1,
			'sort_order' => 0,
			'status' => 1,
			'noindex' => 0,
			'image' => '',
			'category_description' => $description,
			'category_store' => $this->ctx->storeIds(),
		]);
	}
}
