<?php
class ControllerProductProduct extends Controller
{
	private const QUERY_KEYS = [
		'path',
		'filter',
		'manufacturer_id',
		'search',
		'tag',
		'description',
		'category_id',
		'sub_category',
		'sort',
		'order',
		'page',
		'limit',
	];

	public function index()
	{
		$this->load->language('product/product');
		$this->load->model('catalog/product');
		$this->load->model('catalog/category');
		$this->load->model('catalog/manufacturer');
		$this->load->model('tool/image');
		$this->load->model('product/helper');
		$this->load->model('seo/meta');

		$product_id = isset($this->request->get['product_id']) ? (int) $this->request->get['product_id'] : 0;
		$product_info = $this->model_catalog_product->getProduct($product_id);

		if (!$product_info) {
			$this->notFound($product_id);
			return;
		}

		$this->document->addScript('catalog/view/theme/default/javascript/product.js', 'footer');

		$category_info = $this->getPathCategory();
		$category_name = $this->getCategoryName($product_id, $category_info);
		$minimum = $product_info['minimum'] > 0 ? (int) $product_info['minimum'] : 1;
		$images = $this->getImages($product_info);
		$stock = $this->getStock($product_info);

		$data['breadcrumbs'] = $this->getBreadcrumbs($product_info, $category_info);
		$data['product_id'] = $product_id;
		$data['manufacturer'] = $product_info['manufacturer'];
		$data['manufacturers'] = $product_info['manufacturer_id']
			? $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $product_info['manufacturer_id'])
			: '';
		$data['model'] = $product_info['model'];
		$data['reward'] = $product_info['reward'];
		$data['points'] = $product_info['points'];
		$data['description'] = html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8');
		$data['minimum'] = $minimum;
		$data['text_minimum'] = sprintf($this->language->get('text_minimum'), $minimum);
		$data['text_login'] = sprintf(
			$this->language->get('text_login'),
			$this->url->link('account/login', '', true),
			$this->url->link('account/register', '', true),
		);
		$data['stock'] = $stock['status'];
		$data['in_stock'] = $stock['in_stock'];
		$data['quantity'] = $stock['quantity'];
		$data['thumb'] = $images['thumb'];
		$data['popup'] = $images['popup'];
		$data['gallery'] = $images['gallery'];
		$data['price'] = $this->model_product_helper->formatPrice(
			$product_info['price'],
			$product_info['tax_class_id'],
		);

		if (!is_null($product_info['special']) && (float) $product_info['special'] >= 0) {
			$data['special'] = $this->model_product_helper->formatPrice(
				$product_info['special'],
				$product_info['tax_class_id'],
			);
			$tax_price = (float) $product_info['special'];
		} else {
			$data['special'] = false;
			$tax_price = (float) $product_info['price'];
		}

		$data['tax'] = $this->model_product_helper->formatTax($tax_price);
		$data['discounts'] = $this->getDiscounts($product_id, $product_info['tax_class_id']);
		$data['options'] = $this->getOptions($product_id, $product_info['tax_class_id']);
		$data['recurrings'] = $this->model_catalog_product->getProfiles($product_id);
		$data['attribute_groups'] = $this->model_catalog_product->getProductAttributes($product_id);
		$data['products'] = $this->getRelated($product_id);
		$data['tags'] = $this->getTags($product_info['tag']);
		$data['share'] = $this->url->link('product/product', 'product_id=' . $product_id);
		$data['review_status'] = $this->config->get('config_review_status');
		$data['review_guest'] = $this->config->get('config_review_guest') || $this->customer->isLogged();
		$data['customer_name'] = $this->customer->isLogged()
			? $this->customer->getFirstName() . ' ' . $this->customer->getLastName()
			: '';
		$data['reviews'] = sprintf($this->language->get('text_reviews'), (int) $product_info['reviews']);
		$data['rating'] = (int) $product_info['rating'];
		$data['tab_review'] = sprintf($this->language->get('tab_review'), $product_info['reviews']);
		$data['review_url'] = $this->url->link('product/product/review', 'product_id=' . $product_id);
		$data['write_url'] = 'index.php?route=product/product/write&product_id=' . $product_id;
		$data['captcha'] = $this->getReviewCaptcha();

		$meta_price = $data['special'] ?: $data['price'] ?: '';

