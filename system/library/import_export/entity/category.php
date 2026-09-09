<?php

namespace import_export\entity;

class Category extends Base
{
	public function code()
	{
		return 'category';
	}

	public function schema()
	{
		return [
			['key' => 'category_id', 'type' => 'int'],
			['key' => 'parent_id', 'type' => 'int'],
			['key' => 'path'],
			['key' => 'name', 'localized' => true],
			['key' => 'status', 'type' => 'bool'],
			['key' => 'noindex', 'type' => 'bool'],
			['key' => 'top', 'type' => 'bool'],
			['key' => 'column', 'type' => 'int'],
			['key' => 'sort_order', 'type' => 'int'],
			['key' => 'image'],
			['key' => 'description', 'localized' => true],
			['key' => 'meta_title', 'localized' => true],
			['key' => 'meta_h1', 'localized' => true],
			['key' => 'meta_description', 'localized' => true],
			['key' => 'meta_keyword', 'localized' => true],
			['key' => 'keyword', 'localized' => true],
		];
	}

	public function example()
	{
		return [
			'category_id' => 0,
			'parent_id' => 0,
			'path' => 'Clothes > T-shirts',
			'name' => ['uk' => 'Футболки', 'en' => 'T-shirts'],
			'status' => 1,
			'noindex' => 0,
			'keyword' => ['uk' => 'futbolky', 'en' => 't-shirts'],
		];
	}

	public function identity(array $row)
	{
		$id = $this->rowId($row, 'category_id');
		$path = trim((string) $this->mapper->scalar($row, 'path'));
		$name = $this->localizedName($row);
		$label = $path !== '' ? $path : $name;

		if ($id) {
			return $label !== '' ? '#' . $id . ' ' . $label : '#' . $id;
		}

		return $label;
	}

	public function find(array $row)
	{
		$stated = $this->lookupId($row, 'category_id', 'category', 'category_id');

		if ($stated > 0) {
			return $stated;
		}

		if ($stated < 0) {
			return 0;
		}

		if ($this->isForceCreate($row, 'category_id')) {
			return 0;
		}

		$path = trim((string) $this->mapper->scalar($row, 'path'));

		if ($path !== '') {
			$id = $this->resolver->categoryIdByPath($path, false);

			if ($id) {
				return $id;
			}
		}

		$keyword = $this->mapper->localized($row, 'keyword');

		if ($keyword) {
			$default = $keyword[$this->ctx->defaultLanguageId()] ?? reset($keyword);

			return $this->resolver->categoryIdByKeyword($default);
		}

		$name = $this->localizedName($row);

		return $name !== '' ? $this->resolver->categoryIdByPath($name, false) : 0;
	}

	public function read($id)
	{
		$model = $this->ctx->model('catalog/category');
		$row = $model->getCategory($id);

		if (!$row) {
			return [];
		}

		$descriptions = $model->getCategoryDescriptions($id);
		$seo = $model->getCategorySeoUrls($id);
		$keyword = [];

		if (isset($seo[0]) && is_array($seo[0])) {
			$keyword = $this->mapper->idsToCodes($seo[0]);
		}

		return [
			'category_id' => (int) $id,
			'parent_id' => (int) $row['parent_id'],
			'path' => $this->resolver->categoryPath($id),
			'name' => $this->mapper->idsToCodes($this->pluck($descriptions, 'name')),
			'status' => (int) $row['status'],
			'noindex' => (int) $row['noindex'],
			'top' => (int) $row['top'],
			'column' => (int) $row['column'],
			'sort_order' => (int) $row['sort_order'],
			'image' => $row['image'],
			'description' => $this->mapper->idsToCodes($this->pluck($descriptions, 'description')),
			'meta_title' => $this->mapper->idsToCodes($this->pluck($descriptions, 'meta_title')),
			'meta_h1' => $this->mapper->idsToCodes($this->pluck($descriptions, 'meta_h1')),
			'meta_description' => $this->mapper->idsToCodes($this->pluck($descriptions, 'meta_description')),
			'meta_keyword' => $this->mapper->idsToCodes($this->pluck($descriptions, 'meta_keyword')),
			'keyword' => $keyword,
		];
	}

	public function exportAll(array $filter = [])
	{
		unset($filter);
		$model = $this->ctx->model('catalog/category');
		$rows = [];

		foreach ($model->getCategories() as $item) {
			$rows[] = $this->read($item['category_id']);
		}

		return $rows;
	}

	public function preview(array $row)
	{
		$stated = $this->lookupId($row, 'category_id', 'category', 'category_id');

		if ($stated < 0) {
			return $this->unknownIdError('category_id', $this->rowId($row, 'category_id'));
		}

		$parent_id = $this->rowId($row, 'parent_id');

		if ($this->mapper->has($row, 'parent_id') && $parent_id) {
			$error = $this->unknownRef('parent_id', $parent_id);

			if ($error) {
				if ($this->missingMode() === 'skip') {
					return $this->result('skip', $this->identity($row), $this->localizedName($row), $error);
				}

				return $this->unknownIdError('parent_id', $parent_id);
			}
		}

		if ($stated < 1 && $this->localizedName($row) === '' && trim((string) $this->mapper->scalar($row, 'path')) === '') {
			return $this->result('error', '', '', 'category_id, name or path is required.');
		}

		$id = $this->find($row);

		return $this->result($id ? 'update' : 'create', $this->identity($row), $this->localizedName($row) ?: $this->identity($row), '', $id);
	}

