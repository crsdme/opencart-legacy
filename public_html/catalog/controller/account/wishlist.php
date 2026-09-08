<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerAccountWishList extends Controller
{
	public function index()
	{
		if (!$this->customer->isLogged() && !$this->guestWishlistEnabled()) {
			$this->session->data['redirect'] = $this->url->link('account/wishlist', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('account/wishlist');
		$this->load->model('account/wishlist');
		$this->load->model('catalog/product');
		$this->load->model('product/helper');

		if (isset($this->request->get['remove'])) {
			$this->removeProduct($this->request->get['remove']);

			$this->session->data['success'] = $this->language->get('text_remove');

			$this->response->redirect($this->url->link('account/wishlist'));
		}

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,follow');

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home'),
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true),
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/wishlist'),
		];

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['products'] = [];

		foreach ($this->getProductIds() as $product_id) {
			$product_info = $this->model_catalog_product->getProduct($product_id);

			if (!$product_info) {
				$this->removeProduct($product_id);
				continue;
			}

			$product = $this->model_product_helper->prepareProduct(
				$product_info,
				$this->url->link('product/product', 'product_id=' . $product_info['product_id']),
			);

			$product['remove'] = $this->url->link('account/wishlist', 'remove=' . $product_info['product_id']);

			$data['products'][] = $product;
		}

		$data['continue'] = $this->customer->isLogged()
			? $this->url->link('account/account', '', true)
			: $this->url->link('common/home');
		$data['view'] = 'account/wishlist';

		$this->response->setOutput($this->load->controller('common/layout', $data));
	}

	public function add()
	{
		$this->load->language('account/wishlist');

		$json = [];

		if (isset($this->request->post['product_id'])) {
			$product_id = (int) $this->request->post['product_id'];
		} else {
			$product_id = 0;
		}

		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if (!$product_info) {
			$json['error'] = true;
			$json['title'] = $this->language->get('text_error');

			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));

			return;
		}

		if ($this->customer->isLogged()) {
			$this->load->model('account/wishlist');
			$this->model_account_wishlist->addWishlist($product_id);

			$json['title'] = $this->language->get('text_added');
			$json['href'] = $this->url->link('account/wishlist');
			$json['action_text'] = $this->language->get('button_view');
			$json['logged'] = true;
			$json['success'] = sprintf(
				$this->language->get('text_success'),
				$this->url->link('product/product', 'product_id=' . $product_id),
				$product_info['name'],
				$this->url->link('account/wishlist'),
			);
			$json['total'] = sprintf(
				$this->language->get('text_wishlist'),
				$this->model_account_wishlist->getTotalWishlist(),
			);
		} else {
			$this->addSessionProduct($product_id);

			$guest_allowed = $this->guestWishlistEnabled();

			$json['title'] = $guest_allowed
				? $this->language->get('text_added')
				: $this->language->get('text_login_required');
			$json['href'] = $guest_allowed
				? $this->url->link('account/wishlist')
				: $this->url->link('account/login', '', true);
			$json['action_text'] = $guest_allowed
				? $this->language->get('button_view')
				: $this->language->get('button_login');
			$json['logged'] = false;
			$json['success'] = sprintf(
				$this->language->get('text_login'),
				$this->url->link('account/login', '', true),
				$this->url->link('account/register', '', true),
				$this->url->link('product/product', 'product_id=' . $product_id),
				$product_info['name'],
				$this->url->link('account/wishlist'),
			);
			$json['total'] = sprintf($this->language->get('text_wishlist'), count($this->getSessionProductIds()));
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function guestWishlistEnabled()
	{
		return (bool) (int) $this->config->get('theme_default_guest_wishlist');
	}

	private function getProductIds()
	{
		if ($this->customer->isLogged()) {
			return array_column($this->model_account_wishlist->getWishlist(), 'product_id');
		}

		return $this->getSessionProductIds();
	}

	private function getSessionProductIds()
	{
		$ids = $this->session->data['wishlist'] ?? [];

		if (!is_array($ids)) {
			$ids = [];
		}

		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		$this->session->data['wishlist'] = $ids;

		return $ids;
	}

	private function addSessionProduct($product_id)
	{
		$ids = $this->getSessionProductIds();
		$ids[] = (int) $product_id;
		$this->session->data['wishlist'] = array_values(array_unique($ids));
	}

	private function removeProduct($product_id)
	{
		$product_id = (int) $product_id;

		if ($this->customer->isLogged()) {
			$this->model_account_wishlist->deleteWishlist($product_id);

			return;
		}

		$ids = array_values(
			array_filter($this->getSessionProductIds(), function ($id) use ($product_id) {
				return (int) $id !== $product_id;
			}),
		);

		$this->session->data['wishlist'] = $ids;
	}
}
