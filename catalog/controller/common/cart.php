<?php
class ControllerCommonCart extends Controller
{
	public function index()
	{
		$this->load->language('common/cart');

		$this->load->model('setting/extension');
		$this->load->model('tool/image');
		$this->load->model('tool/upload');
		$this->load->model('product/helper');

		$currency = $this->session->data['currency'] ?? $this->config->get('config_currency');

		[$totals, $grandTotal] = $this->getCartTotals();

		$voucherCount = !empty($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0;

		$data['text_items'] = sprintf(
			$this->language->get('text_items'),
			$this->cart->countProducts() + $voucherCount,
			$this->currency->format($grandTotal, $currency),
		);

		$data['products'] = [];
		$data['productCount'] = $this->cart->countProducts();
		foreach ($this->cart->getProducts() as $product) {
			$image = $this->model_product_helper->themeImage($product['image'] ?? '', 'cart');
			$priceFormatted = $this->currency->format($product['price'], $currency);
			$lineTotalFormatted = $this->currency->format($product['price'] * $product['quantity'], $currency);

			$data['products'][] = [
				'cart_id' => $product['cart_id'],
				'thumb' => $image,
				'name' => $product['name'],
				'quantity' => (int) $product['quantity'],
				'price' => $priceFormatted,
				'total' => $lineTotalFormatted,
				'href' => $this->url->link('product/product', 'product_id=' . (int) $product['product_id']),
			];
		}

		$data['totals'] = [];
		foreach ($totals as $total) {
			$data['totals'][] = [
				'title' => $total['title'],
				'text' => $this->currency->format($total['value'], $currency),
			];
		}

		$data['cart'] = $this->url->link('checkout/cart');
		$data['checkout'] = $this->url->link('checkout/checkout', '', true);

		return $this->load->view('common/cart', $data);
	}

	private function renderCartModal()
	{
		$this->load->language('common/cart');

		$this->load->model('setting/extension');
		$this->load->model('tool/image');
		$this->load->model('tool/upload');
		$this->load->model('product/helper');

		$currency = $this->session->data['currency'] ?? $this->config->get('config_currency');

		[$totals, $grandTotal] = $this->getCartTotals();

		$voucherCount = !empty($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0;

		$data['text_items'] = sprintf(
			$this->language->get('text_items'),
			$this->cart->countProducts() + $voucherCount,
			$this->currency->format($grandTotal, $currency),
		);

		$data['products'] = [];
		$data['productCount'] = $this->cart->countProducts();
		foreach ($this->cart->getProducts() as $product) {
			$option_data = [];

			foreach ($product['option'] ?? [] as $option) {
				if ($option['type'] != 'file') {
					$value = $option['value'];
				} else {
					$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);
					$value = $upload_info ? $upload_info['name'] : '';
				}

				$option_data[] = [
					'name' => $option['name'],
					'value' => $value,
				];
			}

			$image = $this->model_product_helper->themeImage($product['image'] ?? '', 'cart');
			$priceFormatted = $this->currency->format($product['price'], $currency);
			$lineTotalFormatted = $this->currency->format($product['price'] * $product['quantity'], $currency);

			$data['products'][] = [
				'cart_id' => $product['cart_id'],
				'thumb' => $image,
				'name' => $product['name'],
				'option' => $option_data,
				'recurring' => !empty($product['recurring']) ? $product['recurring']['name'] : '',
				'quantity' => (int) $product['quantity'],
				'minimum' => max(1, (int) ($product['minimum'] ?? 1)),
				'price' => $priceFormatted,
				'total' => $lineTotalFormatted,
				'href' => $this->url->link('product/product', 'product_id=' . (int) $product['product_id']),
			];
		}

		$data['totals'] = [];
		foreach ($totals as $total) {
			$data['totals'][] = [
				'title' => $total['title'],
				'text' => $this->currency->format($total['value'], $currency),
			];
		}

		$data['cart'] = $this->url->link('checkout/cart');
		$data['checkout'] = $this->url->link('checkout/checkout', '', true);

		return $this->load->view('common/cart_modal', $data);
	}

	public function info()
	{
		$this->response->setOutput($this->renderCartModal());
	}

	public function add()
	{
		$this->load->language('common/cart');

		$this->load->model('catalog/product');

		$json = [];

		$product_id = (int) $this->request->post['product_id'] ?? 0;
		$product_info = $this->model_catalog_product->getProduct($product_id);

		$quantity = (int) ($this->request->post['quantity'] ?? 1);
		$this->cart->add($this->request->post['product_id'], $quantity);

		$json['success'] = sprintf(
			$this->language->get('text_success'),
			$this->url->link('product/product', 'product_id=' . $this->request->post['product_id']),
			$product_info['name'],
			$this->url->link('checkout/cart'),
		);

		$json['total'] = $this->cart->countProducts();

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function edit()
	{
		$json = [];

		if (!empty($this->request->post['quantity']) && is_array($this->request->post['quantity'])) {
			foreach ($this->request->post['quantity'] as $key => $value) {
				$quantity = (int) $value;

				if ($quantity <= 0) {
					$this->cart->remove($key);
					continue;
				}

				$this->cart->update($key, $quantity);
			}

			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['reward']);
		}

		$json['total'] = $this->cart->countProducts();
		$json['html'] = $this->renderCartModal();

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function remove()
	{
		$this->load->language('common/cart');

		$json = [];

		if (isset($this->request->post['key'])) {
			$this->cart->remove($this->request->post['key']);

			unset($this->session->data['vouchers'][$this->request->post['key']]);
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['reward']);
		}

		$json['total'] = $this->cart->countProducts();
		$json['html'] = $this->renderCartModal();

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function getCartTotals()
	{
		// Важно: модель setting/extension должна быть загружена до вызова
		// $this->load->model('setting/extension');

		$totals = [];
		$taxes = $this->cart->getTaxes();
		$total = 0;

		$total_data = [
			'totals' => &$totals,
			'taxes' => &$taxes,
			'total' => &$total,
		];

		$extensions = $this->model_setting_extension->getExtensions('total');

		usort($extensions, function ($a, $b) {
			$aOrder = (int) $this->config->get('total_' . $a['code'] . '_sort_order');
			$bOrder = (int) $this->config->get('total_' . $b['code'] . '_sort_order');
			return $aOrder <=> $bOrder;
		});

		foreach ($extensions as $ext) {
			if ($this->config->get('total_' . $ext['code'] . '_status')) {
				$this->load->model('extension/total/' . $ext['code']);

				$model = 'model_extension_total_' . $ext['code'];

				$this->{$model}->getTotal($total_data);
			}
		}

		usort($totals, function ($a, $b) {
			return ((int) $a['sort_order']) <=> ((int) $b['sort_order']);
		});

		return [$totals, $total];
	}
}
