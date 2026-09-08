<?php

class ControllerCheckoutSuccess extends Controller
{
	public function index()
	{
		$this->load->language('checkout/success');

		if (isset($this->session->data['order_id'])) {
			$this->session->data['last_order_id'] = $this->session->data['order_id'];
			$this->cart->clear();

			unset(
				$this->session->data['shipping_method'],
				$this->session->data['shipping_methods'],
				$this->session->data['payment_method'],
				$this->session->data['payment_methods'],
				$this->session->data['guest'],
				$this->session->data['comment'],
				$this->session->data['order_id'],
				$this->session->data['coupon'],
				$this->session->data['reward'],
				$this->session->data['voucher'],
				$this->session->data['vouchers'],
				$this->session->data['totals']
			);
		}

		$order_id = !empty($this->session->data['last_order_id'])
			? (int) $this->session->data['last_order_id']
			: 0;

		if ($order_id) {
			$this->document->setTitle(sprintf($this->language->get('heading_title_customer'), $order_id));
		} else {
			$this->document->setTitle($this->language->get('heading_title'));
		}

		$this->document->setRobots('noindex,follow');

		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home'),
			],
			[
				'text' => $this->language->get('text_basket'),
				'href' => $this->url->link('checkout/cart'),
			],
			[
				'text' => $this->language->get('text_checkout'),
				'href' => $this->url->link('checkout/checkout', '', true),
			],
			[
				'text' => $this->language->get('text_success'),
				'href' => $this->url->link('checkout/success'),
			],
		];

		$data['heading_title'] = $order_id
			? sprintf($this->language->get('heading_title_customer'), $order_id)
			: $this->language->get('heading_title');

		if ($this->customer->isLogged() && $order_id) {
			$data['text_message'] = sprintf(
				$this->language->get('text_customer'),
				$this->url->link('account/order/info', 'order_id=' . $order_id, true),
				$this->url->link('account/order', '', true),
				$this->url->link('account/download', '', true),
				$this->url->link('information/contact')
			);
		} else {
			$data['text_message'] = sprintf(
				$this->language->get('text_guest'),
				$this->url->link('information/contact')
			);
		}

		$data['continue'] = $this->url->link('common/home');
		$data['button_continue'] = $this->language->get('button_continue');
		$data['view'] = 'common/success';

		$this->response->setOutput($this->load->controller('common/layout', $data));
	}
}
