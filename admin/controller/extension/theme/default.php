<?php

class ControllerExtensionThemeDefault extends Controller
{
	private $error = [];

	public function index()
	{
		$this->load->language('extension/theme/default');
		$this->load->model('setting/setting');

		$this->document->setTitle($this->language->get('heading_title'));

		$store_id = isset($this->request->get['store_id']) ? (int) $this->request->get['store_id'] : 0;
		$token = $this->session->data['user_token'];

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$setting = array_merge(
				$this->model_setting_setting->getSetting('theme_default', $store_id),
				$this->defaults(),
				$this->request->post
			);
			$setting['theme_default_directory'] = $this->defaults()['theme_default_directory'];

			$this->model_setting_setting->editSetting('theme_default', $setting, $store_id);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $token . '&type=theme', true));
		}

		$setting = [];

		if ($this->request->server['REQUEST_METHOD'] != 'POST') {
			$setting = $this->model_setting_setting->getSetting('theme_default', $store_id);
		}

		$data = array_merge($this->defaults(), $setting, $this->request->post);
		$data['theme_default_directory'] = $this->defaults()['theme_default_directory'];

		if ($this->request->server['REQUEST_METHOD'] != 'POST') {
			if (!isset($setting['theme_default_account_auth']) && $this->config->has('config_account_auth')) {
				$data['theme_default_account_auth'] = $this->config->get('config_account_auth');
			}

			if (!isset($setting['theme_default_sms_message']) && $this->config->has('config_sms_message')) {
				$data['theme_default_sms_message'] = $this->config->get('config_sms_message');
			}
		}
		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token=' . $token, true),
			],
			[
				'text' => $this->language->get('text_extension'),
				'href' => $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=theme', true),
			],
			[
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/theme/default', 'user_token=' . $token . '&store_id=' . $store_id, true),
			],
		];

		$data['action'] = $this->url->link('extension/theme/default', 'user_token=' . $token . '&store_id=' . $store_id, true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $token . '&type=theme', true);
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/theme/default', $data));
	}

	public function install()
	{
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('theme_default', $this->defaults());
	}

	private function defaults()
	{
		return [
			'theme_default_directory' => 'default',
			'theme_default_status' => 1,
			'theme_default_guest_wishlist' => 0,
			'theme_default_account_auth' => 'password',
			'theme_default_sms_message' => '{code}',
			'theme_default_product_limit' => 15,
			'theme_default_product_description_length' => 100,
			'theme_default_image_category_width' => 80,
			'theme_default_image_category_height' => 80,
			'theme_default_image_manufacturer_width' => 80,
			'theme_default_image_manufacturer_height' => 80,
			'theme_default_image_thumb_width' => 228,
			'theme_default_image_thumb_height' => 228,
			'theme_default_image_popup_width' => 500,
			'theme_default_image_popup_height' => 500,
			'theme_default_image_product_width' => 228,
			'theme_default_image_product_height' => 228,
			'theme_default_image_additional_width' => 74,
			'theme_default_image_additional_height' => 74,
			'theme_default_image_related_width' => 200,
			'theme_default_image_related_height' => 200,
			'theme_default_image_compare_width' => 90,
			'theme_default_image_compare_height' => 90,
			'theme_default_image_wishlist_width' => 47,
			'theme_default_image_wishlist_height' => 47,
			'theme_default_image_cart_width' => 47,
			'theme_default_image_cart_height' => 47,
			'theme_default_image_location_width' => 268,
			'theme_default_image_location_height' => 50,
		];
	}

	protected function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/theme/default')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
