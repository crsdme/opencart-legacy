<?php

class ControllerExtensionShippingNovaposhta extends Controller
{
	private $error = [];

	public function index()
	{
		$this->load->language('extension/shipping/novaposhta');
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		$this->load->model('localisation/geo_zone');
		$this->load->model('localisation/tax_class');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('extension/shipping/novaposhta');
		$this->model_extension_shipping_novaposhta->install();

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
			$this->model_setting_setting->editSetting('shipping_novaposhta', [
				'shipping_novaposhta_status' => !empty($this->request->post['shipping_novaposhta_status']) ? 1 : 0,
				'shipping_novaposhta_sort_order' => (int) ($this->request->post['shipping_novaposhta_sort_order'] ?? 0),
				'shipping_novaposhta' => $this->postedSettings(),
			]);

			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect(
				$this->url->link('extension/shipping/novaposhta', 'user_token=' . $this->session->data['user_token'], true)
			);
		}

		$token = $this->session->data['user_token'];
		$settings = $this->currentSettings();
		$methods = isset($settings['methods']) && is_array($settings['methods']) ? $settings['methods'] : [];

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');
		$data['text_none'] = $this->language->get('text_none');
		$data['text_online'] = $this->language->get('text_online');
		$data['tab_general'] = $this->language->get('tab_general');
		$data['tab_database'] = $this->language->get('tab_database');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_key_api'] = $this->language->get('entry_key_api');
		$data['entry_method_status'] = $this->language->get('entry_method_status');
		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_cost'] = $this->language->get('entry_cost');
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_tax_class'] = $this->language->get('entry_tax_class');
		$data['entry_regions'] = $this->language->get('entry_regions');
		$data['entry_cities'] = $this->language->get('entry_cities');
		$data['entry_departments'] = $this->language->get('entry_departments');
		$data['entry_settlements'] = $this->language->get('entry_settlements');
		$data['entry_streets'] = $this->language->get('entry_streets');
		$data['help_key_api'] = $this->language->get('help_key_api');
		$data['help_cost'] = $this->language->get('help_cost');
		$data['help_regions'] = $this->language->get('help_regions');
		$data['help_cities'] = $this->language->get('help_cities');
		$data['help_departments'] = $this->language->get('help_departments');
		$data['help_settlements'] = $this->language->get('help_settlements');
		$data['help_streets'] = $this->language->get('help_streets');
		$data['column_type'] = $this->language->get('column_type');
		$data['column_date'] = $this->language->get('column_date');
		$data['column_amount'] = $this->language->get('column_amount');
		$data['column_description'] = $this->language->get('column_description');
		$data['column_action'] = $this->language->get('column_action');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_update'] = $this->language->get('button_update');
		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['error_key_api'] = isset($this->error['key_api']) ? $this->error['key_api'] : '';
		$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
		unset($this->session->data['success']);

		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token=' . $token, true),
			],
			[
				'text' => $this->language->get('text_extension'),
				'href' => $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=shipping', true),
			],
			[
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/shipping/novaposhta', 'user_token=' . $token, true),
			],
		];
		$data['action'] = $this->url->link('extension/shipping/novaposhta', 'user_token=' . $token, true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=shipping', true);
		$data['sync'] = $this->url->link('extension/shipping/novaposhta/sync', 'user_token=' . $token, true);
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();
		$data['status'] = !empty($this->request->post['shipping_novaposhta_status'])
			|| ($this->request->server['REQUEST_METHOD'] != 'POST' && $this->config->get('shipping_novaposhta_status'));
		$data['sort_order'] = $this->request->server['REQUEST_METHOD'] == 'POST'
			? (int) ($this->request->post['shipping_novaposhta_sort_order'] ?? 0)
			: (int) $this->config->get('shipping_novaposhta_sort_order');
		$data['key_api'] = isset($settings['key_api']) ? $settings['key_api'] : '';
		$data['settlements'] = !empty($settings['settlements']);
		$data['streets'] = !empty($settings['streets']);
		$data['methods'] = [];

		foreach (['department', 'poshtomat', 'doors'] as $code) {
			$row = isset($methods[$code]) && is_array($methods[$code]) ? $methods[$code] : [];
			$data['methods'][] = [
				'code' => $code,
				'label' => $this->language->get('text_' . $code),
				'status' => !empty($row['status']),
				'name' => isset($row['name']) && is_array($row['name']) ? $row['name'] : [],
				'cost' => isset($row['cost']) ? $row['cost'] : '0',
				'geo_zone_id' => isset($row['geo_zone_id']) ? (int) $row['geo_zone_id'] : 0,
				'tax_class_id' => isset($row['tax_class_id']) ? (int) $row['tax_class_id'] : 0,
			];
		}

		$client = new \Custom\NovaPoshta($this->registry);
		$stats = $client->stats();
		$data['database'] = [];

		foreach (['regions', 'cities', 'departments'] as $type) {
			$data['database'][] = [
				'type' => $type,
				'label' => $this->language->get('entry_' . $type),
				'help' => $this->language->get('help_' . $type),
				'updated_at' => isset($stats[$type]['updated_at']) ? $stats[$type]['updated_at'] : '—',
				'amount' => isset($stats[$type]['amount']) ? $stats[$type]['amount'] : 0,
			];
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/shipping/novaposhta', $data));
	}

	public function install()
	{
		$this->load->model('extension/shipping/novaposhta');
		$this->load->model('user/user_group');
		$this->model_extension_shipping_novaposhta->install();
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/shipping/novaposhta');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/shipping/novaposhta');
	}

	public function uninstall()
	{
		$this->load->model('extension/shipping/novaposhta');
		$this->model_extension_shipping_novaposhta->uninstall();
	}

	public function sync()
	{
		$this->load->language('extension/shipping/novaposhta');
		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/shipping/novaposhta')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$type = isset($this->request->get['type']) ? (string) $this->request->get['type'] : '';
			$client = new \Custom\NovaPoshta($this->registry);

			if (!$client->key()) {
				$json['error'] = $this->language->get('error_key_api');
			} else {
				$amount = $client->sync($type);

				if ($client->error) {
					$json['error'] = implode(' ', $client->error);
				} elseif (!$amount) {
					$json['error'] = $this->language->get('error_update');
				} else {
					$stats = $client->stats();
					$json['success'] = $this->language->get('text_update_success');
					$json['amount'] = $amount;
					$json['updated_at'] = isset($stats[$type]['updated_at']) ? $stats[$type]['updated_at'] : '';
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function postedSettings()
	{
		$posted = isset($this->request->post['shipping_novaposhta']) && is_array($this->request->post['shipping_novaposhta'])
			? $this->request->post['shipping_novaposhta']
			: [];
		$methods = [];

		foreach (['department', 'poshtomat', 'doors'] as $code) {
			$row = isset($posted['methods'][$code]) && is_array($posted['methods'][$code]) ? $posted['methods'][$code] : [];
			$names = [];

			if (isset($row['name']) && is_array($row['name'])) {
				foreach ($row['name'] as $language_id => $name) {
					$names[(int) $language_id] = trim((string) $name);
				}
			}

			$methods[$code] = [
				'status' => !empty($row['status']) ? 1 : 0,
				'name' => $names,
				'cost' => max(0, (float) str_replace(',', '.', (string) ($row['cost'] ?? 0))),
				'geo_zone_id' => (int) ($row['geo_zone_id'] ?? 0),
				'tax_class_id' => (int) ($row['tax_class_id'] ?? 0),
			];
		}

		return [
			'key_api' => isset($posted['key_api']) ? trim((string) $posted['key_api']) : '',
			'settlements' => !empty($posted['settlements']) ? 1 : 0,
			'streets' => !empty($posted['streets']) ? 1 : 0,
			'methods' => $methods,
		];
	}

	private function currentSettings()
	{
		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			return $this->postedSettings();
		}

		$saved = $this->config->get('shipping_novaposhta');

		return is_array($saved) ? $saved : [];
	}

	private function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/shipping/novaposhta')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		$settings = $this->postedSettings();

		if ($settings['key_api'] === '') {
			$this->error['key_api'] = $this->language->get('error_key_api');
			$this->error['warning'] = $this->language->get('error_key_api');
		}

		return !$this->error;
	}
}
