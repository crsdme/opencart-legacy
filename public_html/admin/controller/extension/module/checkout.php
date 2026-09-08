<?php

class ControllerExtensionModuleCheckout extends Controller
{
	public function index()
	{
		$this->response->redirect(
			$this->url->link('extension/checkout/setting', 'user_token=' . $this->session->data['user_token'], true)
		);
	}

	public function install()
	{
		$this->load->model('setting/setting');
		$this->load->model('user/user_group');

		$group_id = $this->user->getGroupId();
		$this->model_user_user_group->addPermission($group_id, 'access', 'extension/module/checkout');
		$this->model_user_user_group->addPermission($group_id, 'modify', 'extension/module/checkout');
		$this->model_user_user_group->addPermission($group_id, 'access', 'extension/checkout');
		$this->model_user_user_group->addPermission($group_id, 'modify', 'extension/checkout');

		$fields = $this->defaultFields();
		$comment = $this->config->get('theme_default_checkout_comment');

		if ($comment === 'hidden') {
			$fields['comment']['show'] = 0;
			$fields['comment']['required'] = 0;
		} elseif ($comment === 'required') {
			$fields['comment']['show'] = 1;
			$fields['comment']['required'] = 1;
		}

		$this->model_setting_setting->editSetting('module_checkout', [
			'module_checkout_status' => 1,
			'module_checkout_min_total' => max(0, (float) $this->config->get('module_checkout_min_total')),
			'module_checkout_fields' => $fields,
			'module_checkout_shipping' => is_array($this->config->get('module_checkout_shipping')) ? $this->config->get('module_checkout_shipping') : [],
			'module_checkout_payment' => is_array($this->config->get('module_checkout_payment')) ? $this->config->get('module_checkout_payment') : [],
		]);

		$theme = $this->model_setting_setting->getSetting('theme_default');
		unset($theme['theme_default_checkout_comment'], $theme['theme_default_checkout_fields']);

		if ($theme) {
			$this->model_setting_setting->editSetting('theme_default', $theme);
		}
	}

	public function uninstall()
	{
		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('module_checkout');
	}

	private function defaultFields()
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
}
