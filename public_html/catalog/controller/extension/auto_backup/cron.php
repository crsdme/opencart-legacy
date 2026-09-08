<?php

class ControllerExtensionAutoBackupCron extends Controller
{
	public function index()
	{
		$store = new \Backup\Store($this->registry);
		$token = (string) $store->get('module_auto_backup_cron_token', '');
		$given = isset($this->request->get['cron_token']) ? trim((string) $this->request->get['cron_token']) : '';

		$this->response->addHeader('Content-Type: text/plain; charset=utf-8');

		if ($token === '' || !hash_equals($token, $given)) {
			$this->response->setOutput('ERROR: invalid token');
			return;
		}

		$job = new \Backup\Job($this->registry);
		$result = $job->run('cron', false);
		$prefix = !empty($result['ok']) ? 'OK' : 'ERROR';

		$this->response->setOutput($prefix . ': ' . $result['message']);
	}
}
