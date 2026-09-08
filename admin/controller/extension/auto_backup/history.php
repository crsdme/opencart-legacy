<?php

class ControllerExtensionAutoBackupHistory extends Controller
{
	public function index()
	{
		$data = $this->boot();
		$history = new \Backup\History($this->db);
		$store = new \Backup\Store($this->registry);

		$page = isset($this->request->get['page']) ? (int) $this->request->get['page'] : 1;
		$limit = (int) $this->config->get('config_limit_admin');

		if ($limit < 1) {
			$limit = 20;
		}

		if ($page < 1) {
			$page = 1;
		}

		$total = $history->getTotal();
		$results = $history->getJobs(($page - 1) * $limit, $limit);
		$data['backups'] = [];

		foreach ($results as $row) {
			$local = $store->storageDir() . '/' . $row['filename'];
			$can_download = $row['filename'] !== '' && is_file($local);

			$data['backups'][] = [
				'backup_id' => $row['backup_id'],
				'date_start' => $row['date_start'],
				'trigger' => $this->language->get('text_trigger_' . $row['trigger']),
				'destination' => $row['destination'],
				'filename' => $row['filename'],
				'size' => $this->formatSize($row['filesize']),
				'status' => $this->language->get('text_status_' . $row['status']),
				'status_code' => $row['status'],
				'error' => $row['error'],
				'download' => $can_download ? $this->url->link('extension/auto_backup/history/download', 'user_token=' . $data['user_token'] . '&backup_id=' . $row['backup_id'], true) : '',
				'delete' => $this->url->link('extension/auto_backup/history/delete', 'user_token=' . $data['user_token'] . '&backup_id=' . $row['backup_id'], true),
			];
		}

		$pagination = new Pagination();
		$pagination->total = $total;
		$pagination->page = $page;
		$pagination->limit = $limit;
		$pagination->url = $this->url->link('extension/auto_backup/history', 'user_token=' . $data['user_token'] . '&page={page}', true);
		$data['pagination'] = $pagination->render();
		$data['results'] = sprintf(
			$this->language->get('text_pagination'),
			$total ? (($page - 1) * $limit) + 1 : 0,
			min(($page - 1) * $limit + $limit, $total),
			$total,
			ceil($total / $limit) ?: 1
		);
		$data['run'] = $this->url->link('extension/auto_backup/history/run', 'user_token=' . $data['user_token'], true);

		$this->response->setOutput($this->load->view('extension/auto_backup/history', $data));
	}

	public function run()
	{
		$this->load->language('extension/auto_backup/auto_backup');

		if (!$this->user->hasPermission('modify', 'extension/auto_backup')) {
			$this->session->data['error'] = $this->language->get('error_permission');
		} else {
			$job = new \Backup\Job($this->registry);
			$result = $job->run('manual', true);

			if ($result['ok']) {
				$this->session->data['success'] = $this->language->get('text_backup_started') . ' ' . $result['message'];
			} else {
				$this->session->data['error'] = $this->language->get('text_backup_failed') . ' ' . $result['message'];
			}
		}

		$this->response->redirect(
			$this->url->link('extension/auto_backup/history', 'user_token=' . $this->session->data['user_token'], true)
		);
	}

	public function download()
	{
		$this->load->language('extension/auto_backup/auto_backup');

		if (!$this->user->hasPermission('access', 'extension/auto_backup')) {
			$this->session->data['error'] = $this->language->get('error_permission');
			$this->response->redirect($this->url->link('extension/auto_backup/history', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		$history = new \Backup\History($this->db);
		$store = new \Backup\Store($this->registry);
		$row = $history->get(isset($this->request->get['backup_id']) ? (int) $this->request->get['backup_id'] : 0);
		$root = realpath($store->storageDir());
		$path = $row && $row['filename'] !== '' ? realpath($store->storageDir() . '/' . basename($row['filename'])) : false;

		if (!$path || !$root || strpos($path, $root) !== 0 || !is_file($path)) {
			$this->session->data['error'] = $this->language->get('error_file');
			$this->response->redirect($this->url->link('extension/auto_backup/history', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="' . basename($path) . '"');
		header('Content-Length: ' . filesize($path));
		readfile($path);
		exit;
	}

	public function delete()
	{
		$this->load->language('extension/auto_backup/auto_backup');

		if (!$this->user->hasPermission('modify', 'extension/auto_backup')) {
			$this->session->data['error'] = $this->language->get('error_permission');
			$this->response->redirect($this->url->link('extension/auto_backup/history', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		$history = new \Backup\History($this->db);
		$store = new \Backup\Store($this->registry);
		$row = $history->delete(isset($this->request->get['backup_id']) ? (int) $this->request->get['backup_id'] : 0);

		if ($row) {
			if ($row['filename'] !== '') {
				$local = new \Backup\Destination\Local($store);
				$local->delete($row['filename']);
			}

			if ($row['remote_id'] !== '' && $row['destination'] !== 'local') {
				try {
					if ($row['destination'] === 'google_drive') {
						$remote = new \Backup\Destination\GoogleDrive($store);
					} else {
						$remote = new \Backup\Destination\Ftp($store);
					}

					$remote->delete($row['remote_id']);
				} catch (Exception $e) {
					$this->log->write('Auto backup remote delete: ' . $e->getMessage());
				}
			}
		}

		$this->session->data['success'] = $this->language->get('text_deleted');
		$this->response->redirect(
			$this->url->link('extension/auto_backup/history', 'user_token=' . $this->session->data['user_token'], true)
		);
	}

	private function formatSize($bytes)
	{
		$bytes = (int) $bytes;

		if ($bytes >= 1048576) {
			return round($bytes / 1048576, 2) . ' MB';
		}

		if ($bytes >= 1024) {
			return round($bytes / 1024, 1) . ' KB';
		}

		return $bytes ? $bytes . ' B' : '—';
	}

	private function boot()
	{
		$this->load->language('extension/auto_backup/auto_backup');
		$this->document->setTitle($this->language->get('heading_title'));
		$token = $this->session->data['user_token'];

		$data = [];
		$data['user_token'] = $token;
		$data['heading_title'] = $this->language->get('heading_title');
		$data['tabs'] = [
			[
				'name' => $this->language->get('text_tab_settings'),
				'href' => $this->url->link('extension/auto_backup/setting', 'user_token=' . $token, true),
				'active' => false,
			],
			[
				'name' => $this->language->get('text_tab_history'),
				'href' => $this->url->link('extension/auto_backup/history', 'user_token=' . $token, true),
				'active' => true,
			],
		];
		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token=' . $token, true),
			],
			[
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/auto_backup/history', 'user_token=' . $token, true),
			],
		];
		$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
		unset($this->session->data['success']);
		$data['error_warning'] = isset($this->session->data['error']) ? $this->session->data['error'] : '';
		unset($this->session->data['error']);
		$data['can_modify'] = $this->user->hasPermission('modify', 'extension/auto_backup');
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		foreach ([
			'text_tab_history', 'text_no_results', 'text_confirm', 'column_id', 'column_date', 'column_trigger',
			'column_destination', 'column_file', 'column_size', 'column_status', 'column_error',
			'column_action', 'button_run', 'button_download', 'button_delete',
		] as $key) {
			$data[$key] = $this->language->get($key);
		}

		return $data;
	}
}
