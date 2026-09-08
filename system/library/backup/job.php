<?php

namespace Backup;

class Job
{
	const PREFIX = 'backup-';

	private $registry;
	private $store;

	public function __construct($registry)
	{
		$this->registry = $registry;
		$this->store = new Store($registry);
	}

	public function store()
	{
		return $this->store;
	}

	/**
	 * @return array{ok:bool, message:string, skipped?:bool}
	 */
	public function run($trigger = 'cron', $force = false)
	{
		if (!$this->store->enabled() && !$force) {
			return ['ok' => false, 'message' => 'Module is disabled.'];
		}

		if (!$force && !$this->due()) {
			return ['ok' => true, 'skipped' => true, 'message' => 'Skipped: interval has not elapsed.'];
		}

		@set_time_limit(0);
		@ignore_user_abort(true);

		$lock = $this->lock();

		if (!$lock) {
			return ['ok' => false, 'message' => 'Another backup is already running.'];
		}

		$model = new History($this->registry->get('db'));
		$backup_id = $model->add([
			'trigger' => $trigger,
			'status' => 'running',
			'destination' => (string) $this->store->get('module_auto_backup_destination', 'local'),
		]);

		$work = $this->store->storageDir() . '/tmp-' . $backup_id;
		$zip_path = $this->store->storageDir() . '/' . $this->filename();
		$sql_path = $work . '/database.sql';

		try {
			if (!mkdir($work, 0755, true) && !is_dir($work)) {
				throw new \RuntimeException('Cannot create temp folder.');
			}

			$include_db = (int) $this->store->get('module_auto_backup_include_database', 1);
			$include_images = (int) $this->store->get('module_auto_backup_include_images', 1);
			$include_downloads = (int) $this->store->get('module_auto_backup_include_downloads', 0);
			$include_config = (int) $this->store->get('module_auto_backup_include_config', 0);

			if (!$include_db && !$include_images && !$include_downloads && !$include_config) {
				throw new \RuntimeException('Nothing selected to back up.');
			}

			if ($include_db) {
				$dumper = new Dumper($this->registry->get('db'));
				$dumper->dump($sql_path, $this->store->lines('module_auto_backup_exclude_tables'));
			}

			$archiver = new Archiver();
			$archiver->zip($zip_path, [
				'sql' => $include_db ? $sql_path : '',
				'images' => $include_images,
				'downloads' => $include_downloads,
				'config' => $include_config,
				'exclude' => $this->store->lines('module_auto_backup_exclude_paths'),
			]);

			$destination = $this->store->destination();
			$filename = basename($zip_path);
			$remote = $destination->upload($zip_path, $filename);
			$keep = (int) $this->store->get('module_auto_backup_keep', 7);
			$destination->purge($keep, self::PREFIX);

			$code = (string) $this->store->get('module_auto_backup_destination', 'local');
			$keep_local = (int) $this->store->get('module_auto_backup_keep_local', 1);

			if ($code !== 'local') {
				if (!$keep_local && is_file($zip_path)) {
					@unlink($zip_path);
				} else {
					$local = new Destination\Local($this->store);
					$local->purge($keep, self::PREFIX);
				}
			}

			$size = is_file($zip_path) ? filesize($zip_path) : 0;

			$model->finish($backup_id, [
				'status' => 'success',
				'filename' => $filename,
				'filesize' => $size,
				'remote_id' => $remote,
				'error' => '',
			]);

			$this->store->saveKey('module_auto_backup_last_success', date('Y-m-d H:i:s'));
			$this->cleanup($work);
			$this->notify(true, $filename, $size, '');
			$this->unlock($lock);

			return ['ok' => true, 'message' => 'Backup created: ' . $filename];
		} catch (\Exception $e) {
			$model->finish($backup_id, [
				'status' => 'error',
				'filename' => is_file($zip_path) ? basename($zip_path) : '',
				'filesize' => is_file($zip_path) ? filesize($zip_path) : 0,
				'remote_id' => '',
				'error' => $e->getMessage(),
			]);

			$this->cleanup($work);
			$this->notify(false, '', 0, $e->getMessage());
			$this->unlock($lock);

			return ['ok' => false, 'message' => $e->getMessage()];
		}
	}

	public function due()
	{
		$last = (string) $this->store->get('module_auto_backup_last_success', '');
		$hours = (int) $this->store->get('module_auto_backup_interval', 24);

		if ($last === '' || $hours < 1) {
			return true;
		}

		return strtotime($last) <= time() - ($hours * 3600);
	}

	private function filename()
	{
		return self::PREFIX . date('Y-m-d-His') . '.zip';
	}

	private function lock()
	{
		$path = $this->store->storageDir() . '/backup.lock';
		$handle = fopen($path, 'c');

		if (!$handle) {
			return false;
		}

		if (!flock($handle, LOCK_EX | LOCK_NB)) {
			$age = time() - (int) @filemtime($path);

			if ($age < 10800) {
				fclose($handle);
				return false;
			}

			flock($handle, LOCK_UN);
			flock($handle, LOCK_EX);
		}

		ftruncate($handle, 0);
		fwrite($handle, (string) time());

		return $handle;
	}

	private function unlock($handle)
	{
		if (is_resource($handle)) {
			flock($handle, LOCK_UN);
			fclose($handle);
		}
	}

	private function cleanup($dir)
	{
		if (!is_dir($dir)) {
			return;
		}

		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($files as $file) {
			$file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
		}

		@rmdir($dir);
	}

	private function notify($ok, $filename, $size, $error)
	{
		if (!(int) $this->store->get('module_auto_backup_email_status', 0)) {
			return;
		}

		$to = trim((string) $this->store->get('module_auto_backup_email', ''));

		if ($to === '') {
			$to = (string) $this->registry->get('config')->get('config_email');
		}

		if ($to === '') {
			return;
		}

		$config = $this->registry->get('config');
		$shop = html_entity_decode((string) $config->get('config_name'), ENT_QUOTES, 'UTF-8');
		$subject = ($ok ? 'Backup OK' : 'Backup FAILED') . ' — ' . $shop;
		$body = $ok
			? "Backup finished.\nFile: " . $filename . "\nSize: " . $this->size($size) . "\nDestination: " . $this->store->get('module_auto_backup_destination') . "\n"
			: "Backup failed.\n" . $error . "\n";

		try {
			$mail = new \Mail($config->get('config_mail_engine'));
			$mail->parameter = $config->get('config_mail_parameter');
			$mail->smtp_hostname = $config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode((string) $config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $config->get('config_mail_smtp_timeout');
			$mail->setTo($to);
			$mail->setFrom($config->get('config_email'));
			$mail->setSender($shop);
			$mail->setSubject($subject);
			$mail->setText($body);
			$mail->send();
		} catch (\Exception $e) {
			$this->registry->get('log')->write('Auto backup mail: ' . $e->getMessage());
		}
	}

	private function size($bytes)
	{
		$bytes = (int) $bytes;

		if ($bytes >= 1048576) {
			return round($bytes / 1048576, 2) . ' MB';
		}

		if ($bytes >= 1024) {
			return round($bytes / 1024, 1) . ' KB';
		}

		return $bytes . ' B';
	}
}
