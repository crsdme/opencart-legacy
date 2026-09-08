<?php
class ModelProductHelper extends Model
{
	private const PRODUCT_LIMIT = 15;
	private const DESCRIPTION_LENGTH = 100;
	private const IMAGE_SIZES = [
		'category' => [80, 80],
		'manufacturer' => [80, 80],
		'thumb' => [710, 710],
		'popup' => [710, 710],
		'product' => [270, 270],
		'additional' => [74, 74],
		'related' => [200, 200],
		'compare' => [270, 270],
		'wishlist' => [47, 47],
		'cart' => [160, 160],
		'location' => [268, 50],
		'author' => [80, 80],
		'author_page' => [160, 160],
	];

	public function themeLimit(): int
	{
		return self::PRODUCT_LIMIT;
	}

	public function themeDescriptionLength(): int
	{
		return self::DESCRIPTION_LENGTH;
	}

	public function themeImageSize(string $type): array
	{
		return self::IMAGE_SIZES[$type] ?? self::IMAGE_SIZES['product'];
	}

	public function buildUrl(array $get, array $allowed = [], array $encode = []): string
	{
		$url = '';

		foreach ($allowed as $key) {
			if (!isset($get[$key])) {
				continue;
			}

			$value = $get[$key];

			if (in_array($key, $encode, true)) {
				$value = urlencode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
			}

			$url .= '&' . $key . '=' . $value;
		}

		return $url;
	}

	public function getCatalogParams(array $get): array
	{
		return [
			'filter' => $get['filter'] ?? '',
			'sort' => $get['sort'] ?? 'p.sort_order',
			'order' => $get['order'] ?? 'ASC',
			'page' => isset($get['page']) ? (int) $get['page'] : 1,
			'limit' => isset($get['limit']) ? (int) $get['limit'] : $this->themeLimit(),
		];
	}

	public function applyNoindexByParams(array $get, array $keys): void
	{
		if (!$this->config->get('config_noindex_status')) {
			return;
		}

		$disallow_params = [];

		if ($this->config->get('config_noindex_disallow_params')) {
			$disallow_params = explode("\r\n", $this->config->get('config_noindex_disallow_params'));
		}

		foreach ($keys as $key) {
			if (isset($get[$key]) && !in_array($key, $disallow_params, true)) {
				$this->document->setRobots('noindex,follow');
				return;
			}
		}
	}

	public function themeImage($image, string $type, bool $placeholder = true): string
	{
		if (empty($image)) {
			if (!$placeholder) {
				return '';
			}

			$image = 'placeholder.png';
		}

		$this->load->model('tool/image');

		[$width, $height] = $this->themeImageSize($type);

		$resized = $this->model_tool_image->resize($image, $width, $height);

		if ($resized) {
			return $resized;
		}

		if ($placeholder && $image !== 'placeholder.png') {
			$resized = $this->model_tool_image->resize('placeholder.png', $width, $height);
		}

		return $resized ?: '';
	}

	public function formatPrice($price, $tax_class_id, $tax = null)
	{
		if (!$this->customer->isLogged() && $this->config->get('config_customer_price')) {
			return false;
		}

		if ($tax === null) {
			$tax = $this->config->get('config_tax');
		}

		return $this->currency->format(
			$this->tax->calculate($price, $tax_class_id, $tax),
			$this->session->data['currency'],
		);
	}

	public function formatTax($amount)
	{
		if (!$this->config->get('config_tax')) {
			return false;
		}

		return $this->currency->format($amount, $this->session->data['currency']);
	}

