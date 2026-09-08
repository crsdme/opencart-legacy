<?php

namespace Backup\Destination;

class Ftp implements \Backup\Destination
{
	private $store;

	public function __construct(\Backup\Store $store)
	{
		$this->store = $store;
	}

	public function upload($local_path, $filename)
	{
		$conn = $this->connect();
		$path = $this->remotePath($filename);

		$ok = ftp_put($conn, $path, $local_path, FTP_BINARY);
		ftp_close($conn);

		if (!$ok) {
			throw new \RuntimeException('FTP upload failed.');
		}

		return $path;
	}

	public function purge($keep, $prefix)
	{
		$conn = $this->connect();
		$dir = $this->remoteDir();
		$list = ftp_nlist($conn, $dir);

		if (!is_array($list)) {
			ftp_close($conn);
			return;
		}

		$files = [];

		foreach ($list as $item) {
			$name = basename($item);

			if (strpos($name, $prefix) === 0 && substr($name, -4) === '.zip') {
				$files[] = $dir . '/' . $name;
			}
		}

		rsort($files);
		$keep = max(0, (int) $keep);

		foreach (array_slice($files, $keep) as $file) {
			@ftp_delete($conn, $file);
		}

		ftp_close($conn);
	}

	public function delete($ref)
	{
		$conn = $this->connect();
		@ftp_delete($conn, $ref);
		ftp_close($conn);
	}

	public function test()
	{
		$conn = $this->connect();
		ftp_close($conn);

		return $this->store->get('module_auto_backup_ftp_host') . $this->remoteDir();
	}

	private function connect()
	{
		$host = (string) $this->store->get('module_auto_backup_ftp_host', '');
		$port = (int) $this->store->get('module_auto_backup_ftp_port', 21);
		$user = (string) $this->store->get('module_auto_backup_ftp_user', '');
		$password = $this->store->getSecret('module_auto_backup_ftp_password');
		$ssl = (int) $this->store->get('module_auto_backup_ftp_ssl', 0);

		if ($host === '' || $user === '') {
			throw new \RuntimeException('FTP host and user are required.');
		}

		if ($port < 1) {
			$port = 21;
		}

		$conn = $ssl ? @ftp_ssl_connect($host, $port, 20) : @ftp_connect($host, $port, 20);

		if (!$conn) {
			throw new \RuntimeException('Cannot connect to FTP server.');
		}

		if (!@ftp_login($conn, $user, $password)) {
			ftp_close($conn);
			throw new \RuntimeException('FTP login failed.');
		}

		ftp_pasv($conn, true);
		$this->ensureDir($conn, $this->remoteDir());

		return $conn;
	}

	private function ensureDir($conn, $dir)
	{
		$parts = explode('/', trim($dir, '/'));
		$current = '';

		foreach ($parts as $part) {
			if ($part === '') {
				continue;
			}

			$current .= '/' . $part;

			if (@ftp_chdir($conn, $current)) {
				continue;
			}

			if (!@ftp_mkdir($conn, $current)) {
				throw new \RuntimeException('Cannot create FTP folder: ' . $current);
			}
		}

		@ftp_chdir($conn, '/');
	}

	private function remoteDir()
	{
		$path = trim(str_replace('\\', '/', (string) $this->store->get('module_auto_backup_ftp_path', '/backups')));

		if ($path === '') {
			$path = '/backups';
		}

		return rtrim($path, '/');
	}

	private function remotePath($filename)
	{
		return $this->remoteDir() . '/' . $filename;
	}
}
