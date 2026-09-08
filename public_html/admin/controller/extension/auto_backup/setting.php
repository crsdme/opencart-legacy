<?php

class ControllerExtensionAutoBackupSetting extends Controller
{
	private $error = [];

	public function index()
	{
		$this->load->language('extension/auto_backup/auto_backup');
		$store = new \Backup\Store($this->registry);

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
			$store->saveAll($this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect(
				$this->url->link('extension/auto_backup/setting', 'user_token=' . $this->session->data['user_token'], true)
			);
		}

		$data = $this->boot('setting');
		$defaults = $store->defaults();
		$saved = $store->all();

		foreach ($defaults as $key => $default) {
			if (isset($this->request->post[$key])) {
				$data[$key] = $this->request->post[$key];
			} elseif (array_key_exists($key, $saved)) {
				$data[$key] = $saved[$key];
			} else {
				$data[$key] = $default;
			}
		}

		$data['module_auto_backup_ftp_password'] = $store->maskToken($data['module_auto_backup_ftp_password']);
		$data['module_auto_backup_google_client_secret'] = $store->maskToken($data['module_auto_backup_google_client_secret']);
		$data['google_connected'] = $store->getSecret('module_auto_backup_google_refresh_token') !== '';
		$data['oauth_redirect'] = $store->oauthRedirectUri();
		$data['cron_url'] = $store->cronUrl();
		$data['cron_command'] = '0 * * * * curl -fsS ' . escapeshellarg($data['cron_url']);
		$cli_path = defined('DIR_SYSTEM') ? rtrim(str_replace('\\', '/', dirname(DIR_SYSTEM)), '/') . '/cli/auto_backup.php' : 'cli/auto_backup.php';
		$data['cron_command_cli'] = '0 3 * * * php ' . $cli_path;
		$data['last_success'] = $data['module_auto_backup_last_success'] !== '' ? $data['module_auto_backup_last_success'] : $this->language->get('text_never');
		$data['action'] = $this->url->link('extension/auto_backup/setting', 'user_token=' . $data['user_token'], true);
		$data['connect'] = $this->url->link('extension/auto_backup/setting/connect', 'user_token=' . $data['user_token'], true);
		$data['disconnect'] = $this->url->link('extension/auto_backup/setting/disconnect', 'user_token=' . $data['user_token'], true);
		$data['test'] = $this->url->link('extension/auto_backup/setting/test', 'user_token=' . $data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $data['user_token'] . '&type=module', true);
		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : $data['error_warning'];

		$this->response->setOutput($this->load->view('extension/auto_backup/setting', $data));
	}

	public function connect()
	{
		$this->load->language('extension/auto_backup/auto_backup');

		if (!$this->user->hasPermission('modify', 'extension/auto_backup')) {
			$this->session->data['error'] = $this->language->get('error_permission');
			$this->response->redirect($this->url->link('extension/auto_backup/setting', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		$store = new \Backup\Store($this->registry);

		if ($store->get('module_auto_backup_google_client_id') === '' || $store->getSecret('module_auto_backup_google_client_secret') === '') {
			$this->session->data['error'] = $this->language->get('error_google');
			$this->response->redirect($this->url->link('extension/auto_backup/setting', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		$state = bin2hex(random_bytes(16));
		$store->saveKey('module_auto_backup_oauth_state', $state);
		$this->response->redirect($store->googleAuthUrl($state));
	}

	public function disconnect()
	{
		$this->load->language('extension/auto_backup/auto_backup');

		if (!$this->user->hasPermission('modify', 'extension/auto_backup')) {
			$this->session->data['error'] = $this->language->get('error_permission');
		} else {
			$store = new \Backup\Store($this->registry);
			$store->saveKey('module_auto_backup_google_refresh_token', '');
			$store->saveKey('module_auto_backup_oauth_state', '');
			$this->session->data['success'] = $this->language->get('text_google_disconnected');
		}

		$this->response->redirect($this->url->link('extension/auto_backup/setting', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function test()
	{
		$this->load->language('extension/auto_backup/auto_backup');
		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/auto_backup')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			try {
				$store = new \Backup\Store($this->registry);
				$message = $store->destination()->test();
				$json['success'] = sprintf($this->language->get('text_test_ok'), $message);
			} catch (Exception $e) {
				$json['error'] = $e->getMessage();
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	protected function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/auto_backup')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		$destination = isset($this->request->post['module_auto_backup_destination']) ? $this->request->post['module_auto_backup_destination'] : '';

		if (!in_array($destination, ['local', 'ftp', 'google_drive'], true)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	private function boot($active)
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
				'active' => $active === 'setting',
			],
			[
				'name' => $this->language->get('text_tab_history'),
				'href' => $this->url->link('extension/auto_backup/history', 'user_token=' . $token, true),
				'active' => $active === 'history',
			],
		];
		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token=' . $token, true),
			],
			[
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/auto_backup/setting', 'user_token=' . $token, true),
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

		$keys = [
			'text_edit', 'text_enabled', 'text_disabled', 'text_yes', 'text_no',
			'text_destination_local', 'text_destination_ftp', 'text_destination_google',
			'text_cron', 'text_cron_help', 'text_google_connected', 'text_google_disconnected',
			'text_google_redirect', 'text_last_success', 'text_interval_6', 'text_interval_12',
			'text_interval_24', 'text_interval_48', 'text_interval_168',
			'entry_status', 'entry_interval', 'entry_include_database', 'entry_include_images',
			'entry_include_downloads', 'entry_include_config', 'entry_exclude_tables',
			'entry_exclude_paths', 'entry_keep', 'entry_destination', 'entry_keep_local',
			'entry_email_status', 'entry_email', 'entry_ftp_host', 'entry_ftp_port',
			'entry_ftp_user', 'entry_ftp_password', 'entry_ftp_path', 'entry_ftp_ssl',
			'entry_google_client_id', 'entry_google_client_secret', 'entry_google_folder_id',
			'entry_delete_data', 'entry_cron_token',
			'help_status', 'help_interval', 'help_images', 'help_config', 'help_exclude_tables',
			'help_exclude_paths', 'help_keep', 'help_destination', 'help_keep_local',
			'help_email', 'help_google', 'help_folder', 'help_delete_data', 'help_cron_token',
			'button_save', 'button_cancel', 'button_connect', 'button_disconnect', 'button_test',
		];

		foreach ($keys as $key) {
			$data[$key] = $this->language->get($key);
		}

		return $data;
	}
}
