<?php
class ControllerExtensionModuleRecentlyViewed extends Controller
{
	public function index($setting)
	{
		$this->load->language('extension/module/recently_viewed');
		$this->load->model('catalog/product');
		$this->load->model('catalog/recently_viewed');
		$this->load->model('product/helper');

		$limit = !empty($setting['limit']) ? (int) $setting['limit'] : 4;
		$exclude_id = 0;

		if (($this->request->get['route'] ?? '') === 'product/product' && !empty($this->request->get['product_id'])) {
			$exclude_id = (int) $this->request->get['product_id'];
		}

		$ids = $this->model_catalog_recently_viewed->getIds($limit, $exclude_id);
		$results = [];

		foreach ($ids as $product_id) {
			$product = $this->model_catalog_product->getProduct($product_id);

			if ($product) {
				$results[] = $product;
			}
		}

		$data = $this->model_product_helper->buildProductModule(
			$results,
			$setting,
			$this->language->get('heading_title'),
			'recently_viewed',
		);

		if (!$data) {
			return '';
		}

		if (!empty($data['slider'])) {
			$this->document->addScript('catalog/view/theme/default/javascript/embla-carousel.js');
		}

		return $this->load->view('extension/module/recently_viewed', $data);
	}
}
