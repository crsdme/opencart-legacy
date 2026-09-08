<?php
class ControllerExtensionModuleLatest extends Controller
{
	public function index($setting)
	{
		$this->load->language('extension/module/latest');
		$this->load->model('catalog/product');
		$this->load->model('product/helper');

		$limit = !empty($setting['limit']) ? (int) $setting['limit'] : 4;
		$results = $this->model_catalog_product->getLatestProducts($limit, true);
		$data = $this->model_product_helper->buildProductModule(
			$results,
			$setting,
			$this->language->get('heading_title'),
			'latest',
		);

		if (!$data) {
			return '';
		}

		if (!empty($data['slider'])) {
			$this->document->addScript('catalog/view/theme/default/javascript/embla-carousel.js');
		}

		return $this->load->view('extension/module/latest', $data);
	}
}
