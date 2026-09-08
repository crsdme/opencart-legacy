<?php

class ControllerExtensionAnalyticsGoogle extends Controller
{
	private $error = [];

	private $event_codes = [
		'view_item',
		'view_item_list',
		'view_search_results',
		'view_cart',
		'add_to_cart',
		'remove_from_cart',
		'begin_checkout',
		'add_shipping_info',
		'add_payment_info',
		'purchase',
	];

	public function index()
	{
		$this->load->language('extension/analytics/google');
		$this->load->model('setting/setting');
		$this->load->model('setting/event');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->registerEvents();

		$store_id = isset($this->request->get['store_id']) ? (int) $this->request->get['store_id'] : 0;

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
			$this->model_setting_setting->editSetting('analytics_google', $this->request->post, $store_id);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect(
				$this->url->link(
					'marketplace/extension',
					'user_token=' . $this->session->data['user_token'] . '&type=analytics',
					true,
				),
			);
		}

		$setting = $this->model_setting_setting->getSetting('analytics_google', $store_id);

		$data['error_warning'] = $this->error['warning'] ?? '';
		$data['user_token'] = $this->session->data['user_token'];

		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
			],
			[
				'text' => $this->language->get('text_extension'),
				'href' => $this->url->link(
					'marketplace/extension',
					'user_token=' . $this->session->data['user_token'] . '&type=analytics',
					true,
				),
			],
			[
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link(
					'extension/analytics/google',
					'user_token=' . $this->session->data['user_token'] . '&store_id=' . $store_id,
					true,
				),
			],
		];

		$data['action'] = $this->url->link(
			'extension/analytics/google',
			'user_token=' . $this->session->data['user_token'] . '&store_id=' . $store_id,
			true,
		);
		$data['cancel'] = $this->url->link(
			'marketplace/extension',
			'user_token=' . $this->session->data['user_token'] . '&type=analytics',
			true,
		);

		$data['analytics_google_status'] = $this->request->post['analytics_google_status']
			?? ($setting['analytics_google_status'] ?? 0);

		$saved_events = $this->request->post['analytics_google_events']
			?? ($setting['analytics_google_events'] ?? null);

		$data['events'] = [];

		foreach ($this->event_codes as $code) {
			$data['events'][] = [
				'code' => $code,
				'label' => $this->language->get('entry_event_' . $code),
				'help' => $this->language->get('help_event_' . $code),
				'checked' => $saved_events === null ? true : !empty($saved_events[$code]),
			];
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/analytics/google', $data));
	}

	public function install()
	{
		$this->load->model('setting/event');
		$this->registerEvents();
	}

	public function uninstall()
	{
		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode('analytics_google');
	}

	protected function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/analytics/google')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	private function registerEvents()
	{
		$this->model_setting_event->deleteEventByCode('analytics_google');

		$events = [
			['catalog/view/common/layout/after', 'extension/analytics/google/layoutAfter'],
			['catalog/view/common/cart_modal/after', 'extension/analytics/google/cartModalAfter'],
			['catalog/controller/common/cart/add/after', 'extension/analytics/google/cartAddAfter'],
			['catalog/controller/common/cart/remove/before', 'extension/analytics/google/cartRemoveBefore'],
			['catalog/controller/common/cart/remove/after', 'extension/analytics/google/cartRemoveAfter'],
		];

		foreach ($events as $event) {
			$this->model_setting_event->addEvent('analytics_google', $event[0], $event[1]);
		}
	}
}
