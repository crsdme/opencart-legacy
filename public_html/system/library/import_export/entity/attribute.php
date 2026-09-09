<?php

namespace import_export\entity;

class Attribute extends Base
{
	public function code()
	{
		return 'attribute';
	}

	public function schema()
	{
		return [
			['key' => 'attribute_id', 'type' => 'int'],
			['key' => 'attribute_group_id', 'type' => 'int'],
			['key' => 'group'],
			['key' => 'name', 'localized' => true],
			['key' => 'sort_order', 'type' => 'int'],
		];
	}

	public function example()
	{
		return [
			'attribute_id' => 0,
			'attribute_group_id' => 1,
			'group' => 'Specs',
			'name' => ['uk' => 'Колір', 'en' => 'Color'],
			'sort_order' => 0,
		];
	}

	public function identity(array $row)
	{
		$id = $this->rowId($row, 'attribute_id');
		$name = $this->localizedName($row);

		if ($id) {
			return $name !== '' ? '#' . $id . ' ' . $name : '#' . $id;
		}

		$group = trim((string) $this->mapper->scalar($row, 'group'));

		return $group !== '' ? $group . ': ' . $name : $name;
	}

	public function find(array $row)
	{
		$stated = $this->lookupId($row, 'attribute_id', 'attribute', 'attribute_id');

		if ($stated > 0) {
			return $stated;
		}

		if ($stated < 0) {
			return 0;
		}

		if ($this->isForceCreate($row, 'attribute_id')) {
			return 0;
		}

		$group_id = $this->resolveFk('attribute_group', $this->rowId($row, 'attribute_group_id'));
		$group = trim((string) $this->mapper->scalar($row, 'group'));

		if ($group_id && $this->resolver->exists('attribute_group', 'attribute_group_id', $group_id) && $group === '') {
			$names = $this->ctx->model('catalog/attribute_group')->getAttributeGroupDescriptions($group_id);
			$default = $this->ctx->defaultLanguageId();
			$group = isset($names[$default]['name']) ? $names[$default]['name'] : '';
		}

		return $this->resolver->attributeId($this->localizedName($row), $group, false);
	}

	public function read($id)
	{
		$model = $this->ctx->model('catalog/attribute');
		$row = $model->getAttribute($id);

		if (!$row) {
			return [];
		}

		$group_model = $this->ctx->model('catalog/attribute_group');
		$group_names = $group_model->getAttributeGroupDescriptions($row['attribute_group_id']);
		$default = $this->ctx->defaultLanguageId();
		$group = isset($group_names[$default]['name']) ? $group_names[$default]['name'] : '';
		$names = $model->getAttributeDescriptions($id);

		return [
			'attribute_id' => (int) $id,
			'attribute_group_id' => (int) $row['attribute_group_id'],
			'group' => $group,
			'name' => $this->mapper->idsToCodes($this->pluck($names, 'name')),
			'sort_order' => (int) $row['sort_order'],
		];
	}

	public function exportAll(array $filter = [])
	{
		unset($filter);
		$model = $this->ctx->model('catalog/attribute');
		$rows = [];

		foreach ($model->getAttributes() as $item) {
			$rows[] = $this->read($item['attribute_id']);
		}

		return $rows;
	}

	public function preview(array $row)
	{
		$stated = $this->lookupId($row, 'attribute_id', 'attribute', 'attribute_id');

		if ($stated < 0) {
			return $this->unknownIdError('attribute_id', $this->rowId($row, 'attribute_id'));
		}

		$name = $this->localizedName($row);
		$group_id = $this->rowId($row, 'attribute_group_id');
		$group = trim((string) $this->mapper->scalar($row, 'group'));

		if ($stated < 1 && $name === '') {
			return $this->result('error', '', '', 'attribute_id or name is required.');
		}

		if ($stated < 1 && !$group_id && $group === '') {
			return $this->result('error', $this->identity($row), $name, 'attribute_group_id or group is required.');
		}

		if ($group_id) {
			$error = $this->unknownRef('attribute_group_id', $group_id);

			if ($error) {
				if ($this->missingMode() === 'skip') {
					return $this->result('skip', $this->identity($row), $name, $error);
				}

				return $this->unknownIdError('attribute_group_id', $group_id);
			}
		}

		if (!$group_id && $group !== '') {
			$resolved = $this->resolver->attributeGroupId($group, false);

			if (!$resolved && $this->missingMode() === 'error') {
				return $this->result('error', $this->identity($row), $name, 'Unknown attribute group: ' . $group);
			}

			if (!$resolved && $this->missingMode() === 'skip') {
				return $this->result('skip', $this->identity($row), $name, 'Unknown attribute group: ' . $group);
			}
		}

		$id = $this->find($row);

		return $this->result($id ? 'update' : 'create', $this->identity($row), $name ?: $this->identity($row), '', $id);
	}

	public function write(array $row)
	{
		$preview = $this->preview($row);

		if ($preview['action'] === 'error' || $preview['action'] === 'skip') {
			$preview['action'] = $preview['action'] === 'skip' ? 'skipped' : 'error';

			return $preview;
		}

		$name = $this->localizedName($row);
		$group_id = $this->resolveFk('attribute_group', $this->rowId($row, 'attribute_group_id'));

		if (!$group_id) {
			$group = trim((string) $this->mapper->scalar($row, 'group'));
			$group_id = $this->resolver->attributeGroupId($group, $this->createRefs());
		}

		if (!$group_id) {
			return $this->result('error', $this->identity($row), $name, 'Unknown attribute group.');
		}

		$model = $this->ctx->model('catalog/attribute');
		$id = $this->find($row);
		$description = $id ? $model->getAttributeDescriptions($id) : [];
		$description = $this->mapper->mergeDescription($description, $row, ['name']);
		$sort = $id ? (int) $model->getAttribute($id)['sort_order'] : 0;

		if ($this->mapper->has($row, 'sort_order')) {
			$sort = (int) $row['sort_order'];
		}

		$data = [
			'attribute_group_id' => $group_id,
			'sort_order' => $sort,
			'attribute_description' => $description,
		];

		if ($id) {
			$model->editAttribute($id, $data);

			return $this->finishWrite($row, 'attribute_id', 'attribute', $this->result('updated', $this->identity($row), $name ?: $this->identity($row), '', $id));
		}

		$id = (int) $model->addAttribute($data);

		return $this->finishWrite($row, 'attribute_id', 'attribute', $this->result('created', $this->identity($row), $name ?: $this->identity($row), '', $id));
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
