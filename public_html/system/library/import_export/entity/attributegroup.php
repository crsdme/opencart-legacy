<?php

namespace import_export\entity;

class AttributeGroup extends Base
{
	public function code()
	{
		return 'attribute_group';
	}

	public function schema()
	{
		return [
			['key' => 'attribute_group_id', 'type' => 'int'],
			['key' => 'name', 'localized' => true],
			['key' => 'sort_order', 'type' => 'int'],
		];
	}

	public function example()
	{
		return [
			'attribute_group_id' => 0,
			'name' => ['uk' => 'Характеристики', 'en' => 'Specs'],
			'sort_order' => 0,
		];
	}

	public function identity(array $row)
	{
		$id = $this->rowId($row, 'attribute_group_id');
		$name = $this->localizedName($row);

		if ($id) {
			return $name !== '' ? '#' . $id . ' ' . $name : '#' . $id;
		}

		return $name;
	}

	public function find(array $row)
	{
		$stated = $this->lookupId($row, 'attribute_group_id', 'attribute_group', 'attribute_group_id');

		if ($stated > 0) {
			return $stated;
		}

		if ($stated < 0) {
			return 0;
		}

		return $this->resolver->attributeGroupId($this->localizedName($row), false);
	}

	public function read($id)
	{
		$model = $this->ctx->model('catalog/attribute_group');
		$row = $model->getAttributeGroup($id);

		if (!$row) {
			return [];
		}

		$descriptions = $model->getAttributeGroupDescriptions($id);

		return [
			'attribute_group_id' => (int) $row['attribute_group_id'],
			'name' => $this->mapper->idsToCodes($this->pluck($descriptions, 'name')),
			'sort_order' => (int) $row['sort_order'],
		];
	}

	public function exportAll(array $filter = [])
	{
		unset($filter);
		$model = $this->ctx->model('catalog/attribute_group');
		$rows = [];

		foreach ($model->getAttributeGroups() as $item) {
			$rows[] = $this->read($item['attribute_group_id']);
		}

		return $rows;
	}

	public function preview(array $row)
	{
		$stated = $this->lookupId($row, 'attribute_group_id', 'attribute_group', 'attribute_group_id');

		if ($stated < 0) {
			return $this->unknownIdError('attribute_group_id', $this->rowId($row, 'attribute_group_id'));
		}

		$name = $this->localizedName($row);

		if ($stated < 1 && $name === '') {
			return $this->result('error', '', '', 'attribute_group_id or name is required.');
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

		$name = $this->localizedName($row);
		$model = $this->ctx->model('catalog/attribute_group');
		$id = $this->find($row);
		$description = [];

		if ($id) {
			$description = $model->getAttributeGroupDescriptions($id);
			$existing = $model->getAttributeGroup($id);
			$sort = (int) $existing['sort_order'];
		} else {
			$sort = 0;
		}

		$description = $this->mapper->mergeDescription($description, $row, ['name']);

		if ($this->mapper->has($row, 'sort_order')) {
			$sort = (int) $row['sort_order'];
		}

		$data = [
			'sort_order' => $sort,
			'attribute_group_description' => $description,
		];

		if ($id) {
			$model->editAttributeGroup($id, $data);

			return $this->result('updated', $this->identity($row), $name ?: $this->identity($row), '', $id);
		}

		$id = (int) $model->addAttributeGroup($data);

		return $this->result('created', $this->identity($row), $name ?: $this->identity($row), '', $id);
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
