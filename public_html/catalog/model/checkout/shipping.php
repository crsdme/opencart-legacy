<?php

class ModelCheckoutShipping extends Model
{
	public function required()
	{
		if ($this->cart->hasShipping()) {
			return true;
		}

		return $this->cart->hasProducts() && $this->hasEnabledModules();
	}

	public function showAddress()
	{
		if (!$this->required()) {
			return false;
		}

		$method = isset($this->session->data['shipping_method']) && is_array($this->session->data['shipping_method'])
			? $this->session->data['shipping_method']
			: [];

		if (!empty($method['extra'])) {
			return false;
		}

		$this->load->model('checkout/setting');
		$code = isset($method['code']) ? $method['code'] : '';

		return !$this->model_checkout_setting->hideAddress($code);
	}

	public function getViewData()
	{
		$required = $this->required();
		$methods = $required ? $this->getMethods() : [];
		$code = isset($this->session->data['shipping_method']['code'])
			? $this->session->data['shipping_method']['code']
			: '';

		$error = '';

		if ($required && !$methods) {
			$error = sprintf($this->language->get('error_no_shipping'), $this->url->link('information/contact'));
		}

		return [
			'shipping_required' => $required,
			'shipping_methods' => $methods,
			'shipping_code' => $code,
			'shipping_error' => $error,
			'show_shipping_address' => $this->showAddress(),
		];
	}

	public function getMethods()
	{
		$this->load->model('checkout/address');
		$this->load->model('checkout/setting');

		$address = $this->model_checkout_address->current();
		$method_data = [];

		$this->load->model('setting/extension');

		foreach ($this->model_setting_extension->getExtensions('shipping') as $result) {
			if (!$this->config->get('shipping_' . $result['code'] . '_status')) {
				continue;
			}

			$this->load->model('extension/shipping/' . $result['code']);

			$quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($address);

			if (!$quote) {
				continue;
			}

			$quotes = isset($quote['quote']) && is_array($quote['quote']) ? $quote['quote'] : [];
			$single = count($quotes) === 1;

			foreach ($quotes as $key => $item) {
				if ($single) {
					$quotes[$key]['title'] = $this->model_checkout_setting->title(
						'shipping',
						$result['code'],
						$item['title']
					);
				}

				$quotes[$key]['hide_address'] = !empty($item['extra'])
					|| $this->model_checkout_setting->hideAddress($item['code']);
				$quotes[$key]['show_price'] = isset($item['cost']) && (float) $item['cost'] > 0;
				$quotes[$key]['description'] = $this->model_checkout_setting->description('shipping', $result['code']);
			}

			$quote['quote'] = $quotes;

			$method_data[$result['code']] = [
				'title' => $this->model_checkout_setting->title('shipping', $result['code'], $quote['title']),
				'quote' => $quote['quote'],
				'sort_order' => $quote['sort_order'],
				'error' => $quote['error'],
			];
		}

		uasort($method_data, function ($a, $b) {
			return ((int) $a['sort_order']) <=> ((int) $b['sort_order']);
		});

		$this->session->data['shipping_methods'] = $method_data;
		$this->syncSelected($method_data);

		return $method_data;
	}

	public function select($code)
	{
		$methods = isset($this->session->data['shipping_methods'])
			? $this->session->data['shipping_methods']
			: $this->getMethods();
		$parts = explode('.', (string) $code, 2);

		if (count($parts) !== 2 || !isset($methods[$parts[0]]['quote'][$parts[1]])) {
			return false;
		}

		$this->session->data['shipping_method'] = $methods[$parts[0]]['quote'][$parts[1]];
		unset($this->session->data['payment_methods']);

		return true;
	}

	private function syncSelected(array $methods)
	{
		$current = isset($this->session->data['shipping_method']['code'])
			? $this->session->data['shipping_method']['code']
			: '';
		$first = null;
		$matched = null;

		foreach ($methods as $method) {
			if (empty($method['quote']) || !is_array($method['quote'])) {
				continue;
			}

			foreach ($method['quote'] as $quote) {
				if ($first === null) {
					$first = $quote;
				}

				if ($current !== '' && $quote['code'] === $current) {
					$matched = $quote;
					break 2;
				}
			}
		}

		if ($matched) {
			$this->session->data['shipping_method'] = $matched;
			return;
		}

		if ($first) {
			$this->session->data['shipping_method'] = $first;
			return;
		}

		unset($this->session->data['shipping_method']);
	}

	private function hasEnabledModules()
	{
		$this->load->model('setting/extension');

		foreach ($this->model_setting_extension->getExtensions('shipping') as $result) {
			if ($this->config->get('shipping_' . $result['code'] . '_status')) {
				return true;
			}
		}

		return false;
	}
}
