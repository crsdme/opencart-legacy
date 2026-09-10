<?php

namespace Custom;

class Docs
{
	public static function all($lang = '')
	{
		$lang = self::normalizeLanguage($lang);
		$catalog = self::catalog();
		$root = self::docsRoot();
		$docs = [];

		if ($root === '' || empty($catalog['docs'])) {
			return $docs;
		}

		foreach ($catalog['docs'] as $entry) {
			$id = self::id(isset($entry['id']) ? $entry['id'] : '');
			$file = isset($entry['file']) ? (string) $entry['file'] : '';

			if ($id === '' || $file === '') {
				continue;
			}

			$path = self::languageFile($root, $lang, $file);
			$used_lang = $lang;

			if ($path === '' && $lang !== 'en') {
				$path = self::languageFile($root, 'en', $file);
				$used_lang = 'en';
			}

			if ($path === '') {
				continue;
			}

			$item = self::item($id, $path);
			$item['group'] = isset($entry['group']) ? (string) $entry['group'] : 'general';
			$item['lang'] = $used_lang;

			if (isset($entry['title'])) {
				if (is_array($entry['title'])) {
					if (isset($entry['title'][$used_lang]) && $entry['title'][$used_lang] !== '') {
						$item['title'] = (string) $entry['title'][$used_lang];
					} elseif (isset($entry['title']['en'])) {
						$item['title'] = (string) $entry['title']['en'];
					}
				} elseif (is_string($entry['title']) && $entry['title'] !== '') {
					$item['title'] = $entry['title'];
				}
			}

			$docs[] = $item;
		}

		return $docs;
	}

	public static function get($id, $lang = '')
	{
		$id = self::id($id);

		if ($id === 'documentation') {
			$id = 'overview';
		}

		foreach (self::all($lang) as $doc) {
			if ($doc['id'] === $id) {
				return $doc;
			}
		}

		return null;
	}

	public static function catalog()
	{
		$root = self::docsRoot();

		if ($root !== '' && is_file($root . '/catalog.php')) {
			$data = require $root . '/catalog.php';

			if (is_array($data)) {
				return $data;
			}
		}

		return [
			'default_language' => 'en',
			'languages' => [
				'en' => 'EN',
				'ru' => 'RU',
			],
			'groups' => [
				'general' => [
					'en' => 'Guides',
					'ru' => 'Общее',
				],
				'technical' => [
					'en' => 'Technical',
					'ru' => 'Техническое',
				],
			],
			'docs' => [],
		];
	}

	public static function languages()
	{
		$catalog = self::catalog();

		return !empty($catalog['languages']) && is_array($catalog['languages'])
			? $catalog['languages']
			: ['en' => 'EN'];
	}

	public static function groups($lang = '')
	{
		$lang = self::normalizeLanguage($lang);
		$catalog = self::catalog();
		$groups = [];

		if (empty($catalog['groups']) || !is_array($catalog['groups'])) {
			return $groups;
		}

		foreach ($catalog['groups'] as $id => $labels) {
			if (is_string($labels)) {
				$groups[$id] = $labels;
				continue;
			}

			if (!is_array($labels)) {
				continue;
			}

			if (isset($labels[$lang]) && $labels[$lang] !== '') {
				$groups[$id] = (string) $labels[$lang];
			} elseif (isset($labels['en'])) {
				$groups[$id] = (string) $labels['en'];
			} else {
				$groups[$id] = self::label($id);
			}
		}

		return $groups;
	}

	public static function normalizeLanguage($lang)
	{
		$lang = strtolower(trim((string) $lang));
		$languages = self::languages();

		if ($lang !== '' && isset($languages[$lang])) {
			return $lang;
		}

		$catalog = self::catalog();
		$default = isset($catalog['default_language']) ? strtolower((string) $catalog['default_language']) : 'en';

		return isset($languages[$default]) ? $default : 'en';
	}

	public static function mediaSrc($src)
	{
		$src = trim((string) $src);

		if ($src === '' || preg_match('~^(https?:|mailto:|data:|#|/)~i', $src)) {
			return $src;
		}

		$src = str_replace('\\', '/', $src);
		$src = preg_replace('~^\./~', '', $src);

		if ($src === '' || strpos($src, '..') !== false) {
			return '';
		}

		return '/docs-media/' . ltrim($src, '/');
	}

	public static function mediaFile($path)
	{
		$path = str_replace('\\', '/', (string) $path);
		$path = ltrim($path, '/');

		if ($path === '' || strpos($path, '..') !== false) {
			return '';
		}

		if (!preg_match('/\.(png|jpe?g|gif|webp|svg|avif)$/i', $path)) {
			return '';
		}

		$root = self::docsRoot();

		if ($root === '') {
			return '';
		}

		$rootReal = realpath($root);

		if ($rootReal === false) {
			return '';
		}

		$full = $root . '/' . $path;
		$real = realpath($full);

		if ($real === false || strpos(str_replace('\\', '/', $real), str_replace('\\', '/', $rootReal)) !== 0) {
			return '';
		}

		return is_file($real) ? $real : '';
	}

	public static function docsRoot()
	{
		$roots = self::candidates([
			self::repoRoot() . '/docs',
			'/var/www/docs',
			self::webRoot() . '/docs',
		]);

		return $roots ? $roots[0] : '';
	}

	private static function languageFile($root, $lang, $file)
	{
		$file = str_replace('\\', '/', $file);
		$file = ltrim($file, '/');

		if ($file === '' || strpos($file, '..') !== false) {
			return '';
		}

		$path = $root . '/' . $lang . '/' . $file;

		return is_file($path) ? $path : '';
	}

	private static function item($id, $file, $fallback = '')
	{
		$markdown = (string) file_get_contents($file);
		$title = $fallback !== '' ? $fallback : (new Markdown())->title($markdown, self::label($id));
		$label = self::label($id);
		$length = function_exists('mb_strlen') ? mb_strlen($title, 'UTF-8') : strlen($title);

		if ($length > 48) {
			$title = $label;
		}

		return [
			'id' => $id,
			'title' => $title,
			'file' => $file,
			'relative' => self::relative($file),
			'markdown' => $markdown,
		];
	}

	private static function candidates(array $paths)
	{
		$found = [];

		foreach ($paths as $path) {
			$path = str_replace('\\', '/', $path);

			if (($path !== '' && is_file($path)) || is_dir($path)) {
				$found[] = $path;
			}
		}

		return array_values(array_unique($found));
	}

	private static function webRoot()
	{
		return rtrim(str_replace('\\', '/', dirname(DIR_APPLICATION)), '/');
	}

	private static function repoRoot()
	{
		return dirname(self::webRoot());
	}

	private static function relative($file)
	{
		$file = str_replace('\\', '/', $file);
		$web = self::webRoot();
		$repo = self::repoRoot();

		if (strpos($file, $repo . '/') === 0) {
			return ltrim(substr($file, strlen($repo)), '/');
		}

		if (strpos($file, $web . '/') === 0) {
			return ltrim(substr($file, strlen($web)), '/');
		}

		return basename($file);
	}

	private static function id($value)
	{
		$value = strtolower(trim((string) $value));
		$value = preg_replace('/[^a-z0-9_-]+/', '-', $value);
		$value = trim($value, '-');

		return $value;
	}

	private static function label($id)
	{
		return ucwords(str_replace(['-', '_'], ' ', $id));
	}
}
