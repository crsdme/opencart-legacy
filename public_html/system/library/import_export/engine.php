<?php

namespace import_export;

use import_export\entity\Attribute;
use import_export\entity\AttributeGroup;
use import_export\entity\Category;
use import_export\entity\Manufacturer;
use import_export\entity\Product;
use import_export\format\Csv;
use import_export\format\Json;

class Engine
{
	const ORDER = ['manufacturers', 'attribute_groups', 'attributes', 'categories', 'products'];

	const BUCKETS = [
		'manufacturers' => 'manufacturer',
		'attribute_groups' => 'attribute_group',
		'attributes' => 'attribute',
		'categories' => 'category',
		'products' => 'product',
	];

	private $registry;
	private $ctx;
	private $mapper;
	private $resolver;
	private $entities = [];
	private $formats = [];

	public function __construct($registry)
	{
		$this->registry = $registry;
		$this->ctx = new Context($registry);
		$this->mapper = new Mapper($this->ctx);
		$this->resolver = new Resolver($this->ctx, $this->mapper);
		$this->entities = [
			'manufacturer' => new Manufacturer($this->ctx, $this->mapper, $this->resolver),
			'attribute_group' => new AttributeGroup($this->ctx, $this->mapper, $this->resolver),
			'attribute' => new Attribute($this->ctx, $this->mapper, $this->resolver),
			'category' => new Category($this->ctx, $this->mapper, $this->resolver),
			'product' => new Product($this->ctx, $this->mapper, $this->resolver),
		];
		$this->formats = [
			'json' => new Json(),
			'csv' => new Csv(),
		];
	}

	public function context()
	{
		return $this->ctx;
	}

	public function mapper()
	{
		return $this->mapper;
	}

	public function entityCodes()
	{
		return array_keys($this->entities);
	}

	public function entity($code)
	{
		return isset($this->entities[$code]) ? $this->entities[$code] : null;
	}

	public function formatCodes()
	{
		return array_keys($this->formats);
	}

	public function format($name)
	{
		return isset($this->formats[$name]) ? $this->formats[$name] : $this->formats['json'];
	}

	public function tabs($url, $token, $active)
	{
		$items = [
			'dashboard' => 'extension/import_export/dashboard',
			'template' => 'extension/import_export/template',
			'export' => 'extension/import_export/export',
			'import' => 'extension/import_export/import',
			'job' => 'extension/import_export/job',
		];
		$tabs = [];

		foreach ($items as $code => $route) {
			$tabs[] = [
				'code' => $code,
				'text' => 'text_tab_' . $code,
				'href' => $url->link($route, 'user_token=' . $token, true),
				'active' => $code === $active,
			];
		}

		return $tabs;
	}

	public function defaults()
	{
		return [
			'module_import_export_status' => 1,
			'module_import_export_product_key' => 'sku',
			'module_import_export_on_missing_ref' => 'create',
			'module_import_export_delete_data_on_uninstall' => 0,
		];
	}

	public function template($entity, $format_name)
	{
		if ($entity === 'bundle' && $format_name === 'csv') {
			throw new \RuntimeException('CSV is one entity per file. Use JSON for a full catalog bundle.');
		}

		$format = $this->format($format_name);

		if ($entity === 'bundle') {
			$rows = [];

			foreach (self::BUCKETS as $bucket => $code) {
				$rows[$bucket] = [$this->entities[$code]->example()];
			}

			return [
				'filename' => 'catalog.' . $format->extension(),
				'mime' => $format->mime(),
				'content' => $format->encode('bundle', $rows, $this->ctx->languages()),
			];
		}

		$handler = $this->entity($entity);

		if (!$handler) {
			throw new \RuntimeException('Unknown entity.');
		}

		$example = $handler->example();

		if ($format_name === 'csv') {
			$rows = [$this->mapper->flatten($example, $handler->schema())];
		} else {
			$rows = [$example];
		}

		return [
			'filename' => $handler->code() . '.' . $format->extension(),
			'mime' => $format->mime(),
			'content' => $format->encode($handler->code(), $rows, $this->ctx->languages()),
		];
	}

	public function export($entity, $format_name, array $filter = [])
	{
		if ($entity === 'bundle' && $format_name === 'csv') {
			throw new \RuntimeException('CSV is one entity per file. Use JSON for a full catalog bundle.');
		}

		$format = $this->format($format_name);

		if ($entity === 'bundle') {
			$rows = [];

			foreach (self::BUCKETS as $bucket => $code) {
				$rows[$bucket] = $this->entities[$code]->exportAll($filter);
			}

			return [
				'filename' => 'catalog-export.' . $format->extension(),
				'mime' => $format->mime(),
				'content' => $format->encode('bundle', $rows, $this->ctx->languages()),
			];
		}

		$handler = $this->entity($entity);

		if (!$handler) {
			throw new \RuntimeException('Unknown entity.');
		}

		$items = $handler->exportAll($filter);

		if ($format_name === 'csv') {
			$flat = [];

			foreach ($items as $item) {
				$flat[] = $this->mapper->flatten($item, $handler->schema());
			}

			if (!$flat) {
				$flat[] = $this->mapper->flatten($handler->example(), $handler->schema());
				$flat = [array_fill_keys(array_keys($flat[0]), '')];
			}

			$items = $flat;
		}

		return [
			'filename' => $handler->code() . '-export.' . $format->extension(),
			'mime' => $format->mime(),
			'content' => $format->encode($handler->code(), $items, $this->ctx->languages()),
		];
	}

