<?php

class ModelCheckoutSetting extends Model
{
	public function hideAddress($quote_code)
	{
		$code = $this->moduleCode($quote_code);
		$row = $this->shipping($code);

		return !empty($row['hide_address']);
	}

	public function title($type, $code, $fallback)
	{
		$row = $type === 'payment' ? $this->payment($code) : $this->shipping($code);
		$lang = (int) $this->config->get('config_language_id');
		$titles = isset($row['title']) && is_array($row['title']) ? $row['title'] : [];
		$custom = isset($titles[$lang]) ? trim((string) $titles[$lang]) : '';

		return $custom !== '' ? $custom : $fallback;
	}

	public function description($type, $code)
	{
		$row = $type === 'payment' ? $this->payment($code) : $this->shipping($code);
		$lang = (int) $this->config->get('config_language_id');
		$texts = isset($row['description']) && is_array($row['description']) ? $row['description'] : [];

		return isset($texts[$lang]) ? trim((string) $texts[$lang]) : '';
	}

	public function minTotal()
	{
		return max(0, (float) $this->config->get('module_checkout_min_total'));
	}

	public function minTotalError()
	{
		$min = $this->minTotal();

		if ($min <= 0 || $this->cart->getSubTotal() >= $min) {
			return '';
		}

		$this->load->language('checkout/checkout');

		return sprintf(
			$this->language->get('error_min_amount'),
			$this->currency->format($min, $this->session->data['currency'])
		);
	}

	public function paymentAllowed($payment_code, $quote_code)
	{
		$row = $this->payment($payment_code);
		$allowed = isset($row['shipping']) && is_array($row['shipping']) ? $row['shipping'] : [];

		if (!$allowed) {
			return true;
		}

		return in_array($this->moduleCode($quote_code), $allowed, true);
	}

	public function shipping($code)
	{
		$saved = $this->config->get('module_checkout_shipping');
		$row = is_array($saved) && isset($saved[$code]) && is_array($saved[$code]) ? $saved[$code] : [];

		return [
			'title' => isset($row['title']) && is_array($row['title']) ? $row['title'] : [],
			'description' => isset($row['description']) && is_array($row['description']) ? $row['description'] : [],
			'hide_address' => array_key_exists('hide_address', $row)
				? (!empty($row['hide_address']) ? 1 : 0)
				: (in_array($code, ['pickup', 'novaposhta'], true) ? 1 : 0),
		];
	}

	public function payment($code)
	{
		$saved = $this->config->get('module_checkout_payment');
		$row = is_array($saved) && isset($saved[$code]) && is_array($saved[$code]) ? $saved[$code] : [];
		$shipping = [];

		if (isset($row['shipping']) && is_array($row['shipping'])) {
			foreach ($row['shipping'] as $key => $value) {
				$shipping[] = is_string($key) && !is_numeric($key) ? $key : (string) $value;
			}

			$shipping = array_values(array_filter($shipping, 'strlen'));
		}

		return [
			'title' => isset($row['title']) && is_array($row['title']) ? $row['title'] : [],
			'description' => isset($row['description']) && is_array($row['description']) ? $row['description'] : [],
			'shipping' => $shipping,
		];
	}

	private function moduleCode($quote_code)
	{
		$parts = explode('.', (string) $quote_code, 2);

		return $parts[0];
	}
}
