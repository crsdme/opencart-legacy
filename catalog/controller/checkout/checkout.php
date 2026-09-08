<?php

class ControllerCheckoutCheckout extends Controller
{
	public function index()
	{
		$this->load->language('checkout/checkout');
		$this->load->language('common/cart');
		$this->load->model('checkout/fields');
		$this->load->model('checkout/cart');
		$this->load->model('checkout/address');
		$this->load->model('checkout/shipping');
		$this->load->model('checkout/payment');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,follow');

		$account = new \Custom\Account($this->registry);
		$fields = $this->model_checkout_fields->all();

		if (!empty($fields['telephone']['show'])) {
			$account->addPhoneAssets();
		}

		$this->document->addStyle('catalog/view/theme/default/stylesheet/checkout.css');
		$this->document->addScript('catalog/view/theme/default/javascript/checkout.js', 'footer');

		$cart_empty = $this->model_checkout_cart->isEmpty();

		$data = array_merge(
			$this->model_checkout_address->getViewData(),
			$this->model_checkout_shipping->getViewData(),
			$this->model_checkout_cart->getViewData(),
			$this->model_checkout_payment->getViewData(),
			$this->customerData(),
			$this->agreeData(),
		);

		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home'),
			],
			[
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('checkout/checkout', '', true),
			],
		];

		$data['cart_empty'] = $cart_empty;
		$data['continue'] = $this->url->link('common/home');
		$data['action'] = $this->url->link('checkout/checkout', '', true);
		$data['confirm'] = $this->url->link('checkout/confirm/save', '', true);
		$data['fields'] = $fields;
		$data['logged'] = $this->customer->isLogged();
		$data['phone_prefix'] = $account->prefix();
		$data['shipping_extra'] = $this->shippingExtra($data);

		$data['view'] = 'checkout/checkout';

		$this->response->setOutput($this->load->controller('common/layout', $data));
	}

	public function cart()
	{
		$this->load->language('checkout/checkout');
		$this->load->language('common/cart');
		$this->load->model('checkout/cart');

		$this->response->setOutput($this->load->view('checkout/cart', $this->model_checkout_cart->getViewData()));
	}

	private function agreeData()
	{
		$information_id = (int) $this->config->get('config_checkout_id');

		if (!$information_id) {
			return ['text_agree' => ''];
		}

		$this->load->model('catalog/information');

		$information = $this->model_catalog_information->getInformation($information_id);

		if (!$information) {
			return ['text_agree' => ''];
		}

		return [
			'text_agree' => sprintf(
				$this->language->get('text_agree'),
				$this->url->link('information/information/agree', 'information_id=' . $information_id, true),
				$information['title'],
				$information['title'],
			),
		];
	}

	private function shippingExtra(array $data)
	{
		$code = isset($data['shipping_code']) ? $data['shipping_code'] : '';
		$methods = isset($data['shipping_methods']) && is_array($data['shipping_methods'])
			? $data['shipping_methods']
			: [];

		foreach ($methods as $method) {
			if (empty($method['quote']) || !is_array($method['quote'])) {
				continue;
			}

			foreach ($method['quote'] as $quote) {
				if ($quote['code'] !== $code || empty($quote['extra'])) {
					continue;
				}

				$route = preg_replace('#^index\.php\?route=#', '', $quote['extra']);
				$widget = preg_replace('#/extra$#', '/widget', $route);

				return (string) $this->load->controller($widget);
			}
		}

		return '';
	}

	private function customerData()
	{
		$guest = isset($this->session->data['guest']) ? $this->session->data['guest'] : [];

		if ($this->customer->isLogged()) {
			return [
				'firstname' => $this->customer->getFirstName(),
				'lastname' => $this->customer->getLastName(),
				'email' => $this->customer->getEmail(),
				'telephone' => $this->customer->getTelephone(),
				'comment' => isset($this->session->data['comment']) ? $this->session->data['comment'] : '',
			];
		}

		return [
			'firstname' => isset($guest['firstname']) ? $guest['firstname'] : '',
			'lastname' => isset($guest['lastname']) ? $guest['lastname'] : '',
			'email' => isset($guest['email']) ? $guest['email'] : '',
			'telephone' => isset($guest['telephone']) ? $guest['telephone'] : '',
			'comment' => isset($this->session->data['comment']) ? $this->session->data['comment'] : '',
		];
	}
}