	public function parse($content, $format_name, $entity_hint = '')
	{
		$parsed = $this->format($format_name)->decode($content, $entity_hint);

		foreach (self::BUCKETS as $bucket => $code) {
			if (empty($parsed['items'][$bucket])) {
				continue;
			}

			$handler = $this->entities[$code];
			$normalized = [];

			foreach ($parsed['items'][$bucket] as $row) {
				if (!is_array($row)) {
					continue;
				}

				$normalized[] = array_merge($this->mapper->unflatten($row, $handler->schema()), $this->keepUnknown($row, $handler->schema()));
			}

			if ($bucket === 'categories') {
				usort($normalized, function ($a, $b) {
					$pa = isset($a['parent_id']) ? (int) $a['parent_id'] : 0;
					$pb = isset($b['parent_id']) ? (int) $b['parent_id'] : 0;

					if ($pa !== $pb) {
						return $pa - $pb;
					}

					$left = isset($a['path']) ? substr_count((string) $a['path'], '>') : 0;
					$right = isset($b['path']) ? substr_count((string) $b['path'], '>') : 0;

					return $left - $right;
				});
			}

			$parsed['items'][$bucket] = $normalized;
		}

		if ($this->isBundle($parsed)) {
			$parsed['entity'] = 'bundle';
		}

		return $parsed;
	}

	public function preview(array $parsed)
	{
		$this->resolver->reset();
		$rows = [];
		$counts = ['create' => 0, 'update' => 0, 'skip' => 0, 'error' => 0];

		foreach (self::ORDER as $bucket) {
			if (empty($parsed['items'][$bucket])) {
				continue;
			}

			$handler = $this->entities[self::BUCKETS[$bucket]];

			foreach ($parsed['items'][$bucket] as $index => $row) {
				$item = $handler->preview($row);
				$item['row'] = $index + 1;
				$rows[] = $item;
				$action = $item['action'];

				if (isset($counts[$action])) {
					$counts[$action]++;
				}
			}
		}

		return [
			'entity' => $parsed['entity'],
			'rows' => $rows,
			'sample' => array_slice($rows, 0, 50),
			'counts' => $counts,
			'total' => count($rows),
		];
	}

	public function import(array $parsed)
	{
		$this->resolver->reset();
		$created = 0;
		$updated = 0;
		$skipped = 0;
		$errors = 0;
		$messages = [];

		foreach (self::ORDER as $bucket) {
			if (empty($parsed['items'][$bucket])) {
				continue;
			}

			$handler = $this->entities[self::BUCKETS[$bucket]];

			foreach ($parsed['items'][$bucket] as $index => $row) {
				try {
					$result = $handler->write($row);
				} catch (\Exception $e) {
					$result = [
						'action' => 'error',
						'identity' => isset($row['sku']) ? $row['sku'] : '',
						'error' => $e->getMessage(),
					];
				}

				$label = $handler->code() . ' #' . ($index + 1);
				$identity = !empty($result['identity']) ? $result['identity'] : $label;

				if ($result['action'] === 'created') {
					$created++;
				} elseif ($result['action'] === 'updated') {
					$updated++;
				} elseif ($result['action'] === 'skipped') {
					$skipped++;

					if (!empty($result['error'])) {
						$messages[] = $identity . ': ' . $result['error'];
					}
				} else {
					$errors++;
					$messages[] = $identity . ': ' . (!empty($result['error']) ? $result['error'] : 'Import failed.');
				}
			}
		}

		return [
			'created' => $created,
			'updated' => $updated,
			'skipped' => $skipped,
			'errors' => $errors,
			'message' => implode("\n", array_slice($messages, 0, 50)),
		];
	}

	public function detectFormat($filename, $content = '')
	{
		$ext = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));

		if ($ext === 'csv' || $ext === 'json') {
			return $ext;
		}

		$trim = ltrim((string) $content);

		if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
			return 'json';
		}

		return 'csv';
	}

	private function isBundle(array $parsed)
	{
		$filled = 0;

		foreach (self::ORDER as $bucket) {
			if (!empty($parsed['items'][$bucket])) {
				$filled++;
			}
		}

		return $filled > 1 || (!empty($parsed['entity']) && $parsed['entity'] === 'bundle');
	}

	private function keepUnknown(array $row, array $schema)
	{
		$known = [];

		foreach ($schema as $field) {
			$known[$field['key']] = true;

			if (!empty($field['localized'])) {
				foreach ($this->ctx->languages() as $language) {
					$known[$field['key'] . '.' . $language['short']] = true;
					$known[$field['key'] . '.' . $language['code']] = true;
				}
			}
		}

		$out = [];

		foreach ($row as $key => $value) {
			if (!isset($known[$key]) && $value !== '') {
				$out[$key] = $value;
			}
		}

		return $out;
	}
}
