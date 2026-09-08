<?php

class ModelCheckoutAddress extends Model
{
	public function getViewData()
	{
		$address = $this->current();

		return [
			'company' => isset($address['company']) ? $address['company'] : '',
			'city' => isset($address['city']) ? $address['city'] : '',
			'address_1' => isset($address['address_1']) ? $address['address_1'] : '',
			'address_2' => isset($address['address_2']) ? $address['address_2'] : '',
			'postcode' => isset($address['postcode']) ? $address['postcode'] : '',
		];
	}

	public function current()
	{
		if (!empty($this->session->data['shipping_address']['country_id'])) {
			$this->session->data['payment_address'] = $this->session->data['shipping_address'];

			return $this->session->data['shipping_address'];
		}

		$address = $this->defaults();

		if ($this->customer->isLogged()) {
			$this->load->model('account/address');
			$saved = $this->model_account_address->getAddress($this->customer->getAddressId());

			if ($saved) {
				$address = array_merge($address, $saved);
			}
		}

		$this->store($address);

		return $address;
	}

	public function save($post)
	{
		$address = $this->current();

		foreach (['firstname', 'lastname', 'company', 'city', 'address_1', 'address_2', 'postcode'] as $key) {
			if (array_key_exists($key, $post)) {
				$address[$key] = trim((string) $post[$key]);
			}
		}

		if (array_key_exists('shipping_ref', $post)) {
			$this->session->data['shipping_ref'] = trim((string) $post['shipping_ref']);
		}

		$this->store($address);

		return $address;
	}

	private function store(array $address)
	{
		$this->session->data['shipping_address'] = $address;
		$this->session->data['payment_address'] = $address;
	}

	private function defaults()
	{
		$country_id = (int) $this->config->get('config_country_id');
		$zone_id = (int) $this->config->get('config_zone_id');
		$country = [
			'name' => '',
			'iso_code_2' => '',
			'iso_code_3' => '',
			'address_format' => '',
		];
		$zone = [
			'name' => '',
			'code' => '',
		];

		$this->load->model('localisation/country');
		$country_info = $this->model_localisation_country->getCountry($country_id);

		if ($country_info) {
			$country = $country_info;
		}

		$this->load->model('localisation/zone');
		$zone_info = $this->model_localisation_zone->getZone($zone_id);

		if ($zone_info) {
			$zone = $zone_info;
		}

		return [
			'firstname' => $this->customer->isLogged() ? $this->customer->getFirstName() : '',
			'lastname' => $this->customer->isLogged() ? $this->customer->getLastName() : '',
			'company' => '',
			'address_1' => '',
			'address_2' => '',
			'postcode' => '',
			'city' => '',
			'zone_id' => $zone_id,
			'zone' => isset($zone['name']) ? $zone['name'] : '',
			'zone_code' => isset($zone['code']) ? $zone['code'] : '',
			'country_id' => $country_id,
			'country' => isset($country['name']) ? $country['name'] : '',
			'iso_code_2' => isset($country['iso_code_2']) ? $country['iso_code_2'] : '',
			'iso_code_3' => isset($country['iso_code_3']) ? $country['iso_code_3'] : '',
			'address_format' => isset($country['address_format']) ? $country['address_format'] : '',
			'custom_field' => [],
		];
	}
}
