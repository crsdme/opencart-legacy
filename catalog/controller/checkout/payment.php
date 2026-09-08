<?php

class ControllerCheckoutPayment extends Controller
{
	public function save()
	{
		$this->load->model('checkout/payment');

		$code = isset($this->request->post['payment_method']) ? (string) $this->request->post['payment_method'] : '';
		$this->model_checkout_payment->select($code);
		$this->load->controller('checkout/cart/refresh');
	}
}
