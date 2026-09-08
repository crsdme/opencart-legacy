<?php

namespace Custom;

class Docs
{
	public static function all()
	{
		$seen = [];
		$docs = [];

		$overview = self::overviewFile();
		$readme = self::readmeFile();

		if ($readme !== '') {
			$docs[] = self::item('readme', $readme, 'Home');
			$seen['readme'] = true;
		}

		if ($overview !== '') {
			$docs[] = self::item('overview', $overview, 'Overview');
			$seen['overview'] = true;
		}

		foreach (self::roots() as $root) {
			$files = glob($root . '/*.md');

			if (!$files) {
				continue;
			}

			sort($files);

			foreach ($files as $file) {
				$base = strtolower(basename($file));
				$id = self::id(basename($file, '.md'));

				if ($id === '' || isset($seen[$id]) || in_array($base, ['documentation.md', 'readme.md'], true)) {
					continue;
				}

				$seen[$id] = true;
				$docs[] = self::item($id, $file);
			}
		}

		return $docs;
	}

	public static function get($id)
	{
		$id = self::id($id);

		foreach (self::all() as $doc) {
			if ($doc['id'] === $id) {
				return $doc;
			}
		}

		return null;
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

	private static function readmeFile()
	{
		foreach (self::candidates([
			self::repoRoot() . '/README.md',
			'/var/www/README.md',
			self::webRoot() . '/README.md',
		]) as $file) {
			if (is_file($file)) {
				return $file;
			}
		}

		return '';
	}

	private static function overviewFile()
	{
		foreach (self::candidates([
			self::repoRoot() . '/docs/DOCUMENTATION.md',
			'/var/www/docs/DOCUMENTATION.md',
			self::webRoot() . '/docs/DOCUMENTATION.md',
		]) as $file) {
			if (is_file($file)) {
				return $file;
			}
		}

		return '';
	}

	private static function roots()
	{
		return self::candidates([
			self::repoRoot() . '/docs',
			'/var/www/docs',
			self::webRoot() . '/docs',
		]);
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
