<?php

class ModelCheckoutPlace extends Model
{
	public function save($post)
	{
		$this->load->language('checkout/checkout');
		$this->load->model('checkout/fields');
		$this->load->model('checkout/cart');
		$this->load->model('checkout/address');
		$this->load->model('checkout/shipping');
		$this->load->model('checkout/payment');
		$this->load->model('checkout/setting');

		$this->applyPost($post);

		$errors = $this->validate($post);

		if ($errors) {
			return ['errors' => $errors];
		}

		$order_id = $this->createOrder();
		$this->session->data['order_id'] = $order_id;

		$code = isset($this->session->data['payment_method']['code'])
			? $this->session->data['payment_method']['code']
			: '';

		return $this->finishPayment($code);
	}

	private function applyPost($post)
	{
		$firstname = $this->value($post, 'firstname');
		$lastname = $this->value($post, 'lastname');

		$this->model_checkout_address->save(array_merge($post, [
			'firstname' => $firstname,
			'lastname' => $lastname,
		]));

		$this->session->data['guest'] = [
			'customer_group_id' => $this->customer->isLogged()
				? $this->customer->getGroupId()
				: (int) $this->config->get('config_customer_group_id'),
			'firstname' => $firstname,
			'lastname' => $lastname,
			'email' => $this->value($post, 'email'),
			'telephone' => $this->value($post, 'telephone'),
			'custom_field' => [],
		];
		$this->session->data['comment'] = $this->value($post, 'comment');

		if ($this->model_checkout_shipping->required()) {
			$shipping = $this->value($post, 'shipping_method');

			if ($shipping !== '') {
				$this->model_checkout_shipping->select($shipping);
			}
		}

		$payment = $this->value($post, 'payment_method');

		if ($payment !== '') {
			$this->model_checkout_payment->select($payment);
		}
	}

	private function validate($post)
	{
		$errors = [];
		$fields = $this->model_checkout_fields->all();
		$show_address = $this->model_checkout_shipping->showAddress();

		foreach ($fields as $code => $field) {
			if (empty($field['show']) || empty($field['required'])) {
				continue;
			}

			if ($field['group'] === 'address' && !$show_address) {
				continue;
			}

			$value = $this->value($post, $code);
			$error = $this->fieldError($code, $value, true);

			if ($error) {
				$errors[$code] = $error;
			}
		}

		$quote = isset($this->session->data['shipping_method']) ? $this->session->data['shipping_method'] : [];
		$address = $this->model_checkout_address->current();

		if (!empty($quote['extra'])) {
			foreach (['city', 'address_1'] as $code) {
				if (!empty($errors[$code])) {
					continue;
				}

				$value = $this->value($post, $code);

				if ($value === '' && !empty($address[$code])) {
					$value = trim((string) $address[$code]);
				}

				$error = $this->fieldError($code, $value, true);

				if ($error) {
					$errors[$code] = $error;
				}
			}

			if (isset($quote['code']) && substr($quote['code'], -6) === '.doors') {
				$house = $this->value($post, 'address_2');

				if ($house === '' && !empty($address['address_2'])) {
					$house = trim((string) $address['address_2']);
				}

				$error = $this->fieldError('address_2', $house, true);

				if ($error) {
					$errors['address_2'] = $error;
				}
			}
		}

		foreach (['email', 'telephone'] as $code) {
			if (!empty($fields[$code]['show']) && empty($errors[$code])) {
				$error = $this->fieldError($code, $this->value($post, $code), false);

				if ($error) {
					$errors[$code] = $error;
				}
			}
		}

		if ($this->model_checkout_cart->isEmpty()) {
			$errors['warning'] = $this->language->get('text_error_2');
		}

		$min_error = $this->model_checkout_setting->minTotalError();

		if ($min_error) {
			$errors['warning'] = $min_error;
		}

		if (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout')) {
			$errors['warning'] = $this->language->get('error_stock');
		}

		foreach ($this->cart->getProducts() as $product) {
			$product_total = 0;

			foreach ($this->cart->getProducts() as $other) {
				if ($other['product_id'] == $product['product_id']) {
					$product_total += $other['quantity'];
				}
			}

			if ($product['minimum'] > $product_total) {
				$errors['warning'] = sprintf(
					$this->language->get('error_minimum'),
					$product['name'],
					$product['minimum']
				);
				break;
			}
		}

		if ($this->model_checkout_shipping->required()) {
			$shipping_code = $this->value($post, 'shipping_method');
			$shipping_methods = $this->model_checkout_shipping->getMethods();

			if ($shipping_code === '' && !empty($this->session->data['shipping_method']['code'])) {
				$shipping_code = $this->session->data['shipping_method']['code'];
			}

			$parts = explode('.', $shipping_code, 2);

			if (
				count($parts) !== 2
				|| !isset($shipping_methods[$parts[0]]['quote'][$parts[1]])
			) {
				$errors['shipping_method'] = $this->language->get('error_shipping');
			}
		}

		$payment_code = $this->value($post, 'payment_method');
		$payment_methods = $this->model_checkout_payment->getMethods();

		if ($payment_code === '' && !empty($this->session->data['payment_method']['code'])) {
			$payment_code = $this->session->data['payment_method']['code'];
		}

		if (!$payment_methods) {
			$errors['payment_method'] = sprintf(
				$this->language->get('error_no_payment'),
				$this->url->link('information/contact')
			);
		} elseif ($payment_code === '' || !isset($payment_methods[$payment_code])) {
			$errors['payment_method'] = $this->language->get('error_payment');
		}

		$information_id = (int) $this->config->get('config_checkout_id');

		if ($information_id && empty($post['agree'])) {
			$this->load->model('catalog/information');
			$information = $this->model_catalog_information->getInformation($information_id);
			$errors['agree'] = sprintf(
				$this->language->get('error_agree'),
				$information ? $information['title'] : ''
			);
		}

		return $errors;
	}

