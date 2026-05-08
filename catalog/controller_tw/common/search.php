<?php
class ControllerCommonSearch extends Controller
{
	public function index()
	{
		$this->load->language('common/search');

		$data['text_search'] = $this->language->get('text_search');
		$data['language'] = $this->config->get('config_language');

		if (isset($this->request->get['query'])) {
			$data['query'] = $this->request->get['query'];
		} else {
			$data['query'] = '';
		}

		return $this->load->view('common/search', $data);
	}

	public function searchProducts()
	{
		$this->response->addHeader('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		$this->response->addHeader('Pragma: no-cache');
		$this->response->addHeader('Expires: 0');

		$data['products'] = [];

		$search = isset($this->request->get['filter_name']) ? trim($this->request->get['filter_name']) : '';

		if (utf8_strlen($search) < 2) {
			$this->response->addHeader('Content-Type: text/html; charset=utf-8');
			$this->response->setOutput($this->load->view('common/live-search', $data));
			return;
		}

		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$currency_code = $this->session->data['currency'];

		$filter_data = [
			'filter_name' => $search,
			'sort' => 'p.sort_order',
			'order' => 'ASC',
			'start' => 0,
			'limit' => 5,
		];

		$results = $this->model_catalog_product->getProducts($filter_data);

		$image_width = 100;
		$image_height = 100;
		$title_length = 200;

		foreach ($results as $result) {
			if ($result['image']) {
				$thumb = $this->model_tool_image->resize($result['image'], $image_width, $image_height, true);
			} else {
				$thumb = $this->model_tool_image->resize('placeholder.png', $image_width, $image_height);
			}

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$price = $this->currency->format(
					$this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')),
					$currency_code,
				);
			} else {
				$price = false;
			}

			if ((float) $result['special']) {
				$special = $this->currency->format(
					$this->tax->calculate(
						$result['special'],
						$result['tax_class_id'],
						$this->config->get('config_tax'),
					),
					$currency_code,
				);
			} else {
				$special = false;
			}
			$data['products'][] = [
				'product_id' => $result['product_id'],
				'name' => utf8_substr(
					strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
					0,
					$title_length,
				),
				'thumb' => $thumb,
				'price' => $price,
				'special' => $special,
				'href' => $this->url->link('product/product', 'product_id=' . $result['product_id']),
			];
		}

		$this->response->addHeader('Content-Type: text/html; charset=utf-8');
		$this->response->setOutput($this->load->view('common/live_search', $data));
	}
}
