<?php

class ControllerCheckoutCart extends Controller
{
	public function index()
	{
		$this->response->redirect($this->url->link('checkout/checkout', '', true));
	}

	public function add()
	{
		$this->load->language('common/cart');
		$this->load->model('catalog/product');

		$json = [];
		$product_id = (int) ($this->request->post['product_id'] ?? 0);
		$product_info = $this->model_catalog_product->getProduct($product_id);
		$quantity = (int) ($this->request->post['quantity'] ?? 1);

		$this->cart->add($product_id, $quantity);

		$json['success'] = sprintf(
			$this->language->get('text_success'),
			$product_info ? $product_info['name'] : '',
		);
		$json['total'] = $this->cart->countProducts();

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function edit()
	{
		$this->load->model('checkout/cart');

		if (!empty($this->request->post['quantity']) && is_array($this->request->post['quantity'])) {
			foreach ($this->request->post['quantity'] as $key => $value) {
				$quantity = (int) $value;

				if ($quantity <= 0) {
					$this->cart->remove($key);
					continue;
				}

				$this->cart->update($key, $quantity);
			}

			$this->model_checkout_cart->clearQuote();
		}

		$this->jsonCart();
	}

	public function remove()
	{
		$this->load->model('checkout/cart');

		if (isset($this->request->post['key'])) {
			$this->cart->remove($this->request->post['key']);
			unset($this->session->data['vouchers'][$this->request->post['key']]);
			$this->model_checkout_cart->clearQuote();
		}

		$this->jsonCart();
	}

	public function coupon()
	{
		$this->load->language('extension/total/coupon');
		$this->load->model('checkout/cart');

		if (!$this->config->get('total_coupon_status')) {
			$this->jsonCart();
			return;
		}

		$this->load->model('extension/total/coupon');

		$code = isset($this->request->post['coupon']) ? trim((string) $this->request->post['coupon']) : '';
		$extra = [];

		if ($code === '') {
			unset($this->session->data['coupon']);
			$this->model_checkout_cart->clearQuote();
		} elseif ($this->model_extension_total_coupon->getCoupon($code)) {
			$this->session->data['coupon'] = $code;
			$this->model_checkout_cart->clearQuote();
			$extra['coupon_success'] = $this->language->get('text_success');
		} else {
			$extra['coupon_error'] = $this->language->get('error_coupon');
		}

		$this->jsonCart($extra);
	}

	public function refresh()
	{
		$this->jsonCart();
	}

	private function jsonCart($extra = [])
	{
		$this->load->language('checkout/checkout');
		$this->load->language('common/cart');
		$this->load->model('checkout/cart');
		$this->load->model('checkout/shipping');
		$this->load->model('checkout/payment');

		$json = [
			'empty' => $this->model_checkout_cart->isEmpty(),
			'total' => $this->cart->countProducts(),
			'html' => '',
			'summary' => '',
			'shipping' => '',
			'payment' => '',
			'show_shipping_address' => false,
		];

		if (!$json['empty']) {
			$data = array_merge(
				$this->model_checkout_shipping->getViewData(),
				$this->model_checkout_cart->getViewData(),
				$this->model_checkout_payment->getViewData(),
				$extra,
			);

			$json['html'] = $this->load->view('checkout/cart', $data);
			$json['summary'] = $this->load->view('checkout/summary', $data);
			$json['payment'] = $this->load->view('checkout/payment', $data);
			$json['shipping'] = $this->load->view('checkout/shipping_type', $data);
			$json['shipping_required'] = !empty($data['shipping_required']);
			$json['show_shipping_address'] = !empty($data['show_shipping_address']);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