		$seo = $this->model_seo_meta->build(
			$product_info,
			[
				'name' => $product_info['name'],
				'model' => $product_info['model'],
				'sku' => $product_info['sku'],
				'manufacturer' => $product_info['manufacturer'] ?? '',
				'category' => $category_name,
				'price' => is_string($meta_price) ? $meta_price : '',
			],
			'product',
			'product/product',
			'product_id=' . $product_id,
		);

		$this->model_seo_meta->apply($seo);
		$data['heading_title'] = $seo['h1'];

		$this->load->model('seo/faq');
		$data['faq'] = $this->model_seo_faq->attach($data, 'product', $product_id, $product_info, [
			'heading_title' => $data['heading_title'],
			'price' => $data['special'] ?: $data['price'] ?: '',
			'category_name' => $category_name,
		]);

		$this->model_catalog_product->updateViewed($product_id);

		$data['view'] = 'product/product';
		$this->response->setOutput($this->load->controller('common/layout', $data));
	}

	public function review()
	{
		$this->load->language('product/product');
		$this->load->model('catalog/review');

		$product_id = isset($this->request->get['product_id']) ? (int) $this->request->get['product_id'] : 0;
		$page = isset($this->request->get['page']) ? (int) $this->request->get['page'] : 1;
		$limit = 5;

		$review_total = $this->model_catalog_review->getTotalReviewsByProductId($product_id);
		$results = $this->model_catalog_review->getReviewsByProductId($product_id, ($page - 1) * $limit, $limit);

		$data['reviews'] = [];

		foreach ($results as $result) {
			$data['reviews'][] = [
				'author' => $result['author'],
				'text' => nl2br($result['text']),
				'rating' => (int) $result['rating'],
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
			];
		}

		$data['pagination_data'] = [
			'total' => $review_total,
			'page' => $page,
			'limit' => $limit,
			'text_prev' => $this->language->get('text_prev'),
			'text_next' => $this->language->get('text_next'),
			'url' => $this->url->link('product/product/review', 'product_id=' . $product_id . '&page={page}'),
		];

		$this->response->setOutput($this->load->view('product/review', $data));
	}

	public function write()
	{
		$this->load->language('product/product');

		$json = [];

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			if (utf8_strlen($this->request->post['name']) < 3 || utf8_strlen($this->request->post['name']) > 25) {
				$json['error'] = $this->language->get('error_name');
			}

			if (utf8_strlen($this->request->post['text']) < 25 || utf8_strlen($this->request->post['text']) > 1000) {
				$json['error'] = $this->language->get('error_text');
			}

			if (
				empty($this->request->post['rating']) ||
				$this->request->post['rating'] < 0 ||
				$this->request->post['rating'] > 5
			) {
				$json['error'] = $this->language->get('error_rating');
			}

			if (
				$this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') &&
				in_array('review', (array) $this->config->get('config_captcha_page'))
			) {
				$captcha = $this->load->controller(
					'extension/captcha/' . $this->config->get('config_captcha') . '/validate',
				);

				if ($captcha) {
					$json['error'] = $captcha;
				}
			}

			if (!isset($json['error'])) {
				$this->load->model('catalog/review');
				$this->model_catalog_review->addReview($this->request->get['product_id'], $this->request->post);
				$json['success'] = $this->language->get('text_success');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getRecurringDescription()
	{
		$this->load->language('product/product');
		$this->load->model('catalog/product');
		$this->load->model('product/helper');

		$product_id = isset($this->request->post['product_id']) ? (int) $this->request->post['product_id'] : 0;
		$recurring_id = isset($this->request->post['recurring_id']) ? (int) $this->request->post['recurring_id'] : 0;
		$quantity = isset($this->request->post['quantity']) ? (int) $this->request->post['quantity'] : 1;

		$product_info = $this->model_catalog_product->getProduct($product_id);
		$recurring_info = $this->model_catalog_product->getProfile($product_id, $recurring_id);

		$json = [];

		if ($product_info && $recurring_info) {
			$frequencies = [
				'day' => $this->language->get('text_day'),
				'week' => $this->language->get('text_week'),
				'semi_month' => $this->language->get('text_semi_month'),
				'month' => $this->language->get('text_month'),
				'year' => $this->language->get('text_year'),
			];

			$trial_text = '';

			if ($recurring_info['trial_status'] == 1) {
				$price = $this->model_product_helper->formatPrice(
					$recurring_info['trial_price'] * $quantity,
					$product_info['tax_class_id'],
				);
				$trial_text =
					sprintf(
						$this->language->get('text_trial_description'),
						$price,
						$recurring_info['trial_cycle'],
						$frequencies[$recurring_info['trial_frequency']],
						$recurring_info['trial_duration'],
					) . ' ';
			}

			$price = $this->model_product_helper->formatPrice(
				$recurring_info['price'] * $quantity,
				$product_info['tax_class_id'],
			);

			$key = $recurring_info['duration'] ? 'text_payment_description' : 'text_payment_cancel';

			$json['success'] =
				$trial_text .
				sprintf(
					$this->language->get($key),
					$price,
					$recurring_info['cycle'],
					$frequencies[$recurring_info['frequency']],
					$recurring_info['duration'],
				);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function getBreadcrumbs(array $product_info, array $category_info): array
	{
		$breadcrumbs = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home'),
			],
		];

		$path_param = $this->request->get['path'] ?? '';

		if ($path_param) {
			$path = '';
			$parts = explode('_', (string) $path_param);
			array_pop($parts);

			foreach ($parts as $path_id) {
				$path = $path ? $path . '_' . (int) $path_id : (string) (int) $path_id;
				$info = $this->model_catalog_category->getCategory($path_id);

				if ($info) {
					$breadcrumbs[] = [
						'text' => $info['name'],
						'href' => $this->url->link('product/category', 'path=' . $path),
					];
				}
			}

			if ($category_info) {
				$breadcrumbs[] = [
					'text' => $category_info['name'],
					'href' => $this->url->link(
						'product/category',
						'path=' . $path_param . $this->queryUrl(['sort', 'order', 'page', 'limit']),
					),
				];
			}
		}

		if (isset($this->request->get['manufacturer_id'])) {
			$breadcrumbs[] = [
				'text' => $this->language->get('text_brand'),
				'href' => $this->url->link('product/manufacturer'),
			];

			$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer(
				$this->request->get['manufacturer_id'],
			);

			if ($manufacturer_info) {
				$breadcrumbs[] = [
					'text' => $manufacturer_info['name'],
					'href' => $this->url->link(
						'product/manufacturer/info',
						'manufacturer_id=' .
							$this->request->get['manufacturer_id'] .
							$this->queryUrl(['sort', 'order', 'page', 'limit']),
					),
				];
			}
		}

		if (isset($this->request->get['search']) || isset($this->request->get['tag'])) {
			$breadcrumbs[] = [
				'text' => $this->language->get('text_search'),
				'href' => $this->url->link(
					'product/search',
					$this->queryUrl([
						'search',
						'tag',
						'description',
						'category_id',
						'sub_category',
						'sort',
						'order',
						'page',
						'limit',
					]),
				),
			];
		}

		$breadcrumbs[] = [
			'text' => $product_info['name'],
			'href' => $this->url->link(
				'product/product',
				$this->queryUrl(self::QUERY_KEYS) . '&product_id=' . $product_info['product_id'],
			),
		];

		return $breadcrumbs;
	}

	private function getPathCategory(): array
	{
		if (empty($this->request->get['path'])) {
			return [];
		}

		$parts = explode('_', (string) $this->request->get['path']);
		$category_id = (int) array_pop($parts);
		$category_info = $this->model_catalog_category->getCategory($category_id);

		return $category_info ?: [];
	}

	private function getCategoryName(int $product_id, array $category_info): string
	{
		if (!empty($category_info['name'])) {
			return $category_info['name'];
		}

		$product_categories = $this->model_catalog_product->getCategories($product_id);

		if (!$product_categories) {
			return '';
		}

		$first_category = $this->model_catalog_category->getCategory((int) $product_categories[0]['category_id']);

		return $first_category['name'] ?? '';
	}

	private function getStock(array $product_info): array
	{
		$quantity = (int) $product_info['quantity'];

		if ($quantity <= 0) {
			return [
				'status' => $product_info['stock_status'],
				'in_stock' => false,
				'quantity' => $quantity,
			];
		}

		return [
			'status' => $this->config->get('config_stock_display')
				? (string) $quantity
				: $this->language->get('text_instock'),
			'in_stock' => true,
			'quantity' => $quantity,
		];
	}

	private function getImages(array $product_info): array
	{
		$product_id = (int) $product_info['product_id'];
		$gallery = [];

		if (!empty($product_info['image'])) {
			$gallery[] = $this->buildGalleryImage($product_info['image']);
		}

		foreach ($this->model_catalog_product->getProductImages($product_id) as $result) {
			$gallery[] = $this->buildGalleryImage($result['image']);
		}

		return [
			'thumb' => $gallery[0]['preview'] ?? '',
			'popup' => $gallery[0]['popup'] ?? '',
			'gallery' => $gallery,
		];
	}

	private function buildGalleryImage(string $image): array
	{
		return [
			'thumb' => $this->model_product_helper->themeImage($image, 'additional'),
			'preview' => $this->model_product_helper->themeImage($image, 'thumb'),
			'popup' => $this->model_product_helper->themeImage($image, 'popup', false),
		];
	}

	private function getDiscounts(int $product_id, $tax_class_id): array
	{
		$discounts = [];

		foreach ($this->model_catalog_product->getProductDiscounts($product_id) as $discount) {
			$discounts[] = [
				'quantity' => $discount['quantity'],
				'price' => $this->model_product_helper->formatPrice($discount['price'], $tax_class_id),
			];
		}

		return $discounts;
	}

	private function getOptions(int $product_id, $tax_class_id): array
	{
		$options = [];

		foreach ($this->model_catalog_product->getProductOptions($product_id) as $option) {
			$values = [];

			foreach ($option['product_option_value'] as $option_value) {
				if ($option_value['subtract'] && $option_value['quantity'] <= 0) {
					continue;
				}

				$values[] = [
					'product_option_value_id' => $option_value['product_option_value_id'],
					'option_value_id' => $option_value['option_value_id'],
					'name' => $option_value['name'],
					'image' => !empty($option_value['image'])
						? $this->model_tool_image->resize($option_value['image'], 50, 50)
						: '',
					'price' => (float) $option_value['price']
						? $this->model_product_helper->formatPrice(
							$option_value['price'],
							$tax_class_id,
							$this->config->get('config_tax') ? 'P' : false,
						)
						: false,
					'price_prefix' => $option_value['price_prefix'],
				];
			}

			$options[] = [
				'product_option_id' => $option['product_option_id'],
				'product_option_value' => $values,
				'option_id' => $option['option_id'],
				'name' => $option['name'],
				'type' => $option['type'],
				'value' =>
				$option['type'] === 'datetime'
					? str_replace(' ', 'T', (string) $option['value'])
					: $option['value'],
				'required' => $option['required'],
			];
		}

		return $options;
	}

	private function getRelated(int $product_id): array
	{
		$products = [];

		foreach ($this->model_catalog_product->getProductRelated($product_id) as $result) {
			$products[] = $this->model_product_helper->prepareProduct(
				$result,
				$this->url->link('product/product', 'product_id=' . $result['product_id']),
				'related',
			);
		}

		return $products;
	}

	private function getTags($tag): array
	{
		if (!$tag) {
			return [];
		}

		$tags = [];

		foreach (explode(',', $tag) as $value) {
			$value = trim($value);

			if ($value === '') {
				continue;
			}

			$tags[] = [
				'tag' => $value,
				'href' => $this->url->link('product/search', 'tag=' . $value),
			];
		}

		return $tags;
	}

	private function getReviewCaptcha(): string
	{
		if (
			$this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') &&
			in_array('review', (array) $this->config->get('config_captcha_page'))
		) {
			return $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
		}

		return '';
	}

	private function queryUrl(array $keys): string
	{
		return $this->model_product_helper->buildUrl($this->request->get, $keys);
	}

	private function notFound(int $product_id): void
	{
		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home'),
			],
			[
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link(
					'product/product',
					$this->queryUrl(self::QUERY_KEYS) . '&product_id=' . $product_id,
				),
			],
		];

		$this->document->setTitle($this->language->get('text_error'));

		$data['heading_title'] = $this->language->get('text_error');
		$data['text_error'] = $this->language->get('text_error');
		$data['continue'] = $this->url->link('common/home');

		$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

		$data['view'] = 'error/not_found';
		$this->response->setOutput($this->load->controller('common/layout', $data));
	}
}
