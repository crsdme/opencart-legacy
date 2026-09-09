<?php

class ControllerExtensionImportExportDashboard extends Controller
{
	private $error = [];

	public function index()
	{
		$this->load->language('extension/import_export/import_export');
		$engine = new \import_export\Engine($this->registry);

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
			$this->load->model('setting/setting');
			$settings = $engine->defaults();
			$settings['module_import_export_status'] = isset($this->request->post['module_import_export_status']) ? (int) $this->request->post['module_import_export_status'] : 0;
			$settings['module_import_export_product_key'] = isset($this->request->post['module_import_export_product_key']) && $this->request->post['module_import_export_product_key'] === 'model' ? 'model' : 'sku';
			$mode = isset($this->request->post['module_import_export_on_missing_ref']) ? $this->request->post['module_import_export_on_missing_ref'] : 'create';
			$settings['module_import_export_on_missing_ref'] = in_array($mode, ['create', 'error', 'skip'], true) ? $mode : 'create';
			$settings['module_import_export_download_images'] = isset($this->request->post['module_import_export_download_images']) ? (int) $this->request->post['module_import_export_download_images'] : 0;
			$settings['module_import_export_delete_data_on_uninstall'] = isset($this->request->post['module_import_export_delete_data_on_uninstall']) ? (int) $this->request->post['module_import_export_delete_data_on_uninstall'] : 0;
			$this->model_setting_setting->editSetting('module_import_export', $settings);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/import_export/dashboard', 'user_token=' . $this->session->data['user_token'], true));
		}

		$data = $this->boot('dashboard');
		$data['module_import_export_status'] = $this->config->get('module_import_export_status');
		$data['module_import_export_product_key'] = $this->config->get('module_import_export_product_key') ? $this->config->get('module_import_export_product_key') : 'sku';
		$data['module_import_export_on_missing_ref'] = $this->config->get('module_import_export_on_missing_ref') ? $this->config->get('module_import_export_on_missing_ref') : 'create';
		$data['module_import_export_download_images'] = $this->config->get('module_import_export_download_images') === null || $this->config->get('module_import_export_download_images') === '' ? 1 : (int) $this->config->get('module_import_export_download_images');
		$data['module_import_export_delete_data_on_uninstall'] = (int) $this->config->get('module_import_export_delete_data_on_uninstall');
		$data['action'] = $this->url->link('extension/import_export/dashboard', 'user_token=' . $data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $data['user_token'] . '&type=module', true);

		$job = new \import_export\Job($this->db);
		$data['jobs'] = [];

		try {
			foreach ($job->recent(8) as $row) {
				$data['jobs'][] = [
					'date_added' => $row['date_added'],
					'action' => $this->language->get('text_action_' . $row['action']),
					'entity' => $row['entity'],
					'status' => $this->language->get('text_status_' . $row['status']),
					'created' => $row['created'],
					'updated' => $row['updated'],
					'errors' => $row['errors'],
				];
			}
		} catch (\Exception $e) {
			$data['jobs'] = [];
		}

		foreach ([
			'text_edit', 'text_enabled', 'text_disabled', 'text_yes', 'text_no',
			'text_recent', 'text_no_results', 'entry_status', 'entry_product_key',
			'entry_on_missing', 'entry_download_images', 'entry_delete_data', 'help_status', 'help_product_key',
			'help_on_missing', 'help_download_images', 'help_delete_data', 'text_key_sku', 'text_key_model',
			'text_missing_create', 'text_missing_error', 'text_missing_skip',
			'button_save', 'button_cancel', 'column_date', 'column_action',
			'column_entity', 'column_status', 'column_created', 'column_updated', 'column_errors',
		] as $key) {
			$data[$key] = $this->language->get($key);
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		}

		$this->response->setOutput($this->load->view('extension/import_export/dashboard', $data));
	}

	private function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/import_export')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	private function boot($active)
	{
		$this->load->language('extension/import_export/import_export');
		$this->document->setTitle($this->language->get('heading_title'));
		$engine = new \import_export\Engine($this->registry);
		$token = $this->session->data['user_token'];
		$tabs = $engine->tabs($this->url, $token, $active);

		foreach ($tabs as &$tab) {
			$tab['name'] = $this->language->get($tab['text']);
		}

		$data = [];
		$data['user_token'] = $token;
		$data['tabs'] = $tabs;
		$data['heading_title'] = $this->language->get('heading_title');
		$data['breadcrumbs'] = [
			['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $token, true)],
			['text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/import_export/dashboard', 'user_token=' . $token, true)],
		];
		$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
		unset($this->session->data['success']);
		$data['error_warning'] = isset($this->session->data['error']) ? $this->session->data['error'] : '';
		unset($this->session->data['error']);
		$data['can_modify'] = $this->user->hasPermission('modify', 'extension/import_export');
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		return $data;
	}
}
