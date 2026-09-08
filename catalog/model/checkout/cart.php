<?php

class ModelCheckoutCart extends Model
{
	public function isEmpty()
	{
		return !$this->cart->hasProducts() && empty($this->session->data['vouchers']);
	}

	public function getViewData()
	{
		$this->load->model('tool/upload');
		$this->load->model('product/helper');
		$this->load->model('setting/extension');

		$currency = $this->session->data['currency'] ?? $this->config->get('config_currency');
		$show_price = $this->customer->isLogged() || !$this->config->get('config_customer_price');
		$error_warning = '';

		if (!$this->cart->hasStock() && (!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning'))) {
			$error_warning = $this->language->get('error_stock');
		}

		$this->load->model('checkout/setting');
		$min_error = $this->model_checkout_setting->minTotalError();

		if ($min_error) {
			$error_warning = $error_warning ? $error_warning . ' ' . $min_error : $min_error;
		}

		$products = [];

		foreach ($this->cart->getProducts() as $product) {
			$product_total = 0;

			foreach ($this->cart->getProducts() as $product_2) {
				if ($product_2['product_id'] == $product['product_id']) {
					$product_total += $product_2['quantity'];
				}
			}

			if ($product['minimum'] > $product_total) {
				$error_warning = sprintf($this->language->get('error_minimum'), $product['name'], $product['minimum']);
			}

			$option_data = [];

			foreach ($product['option'] ?? [] as $option) {
				if ($option['type'] != 'file') {
					$value = $option['value'];
				} else {
					$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);
					$value = $upload_info ? $upload_info['name'] : '';
				}

				$option_data[] = [
					'name' => $option['name'],
					'value' => $value,
				];
			}

			if ($show_price) {
				$unit_price = $this->tax->calculate(
					$product['price'],
					$product['tax_class_id'],
					$this->config->get('config_tax'),
				);
				$price = $this->currency->format($unit_price, $currency);
				$total = $this->currency->format($unit_price * $product['quantity'], $currency);
			} else {
				$price = false;
				$total = false;
			}

			$products[] = [
				'cart_id' => $product['cart_id'],
				'thumb' => $this->model_product_helper->themeImage($product['image'] ?? '', 'cart'),
				'name' => $product['name'],
				'option' => $option_data,
				'quantity' => (int) $product['quantity'],
				'minimum' => max(1, (int) ($product['minimum'] ?? 1)),
				'stock' => !empty($product['stock']),
				'price' => $price,
				'total' => $total,
				'href' => $this->url->link('product/product', 'product_id=' . (int) $product['product_id']),
			];
		}

		$vouchers = [];

		if (!empty($this->session->data['vouchers'])) {
			foreach ($this->session->data['vouchers'] as $key => $voucher) {
				$vouchers[] = [
					'key' => $key,
					'description' => $voucher['description'],
					'amount' => $this->currency->format($voucher['amount'], $currency),
				];
			}
		}

		[$totals] = $this->getTotals();
		$total_rows = [];

		foreach ($totals as $total) {
			$total_rows[] = [
				'title' => $total['title'],
				'text' => $this->currency->format($total['value'], $currency),
			];
		}

		return [
			'products' => $products,
			'vouchers' => $vouchers,
			'totals' => $total_rows,
			'error_warning' => $error_warning,
			'coupon_status' => (bool) $this->config->get('total_coupon_status'),
			'coupon' => isset($this->session->data['coupon']) ? $this->session->data['coupon'] : '',
			'coupon_error' => '',
			'coupon_success' => '',
		];
	}

	public function getTotals()
	{
		$totals = [];
		$taxes = $this->cart->getTaxes();
		$total = 0;

		$total_data = [
			'totals' => &$totals,
			'taxes' => &$taxes,
			'total' => &$total,
		];

		if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
			$this->load->model('setting/extension');

			$extensions = $this->model_setting_extension->getExtensions('total');

			usort($extensions, function ($a, $b) {
				$aOrder = (int) $this->config->get('total_' . $a['code'] . '_sort_order');
				$bOrder = (int) $this->config->get('total_' . $b['code'] . '_sort_order');

				return $aOrder <=> $bOrder;
			});

			foreach ($extensions as $ext) {
				if ($this->config->get('total_' . $ext['code'] . '_status')) {
					$this->load->model('extension/total/' . $ext['code']);
					$this->{'model_extension_total_' . $ext['code']}->getTotal($total_data);
				}
			}

			usort($totals, function ($a, $b) {
				return ((int) $a['sort_order']) <=> ((int) $b['sort_order']);
			});
		}

		return [$totals, $total];
	}

	public function count()
	{
		$vouchers = !empty($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0;

		return $this->cart->countProducts() + $vouchers;
	}

	public function clearQuote()
	{
		unset($this->session->data['shipping_methods']);
		unset($this->session->data['payment_methods']);
		unset($this->session->data['reward']);
	}
}
