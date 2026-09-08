<?php

namespace Backup\Destination;

class Local implements \Backup\Destination
{
	private $store;

	public function __construct(\Backup\Store $store)
	{
		$this->store = $store;
	}

	public function upload($local_path, $filename)
	{
		$target = $this->store->storageDir() . '/' . $filename;

		if (str_replace('\\', '/', $local_path) !== str_replace('\\', '/', $target)) {
			if (!@copy($local_path, $target)) {
				throw new \RuntimeException('Cannot copy backup to local storage.');
			}
		}

		return $filename;
	}

	public function purge($keep, $prefix)
	{
		$files = glob($this->store->storageDir() . '/' . $prefix . '*.zip');

		if (!$files) {
			return;
		}

		rsort($files);
		$keep = max(0, (int) $keep);

		foreach (array_slice($files, $keep) as $file) {
			@unlink($file);
		}
	}

	public function delete($ref)
	{
		$path = $this->store->storageDir() . '/' . basename($ref);

		if (is_file($path)) {
			@unlink($path);
		}
	}

	public function test()
	{
		$dir = $this->store->storageDir();

		if (!is_writable($dir)) {
			throw new \RuntimeException('Backup folder is not writable: ' . $dir);
		}

		return $dir;
	}
}
