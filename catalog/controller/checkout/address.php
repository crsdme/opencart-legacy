<?php

class ControllerCheckoutAddress extends Controller
{
	public function save()
	{
		$this->load->model('checkout/address');
		$this->model_checkout_address->save($this->request->post);

		if (!empty($this->request->post['quiet'])) {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode(['ok' => 1]));
			return;
		}

		$this->load->controller('checkout/cart/refresh');
	}
}
