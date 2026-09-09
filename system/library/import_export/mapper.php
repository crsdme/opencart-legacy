<?php

namespace import_export;

class Mapper
{
	private $ctx;
	private $remote;

	public function __construct(Context $ctx)
	{
		$this->ctx = $ctx;
		$this->remote = new RemoteImage($ctx);
	}

	public function has(array $row, $key)
	{
		return array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '';
	}

	public function scalar(array $row, $key, $default = '')
	{
		return $this->has($row, $key) ? $row[$key] : $default;
	}

	public function localized(array $row, $key)
	{
		if (!array_key_exists($key, $row) || $row[$key] === null) {
			$from_columns = $this->localizedFromDotKeys($row, $key);

			return $from_columns ? $from_columns : null;
		}

		$value = $row[$key];

		if (is_array($value)) {
			return $this->codesToIds($value);
		}

		if (is_string($value) && $value !== '') {
			$decoded = json_decode($value, true);

			if (is_array($decoded)) {
				return $this->codesToIds($decoded);
			}

			$out = [];

			foreach ($this->ctx->languages() as $language) {
				$out[$language['language_id']] = $value;
			}

			return $out;
		}

		return $this->localizedFromDotKeys($row, $key);
	}

	public function listValues(array $row, $key)
	{
		if (!array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
			return null;
		}

		$value = $row[$key];

		if (is_array($value)) {
			return array_values(array_filter(array_map('trim', $value), 'strlen'));
		}

		$decoded = json_decode((string) $value, true);

		if (is_array($decoded)) {
			return array_values(array_filter(array_map('trim', $decoded), 'strlen'));
		}

		$parts = preg_split('/\s*\|\s*/', (string) $value);

		return array_values(array_filter($parts, 'strlen'));
	}

	public function jsonField(array $row, $key)
	{
		if (!array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
			return null;
		}

		$value = $row[$key];

		if (is_array($value)) {
			return $value;
		}

		$decoded = json_decode((string) $value, true);

		return is_array($decoded) ? $decoded : null;
	}

	public function padLanguageMap($value)
	{
		$map = is_array($value) ? $value : [];
		$out = [];

		foreach ($this->ctx->languages() as $language) {
			$short = $language['short'];
			$code = $language['code'];

			if (array_key_exists($short, $map)) {
				$out[$short] = $map[$short];
			} elseif (array_key_exists($code, $map)) {
				$out[$short] = $map[$code];
			} elseif (array_key_exists($language['language_id'], $map)) {
				$out[$short] = $map[$language['language_id']];
			} else {
				$out[$short] = '';
			}
		}

		return $out;
	}

	public function padLocalized(array $row, array $schema)
	{
		foreach ($schema as $field) {
			$key = $field['key'];

			if (!empty($field['localized'])) {
				$row[$key] = $this->padLanguageMap(isset($row[$key]) ? $row[$key] : []);
			} elseif (!empty($field['json']) && !empty($row[$key]) && is_array($row[$key])) {
				foreach ($row[$key] as $index => $item) {
					if (!is_array($item)) {
						continue;
					}

					foreach (['text', 'name'] as $nested) {
						if (isset($item[$nested]) && is_array($item[$nested])) {
							$row[$key][$index][$nested] = $this->padLanguageMap($item[$nested]);
						}
					}
				}
			}
		}

		return $row;
	}

