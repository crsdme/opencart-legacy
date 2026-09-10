<?php

namespace import_export\entity;

class Manufacturer extends Base
{
	public function code()
	{
		return 'manufacturer';
	}

	public function schema()
	{
		return [
			['key' => 'manufacturer_id', 'type' => 'int'],
			['key' => 'name'],
			['key' => 'sort_order', 'type' => 'int'],
			['key' => 'noindex', 'type' => 'bool'],
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
			'manufacturer_id' => 0,
			'name' => 'Acme',
			'sort_order' => 0,
			'noindex' => 1,
			'image' => 'catalog/demo/acme.png',
			'description' => ['uk' => '', 'en' => ''],
			'keyword' => ['uk' => 'acme', 'en' => 'acme'],
		];
	}

	public function identity(array $row)
	{
		$id = $this->rowId($row, 'manufacturer_id');
		$name = trim((string) $this->mapper->scalar($row, 'name'));

		if ($id) {
			return $name !== '' ? '#' . $id . ' ' . $name : '#' . $id;
		}

		return $name;
	}

	public function find(array $row)
	{
		$stated = $this->lookupId($row, 'manufacturer_id', 'manufacturer', 'manufacturer_id');

		if ($stated > 0) {
			return $stated;
		}

		if ($stated < 0) {
			return 0;
		}

		if ($this->isForceCreate($row, 'manufacturer_id')) {
			return 0;
		}

		return $this->resolver->manufacturerId(trim((string) $this->mapper->scalar($row, 'name')), false);
	}

	public function read($id)
	{
		$model = $this->ctx->model('catalog/manufacturer');
		$row = $model->getManufacturer($id);

		if (!$row) {
			return [];
		}

		$descriptions = $model->getManufacturerDescriptions($id);
		$seo = $model->getManufacturerSeoUrls($id);
		$keyword = [];

		if (isset($seo[0]) && is_array($seo[0])) {
			$keyword = $this->mapper->idsToCodes($seo[0]);
		}

		return [
			'manufacturer_id' => (int) $row['manufacturer_id'],
			'name' => $row['name'],
			'sort_order' => (int) $row['sort_order'],
			'noindex' => (int) $row['noindex'],
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
		$model = $this->ctx->model('catalog/manufacturer');
		$rows = [];

		foreach ($model->getManufacturers() as $item) {
			$rows[] = $this->read($item['manufacturer_id']);
		}

		return $rows;
	}

	public function preview(array $row)
	{
		$stated = $this->lookupId($row, 'manufacturer_id', 'manufacturer', 'manufacturer_id');

		if ($stated < 0) {
			return $this->unknownIdError('manufacturer_id', $this->rowId($row, 'manufacturer_id'));
		}

		$name = trim((string) $this->mapper->scalar($row, 'name'));

		if ($stated < 1 && $name === '') {
			return $this->result('error', '', '', 'manufacturer_id or name is required.');
		}

		$id = $this->find($row);

		return $this->result($id ? 'update' : 'create', $this->identity($row), $name ?: $this->identity($row), '', $id);
	}

	public function write(array $row)
	{
		$preview = $this->preview($row);

		if ($preview['action'] === 'error') {
			return $preview;
		}

		$model = $this->ctx->model('catalog/manufacturer');
		$id = $this->find($row);
		$name = trim((string) $this->mapper->scalar($row, 'name'));
		$data = $id ? $this->form($id) : $this->defaults($name !== '' ? $name : 'Manufacturer');

		if ($name !== '') {
			$data['name'] = $name;
		}

		if ($this->mapper->has($row, 'sort_order')) {
			$data['sort_order'] = (int) $row['sort_order'];
		}

		if ($this->mapper->has($row, 'noindex')) {
			$data['noindex'] = (int) $row['noindex'];
		}

		if ($this->mapper->has($row, 'image')) {
			$image = $this->mapper->imagePath($row['image']);
			$data['image'] = $image === false ? $data['image'] : $image;
		}

		$data['manufacturer_description'] = $this->mapper->mergeDescription(
			$data['manufacturer_description'],
			$row,
			['description', 'meta_title', 'meta_h1', 'meta_description', 'meta_keyword']
		);

		$seo = $this->mapper->seoMap(isset($row['keyword']) ? $row['keyword'] : null);

		if ($seo !== null) {
			$data['manufacturer_seo_url'] = $seo;
		}

		if ($id) {
			$model->editManufacturer($id, $data);

			return $this->finishWrite($row, 'manufacturer_id', 'manufacturer', $this->result('updated', $this->identity($row), $data['name'], '', $id));
		}

		$id = (int) $model->addManufacturer($data);

		return $this->finishWrite($row, 'manufacturer_id', 'manufacturer', $this->result('created', $this->identity($row), $data['name'], '', $id));
	}

	private function defaults($name)
	{
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

		return [
			'name' => $name,
			'sort_order' => 0,
			'noindex' => 1,
			'image' => '',
			'manufacturer_description' => $description,
			'manufacturer_store' => $this->ctx->storeIds(),
		];
	}

	private function form($id)
	{
		$model = $this->ctx->model('catalog/manufacturer');
		$row = $model->getManufacturer($id);
		$data = $this->defaults($row['name']);
		$data['sort_order'] = (int) $row['sort_order'];
		$data['noindex'] = (int) $row['noindex'];
		$data['image'] = $row['image'];
		$data['manufacturer_description'] = $model->getManufacturerDescriptions($id);
		$data['manufacturer_store'] = $model->getManufacturerStores($id);
		$data['manufacturer_seo_url'] = $model->getManufacturerSeoUrls($id);
		$data['manufacturer_layout'] = $model->getManufacturerLayouts($id);
		$data['product_related'] = $model->getProductRelated($id);
		$data['article_related'] = $model->getArticleRelated($id);

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
