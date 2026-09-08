<?php

namespace import_export\format;

class Json implements FormatInterface
{
	public function name()
	{
		return 'json';
	}

	public function mime()
	{
		return 'application/json';
	}

	public function extension()
	{
		return 'json';
	}

	public function encode($entity, array $rows, array $languages)
	{
		unset($languages);

		if ($entity === 'bundle') {
			$payload = [
				'version' => 1,
				'entities' => $rows,
			];
		} else {
			$payload = [
				'version' => 1,
				'entity' => $entity,
				'items' => $rows,
			];
		}

		return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	public function decode($content, $entity_hint = '')
	{
		$data = json_decode($content, true);

		if (!is_array($data)) {
			throw new \RuntimeException('Invalid JSON.');
		}

		if (isset($data['entities']) && is_array($data['entities'])) {
			return [
				'entity' => 'bundle',
				'items' => $this->normalizeItems($data['entities']),
			];
		}

		$entity = !empty($data['entity']) ? $data['entity'] : $entity_hint;
		$items = [];

		if (isset($data['items']) && is_array($data['items'])) {
			$items = $data['items'];
		} elseif ($this->isList($data)) {
			$items = $data;
		}

		if ($entity === '' || $entity === 'bundle') {
			$entity = $entity_hint !== '' ? $entity_hint : 'product';
		}

		return [
			'entity' => $entity,
			'items' => $this->normalizeItems([$this->plural($entity) => $items]),
		];
	}

	private function normalizeItems(array $items)
	{
		$out = [
			'manufacturers' => [],
			'attribute_groups' => [],
			'attributes' => [],
			'categories' => [],
			'products' => [],
		];

		$map = [
			'manufacturer' => 'manufacturers',
			'manufacturers' => 'manufacturers',
			'attribute_group' => 'attribute_groups',
			'attribute_groups' => 'attribute_groups',
			'attribute' => 'attributes',
			'attributes' => 'attributes',
			'category' => 'categories',
			'categories' => 'categories',
			'product' => 'products',
			'products' => 'products',
		];

		foreach ($items as $key => $rows) {
			$bucket = isset($map[$key]) ? $map[$key] : '';

			if ($bucket === '' || !is_array($rows)) {
				continue;
			}

			if ($this->isList($rows)) {
				$out[$bucket] = $rows;
			}
		}

		return $out;
	}

	private function plural($entity)
	{
		$map = [
			'manufacturer' => 'manufacturers',
			'attribute_group' => 'attribute_groups',
			'attribute' => 'attributes',
			'category' => 'categories',
			'product' => 'products',
		];

		return isset($map[$entity]) ? $map[$entity] : $entity;
	}

	private function isList(array $data)
	{
		if ($data === []) {
			return true;
		}

		return array_keys($data) === range(0, count($data) - 1);
	}
}