	public function flatten(array $row, array $schema)
	{
		$out = [];

		foreach ($schema as $field) {
			$key = $field['key'];
			$value = isset($row[$key]) ? $row[$key] : '';

			if (!empty($field['localized'])) {
				foreach ($this->ctx->languages() as $language) {
					$code = $language['short'];
					$cell = '';

					if (is_array($value)) {
						if (isset($value[$code])) {
							$cell = $value[$code];
						} elseif (isset($value[$language['code']])) {
							$cell = $value[$language['code']];
						} elseif (isset($value[$language['language_id']])) {
							$cell = $value[$language['language_id']];
						}
					} elseif ($value !== '' && $value !== null) {
						$cell = $value;
					}

					$out[$key . '.' . $code] = is_scalar($cell) ? (string) $cell : '';
				}
			} elseif (!empty($field['list'])) {
				$out[$key] = is_array($value) ? implode('|', $value) : (string) $value;
			} elseif (!empty($field['json'])) {
				$out[$key] = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $value;
			} else {
				$out[$key] = is_bool($value) ? (int) $value : (is_scalar($value) || $value === null ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
			}
		}

		return $out;
	}

	public function unflatten(array $row, array $schema)
	{
		$out = [];

		foreach ($schema as $field) {
			$key = $field['key'];

			if (!empty($field['localized'])) {
				$localized = $this->localized($row, $key);

				if ($localized !== null) {
					$out[$key] = $this->idsToCodes($localized);
				}
			} elseif (!empty($field['list'])) {
				$list = $this->listValues($row, $key);

				if ($list !== null) {
					$out[$key] = $list;
				}
			} elseif (!empty($field['json'])) {
				$json = $this->jsonField($row, $key);

				if ($json !== null) {
					$out[$key] = $json;
				}
			} elseif ($this->has($row, $key)) {
				$out[$key] = $this->cast($row[$key], isset($field['type']) ? $field['type'] : 'string');
			}
		}

		return $out;
	}

	public function idsToCodes(array $by_id)
	{
		$mapped = [];

		foreach ($by_id as $language_id => $value) {
			$code = $this->ctx->languageCode($language_id);

			if ($code !== '') {
				$mapped[$code] = $value;
			}
		}

		return $this->padLanguageMap($mapped);
	}

	public function mergeDescription(array $existing, array $incoming, array $fields)
	{
		foreach ($this->ctx->languages() as $language) {
			$id = $language['language_id'];

			if (!isset($existing[$id])) {
				$existing[$id] = [];
			}

			foreach ($fields as $field) {
				if (!isset($existing[$id][$field])) {
					$existing[$id][$field] = '';
				}
			}
		}

		foreach ($fields as $field) {
			if (!array_key_exists($field, $incoming)) {
				continue;
			}

			$map = $this->localized([$field => $incoming[$field]], $field);

			if ($map === null) {
				continue;
			}

			foreach ($map as $language_id => $value) {
				if (!isset($existing[$language_id])) {
					$existing[$language_id] = [];
				}

				$existing[$language_id][$field] = $value;
			}
		}

		$fallback = $existing[$this->ctx->defaultLanguageId()] ?? reset($existing);

		foreach ($existing as $language_id => $row) {
			foreach ($fields as $field) {
				if (!isset($existing[$language_id][$field]) || $existing[$language_id][$field] === '') {
					$existing[$language_id][$field] = isset($fallback[$field]) ? $fallback[$field] : '';
				}
			}
		}

		return $existing;
	}

	public function seoMap($keyword)
	{
		$map = $this->localized(['keyword' => $keyword], 'keyword');

		if ($map === null) {
			return null;
		}

		$out = [];

		foreach ($this->ctx->storeIds() as $store_id) {
			foreach ($this->ctx->languages() as $language) {
				$id = $language['language_id'];
				$out[$store_id][$id] = isset($map[$id]) ? trim((string) $map[$id]) : '';
			}
		}

		return $out;
	}

	public function imagePath($path)
	{
		$path = trim((string) $path);

		if ($path === '') {
			return '';
		}

		if (preg_match('#^https?:#i', $path) || strpos($path, '//') === 0) {
			return $this->remote->fetch($path);
		}

		return ltrim(str_replace('\\', '/', $path), '/');
	}

	private function localizedFromDotKeys(array $row, $key)
	{
		$found = [];

		foreach ($this->ctx->languages() as $language) {
			foreach ([$language['short'], $language['code']] as $code) {
				$column = $key . '.' . $code;

				if (array_key_exists($column, $row) && $row[$column] !== null && $row[$column] !== '') {
					$found[$language['language_id']] = $row[$column];
					break;
				}
			}
		}

		return $found ? $found : null;
	}

	private function codesToIds(array $value)
	{
		$out = [];

		foreach ($value as $code => $text) {
			if (is_int($code) || ctype_digit((string) $code)) {
				$out[(int) $code] = $text;
				continue;
			}

			$id = $this->ctx->languageId($code);

			if ($id) {
				$out[$id] = $text;
			}
		}

		return $out;
	}

	private function cast($value, $type)
	{
		if ($type === 'int') {
			return (int) $value;
		}

		if ($type === 'float') {
			return (float) $value;
		}

		if ($type === 'bool') {
			if (is_bool($value)) {
				return $value ? 1 : 0;
			}

			$raw = strtolower(trim((string) $value));

			if (in_array($raw, ['0', 'false', 'no', 'off'], true)) {
				return 0;
			}

			return $raw === '' ? 0 : 1;
		}

		return is_scalar($value) ? (string) $value : '';
	}
}
