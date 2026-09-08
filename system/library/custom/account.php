<?php

namespace Custom;

class Account
{
	private $registry;
	private $config;
	private $load;
	private $customer;
	private $session;
	private $sms;

	public function __construct($registry)
	{
		$this->registry = $registry;
		$this->config = $registry->get('config');
		$this->load = $registry->get('load');
		$this->customer = $registry->get('customer');
		$this->session = $registry->get('session');
		$this->sms = $registry->get('sms');
	}

	public function isPhone()
	{
		return \Custom\Setting::theme($this->config, 'account_auth', 'password') === 'phone';
	}

	public function prefix()
	{
		return Phone::prefix($this->config);
	}

	public function addPhoneAssets()
	{
		$document = $this->registry->get('document');
		$document->addStyle('catalog/view/theme/default/stylesheet/intlTelInput.css');
		$document->addScript('catalog/view/theme/default/javascript/intlTelInput.min.js', 'footer');
		$document->addScript('catalog/view/theme/default/javascript/phone-login.js', 'footer');
	}

	public function addCatalogAssets()
	{
		if (!$this->isPhone()) {
			return;
		}

		$this->addPhoneAssets();
	}

	public function sendLoginCode($telephone)
	{
		$phone = Phone::normalize($telephone, $this->prefix());

		if (!Phone::valid($telephone, $this->prefix())) {
			return ['error' => 'telephone'];
		}

		$otp = new Otp($this->registry);
		$result = $otp->issue($phone);

		if (!empty($result['error'])) {
			return $result;
		}

		$template = str_replace(["\r\n", "\r"], "\n", (string) \Custom\Setting::theme($this->config, 'sms_message'));

		if ($template === '') {
			$template = '{code}';
		}

		$message = str_replace('{code}', $result['code'], $template);

		if (!$this->sms->send($phone, $message)) {
			$otp->clear();

			return ['error' => 'sms'];
		}

		$otp->recordSend($phone);

		return [
			'success' => true,
			'retry_after' => isset($result['retry_after']) ? (int) $result['retry_after'] : Otp::RESEND,
		];
	}

	public function loginByPhone($telephone, $code)
	{
		$phone = Phone::normalize($telephone, $this->prefix());

		if (!Phone::valid($telephone, $this->prefix())) {
			return ['error' => 'telephone'];
		}

		$otp = new Otp($this->registry);
		$error = $otp->verify($phone, $code);

		if ($error) {
			return ['error' => $error];
		}

		$this->load->model('account/phone');
		$this->load->model('account/customer');

		$customer_info = $this->registry->get('model_account_phone')->getCustomerByTelephone($phone);

		if ($customer_info && empty($customer_info['status'])) {
			return ['error' => 'approved'];
		}

		if (!$customer_info) {
			$pages = new Pages($this->config);

			if (!$pages->enabled('account_register')) {
				return ['error' => 'not_found'];
			}

			$customer_id = $this->registry->get('model_account_phone')->addCustomerByTelephone($phone);
			$customer_info = $this->registry->get('model_account_customer')->getCustomer($customer_id);

			if (!$customer_info || empty($customer_info['status'])) {
				return ['error' => 'approved'];
			}
		}

		if (!$this->customer->loginById($customer_info['customer_id'])) {
			return ['error' => 'login'];
		}

		$this->afterLogin();

		return [
			'success' => true,
			'customer_id' => (int) $customer_info['customer_id'],
		];
	}

	public function afterLogin()
	{
		unset($this->session->data['guest']);

		$this->load->model('account/address');

		if ($this->config->get('config_tax_customer') == 'payment') {
			$this->session->data['payment_address'] = $this->registry
				->get('model_account_address')
				->getAddress($this->customer->getAddressId());
		}

		if ($this->config->get('config_tax_customer') == 'shipping') {
			$this->session->data['shipping_address'] = $this->registry
				->get('model_account_address')
				->getAddress($this->customer->getAddressId());
		}

		if (isset($this->session->data['wishlist']) && is_array($this->session->data['wishlist'])) {
			$this->load->model('account/wishlist');

			foreach ($this->session->data['wishlist'] as $key => $product_id) {
				$this->registry->get('model_account_wishlist')->addWishlist($product_id);

				unset($this->session->data['wishlist'][$key]);
			}
		}
	}

	public function redirectAfterLogin($request)
	{
		$url = $this->registry->get('url');
		$logout = $url->link('account/logout', '', true);

		if (
			isset($request->post['redirect']) &&
			$request->post['redirect'] != $logout &&
			(strpos($request->post['redirect'], $this->config->get('config_url')) !== false ||
				strpos($request->post['redirect'], $this->config->get('config_ssl')) !== false)
		) {
			return str_replace('&amp;', '&', $request->post['redirect']);
		}

		return $url->link('account/account', '', true);
	}
}
