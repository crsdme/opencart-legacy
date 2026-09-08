<?php
class ControllerExtensionModuleSpecial extends Controller
{
	public function index($setting)
	{
		$this->load->language('extension/module/special');
		$this->load->model('catalog/product');
		$this->load->model('product/helper');

		$limit = !empty($setting['limit']) ? (int) $setting['limit'] : 4;
		$results = $this->model_catalog_product->getProductSpecials([
			'sort' => 'p.sort_order',
			'order' => 'ASC',
			'start' => 0,
			'limit' => $limit,
			'stock_priority' => true,
		]);
		$data = $this->model_product_helper->buildProductModule(
			$results,
			$setting,
			$this->language->get('heading_title'),
			'special',
		);

		if (!$data) {
			return '';
		}

		if (!empty($data['slider'])) {
			$this->document->addScript('catalog/view/theme/default/javascript/embla-carousel.js');
		}

		return $this->load->view('extension/module/special', $data);
	}
}
