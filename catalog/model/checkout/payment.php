<?php

class ModelCheckoutPayment extends Model
{
	public function getViewData()
	{
		$methods = $this->getMethods();
		$code = isset($this->session->data['payment_method']['code'])
			? $this->session->data['payment_method']['code']
			: '';
		$error = '';

		if (!$methods) {
			$error = sprintf($this->language->get('error_no_payment'), $this->url->link('information/contact'));
		}

		return [
			'payment_methods' => $methods,
			'payment_code' => $code,
			'payment_error' => $error,
		];
	}

	public function getMethods()
	{
		$this->load->model('checkout/address');
		$this->load->model('checkout/cart');
		$this->load->model('checkout/setting');
		$this->load->model('setting/extension');

		$address = $this->model_checkout_address->current();
		[, $total] = $this->model_checkout_cart->getTotals();
		$method_data = [];
		$recurring = $this->cart->hasRecurringProducts();
		$shipping_code = isset($this->session->data['shipping_method']['code'])
			? $this->session->data['shipping_method']['code']
			: '';

		foreach ($this->model_setting_extension->getExtensions('payment') as $result) {
			if (!$this->config->get('payment_' . $result['code'] . '_status')) {
				continue;
			}

			if ($shipping_code !== '' && !$this->model_checkout_setting->paymentAllowed($result['code'], $shipping_code)) {
				continue;
			}

			$this->load->model('extension/payment/' . $result['code']);

			$method = $this->{'model_extension_payment_' . $result['code']}->getMethod($address, $total);

			if (!$method) {
				continue;
			}

			if (
				$recurring &&
				!(
					method_exists($this->{'model_extension_payment_' . $result['code']}, 'recurringPayments') &&
					$this->{'model_extension_payment_' . $result['code']}->recurringPayments()
				)
			) {
				continue;
			}

			$method['title'] = $this->model_checkout_setting->title('payment', $result['code'], $method['title']);
			$method['description'] = $this->model_checkout_setting->description('payment', $result['code']);
			$method_data[$result['code']] = $method;
		}

		uasort($method_data, function ($a, $b) {
			return ((int) $a['sort_order']) <=> ((int) $b['sort_order']);
		});

		$this->session->data['payment_methods'] = $method_data;
		$this->syncSelected($method_data);

		return $method_data;
	}

	public function select($code)
	{
		$methods = isset($this->session->data['payment_methods'])
			? $this->session->data['payment_methods']
			: $this->getMethods();

		if (!isset($methods[$code])) {
			return false;
		}

		$this->session->data['payment_method'] = $methods[$code];

		return true;
	}

	private function syncSelected(array $methods)
	{
		$current = isset($this->session->data['payment_method']['code'])
			? $this->session->data['payment_method']['code']
			: '';

		if ($current !== '' && isset($methods[$current])) {
			$this->session->data['payment_method'] = $methods[$current];
			return;
		}

		$first = reset($methods);

		if ($first) {
			$this->session->data['payment_method'] = $first;
			return;
		}

		unset($this->session->data['payment_method']);
	}
}