	public function prepareProduct(array $product, string $href, string $image_type = 'product'): array
	{
		$description_length = $this->themeDescriptionLength();

		if (!is_null($product['special']) && (float) $product['special'] >= 0) {
			$special = $this->formatPrice($product['special'], $product['tax_class_id']);
			$tax_price = (float) $product['special'];
		} else {
			$special = false;
			$tax_price = (float) $product['price'];
		}

		return [
			'product_id' => $product['product_id'],
			'thumb' => $this->themeImage($product['image'] ?? '', $image_type),
			'name' => $product['name'],
			'description' =>
				utf8_substr(
					trim(strip_tags(html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8'))),
					0,
					$description_length,
				) . '..',
			'price' => $this->formatPrice($product['price'], $product['tax_class_id']),
			'special' => $special,
			'tax' => $this->formatTax($tax_price),
			'quantity' => $product['quantity'] ?? 0,
			'minimum' => $product['minimum'] > 0 ? $product['minimum'] : 1,
			'rating' => $this->config->get('config_review_status') ? (int) $product['rating'] : false,
			'href' => $href,
		];
	}

	public function getModuleHeading(array $setting, string $default = ''): string
	{
		$language_id = (int) $this->config->get('config_language_id');
		$title = '';

		if (!empty($setting['module_description'][$language_id]['title'])) {
			$title = html_entity_decode($setting['module_description'][$language_id]['title'], ENT_QUOTES, 'UTF-8');
		}

		$title = trim($title);

		return $title !== '' ? $title : $default;
	}

	public function prepareModuleProduct(array $product, array $setting = []): array
	{
		return $this->prepareProduct(
			$product,
			$this->url->link('product/product', 'product_id=' . $product['product_id']),
		);
	}

	public function buildProductModule(array $results, array $setting, string $heading_default, string $prefix): array
	{
		$products = [];

		foreach ($results as $result) {
			if (empty($result['product_id'])) {
				continue;
			}

			$products[] = $this->prepareModuleProduct($result, $setting);
		}

		$limit = !empty($setting['limit']) ? (int) $setting['limit'] : 4;

		if ($limit > 0) {
			$products = array_slice($products, 0, $limit);
		}

		if (!$products) {
			return [];
		}

		static $module = 0;

		$id = $prefix . '-' . $module++;

		return [
			'heading_title' => $this->getModuleHeading($setting, $heading_default),
			'heading_id' => $id,
			'products' => $products,
			'slider' => count($products) > 4,
			'slider_config' => $this->getSliderConfig($setting, $id),
		];
	}

	public function getSliderConfig(array $setting, string $id): array
	{
		$use_autoplay = $this->settingEnabled($setting, 'use_autoplay', false);
		$use_controls = $this->settingEnabled($setting, 'use_controls', true);
		$use_loop = $this->settingEnabled($setting, 'use_loop', true);
		$gap = $this->settingInt($setting, 'gap', 16, 0);

		return [
			'id' => $id,
			'use_autoplay' => $use_autoplay ? 1 : 0,
			'use_controls' => $use_controls ? 1 : 0,
			'use_loop' => $use_loop ? 1 : 0,
			'use_autoplay_mobile' => $this->settingEnabled($setting, 'use_autoplay_mobile', $use_autoplay) ? 1 : 0,
			'use_controls_mobile' => $this->settingEnabled($setting, 'use_controls_mobile', $use_controls) ? 1 : 0,
			'use_loop_mobile' => $this->settingEnabled($setting, 'use_loop_mobile', $use_loop) ? 1 : 0,
			'gap' => $gap,
			'gap_mobile' => $this->settingInt($setting, 'gap_mobile', $gap, 0),
			'breakpoint' => $this->settingInt($setting, 'breakpoint', 1024, 1),
			'autoplay_delay' => $this->settingInt($setting, 'autoplay_delay', 3, 1),
		];
	}

	private function settingEnabled(array $setting, string $key, bool $default): bool
	{
		if (!isset($setting[$key]) || $setting[$key] === '') {
			return $default;
		}

		return (bool) (int) $setting[$key];
	}

	private function settingInt(array $setting, string $key, int $default, int $min): int
	{
		if (!isset($setting[$key]) || $setting[$key] === '') {
			return $default;
		}

		return max($min, (int) $setting[$key]);
	}

	public function getSorts(string $route, string $base_query, string $url = '', string $price_sort = 'p.price'): array
	{
		$sorts = [
			[
				'text' => $this->language->get('text_default'),
				'value' => 'p.sort_order-ASC',
				'sort' => 'p.sort_order',
				'order' => 'ASC',
			],
			[
				'text' => $this->language->get('text_name_asc'),
				'value' => 'pd.name-ASC',
				'sort' => 'pd.name',
				'order' => 'ASC',
			],
			[
				'text' => $this->language->get('text_name_desc'),
				'value' => 'pd.name-DESC',
				'sort' => 'pd.name',
				'order' => 'DESC',
			],
			[
				'text' => $this->language->get('text_price_asc'),
				'value' => $price_sort . '-ASC',
				'sort' => $price_sort,
				'order' => 'ASC',
			],
			[
				'text' => $this->language->get('text_price_desc'),
				'value' => $price_sort . '-DESC',
				'sort' => $price_sort,
				'order' => 'DESC',
			],
		];

		// if ($this->config->get('config_review_status')) {
		//   $sorts[] = [
		//     'text' => $this->language->get('text_rating_desc'),
		//     'value' => 'rating-DESC',
		//     'sort' => 'rating',
		//     'order' => 'DESC',
		//   ];

		//   $sorts[] = [
		//     'text' => $this->language->get('text_rating_asc'),
		//     'value' => 'rating-ASC',
		//     'sort' => 'rating',
		//     'order' => 'ASC',
		//   ];
		// }

		// $sorts[] = [
		//   'text' => $this->language->get('text_model_asc'),
		//   'value' => 'p.model-ASC',
		//   'sort' => 'p.model',
		//   'order' => 'ASC',
		// ];

		// $sorts[] = [
		//   'text' => $this->language->get('text_model_desc'),
		//   'value' => 'p.model-DESC',
		//   'sort' => 'p.model',
		//   'order' => 'DESC',
		// ];

		foreach ($sorts as &$sort) {
			$sort['href'] = $this->url->link(
				$route,
				$base_query . '&sort=' . $sort['sort'] . '&order=' . $sort['order'] . $url,
			);

			unset($sort['sort'], $sort['order']);
		}

		unset($sort);

		return $sorts;
	}

	public function getLimits(string $route, string $base_query, string $url = ''): array
	{
		$limits = array_unique([$this->themeLimit(), 25, 50, 75, 100]);

		sort($limits);

		$data = [];

		foreach ($limits as $value) {
			$data[] = [
				'text' => $value,
				'value' => $value,
				'href' => $this->url->link($route, $base_query . $url . '&limit=' . $value),
			];
		}

		return $data;
	}

	public function addPaginationLinks(string $route, string $base_query, int $page, int $limit, int $total): void
	{
		if (!$this->config->get('config_canonical_method')) {
			if ($page == 1) {
				$this->document->addLink($this->url->link($route, $base_query), 'canonical');
			} elseif ($page == 2) {
				$this->document->addLink($this->url->link($route, $base_query), 'prev');
			} else {
				$this->document->addLink($this->url->link($route, $base_query . '&page=' . ($page - 1)), 'prev');
			}

			if ($limit && ceil($total / $limit) > $page) {
				$this->document->addLink($this->url->link($route, $base_query . '&page=' . ($page + 1)), 'next');
			}

			return;
		}

		$server =
			isset($this->request->server['HTTPS']) &&
			($this->request->server['HTTPS'] == 'on' || $this->request->server['HTTPS'] == '1')
				? $this->config->get('config_ssl')
				: $this->config->get('config_url');

		$request_url = rtrim($server, '/') . $this->request->server['REQUEST_URI'];
		$canonical_url = $this->url->link($route, $base_query);

		if ($request_url != $canonical_url || $this->config->get('config_canonical_self')) {
			$this->document->addLink($canonical_url, 'canonical');
		}

		if (!$this->config->get('config_add_prevnext')) {
			return;
		}

		if ($page == 2) {
			$this->document->addLink($this->url->link($route, $base_query), 'prev');
		} elseif ($page > 2) {
			$this->document->addLink($this->url->link($route, $base_query . '&page=' . ($page - 1)), 'prev');
		}

		if ($limit && ceil($total / $limit) > $page) {
			$this->document->addLink($this->url->link($route, $base_query . '&page=' . ($page + 1)), 'next');
		}
	}

	public function plural(int $number, string $one, string $few, string $many): string
	{
		$number = abs($number);
		$last = $number % 10;
		$last_two = $number % 100;

		if ($last_two >= 11 && $last_two <= 14) {
			return sprintf($many, $number);
		}

		if ($last == 1) {
			return sprintf($one, $number);
		}

		if ($last >= 2 && $last <= 4) {
			return sprintf($few, $number);
		}

		return sprintf($many, $number);
	}
}