	private function fieldError($code, $value, $required)
	{
		$length = utf8_strlen($value);

		if ($required && $value === '') {
			$key = 'error_' . $code;

			return $this->language->get($key) !== $key
				? $this->language->get($key)
				: $this->language->get('error_required');
		}

		if ($value === '') {
			return '';
		}

		if ($code === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
			return $this->language->get('error_email');
		}

		$limits = [
			'firstname' => [1, 32],
			'lastname' => [1, 32],
			'telephone' => [3, 32],
			'company' => [1, 40],
			'address_1' => [3, 128],
			'address_2' => [1, 128],
			'city' => [2, 128],
			'postcode' => [2, 10],
			'comment' => [1, 2000],
		];

		if (isset($limits[$code]) && ($length < $limits[$code][0] || $length > $limits[$code][1])) {
			return $this->language->get('error_' . $code);
		}

		return '';
	}

	private function createOrder()
	{
		$this->load->model('checkout/order');
		$this->load->model('account/customer');

		[$totals, $total] = $this->model_checkout_cart->getTotals();
		$address = $this->model_checkout_address->current();
		$guest = $this->session->data['guest'];
		$firstname = $guest['firstname'] !== '' ? $guest['firstname'] : '—';
		$lastname = $guest['lastname'];

		$order = [
			'invoice_prefix' => $this->config->get('config_invoice_prefix'),
			'store_id' => $this->config->get('config_store_id'),
			'store_name' => $this->config->get('config_name'),
			'store_url' => $this->config->get('config_url') ?: HTTP_SERVER,
			'totals' => $totals,
			'total' => $total,
			'comment' => isset($this->session->data['comment']) ? $this->session->data['comment'] : '',
			'custom_field' => [],
		];

		if ($this->customer->isLogged()) {
			$customer = $this->model_account_customer->getCustomer($this->customer->getId());
			$order['customer_id'] = $this->customer->getId();
			$order['customer_group_id'] = $customer['customer_group_id'];
			$order['custom_field'] = json_decode($customer['custom_field'], true) ?: [];
		} else {
			$order['customer_id'] = 0;
			$order['customer_group_id'] = $guest['customer_group_id'];
		}

		$order['firstname'] = $firstname;
		$order['lastname'] = $lastname;
		$order['email'] = $guest['email'];
		$order['telephone'] = $guest['telephone'];

		foreach (['payment', 'shipping'] as $prefix) {
			$source = $address;

			if ($prefix === 'shipping' && !$this->model_checkout_shipping->required()) {
				$source = [];
			}

			$order[$prefix . '_firstname'] = $firstname;
			$order[$prefix . '_lastname'] = $lastname;
			$order[$prefix . '_company'] = isset($source['company']) ? $source['company'] : '';
			$order[$prefix . '_address_1'] = isset($source['address_1']) ? $source['address_1'] : '';
			$order[$prefix . '_address_2'] = isset($source['address_2']) ? $source['address_2'] : '';
			$order[$prefix . '_city'] = isset($source['city']) ? $source['city'] : '';
			$order[$prefix . '_postcode'] = isset($source['postcode']) ? $source['postcode'] : '';
			$order[$prefix . '_zone'] = isset($source['zone']) ? $source['zone'] : '';
			$order[$prefix . '_zone_id'] = isset($source['zone_id']) ? $source['zone_id'] : 0;
			$order[$prefix . '_country'] = isset($source['country']) ? $source['country'] : '';
			$order[$prefix . '_country_id'] = isset($source['country_id']) ? $source['country_id'] : 0;
			$order[$prefix . '_address_format'] = isset($source['address_format']) ? $source['address_format'] : '';
			$order[$prefix . '_custom_field'] = isset($source['custom_field']) ? $source['custom_field'] : [];
		}

		$order['payment_method'] = isset($this->session->data['payment_method']['title'])
			? $this->session->data['payment_method']['title']
			: '';
		$order['payment_code'] = isset($this->session->data['payment_method']['code'])
			? $this->session->data['payment_method']['code']
			: '';

		if ($this->model_checkout_shipping->required()) {
			$order['shipping_method'] = isset($this->session->data['shipping_method']['title'])
				? $this->session->data['shipping_method']['title']
				: '';
			$order['shipping_code'] = isset($this->session->data['shipping_method']['code'])
				? $this->session->data['shipping_method']['code']
				: '';
		} else {
			$order['shipping_method'] = '';
			$order['shipping_code'] = '';
		}

		$order['products'] = [];

		foreach ($this->cart->getProducts() as $product) {
			$option_data = [];

			foreach ($product['option'] as $option) {
				$option_data[] = [
					'product_option_id' => $option['product_option_id'],
					'product_option_value_id' => $option['product_option_value_id'],
					'option_id' => $option['option_id'],
					'option_value_id' => $option['option_value_id'],
					'name' => $option['name'],
					'value' => $option['value'],
					'type' => $option['type'],
				];
			}

			$order['products'][] = [
				'product_id' => $product['product_id'],
				'name' => $product['name'],
				'model' => $product['model'],
				'option' => $option_data,
				'download' => $product['download'],
				'quantity' => $product['quantity'],
				'subtract' => $product['subtract'],
				'price' => $product['price'],
				'total' => $product['total'],
				'tax' => $this->tax->getTax($product['price'], $product['tax_class_id']),
				'reward' => $product['reward'],
			];
		}

		$order['vouchers'] = [];

		if (!empty($this->session->data['vouchers'])) {
			foreach ($this->session->data['vouchers'] as $voucher) {
				$order['vouchers'][] = [
					'description' => $voucher['description'],
					'code' => token(10),
					'to_name' => $voucher['to_name'],
					'to_email' => $voucher['to_email'],
					'from_name' => $voucher['from_name'],
					'from_email' => $voucher['from_email'],
					'voucher_theme_id' => $voucher['voucher_theme_id'],
					'message' => $voucher['message'],
					'amount' => $voucher['amount'],
				];
			}
		}

		$order['affiliate_id'] = 0;
		$order['commission'] = 0;
		$order['marketing_id'] = 0;
		$order['tracking'] = '';

		if (!empty($this->request->cookie['tracking'])) {
			$order['tracking'] = $this->request->cookie['tracking'];
			$affiliate = $this->model_account_customer->getAffiliateByTracking($this->request->cookie['tracking']);

			if ($affiliate) {
				$order['affiliate_id'] = $affiliate['customer_id'];
				$order['commission'] = ($this->cart->getSubTotal() / 100) * $affiliate['commission'];
			}

			$this->load->model('checkout/marketing');
			$marketing = $this->model_checkout_marketing->getMarketingByCode($this->request->cookie['tracking']);
			$order['marketing_id'] = $marketing ? $marketing['marketing_id'] : 0;
		}

		$order['language_id'] = $this->config->get('config_language_id');
		$order['currency_id'] = $this->currency->getId($this->session->data['currency']);
		$order['currency_code'] = $this->session->data['currency'];
		$order['currency_value'] = $this->currency->getValue($this->session->data['currency']);
		$order['ip'] = isset($this->request->server['REMOTE_ADDR']) ? $this->request->server['REMOTE_ADDR'] : '';
		$order['forwarded_ip'] = '';

		if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
			$order['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
		} elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
			$order['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
		}

		$order['user_agent'] = isset($this->request->server['HTTP_USER_AGENT'])
			? $this->request->server['HTTP_USER_AGENT']
			: '';
		$order['accept_language'] = isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])
			? $this->request->server['HTTP_ACCEPT_LANGUAGE']
			: '';

		if ($this->request->server['HTTPS']) {
			$order['store_url'] = HTTPS_SERVER;
		}

		if ($this->config->get('config_store_id')) {
			$order['store_url'] = $this->config->get('config_url');
		}

		return $this->model_checkout_order->addOrder($order);
	}

	private function finishPayment($code)
	{
		$statuses = [
			'cod' => 'payment_cod_order_status_id',
			'bank_transfer' => 'payment_bank_transfer_order_status_id',
			'cheque' => 'payment_cheque_order_status_id',
			'free_checkout' => 'payment_free_checkout_order_status_id',
		];

		if (!isset($statuses[$code])) {
			$html = $this->load->controller('extension/payment/' . $code);

			return [
				'payment' => is_string($html) ? $html : '',
			];
		}

		$comment = '';
		$notify = false;

		if ($code === 'bank_transfer') {
			$this->load->language('extension/payment/bank_transfer');
			$comment = $this->language->get('text_instruction') . "\n\n";
			$comment .= $this->config->get('payment_bank_transfer_bank' . $this->config->get('config_language_id')) . "\n\n";
			$comment .= $this->language->get('text_payment');
			$notify = true;
		} elseif ($code === 'cheque') {
			$this->load->language('extension/payment/cheque');
			$comment = $this->language->get('text_payable') . "\n";
			$comment .= $this->config->get('payment_cheque_payable') . "\n\n";
			$comment .= $this->language->get('text_address') . "\n";
			$comment .= $this->config->get('config_address') . "\n\n";
			$comment .= $this->language->get('text_payment') . "\n";
			$notify = true;
		}

		$this->model_checkout_order->addOrderHistory(
			$this->session->data['order_id'],
			$this->config->get($statuses[$code]),
			$comment,
			$notify
		);

		return [
			'redirect' => $this->url->link('checkout/success', '', true),
		];
	}

	private function value($post, $key)
	{
		return isset($post[$key]) ? trim((string) $post[$key]) : '';
	}
}
