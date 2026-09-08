<?php
class ControllerProductCompare extends Controller
{
	public function index()
	{
		$this->load->language('product/compare');

		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$this->load->model('product/helper');

		if (!isset($this->session->data['compare'])) {
			$this->session->data['compare'] = [];
		}

		if (isset($this->request->get['remove'])) {
			$key = array_search($this->request->get['remove'], $this->session->data['compare']);

			if ($key !== false) {
				unset($this->session->data['compare'][$key]);

				$this->session->data['success'] = $this->language->get('text_remove');
			}

			$this->response->redirect($this->url->link('product/compare'));
		}

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,follow');

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home'),
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('product/compare'),
		];

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['review_status'] = $this->config->get('config_review_status');

		$data['products'] = [];

		$data['attribute_groups'] = [];

		foreach ($this->session->data['compare'] as $key => $product_id) {
			$product_info = $this->model_catalog_product->getProduct($product_id);

			if ($product_info) {
				$image = $product_info['image']
					? $this->model_product_helper->themeImage($product_info['image'], 'compare', false)
					: false;

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format(
						$this->tax->calculate(
							$product_info['price'],
							$product_info['tax_class_id'],
							$this->config->get('config_tax'),
						),
						$this->session->data['currency'],
					);
				} else {
					$price = false;
				}

				if (!is_null($product_info['special']) && (float) $product_info['special'] >= 0) {
					$special = $this->currency->format(
						$this->tax->calculate(
							$product_info['special'],
							$product_info['tax_class_id'],
							$this->config->get('config_tax'),
						),
						$this->session->data['currency'],
					);
				} else {
					$special = false;
				}

				if ($product_info['quantity'] <= 0) {
					$availability = $product_info['stock_status'];
				} elseif ($this->config->get('config_stock_display')) {
					$availability = $product_info['quantity'];
				} else {
					$availability = $this->language->get('text_instock');
				}

				$attribute_data = [];

				$attribute_groups = $this->model_catalog_product->getProductAttributes($product_id);

				foreach ($attribute_groups as $attribute_group) {
					foreach ($attribute_group['attribute'] as $attribute) {
						$attribute_data[$attribute['attribute_id']] = $attribute['text'];
					}
				}

				$data['products'][$product_id] = [
					'product_id' => $product_info['product_id'],
					'name' => $product_info['name'],
					'thumb' => $image,
					'price' => $price,
					'special' => $special,
					'description' =>
						utf8_substr(
							strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8')),
							0,
							200,
						) . '..',
					'model' => $product_info['model'],
					'manufacturer' => $product_info['manufacturer'],
					'availability' => $availability,
					'minimum' => $product_info['minimum'] > 0 ? $product_info['minimum'] : 1,
					'quantity' => (int) $product_info['quantity'],
					'rating' => (int) $product_info['rating'],
					'reviews' => sprintf($this->language->get('text_reviews'), (int) $product_info['reviews']),
					'weight' => $this->weight->format($product_info['weight'], $product_info['weight_class_id']),
					'length' => $this->length->format($product_info['length'], $product_info['length_class_id']),
					'width' => $this->length->format($product_info['width'], $product_info['length_class_id']),
					'height' => $this->length->format($product_info['height'], $product_info['length_class_id']),
					'attribute' => $attribute_data,
					'href' => $this->url->link('product/product', 'product_id=' . $product_id),
					'remove' => $this->url->link('product/compare', 'remove=' . $product_id),
				];

				foreach ($attribute_groups as $attribute_group) {
					$data['attribute_groups'][$attribute_group['attribute_group_id']]['name'] =
						$attribute_group['name'];

					foreach ($attribute_group['attribute'] as $attribute) {
						$data['attribute_groups'][$attribute_group['attribute_group_id']]['attribute'][
							$attribute['attribute_id']
						]['name'] = $attribute['name'];
					}
				}
			} else {
				unset($this->session->data['compare'][$key]);
			}
		}

		$data['continue'] = $this->url->link('common/home');
		$data['view'] = 'product/compare';

		$this->response->setOutput($this->load->controller('common/layout', $data));
	}

	public function add()
	{
		$this->load->language('product/compare');

		$json = [];

		if (!isset($this->session->data['compare'])) {
			$this->session->data['compare'] = [];
		}

		$product_id = isset($this->request->post['product_id']) ? (int) $this->request->post['product_id'] : 0;

		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if (!$product_info) {
			$json['error'] = true;
			$json['title'] = $this->language->get('text_error');

			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));

			return;
		}

		$compare = array_map('intval', $this->session->data['compare']);

		if (!in_array($product_id, $compare, true)) {
			if (count($compare) >= 4) {
				array_shift($compare);
			}

			$compare[] = $product_id;
		}

		$this->session->data['compare'] = array_values($compare);

		$json['title'] = $this->language->get('text_added');
		$json['href'] = $this->url->link('product/compare');
		$json['action_text'] = $this->language->get('button_view');
		$json['success'] = sprintf(
			$this->language->get('text_success'),
			$this->url->link('product/product', 'product_id=' . $product_id),
			$product_info['name'],
			$this->url->link('product/compare'),
		);
		$json['total'] = sprintf($this->language->get('text_compare'), count($this->session->data['compare']));

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
