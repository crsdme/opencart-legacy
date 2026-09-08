<?php
class ControllerExtensionModuleFeatured extends Controller
{
	public function index($setting)
	{
		$this->load->language('extension/module/featured');
		$this->load->model('catalog/product');
		$this->load->model('product/helper');

		$results = $this->model_catalog_product->getModuleProducts([
			'product_id' => $setting['product'] ?? [],
			'category_id' => $setting['category'] ?? [],
			'limit' => !empty($setting['limit']) ? (int) $setting['limit'] : 4,
		]);

		$data = $this->model_product_helper->buildProductModule(
			$results,
			$setting,
			$this->language->get('heading_title'),
			'featured',
		);

		if (!$data) {
			return '';
		}

		if (!empty($data['slider'])) {
			$this->document->addScript('catalog/view/theme/default/javascript/embla-carousel.js');
		}

		return $this->load->view('extension/module/featured', $data);
	}
}