	public function write(array $row)
	{
		$preview = $this->preview($row);

		if ($preview['action'] === 'error' || $preview['action'] === 'skip') {
			$preview['action'] = $preview['action'] === 'skip' ? 'skipped' : 'error';

			return $preview;
		}

		$id = $this->find($row);
		$parent_id = 0;
		$path = trim((string) $this->mapper->scalar($row, 'path'));
		$leaf = $this->localizedName($row);

		if ($this->mapper->has($row, 'parent_id')) {
			$raw_parent = $this->rowId($row, 'parent_id');
			$parent_id = $this->resolveFk('category', $raw_parent);

			if ($raw_parent && !$parent_id) {
				$action = $this->missingMode() === 'skip' ? 'skipped' : 'error';

				return $this->result($action, $this->identity($row), $leaf, 'Unknown parent_id: ' . $raw_parent);
			}
		} elseif (strpos($path, '>') !== false) {
			$parts = array_values(array_filter(array_map('trim', preg_split('/\s*>\s*/', $path)), 'strlen'));
			$leaf = $leaf !== '' ? $leaf : (string) array_pop($parts);
			$parent_path = implode(' > ', $parts);

			if ($parent_path !== '') {
				$parent_id = $this->resolver->categoryIdByPath($parent_path, $this->createRefs());

				if (!$parent_id) {
					$action = $this->missingMode() === 'skip' ? 'skipped' : 'error';

					return $this->result($action, $this->identity($row), $leaf, 'Unknown parent category: ' . $parent_path);
				}
			}
		} elseif ($path !== '' && $leaf === '') {
			$leaf = $path;
		}

		if ($leaf === '' && $id) {
			$existing = $this->ctx->model('catalog/category')->getCategory($id);
			$leaf = $existing ? html_entity_decode($existing['name'], ENT_QUOTES, 'UTF-8') : 'Category';
		}

		if ($leaf === '') {
			return $this->result('error', $this->identity($row), '', 'Category name is required to create.');
		}

		$model = $this->ctx->model('catalog/category');
		$data = $id ? $this->form($id) : $this->defaults($leaf, $parent_id);

		if (!$id || $this->mapper->has($row, 'parent_id') || strpos($path, '>') !== false) {
			$data['parent_id'] = $parent_id;
		}

		if ($this->mapper->has($row, 'status')) {
			$data['status'] = (int) $row['status'];
		}

		if ($this->mapper->has($row, 'noindex')) {
			$data['noindex'] = (int) $row['noindex'];
		}

		if ($this->mapper->has($row, 'top')) {
			$data['top'] = (int) $row['top'];
		}

		if ($this->mapper->has($row, 'column')) {
			$data['column'] = (int) $row['column'];
		}

		if ($this->mapper->has($row, 'sort_order')) {
			$data['sort_order'] = (int) $row['sort_order'];
		}

		if ($this->mapper->has($row, 'image')) {
			$image = $this->mapper->imagePath($row['image']);
			$data['image'] = $image === false ? $data['image'] : $image;
		}

		if (!isset($row['name'])) {
			$row['name'] = $leaf;
		}

		$data['category_description'] = $this->mapper->mergeDescription(
			$data['category_description'],
			$row,
			['name', 'description', 'meta_title', 'meta_h1', 'meta_description', 'meta_keyword']
		);

		$seo = $this->mapper->seoMap(isset($row['keyword']) ? $row['keyword'] : null);

		if ($seo !== null) {
			$data['category_seo_url'] = $seo;
		}

		if ($id) {
			$model->editCategory($id, $data);

			return $this->finishWrite($row, 'category_id', 'category', $this->result('updated', $this->identity($row), $leaf, '', $id));
		}

		$id = (int) $model->addCategory($data);

		if ($path !== '') {
			$this->resolver->categoryIdByPath($path, false);
		}

		return $this->finishWrite($row, 'category_id', 'category', $this->result('created', $this->identity($row), $leaf, '', $id));
	}

	private function defaults($name, $parent_id)
	{
		return [
			'parent_id' => (int) $parent_id,
			'top' => $parent_id ? 0 : 1,
			'column' => 1,
			'sort_order' => 0,
			'status' => 1,
			'noindex' => 0,
			'image' => '',
			'category_description' => $this->ctx->emptyDescription($name),
			'category_store' => $this->ctx->storeIds(),
		];
	}

	private function form($id)
	{
		$model = $this->ctx->model('catalog/category');
		$row = $model->getCategory($id);
		$data = $this->defaults($row['name'], $row['parent_id']);
		$data['top'] = (int) $row['top'];
		$data['column'] = (int) $row['column'];
		$data['sort_order'] = (int) $row['sort_order'];
		$data['status'] = (int) $row['status'];
		$data['noindex'] = (int) $row['noindex'];
		$data['image'] = $row['image'];
		$data['category_description'] = $model->getCategoryDescriptions($id);
		$data['category_store'] = $model->getCategoryStores($id);
		$data['category_seo_url'] = $model->getCategorySeoUrls($id);
		$data['category_layout'] = $model->getCategoryLayouts($id);
		$data['category_filter'] = $model->getCategoryFilters($id);

		return $data;
	}

	private function pluck(array $descriptions, $field)
	{
		$out = [];

		foreach ($descriptions as $language_id => $row) {
			$out[$language_id] = isset($row[$field]) ? $row[$field] : '';
		}

		return $out;
	}
}
