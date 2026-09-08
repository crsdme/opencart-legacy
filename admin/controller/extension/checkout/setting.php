<?php

class ControllerExtensionCheckoutSetting extends Controller
{
	private $error = [];

	public function index()
	{
		$this->load->language('extension/checkout/checkout');
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		$this->load->model('setting/extension');

		$this->document->setTitle($this->language->get('heading_title'));

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
			$this->model_setting_setting->editSetting('module_checkout', [
				'module_checkout_status' => 1,
				'module_checkout_min_total' => $this->postedMinTotal(),
				'module_checkout_fields' => $this->postedFields(),
				'module_checkout_shipping' => $this->postedShipping(),
				'module_checkout_payment' => $this->postedPayment(),
			]);

			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect(
				$this->url->link('extension/checkout/setting', 'user_token=' . $this->session->data['user_token'], true)
			);
		}

		$token = $this->session->data['user_token'];
		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_help'] = $this->language->get('text_help');
		$data['text_help_min_total'] = $this->language->get('text_help_min_total');
		$data['text_help_shipping'] = $this->language->get('text_help_shipping');
		$data['text_help_payment'] = $this->language->get('text_help_payment');
		$data['text_help_title'] = $this->language->get('text_help_title');
		$data['text_help_description'] = $this->language->get('text_help_description');
		$data['text_no_shipping'] = $this->language->get('text_no_shipping');
		$data['text_no_payment'] = $this->language->get('text_no_payment');
		$data['text_all_shipping'] = $this->language->get('text_all_shipping');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['tab_fields'] = $this->language->get('tab_fields');
		$data['tab_shipping'] = $this->language->get('tab_shipping');
		$data['tab_payment'] = $this->language->get('tab_payment');
		$data['column_field'] = $this->language->get('column_field');
		$data['column_show'] = $this->language->get('column_show');
		$data['column_required'] = $this->language->get('column_required');
		$data['column_method'] = $this->language->get('column_method');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_title'] = $this->language->get('column_title');
		$data['column_description'] = $this->language->get('column_description');
		$data['column_hide_address'] = $this->language->get('column_hide_address');
		$data['column_for_shipping'] = $this->language->get('column_for_shipping');
		$data['entry_min_total'] = $this->language->get('entry_min_total');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
		unset($this->session->data['success']);

		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token=' . $token, true),
			],
			[
				'text' => $this->language->get('text_extension'),
				'href' => $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=module', true),
			],
			[
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/checkout/setting', 'user_token=' . $token, true),
			],
		];
		$data['action'] = $this->url->link('extension/checkout/setting', 'user_token=' . $token, true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=module', true);
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$data['field_groups'] = $this->fieldGroups();
		$data['shipping_methods'] = $this->shippingRows();
		$data['payment_methods'] = $this->paymentRows();
		$data['min_total'] = $this->request->server['REQUEST_METHOD'] == 'POST'
			? $this->postedMinTotal()
			: (float) $this->config->get('module_checkout_min_total');
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/checkout/setting', $data));
	}

	private function fieldGroups()
	{
		$saved = $this->request->server['REQUEST_METHOD'] == 'POST'
			? $this->postedFields()
			: $this->savedFields();
		$groups = [];

		foreach ($this->catalog() as $code => $field) {
			$group = $field['group'];

			if (!isset($groups[$group])) {
				$groups[$group] = [
					'name' => $this->language->get('text_group_' . $group),
					'fields' => [],
				];
			}

			$current = isset($saved[$code]) ? $saved[$code] : $field;
			$groups[$group]['fields'][] = [
				'code' => $code,
				'label' => $this->language->get('field_' . $code),
				'show' => !empty($current['show']),
				'required' => !empty($current['required']),
			];
		}

		return array_values($groups);
	}

	private function shippingRows()
	{
		$posted = $this->request->server['REQUEST_METHOD'] == 'POST' ? $this->postedShipping() : [];
		$saved = $this->config->get('module_checkout_shipping');
		$saved = is_array($saved) ? $saved : [];
		$rows = [];

		foreach ($this->installed('shipping') as $code => $method) {
			$current = isset($posted[$code])
				? $posted[$code]
				: (isset($saved[$code]) ? $saved[$code] : []);
			$titles = isset($current['title']) && is_array($current['title']) ? $current['title'] : [];
			$description = isset($current['description']) && is_array($current['description']) ? $current['description'] : [];
			$hide = array_key_exists('hide_address', $current)
				? !empty($current['hide_address'])
				: in_array($code, ['pickup', 'novaposhta'], true);

			$rows[] = [
				'code' => $code,
				'name' => $method['name'],
				'status' => $method['status'],
				'title' => $titles,
				'description' => $description,
				'hide_address' => $hide,
			];
		}

		return $rows;
	}

	private function paymentRows()
	{
		$posted = $this->request->server['REQUEST_METHOD'] == 'POST' ? $this->postedPayment() : [];
		$saved = $this->config->get('module_checkout_payment');
		$saved = is_array($saved) ? $saved : [];
		$shipping = [];

		foreach ($this->installed('shipping') as $code => $method) {
			$shipping[] = [
				'code' => $code,
				'name' => $method['name'],
			];
		}

		$rows = [];

		foreach ($this->installed('payment') as $code => $method) {
			$current = isset($posted[$code])
				? $posted[$code]
				: (isset($saved[$code]) ? $saved[$code] : []);
			$titles = isset($current['title']) && is_array($current['title']) ? $current['title'] : [];
			$description = isset($current['description']) && is_array($current['description']) ? $current['description'] : [];
			$allowed = isset($current['shipping']) && is_array($current['shipping']) ? $current['shipping'] : [];
			$options = [];

			foreach ($shipping as $item) {
				$options[] = [
					'code' => $item['code'],
					'name' => $item['name'],
					'checked' => in_array($item['code'], $allowed, true),
				];
			}

			$rows[] = [
				'code' => $code,
				'name' => $method['name'],
				'status' => $method['status'],
				'title' => $titles,
				'description' => $description,
				'shipping' => $options,
			];
		}

		return $rows;
	}

	private function postedFields()
	{
		$posted = isset($this->request->post['module_checkout_fields'])
			? $this->request->post['module_checkout_fields']
			: [];
		$fields = [];

		foreach ($this->catalog() as $code => $field) {
			$show = !empty($posted[$code]['show']);
			$fields[$code] = [
				'group' => $field['group'],
				'show' => $show ? 1 : 0,
				'required' => $show && !empty($posted[$code]['required']) ? 1 : 0,
			];
		}

		return $fields;
	}

	private function postedShipping()
	{
		$posted = isset($this->request->post['module_checkout_shipping'])
			? $this->request->post['module_checkout_shipping']
			: [];
		$rows = [];

		foreach ($this->installed('shipping') as $code => $method) {
			$row = isset($posted[$code]) && is_array($posted[$code]) ? $posted[$code] : [];
			$rows[$code] = [
				'title' => $this->postedTexts($row, 'title'),
				'description' => $this->postedTexts($row, 'description'),
				'hide_address' => !empty($row['hide_address']) ? 1 : 0,
			];
		}

		return $rows;
	}

	private function postedPayment()
	{
		$posted = isset($this->request->post['module_checkout_payment'])
			? $this->request->post['module_checkout_payment']
			: [];
		$known = array_keys($this->installed('shipping'));
		$rows = [];

		foreach ($this->installed('payment') as $code => $method) {
			$row = isset($posted[$code]) && is_array($posted[$code]) ? $posted[$code] : [];
			$shipping = [];

			if (isset($row['shipping']) && is_array($row['shipping'])) {
				foreach ($row['shipping'] as $value) {
					$value = (string) $value;

					if (in_array($value, $known, true)) {
						$shipping[] = $value;
					}
				}
			}

			$rows[$code] = [
				'title' => $this->postedTexts($row, 'title'),
				'description' => $this->postedTexts($row, 'description'),
				'shipping' => array_values(array_unique($shipping)),
			];
		}

		return $rows;
	}

	private function postedMinTotal()
	{
		$value = isset($this->request->post['module_checkout_min_total'])
			? str_replace(',', '.', (string) $this->request->post['module_checkout_min_total'])
			: '0';

		return max(0, round((float) $value, 2));
	}

	private function postedTexts($row, $key)
	{
		$texts = [];
		$posted = isset($row[$key]) && is_array($row[$key]) ? $row[$key] : [];

		foreach ($this->model_localisation_language->getLanguages() as $language) {
			$id = (int) $language['language_id'];
			$texts[$id] = isset($posted[$id]) ? trim((string) $posted[$id]) : '';
		}

		return $texts;
	}

	private function postedTitles($row)
	{
		return $this->postedTexts($row, 'title');
	}

	private function installed($type)
	{
		$installed = $this->model_setting_extension->getInstalled($type);
		$methods = [];

		foreach ($installed as $code) {
			$file = DIR_CATALOG . 'model/extension/' . $type . '/' . $code . '.php';

			if (!is_file($file)) {
				continue;
			}

			$this->load->language('extension/' . $type . '/' . $code, 'extension');
			$extension = $this->language->get('extension');
			$name = $code;

			if (is_object($extension)) {
				$title = $extension->get('heading_title');

				if ($title && $title !== 'heading_title') {
					$name = $title;
				}
			}

			$methods[$code] = [
				'name' => $name,
				'status' => (int) $this->config->get($type . '_' . $code . '_status'),
			];
		}

		return $methods;
	}

	private function savedFields()
	{
		$saved = $this->config->get('module_checkout_fields');

		if (!is_array($saved)) {
			$saved = [];
			$comment = $this->config->get('theme_default_checkout_comment');

			if ($comment === 'hidden') {
				$saved['comment'] = ['show' => 0, 'required' => 0];
			} elseif ($comment === 'required') {
				$saved['comment'] = ['show' => 1, 'required' => 1];
			}
		}

		return array_replace_recursive($this->catalog(), $saved);
	}

	private function catalog()
	{
		return [
			'firstname' => ['group' => 'customer', 'show' => 1, 'required' => 1],
			'lastname' => ['group' => 'customer', 'show' => 0, 'required' => 0],
			'email' => ['group' => 'customer', 'show' => 1, 'required' => 0],
			'telephone' => ['group' => 'customer', 'show' => 1, 'required' => 1],
			'company' => ['group' => 'address', 'show' => 0, 'required' => 0],
			'city' => ['group' => 'address', 'show' => 1, 'required' => 1],
			'address_1' => ['group' => 'address', 'show' => 1, 'required' => 1],
			'address_2' => ['group' => 'address', 'show' => 0, 'required' => 0],
			'postcode' => ['group' => 'address', 'show' => 0, 'required' => 0],
			'comment' => ['group' => 'comment', 'show' => 1, 'required' => 0],
		];
	}

	protected function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/checkout') && !$this->user->hasPermission('modify', 'extension/module/checkout')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
