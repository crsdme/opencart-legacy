<?php

class ControllerCheckoutShipping extends Controller
{
	public function save()
	{
		$this->load->model('checkout/shipping');

		$code = isset($this->request->post['shipping_method']) ? (string) $this->request->post['shipping_method'] : '';

		if ($this->model_checkout_shipping->required()) {
			$this->model_checkout_shipping->select($code);
		}

		$this->load->controller('checkout/cart/refresh');
	}
}
