<?php

class ControllerExtensionAutoBackupOauth extends Controller
{
	public function index()
	{
		$store = new \Backup\Store($this->registry);
		$expected = (string) $store->get('module_auto_backup_oauth_state', '');
		$state = isset($this->request->get['state']) ? (string) $this->request->get['state'] : '';
		$code = isset($this->request->get['code']) ? (string) $this->request->get['code'] : '';
		$error = isset($this->request->get['error']) ? (string) $this->request->get['error'] : '';
		$message = '';
		$ok = false;

		if ($error !== '') {
			$message = 'Google OAuth: ' . $error;
		} elseif ($expected === '' || $state === '' || !hash_equals($expected, $state)) {
			$message = 'Invalid OAuth state. Try Connect again from admin.';
		} elseif ($code === '') {
			$message = 'Google did not return an authorization code.';
		} else {
			try {
				$drive = new \Backup\Destination\GoogleDrive($store);
				$drive->exchangeCode($code);
				$store->saveKey('module_auto_backup_oauth_state', '');
				$ok = true;
				$message = 'Google Drive connected. You can close this tab and return to admin.';
			} catch (Exception $e) {
				$message = $e->getMessage();
			}
		}

		$this->response->addHeader('Content-Type: text/html; charset=utf-8');
		$this->response->setOutput(
			'<!DOCTYPE html><html><head><meta charset="utf-8"><title>Auto Backup</title></head><body>'
			. '<p>' . ($ok ? '' : '') . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
			. '</body></html>'
		);
	}
}
