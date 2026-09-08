<?php

class ControllerExtensionAnalyticsGoogle extends Controller
{
	public function index()
	{
		$this->load->model('extension/analytics/google');

		if (!$this->model_extension_analytics_google->isActive()) {
			return '';
		}

		return $this->load->view('extension/analytics/google', [
			'events_json' => $this->model_extension_analytics_google->encode(
				$this->model_extension_analytics_google->eventsMap(),
			),
		]);
	}

	public function layoutAfter(&$route, &$data, &$output)
	{
		$this->load->model('extension/analytics/google');

		if (!$this->model_extension_analytics_google->isActive()) {
			return;
		}

		$page_route = isset($this->request->get['route'])
			? (string) $this->request->get['route']
			: $this->config->get('action_default');

		$script = $this->pageEventScript($page_route, $data);

		if ($script === '') {
			return;
		}

		$output = str_replace('</body>', $script . '</body>', $output);
	}

	public function cartModalAfter(&$route, &$data, &$output)
	{
		$this->load->model('extension/analytics/google');

		if (!$this->model_extension_analytics_google->eventEnabled('view_cart')) {
			return;
		}

		$json = $this->model_extension_analytics_google->encode(
			$this->model_extension_analytics_google->cartPayload(),
		);

		$output .= '<script type="application/json" id="cart-ecommerce">' . $json . '</script>';
	}

	public function cartAddAfter(&$route, &$data, &$output)
	{
		$this->load->model('extension/analytics/google');

		if (!$this->model_extension_analytics_google->eventEnabled('add_to_cart')) {
			return;
		}

		$json = json_decode((string) $this->response->getOutput(), true);

		if (!is_array($json) || !empty($json['error'])) {
			return;
		}

		$payload = $this->model_extension_analytics_google->addToCartPayload(
			(int) ($this->request->post['product_id'] ?? 0),
			(int) ($this->request->post['quantity'] ?? 1),
		);

		if (!$payload) {
			return;
		}

		$json['ecommerce'] = $payload;
		$this->response->setOutput(json_encode($json));
	}

	public function cartRemoveBefore(&$route, &$data)
	{
		$this->load->model('extension/analytics/google');

		if (!$this->model_extension_analytics_google->eventEnabled('remove_from_cart')) {
			return;
		}

		$key = isset($this->request->post['key']) ? (string) $this->request->post['key'] : '';

		if ($key === '') {
			return;
		}

		foreach ($this->cart->getProducts() as $product) {
			if ((string) $product['cart_id'] === $key) {
				$this->session->data['analytics_removed_product'] = $product;
				return;
			}
		}
	}

	public function cartRemoveAfter(&$route, &$data, &$output)
	{
		$this->load->model('extension/analytics/google');

		if (!$this->model_extension_analytics_google->eventEnabled('remove_from_cart')) {
			unset($this->session->data['analytics_removed_product']);
			return;
		}

		$json = json_decode((string) $this->response->getOutput(), true);

		if (!is_array($json) || empty($this->session->data['analytics_removed_product'])) {
			unset($this->session->data['analytics_removed_product']);
			return;
		}

		$product = $this->session->data['analytics_removed_product'];
		unset($this->session->data['analytics_removed_product']);

		$item = $this->model_extension_analytics_google->itemFromProduct($product, $product['quantity']);
		$json['ecommerce'] = $this->model_extension_analytics_google->payloadFromItems([$item]);
		$this->response->setOutput(json_encode($json));
	}

	private function pageEventScript($page_route, $data)
	{
		$model = $this->model_extension_analytics_google;

		if ($page_route === 'product/product' && $model->eventEnabled('view_item')) {
			$product_id = (int) ($this->request->get['product_id'] ?? 0);
			$this->load->model('catalog/product');
			$product = $this->model_catalog_product->getProduct($product_id);

			if (!$product) {
				return '';
			}

			$item = $model->itemFromProduct($product, 1);

			if (!empty($data['heading_title'])) {
				$item['item_name'] = (string) $data['heading_title'];
			}

			return $model->eventScript('view_item', $model->payloadFromItems([$item]));
		}

		if (in_array($page_route, ['product/category', 'product/manufacturer/info', 'product/special'], true)
			&& $model->eventEnabled('view_item_list')
		) {
			return $this->itemListScript($data, $page_route);
		}

		if ($page_route === 'product/search') {
			$script = '';

			if ($model->eventEnabled('view_search_results')) {
				$term = (string) ($this->request->get['search'] ?? $this->request->get['query'] ?? $this->request->get['tag'] ?? '');
				$script .= $model->eventScript('view_search_results', ['search_term' => $term]);
			}

			if ($model->eventEnabled('view_item_list')) {
				$script .= $this->itemListScript($data, $page_route);
			}

			return $script;
		}

		if ($page_route === 'checkout/checkout' && $model->eventEnabled('begin_checkout')) {
			return $model->eventScript('begin_checkout', $model->cartPayload());
		}

		if ($page_route === 'checkout/success' && $model->eventEnabled('purchase')) {
			$order_id = (int) ($this->session->data['last_order_id'] ?? 0);

			if (!$order_id) {
				return '';
			}

			$sent_key = 'analytics_google_purchase_' . $order_id;

			if (!empty($this->session->data[$sent_key])) {
				return '';
			}

			$payload = $model->purchasePayload($order_id);

			if (!$payload) {
				return '';
			}

			$this->session->data[$sent_key] = 1;

			return $model->eventScript('purchase', $payload);
		}

		return '';
	}

	private function itemListScript($data, $page_route)
	{
		$products = $data['products'] ?? [];

		if (!$products) {
			return '';
		}

		$items = [];
		$index = 0;

		foreach ($products as $product) {
			if (empty($product['product_id'])) {
				continue;
			}

			$items[] = [
				'item_id' => (string) $product['product_id'],
				'item_name' => (string) ($product['name'] ?? ''),
				'index' => $index,
				'quantity' => 1,
			];
			$index++;
		}

		if (!$items) {
			return '';
		}

		$list_name = (string) ($data['heading_title'] ?? $page_route);

		return $this->model_extension_analytics_google->eventScript(
			'view_item_list',
			$this->model_extension_analytics_google->payloadFromItems($items, [
				'item_list_id' => $page_route,
				'item_list_name' => $list_name,
			]),
		);
	}
}
