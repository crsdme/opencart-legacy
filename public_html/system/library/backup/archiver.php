<?php

namespace Backup;

class Archiver
{
	private $exclude = [];

	public function zip($zip_path, array $parts)
	{
		if (!class_exists('ZipArchive')) {
			throw new \RuntimeException('PHP zip extension is required.');
		}

		$zip = new \ZipArchive();

		if ($zip->open($zip_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
			throw new \RuntimeException('Cannot create archive.');
		}

		$this->exclude = isset($parts['exclude']) ? $parts['exclude'] : [];

		if (!empty($parts['sql']) && is_file($parts['sql'])) {
			$zip->addFile($parts['sql'], 'database.sql');
		}

		if (!empty($parts['images']) && is_dir(DIR_IMAGE . 'catalog')) {
			$this->addDir($zip, DIR_IMAGE . 'catalog', 'image/catalog');
		}

		if (!empty($parts['downloads']) && is_dir(DIR_DOWNLOAD)) {
			$this->addDir($zip, DIR_DOWNLOAD, 'storage/download');
		}

		if (!empty($parts['config'])) {
			$root = rtrim(str_replace('\\', '/', dirname(DIR_SYSTEM)), '/');

			if (is_file($root . '/config.php')) {
				$zip->addFile($root . '/config.php', 'config.php');
			}
		}

		$zip->close();

		if (!is_file($zip_path) || filesize($zip_path) < 1) {
			throw new \RuntimeException('Archive is empty.');
		}
	}

	private function addDir($zip, $dir, $local)
	{
		$dir = rtrim(str_replace('\\', '/', $dir), '/');
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
		);

		foreach ($iterator as $file) {
			if (!$file->isFile()) {
				continue;
			}

			$full = str_replace('\\', '/', $file->getPathname());
			$relative = $local . '/' . ltrim(substr($full, strlen($dir)), '/');

			if ($this->isExcluded($relative) || $this->isExcluded($full)) {
				continue;
			}

			$zip->addFile($full, $relative);
		}
	}

	private function isExcluded($path)
	{
		$path = str_replace('\\', '/', $path);

		if (strpos($path, '/image/cache/') !== false) {
			return true;
		}

		if (strpos($path, '/storage/backup/') !== false || strpos($path, '/storage/cache/') !== false) {
			return true;
		}

		if (strpos($path, '/storage/session/') !== false) {
			return true;
		}

		foreach ($this->exclude as $rule) {
			$rule = str_replace('\\', '/', trim($rule));

			if ($rule === '') {
				continue;
			}

			if (strpos($path, $rule) !== false) {
				return true;
			}
		}

		return false;
	}
}
