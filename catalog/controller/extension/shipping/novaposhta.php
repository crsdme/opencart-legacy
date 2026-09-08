<?php

class ControllerExtensionShippingNovaposhta extends Controller
{
	public function extra()
	{
		$this->response->setOutput($this->widget());
	}

	public function widget($data = [])
	{
		$this->load->language('extension/shipping/novaposhta');
		$this->load->model('checkout/address');

		$code = isset($this->session->data['shipping_method']['code'])
			? $this->session->data['shipping_method']['code']
			: '';
		$parts = explode('.', $code, 2);
		$type = isset($parts[1]) ? $parts[1] : 'department';

		if (!in_array($type, ['department', 'poshtomat', 'doors'], true)) {
			return '';
		}

		$address = $this->model_checkout_address->current();
		$settings = $this->config->get('shipping_novaposhta');
		$settings = is_array($settings) ? $settings : [];
		$data = is_array($data) ? $data : [];

		$data['type'] = $type;
		$data['city'] = isset($address['city']) ? $address['city'] : '';
		$data['address_1'] = isset($address['address_1']) ? $address['address_1'] : '';
		$data['address_2'] = isset($address['address_2']) ? $address['address_2'] : '';
		$data['city_ref'] = isset($this->session->data['shipping_ref'])
			? $this->session->data['shipping_ref']
			: '';
		$data['search'] = 'index.php?route=extension/shipping/novaposhta/search';
		$data['online_streets'] = $type === 'doors' && !empty($settings['streets']);
		$data['text_heading'] = $this->language->get('text_heading_' . $type);
		$data['text_hint'] = $this->language->get('text_hint_' . $type);
		$data['entry_city'] = $this->language->get('entry_city');
		$data['entry_warehouse'] = $this->language->get('entry_warehouse');
		$data['entry_poshtomat'] = $this->language->get('entry_poshtomat');
		$data['entry_street'] = $this->language->get('entry_street');
		$data['entry_house'] = $this->language->get('entry_house');
		$data['text_city'] = $this->language->get('text_city');
		$data['text_warehouse'] = $this->language->get('text_warehouse');
		$data['text_street'] = $this->language->get('text_street');

		return $this->load->view('extension/shipping/novaposhta', $data);
	}

	public function search()
	{
		$this->load->model('extension/shipping/novaposhta');

		$action = isset($this->request->get['action']) ? (string) $this->request->get['action'] : '';
		$query = isset($this->request->get['q']) ? (string) $this->request->get['q'] : '';
		$city = isset($this->request->get['city']) ? (string) $this->request->get['city'] : '';
		$city_ref = isset($this->request->get['city_ref']) ? (string) $this->request->get['city_ref'] : '';
		$type = isset($this->request->get['type']) ? (string) $this->request->get['type'] : 'department';
		$json = $this->model_extension_shipping_novaposhta->search($action, $query, $city, $city_ref, $type);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
