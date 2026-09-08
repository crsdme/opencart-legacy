<?php

class ControllerExtensionFaqSetting extends Controller
{
	private $error = [];

	public function index()
	{
		$this->load->language('extension/faq/faq');
		$this->load->model('setting/setting');
		$this->load->model('extension/faq/faq');
		$this->load->model('localisation/language');

		$this->document->setTitle($this->language->get('heading_title'));

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
			foreach (['product', 'category', 'manufacturer'] as $type) {
				$this->model_extension_faq_faq->saveItems(
					$type,
					0,
					isset($this->request->post['faq_global'][$type]) ? $this->request->post['faq_global'][$type] : []
				);
			}

			$settings = [
				'module_faq_status' => isset($this->request->post['module_faq_status']) ? (int) $this->request->post['module_faq_status'] : 0,
				'module_faq_product' => isset($this->request->post['module_faq_product']) ? (int) $this->request->post['module_faq_product'] : 0,
				'module_faq_category' => isset($this->request->post['module_faq_category']) ? (int) $this->request->post['module_faq_category'] : 0,
				'module_faq_manufacturer' => isset($this->request->post['module_faq_manufacturer']) ? (int) $this->request->post['module_faq_manufacturer'] : 0,
				'module_faq_first_page_only' => isset($this->request->post['module_faq_first_page_only']) ? (int) $this->request->post['module_faq_first_page_only'] : 0,
				'module_faq_delete_data_on_uninstall' => isset($this->request->post['module_faq_delete_data_on_uninstall']) ? (int) $this->request->post['module_faq_delete_data_on_uninstall'] : 0,
				'module_faq_title_product' => isset($this->request->post['module_faq_title_product']) ? $this->request->post['module_faq_title_product'] : [],
				'module_faq_title_category' => isset($this->request->post['module_faq_title_category']) ? $this->request->post['module_faq_title_category'] : [],
				'module_faq_title_manufacturer' => isset($this->request->post['module_faq_title_manufacturer']) ? $this->request->post['module_faq_title_manufacturer'] : [],
			];

			$this->model_setting_setting->editSetting('module_faq', $settings);
			$this->model_extension_faq_faq->deleteProductScope();
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect(
				$this->url->link('extension/faq/setting', 'user_token=' . $this->session->data['user_token'], true)
			);
		}

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_settings'] = $this->language->get('text_settings');
		$data['text_product'] = $this->language->get('text_product');
		$data['text_category'] = $this->language->get('text_category');
		$data['text_manufacturer'] = $this->language->get('text_manufacturer');
		$data['text_global_faq'] = $this->language->get('text_global_faq');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_enable_product'] = $this->language->get('entry_enable_product');
		$data['entry_enable_category'] = $this->language->get('entry_enable_category');
		$data['entry_enable_manufacturer'] = $this->language->get('entry_enable_manufacturer');
		$data['entry_first_page_only'] = $this->language->get('entry_first_page_only');
		$data['entry_delete_data'] = $this->language->get('entry_delete_data');
		$data['entry_title'] = $this->language->get('entry_title');
		$data['help_status'] = $this->language->get('help_status');
		$data['help_type'] = $this->language->get('help_type');
		$data['help_first_page_only'] = $this->language->get('help_first_page_only');
		$data['help_delete_data'] = $this->language->get('help_delete_data');
		$data['help_title'] = $this->language->get('help_title');
		$data['help_placeholders'] = $this->language->get('help_placeholders');
		$data['help_placeholder_copy'] = $this->language->get('help_placeholder_copy');
		$data['text_copied'] = $this->language->get('text_copied');
		$data['placeholders_product'] = $this->model_extension_faq_faq->getPlaceholders('product');
		$data['placeholders_category'] = $this->model_extension_faq_faq->getPlaceholders('category');
		$data['placeholders_manufacturer'] = $this->model_extension_faq_faq->getPlaceholders('manufacturer');
		$data['column_question'] = $this->language->get('column_question');
		$data['column_answer'] = $this->language->get('column_answer');
		$data['column_sort_order'] = $this->language->get('column_sort_order');
		$data['column_status'] = $this->language->get('column_status');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_add'] = $this->language->get('button_add');
		$data['button_remove'] = $this->language->get('button_remove');

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
		unset($this->session->data['success']);

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link(
				'marketplace/extension',
				'user_token=' . $this->session->data['user_token'] . '&type=module',
				true
			),
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/faq/setting', 'user_token=' . $this->session->data['user_token'], true),
		];

		$data['action'] = $this->url->link(
			'extension/faq/setting',
			'user_token=' . $this->session->data['user_token'],
			true
		);
		$data['cancel'] = $this->url->link(
			'marketplace/extension',
			'user_token=' . $this->session->data['user_token'] . '&type=module',
			true
		);

		$keys = [
			'module_faq_status',
			'module_faq_product',
			'module_faq_category',
			'module_faq_manufacturer',
			'module_faq_first_page_only',
			'module_faq_delete_data_on_uninstall',
			'module_faq_title_product',
			'module_faq_title_category',
			'module_faq_title_manufacturer',
		];

		foreach ($keys as $key) {
			if (isset($this->request->post[$key])) {
				$data[$key] = $this->request->post[$key];
			} else {
				$value = $this->config->get($key);
				$data[$key] = $value !== null && $value !== '' ? $value : (strpos($key, 'title_') !== false ? [] : 0);
			}
		}

		if (!is_array($data['module_faq_title_product'])) {
			$data['module_faq_title_product'] = [];
		}

		if (!is_array($data['module_faq_title_category'])) {
			$data['module_faq_title_category'] = [];
		}

		if (!is_array($data['module_faq_title_manufacturer'])) {
			$data['module_faq_title_manufacturer'] = [];
		}

		$data['languages'] = array_values($this->model_localisation_language->getLanguages());
		$data['faq_global'] = [
			'product' => $this->model_extension_faq_faq->getItems('product', 0),
			'category' => $this->model_extension_faq_faq->getItems('category', 0),
			'manufacturer' => $this->model_extension_faq_faq->getItems('manufacturer', 0),
		];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/faq/setting', $data));
	}

	protected function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/faq') && !$this->user->hasPermission('modify', 'extension/module/faq')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
