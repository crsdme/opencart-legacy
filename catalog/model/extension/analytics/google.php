<?php

class ModelExtensionAnalyticsGoogle extends Model
{
	public function isActive()
	{
		return (bool) $this->config->get('analytics_google_status');
	}

	public function eventEnabled($code)
	{
		if (!$this->isActive()) {
			return false;
		}

		$events = $this->config->get('analytics_google_events');

		if (!is_array($events)) {
			return true;
		}

		return !empty($events[$code]);
	}

	public function eventsMap()
	{
		$codes = [
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

		$saved = $this->config->get('analytics_google_events');
		$map = [];

		foreach ($codes as $code) {
			$map[$code] = !is_array($saved) ? true : !empty($saved[$code]);
		}

		return $map;
	}

	public function currency()
	{
		return $this->session->data['currency'] ?? $this->config->get('config_currency');
	}

	public function encode($data)
	{
		return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
	}

	public function itemFromProduct(array $product, $quantity = 1, $index = null)
	{
		$quantity = max(1, (int) $quantity);
		$price = $this->unitPrice($product);

		$item = [
			'item_id' => (string) ($product['product_id'] ?? ''),
			'item_name' => (string) ($product['name'] ?? ''),
			'price' => $price,
			'quantity' => $quantity,
		];

		if (!empty($product['model'])) {
			$item['item_variant'] = (string) $product['model'];
		}

		if (!empty($product['manufacturer'])) {
			$item['item_brand'] = (string) $product['manufacturer'];
		}

		if ($index !== null) {
			$item['index'] = (int) $index;
		}

		return $item;
	}

	public function payloadFromItems(array $items, array $extra = [])
	{
		$value = 0;

		foreach ($items as $item) {
			$value += ((float) ($item['price'] ?? 0)) * ((int) ($item['quantity'] ?? 1));
		}

		return array_merge(
			[
				'currency' => $this->currency(),
				'value' => round($value, 2),
				'items' => array_values($items),
			],
			$extra,
		);
	}

	public function cartItems()
	{
		$items = [];

		foreach ($this->cart->getProducts() as $index => $product) {
			$items[] = $this->itemFromProduct($product, $product['quantity'], $index);
		}

		return $items;
	}

	public function cartPayload()
	{
		return $this->payloadFromItems($this->cartItems());
	}

	public function addToCartPayload($product_id, $quantity)
	{
		$this->load->model('catalog/product');

		$product = $this->model_catalog_product->getProduct((int) $product_id);

		if (!$product) {
			return null;
		}

		$item = $this->itemFromProduct($product, $quantity);

		return $this->payloadFromItems([$item]);
	}

	public function purchasePayload($order_id)
	{
		$this->load->model('checkout/order');

		$order = $this->model_checkout_order->getOrder((int) $order_id);

		if (!$order) {
			return null;
		}

		$items = [];

		foreach ($this->model_checkout_order->getOrderProducts($order_id) as $index => $product) {
			$items[] = [
				'item_id' => (string) $product['product_id'],
				'item_name' => (string) $product['name'],
				'price' => $this->formatAmount($product['price'], $order['currency_code'], $order['currency_value']),
				'quantity' => (int) $product['quantity'],
			];
		}

		$tax = 0;
		$shipping = 0;
		$coupon = null;

		foreach ($this->model_checkout_order->getOrderTotals($order_id) as $total) {
			if ($total['code'] === 'tax') {
				$tax += (float) $total['value'];
			}

			if ($total['code'] === 'shipping') {
				$shipping += (float) $total['value'];
			}

			if ($total['code'] === 'coupon') {
				$coupon = $total['title'];
			}
		}

		$payload = [
			'transaction_id' => (string) $order_id,
			'currency' => $order['currency_code'],
			'value' => $this->formatAmount($order['total'], $order['currency_code'], $order['currency_value']),
			'tax' => $this->formatAmount($tax, $order['currency_code'], $order['currency_value']),
			'shipping' => $this->formatAmount($shipping, $order['currency_code'], $order['currency_value']),
			'items' => $items,
		];

		if ($coupon) {
			$payload['coupon'] = $coupon;
		}

		return $payload;
	}

	public function eventScript($name, array $params)
	{
		return '<script>if (window.Ecommerce) Ecommerce.event(' .
			json_encode($name) .
			', ' .
			$this->encode($params) .
			');</script>';
	}

	private function unitPrice(array $product)
	{
		$price = isset($product['special']) && (float) $product['special'] > 0
			? (float) $product['special']
			: (float) ($product['price'] ?? 0);

		if (!empty($product['tax_class_id'])) {
			$price = $this->tax->calculate($price, $product['tax_class_id'], $this->config->get('config_tax'));
		}

		return $this->formatAmount($price);
	}

	private function formatAmount($amount, $currency = '', $value = '')
	{
		$currency = $currency ?: $this->currency();

		return (float) $this->currency->format($amount, $currency, $value, false);
	}
}
