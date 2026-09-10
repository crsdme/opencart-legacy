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
			'module_import_export_download_images' => 1,
			'module_import_export_delete_data_on_uninstall' => 0,
		];
	}

	private function fileHelp()
	{
		$language = $this->registry->get('language');

		if ($language && method_exists($language, 'get')) {
			$text = $language->get('help_import');

			if ($text !== '' && $text !== 'help_import') {
				return $text;
			}
		}

		$path = $this->languageFile();

		if ($path) {
			$_ = [];
			include $path;

			if (!empty($_['help_import'])) {
				return $_['help_import'];
			}
		}

		return 'Preview first. Live positive ids update that row. 0 = create. Negative ids (-1, -2) create and let other rows in this file link to them (JSON bundle for category + product). Images: local image/catalog path, or http(s) URL to download.';
	}

	private function languageFile()
	{
		$config = $this->ctx->config();
		$code = $config ? (string) $config->get('config_admin_language') : '';

		if ($code === '' && $config) {
			$code = (string) $config->get('config_language');
		}

		$code = strtolower($code !== '' ? $code : 'en');
		$short = substr($code, 0, 2);
		$base = defined('DIR_LANGUAGE') ? rtrim(DIR_LANGUAGE, '/\\') : '';

		if ($base === '') {
			return '';
		}

		foreach (array_unique([$code, $short, 'en', 'ua']) as $dir) {
			$path = $base . '/' . $dir . '/extension/import_export/import_export.php';

			if (is_file($path)) {
				return $path;
			}
		}

		return '';
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
				$handler = $this->entities[$code];
				$rows[$bucket] = [$this->mapper->padLocalized($handler->example(), $handler->schema())];
			}

			return [
				'filename' => 'catalog.' . $format->extension(),
				'mime' => $format->mime(),
				'content' => $format->encode('bundle', $rows, $this->ctx->languages(), $this->fileHelp()),
			];
		}

		$handler = $this->entity($entity);

		if (!$handler) {
			throw new \RuntimeException('Unknown entity.');
		}

		$example = $this->mapper->padLocalized($handler->example(), $handler->schema());

		if ($format_name === 'csv') {
			$rows = [$this->mapper->flatten($example, $handler->schema())];
		} else {
			$rows = [$example];
		}

		return [
			'filename' => $handler->code() . '.' . $format->extension(),
			'mime' => $format->mime(),
			'content' => $format->encode($handler->code(), $rows, $this->ctx->languages(), $this->fileHelp()),
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
				$normalized = $this->sortCategories($normalized);
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
		$this->declareLocals($parsed);
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
			'sample' => array_slice($rows, 0, 50),
			'counts' => $counts,
			'total' => count($rows),
		];
	}

	public function import(array $parsed)
	{
		@set_time_limit(0);
		@ignore_user_abort(true);
		$this->resolver->reset();
		$this->declareLocals($parsed);
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

		$this->clearSitemapCache();

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

	private function clearSitemapCache()
	{
		$files = glob(DIR_CACHE . 'sitemap_*.xml');

		if (!$files) {
			return;
		}

		foreach ($files as $file) {
			if (is_file($file)) {
				@unlink($file);
			}
		}
	}

	private function declareLocals(array $parsed)
	{
		$keys = [
			'manufacturers' => ['manufacturer', 'manufacturer_id'],
			'attribute_groups' => ['attribute_group', 'attribute_group_id'],
			'attributes' => ['attribute', 'attribute_id'],
			'categories' => ['category', 'category_id'],
			'products' => ['product', 'product_id'],
		];

		foreach ($keys as $bucket => $pair) {
			if (empty($parsed['items'][$bucket])) {
				continue;
			}

			foreach ($parsed['items'][$bucket] as $row) {
				if (!is_array($row) || !isset($row[$pair[1]]) || $row[$pair[1]] === '' || $row[$pair[1]] === null) {
					continue;
				}

				$id = (int) $row[$pair[1]];

				if ($id < 0) {
					$this->resolver->declareLocal($pair[0], $id);
				}
			}
		}
	}

	private function sortCategories(array $rows)
	{
		$count = count($rows);

		if ($count < 2) {
			return $rows;
		}

		$placed = array_fill(0, $count, false);
		$ready = [];
		$out = [];
		$guard = 0;

		while (count($out) < $count && $guard < $count + 1) {
			$guard++;
			$progress = false;

			for ($i = 0; $i < $count; $i++) {
				if ($placed[$i]) {
					continue;
				}

				$parent = 0;

				if (isset($rows[$i]['parent_id']) && $rows[$i]['parent_id'] !== '' && $rows[$i]['parent_id'] !== null) {
					$parent = (int) $rows[$i]['parent_id'];
				}

				if ($parent < 0 && empty($ready[$parent])) {
					continue;
				}

				$out[] = $rows[$i];
				$placed[$i] = true;
				$progress = true;
				$own = isset($rows[$i]['category_id']) ? (int) $rows[$i]['category_id'] : 0;

				if ($own < 0) {
					$ready[$own] = true;
				}
			}

			if (!$progress) {
				break;
			}
		}

		for ($i = 0; $i < $count; $i++) {
			if (!$placed[$i]) {
				$out[] = $rows[$i];
			}
		}

		return $out;
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
