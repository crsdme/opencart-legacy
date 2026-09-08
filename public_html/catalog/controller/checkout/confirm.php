<?php

class ControllerCheckoutConfirm extends Controller
{
	public function save()
	{
		$this->load->language('checkout/checkout');
		$this->load->model('checkout/place');

		$json = $this->model_checkout_place->save($this->request->post);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
