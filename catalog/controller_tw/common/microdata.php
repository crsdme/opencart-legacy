<?php
class ControllerCommonMicrodata extends Controller
{
	public function index($data)
	{
		$empty = [
			'head' => '',
			'body' => '',
		];

		if (!$this->config->get('config_microdata')) {
			return $empty;
		}

		$context = $this->getContext($data);
		$route = $this->request->get['route'] ?? 'common/home';

		$head = '';

		if ($this->isEnabled('config_microdata_opengraph', true)) {
			$head .= $this->openGraphSchema($context, $route);
		}

		if ($this->isEnabled('config_microdata_twitter', false)) {
			$head .= $this->twitterSchema($context);
		}

		$blocks = [];
		$blocks[] = $this->organizationSchema($context);
		$blocks[] = $this->websiteSchema($context);
		$blocks[] = $this->webPageSchema($context);
		$blocks[] = $this->breadcrumbsSchema($data);

		if ($route === 'common/home') {
			$blocks[] = $this->localBusinessSchema($context);
		}

		if ($route === 'product/product') {
			$blocks[] = $this->productSchema($data, $context);
		}

		if (
			in_array(
				$route,
				['product/category', 'product/manufacturer/info', 'product/special', 'product/search'],
				true,
			)
		) {
			$blocks[] = $this->productListSchema($data);
		}

		$html = '';

		foreach ($blocks as $block) {
			if (empty($block['json'])) {
				continue;
			}

			$html .= '<!-- ' . $block['comment'] . ' -->' . PHP_EOL;
			$html .= '<script type="application/ld+json">' . PHP_EOL;
			$html .= $block['json'] . PHP_EOL;
			$html .= '</script>' . PHP_EOL;
			$html .= '<!-- ' . $block['comment'] . ' END -->' . PHP_EOL;
		}

		return [
			'head' => $head,
			'body' => $html,
		];
	}

	private function isEnabled($key, $default = false)
	{
		if ($this->config->has($key)) {
			return (bool) $this->config->get($key);
		}

		return (bool) $default;
	}

	private function getContext($data)
	{
		$store_url = $this->getStoreUrl();
		$locale_code = $this->language->get('code');
		$country = $this->getCountry();
		$zone = $this->getZone();
		$geo = $this->parseGeocode();
		$logo = $this->getImageUrl($this->config->get('config_logo'));
		$image = !empty($data['popup'])
			? $data['popup']
			: (!empty($data['thumb'])
				? $data['thumb']
				: $logo);

		$title = $this->document->getTitle();
		$description = $this->plainText($this->document->getDescription());

		if ($description === '' && !empty($data['description'])) {
			$description = $this->plainText($data['description']);
		}

		return [
			'locale' => $this->getOgLocale($locale_code),
			'language' => $locale_code,
			'title' => $title,
			'description' => $description,
			'url' => $this->getPageUrl($data),
			'image' => $image,
			'store_url' => $store_url,
			'store_name' => (string) $this->config->get('config_name'),
			'store_email' => (string) $this->config->get('config_email'),
			'store_telephone' => (string) $this->config->get('config_telephone'),
			'store_address' => (string) $this->config->get('config_address'),
			'store_open' => (string) $this->config->get('config_open'),
			'logo' => $logo,
			'postal_code' => '',
			'locality' => $zone['name'] ?? '',
			'region' => $zone['name'] ?? '',
			'country_name' => $country['name'] ?? '',
			'country_code' => $country['iso_code_2'] ?? '',
			'latitude' => $geo['latitude'],
			'longitude' => $geo['longitude'],
			'same_as' => $this->getSameAs(),
			'facebook_id' => (string) $this->config->get('config_microdata_facebook_id'),
			'twitter_account' => (string) $this->config->get('config_microdata_twitter_account'),
			'currency' => $this->session->data['currency'] ?? $this->config->get('config_currency'),
		];
	}

	private function openGraphSchema($context, $route)
	{
		$type = $route === 'product/product' ? 'product' : 'website';

		$tags = [
			'og:locale' => $context['locale'],
			'og:type' => $type,
			'og:site_name' => $context['store_name'],
			'og:title' => $context['title'],
			'og:description' => $context['description'],
			'og:url' => $context['url'],
			'og:image' => $context['image'],
			'business:contact_data:street_address' => $context['store_address'],
			'business:contact_data:locality' => $context['locality'],
			'business:contact_data:country_name' => $context['country_name'],
			'business:contact_data:email' => $context['store_email'],
			'place:location:latitude' => $context['latitude'],
			'place:location:longitude' => $context['longitude'],
		];

		if ($context['facebook_id']) {
			$tags['fb:app_id'] = $context['facebook_id'];
		}

		$html = '<!-- schema open graph start -->' . PHP_EOL;

		foreach ($tags as $property => $content) {
			if ($content === '' || $content === null) {
				continue;
			}

			$html .=
				'<meta property="' .
				$this->escape($property) .
				'" content="' .
				$this->escape($content) .
				'">' .
				PHP_EOL;
		}

		$html .= '<!-- schema open graph end -->' . PHP_EOL;

		return $html;
	}

	private function twitterSchema($context)
	{
		if ($context['twitter_account'] === '' && $context['image'] === '') {
			return '';
		}

		$tags = [
			'twitter:card' => 'summary_large_image',
			'twitter:title' => $context['title'],
			'twitter:description' => $context['description'],
			'twitter:image' => $context['image'],
			'twitter:image:alt' => $context['title'],
		];

		if ($context['twitter_account']) {
			$account = $this->normalizeTwitter($context['twitter_account']);
			$tags['twitter:site'] = $account;
			$tags['twitter:creator'] = $account;
		}

		$html = '<!-- schema twitter cards start -->' . PHP_EOL;

		foreach ($tags as $property => $content) {
			if ($content === '' || $content === null) {
				continue;
			}

			$html .=
				'<meta name="' .
				$this->escape($property) .
				'" content="' .
				$this->escape($content) .
				'">' .
				PHP_EOL;
		}

		$html .= '<!-- schema twitter cards end -->' . PHP_EOL;

		return $html;
	}

	private function organizationSchema($context)
	{
		$schema = [
			'@context' => 'https://schema.org',
			'@type' => 'Organization',
			'@id' => $context['store_url'] . '#organization',
			'name' => $context['store_name'],
			'url' => $context['store_url'],
			'email' => $context['store_email'] ?: null,
			'telephone' => $context['store_telephone'] ?: null,
			'sameAs' => $context['same_as'],
		];

		if ($context['logo']) {
			$schema['logo'] = [
				'@type' => 'ImageObject',
				'url' => $context['logo'],
			];
		}

		if ($context['store_address'] || $context['country_code']) {
			$schema['address'] = [
				'@type' => 'PostalAddress',
				'streetAddress' => $context['store_address'] ?: null,
				'addressLocality' => $context['locality'] ?: null,
				'addressRegion' => $context['region'] ?: null,
				'addressCountry' => $context['country_code'] ?: null,
			];
		}

		$phones = $this->splitPhones($context['store_telephone']);

		if ($phones) {
			$schema['contactPoint'] = array_map(function ($phone) use ($context) {
				return [
					'@type' => 'ContactPoint',
					'telephone' => $phone,
					'contactType' => 'customer service',
					'areaServed' => $context['country_code'] ?: null,
					'availableLanguage' => $context['language'],
				];
			}, $phones);
		}

		return $this->jsonLdBlock('Organization JSON-LD', $schema);
	}

	private function localBusinessSchema($context)
	{
		$schema = [
			'@context' => 'https://schema.org',
			'@type' => 'OnlineStore',
			'@id' => $context['store_url'] . '#store',
			'name' => $context['store_name'],
			'url' => $context['store_url'],
			'image' => $context['logo'] ?: null,
			'logo' => $context['logo'] ?: null,
			'email' => $context['store_email'] ?: null,
			'telephone' => $context['store_telephone'] ?: null,
			'sameAs' => $context['same_as'],
			'currenciesAccepted' => $context['currency'],
			'parentOrganization' => [
				'@id' => $context['store_url'] . '#organization',
			],
		];

		if ($context['store_address'] || $context['country_code']) {
			$schema['address'] = [
				'@type' => 'PostalAddress',
				'streetAddress' => $context['store_address'] ?: null,
				'addressLocality' => $context['locality'] ?: null,
				'addressRegion' => $context['region'] ?: null,
				'addressCountry' => $context['country_code'] ?: null,
			];
		}

		if ($context['latitude'] !== '' && $context['longitude'] !== '') {
			$schema['geo'] = [
				'@type' => 'GeoCoordinates',
				'latitude' => $context['latitude'],
				'longitude' => $context['longitude'],
			];
		}

		if ($context['store_open']) {
			$schema['openingHours'] = $context['store_open'];
		}

		return $this->jsonLdBlock('LocalBusiness JSON-LD', $schema);
	}

	private function breadcrumbsSchema($data)
	{
		if (empty($data['breadcrumbs']) || !is_array($data['breadcrumbs'])) {
			return null;
		}

		$items = [];
		$position = 1;

		foreach ($data['breadcrumbs'] as $breadcrumb) {
			if (empty($breadcrumb['href']) || empty($breadcrumb['text'])) {
				continue;
			}

			$items[] = [
				'@type' => 'ListItem',
				'position' => $position,
				'item' => [
					'@id' => $breadcrumb['href'],
					'name' => html_entity_decode($breadcrumb['text'], ENT_QUOTES, 'UTF-8'),
				],
			];

			$position++;
		}

		if (!$items) {
			return null;
		}

		return $this->jsonLdBlock('Breadcrumbs JSON-LD', [
			'@context' => 'https://schema.org',
			'@type' => 'BreadcrumbList',
			'itemListElement' => $items,
		]);
	}

	private function productSchema($data, $context)
	{
		$product_id = (int) ($this->request->get['product_id'] ?? 0);

		if (!$product_id) {
			return null;
		}

		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if (!$product_info) {
			return null;
		}

		$name = html_entity_decode($product_info['name'], ENT_QUOTES, 'UTF-8');
		$description = $this->plainText($product_info['meta_description'] ?: $product_info['description']);
		$url = $this->url->link('product/product', 'product_id=' . $product_id);
		$image = !empty($data['popup']) ? $data['popup'] : $context['image'];
		$sku = $product_info['sku'] ?: $product_info['model'];

		$schema = [
			'@context' => 'https://schema.org',
			'@type' => 'Product',
			'name' => $name,
			'url' => $url,
			'description' => $description ?: null,
			'sku' => $sku ?: null,
			'mpn' => !empty($product_info['mpn']) ? $product_info['mpn'] : null,
			'image' => $image ?: null,
			'itemCondition' => 'https://schema.org/NewCondition',
		];

		if (!empty($product_info['manufacturer'])) {
			$schema['brand'] = [
				'@type' => 'Brand',
				'name' => html_entity_decode($product_info['manufacturer'], ENT_QUOTES, 'UTF-8'),
			];
		}

		$review_count = (int) $product_info['reviews'];
		$rating = (float) $product_info['rating'];

		if ($this->config->get('config_review_status') && $review_count > 0 && $rating > 0) {
			$schema['aggregateRating'] = [
				'@type' => 'AggregateRating',
				'ratingValue' => $rating,
				'reviewCount' => $review_count,
				'bestRating' => 5,
				'worstRating' => 1,
			];
		}

		if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
			$raw_price = !is_null($product_info['special']) && (float) $product_info['special'] >= 0
				? (float) $product_info['special']
				: (float) $product_info['price'];

			$price = $this->tax->calculate($raw_price, $product_info['tax_class_id'], $this->config->get('config_tax'));
			$formatted_price = $this->currency->format($price, $context['currency'], '', false);

			$schema['offers'] = [
				'@type' => 'Offer',
				'url' => $url,
				'priceCurrency' => $context['currency'],
				'price' => $formatted_price,
				'availability' =>
					(int) $product_info['quantity'] > 0
						? 'https://schema.org/InStock'
						: 'https://schema.org/OutOfStock',
				'itemCondition' => 'https://schema.org/NewCondition',
				'seller' => [
					'@id' => $context['store_url'] . '#organization',
				],
			];
		}

		return $this->jsonLdBlock('Product JSON-LD', $schema);
	}

	private function productListSchema($data)
	{
		if (empty($data['products']) || !is_array($data['products'])) {
			return null;
		}

		$elements = [];
		$position = 1;

		foreach ($data['products'] as $product) {
			if (empty($product['href']) || empty($product['name'])) {
				continue;
			}

			$elements[] = [
				'@type' => 'ListItem',
				'position' => $position,
				'url' => $product['href'],
				'name' => html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8'),
			];

			$position++;
		}

		if (!$elements) {
			return null;
		}

		return $this->jsonLdBlock('Item List JSON-LD', [
			'@context' => 'https://schema.org',
			'@type' => 'ItemList',
			'name' => $this->document->getTitle(),
			'url' => $this->getPageUrl($data),
			'numberOfItems' => count($elements),
			'itemListElement' => $elements,
		]);
	}

	private function websiteSchema($context)
	{
		$search_url = $this->url->link('product/search');
		$separator = strpos($search_url, '?') !== false ? '&' : '?';

		return $this->jsonLdBlock('Website JSON-LD', [
			'@context' => 'https://schema.org',
			'@type' => 'WebSite',
			'@id' => $context['store_url'] . '#website',
			'name' => $context['store_name'],
			'url' => $context['store_url'],
			'publisher' => [
				'@id' => $context['store_url'] . '#organization',
			],
			'potentialAction' => [
				'@type' => 'SearchAction',
				'target' => [
					'@type' => 'EntryPoint',
					'urlTemplate' => $search_url . $separator . 'search={search_term_string}',
				],
				'query-input' => 'required name=search_term_string',
			],
		]);
	}

	private function webPageSchema($context)
	{
		return $this->jsonLdBlock('WebPage JSON-LD', [
			'@context' => 'https://schema.org',
			'@type' => 'WebPage',
			'@id' => $context['url'] . '#webpage',
			'url' => $context['url'],
			'name' => $context['title'],
			'description' => $context['description'] ?: null,
			'inLanguage' => $context['locale'],
			'isPartOf' => [
				'@id' => $context['store_url'] . '#website',
			],
			'primaryImageOfPage' => $context['image']
				? [
					'@type' => 'ImageObject',
					'url' => $context['image'],
				]
				: null,
		]);
	}

	private function jsonLdBlock($comment, $schema)
	{
		$schema = $this->filterEmpty($schema);

		return [
			'comment' => $comment,
			'json' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
		];
	}

	private function filterEmpty($value)
	{
		if (!is_array($value)) {
			return $value;
		}

		$filtered = [];

		foreach ($value as $key => $item) {
			if (is_array($item)) {
				$item = $this->filterEmpty($item);

				if ($item === [] || $item === null) {
					continue;
				}
			}

			if ($item === null || $item === '') {
				continue;
			}

			$filtered[$key] = $item;
		}

		return $filtered;
	}

	private function getStoreUrl()
	{
		$url = $this->request->server['HTTPS']
			? $this->config->get('config_ssl')
			: $this->config->get('config_url');

		return rtrim((string) $url, '/') . '/';
	}

	private function getPageUrl($data)
	{
		if (!empty($data['share'])) {
			return $data['share'];
		}

		$links = $this->document->getLinks();

		if (is_array($links)) {
			foreach ($links as $link) {
				if (!empty($link['rel']) && $link['rel'] === 'canonical' && !empty($link['href'])) {
					return $link['href'];
				}
			}
		}

		$route = $this->request->get['route'] ?? 'common/home';
		$query = $this->request->get;
		unset($query['route'], $query['_route_'], $query['language']);

		return $this->url->link($route, http_build_query($query));
	}

	private function getImageUrl($filename)
	{
		if (!$filename) {
			return '';
		}

		if (preg_match('#^https?://#i', $filename)) {
			return $filename;
		}

		return $this->getStoreUrl() . 'image/' . ltrim(str_replace('\\', '/', $filename), '/');
	}

	private function getCountry()
	{
		$country_id = (int) $this->config->get('config_country_id');

		if (!$country_id) {
			return [];
		}

		$this->load->model('localisation/country');

		return $this->model_localisation_country->getCountry($country_id) ?: [];
	}

	private function getZone()
	{
		$zone_id = (int) $this->config->get('config_zone_id');

		if (!$zone_id) {
			return [];
		}

		$this->load->model('localisation/zone');

		return $this->model_localisation_zone->getZone($zone_id) ?: [];
	}

	private function parseGeocode()
	{
		$geocode = trim((string) $this->config->get('config_geocode'));

		if ($geocode === '' || strpos($geocode, ',') === false) {
			return [
				'latitude' => '',
				'longitude' => '',
			];
		}

		$parts = array_map('trim', explode(',', $geocode, 2));

		return [
			'latitude' => $parts[0] ?? '',
			'longitude' => $parts[1] ?? '',
		];
	}

	private function getSameAs()
	{
		$raw = (string) $this->config->get('config_microdata_same_as');

		if ($raw === '') {
			return [];
		}

		$lines = preg_split('/\r\n|\r|\n/', $raw);
		$urls = [];

		foreach ($lines as $line) {
			$line = trim($line);

			if ($line !== '') {
				$urls[] = $line;
			}
		}

		return array_values(array_unique($urls));
	}

	private function splitPhones($telephone)
	{
		if ($telephone === '') {
			return [];
		}

		$parts = preg_split('/[,;]+/', $telephone);
		$phones = [];

		foreach ($parts as $part) {
			$part = trim($part);

			if ($part !== '') {
				$phones[] = $part;
			}
		}

		return $phones;
	}

	private function getOgLocale($code)
	{
		$map = [
			'ua' => 'uk_UA',
			'uk' => 'uk_UA',
			'uk-ua' => 'uk_UA',
			'ru' => 'ru_RU',
			'ru-ru' => 'ru_RU',
			'en' => 'en_US',
			'en-gb' => 'en_GB',
			'en-us' => 'en_US',
		];

		$code = strtolower((string) $code);

		if (isset($map[$code])) {
			return $map[$code];
		}

		return str_replace('-', '_', $code);
	}

	private function normalizeTwitter($account)
	{
		$account = trim($account);

		if ($account === '') {
			return '';
		}

		if ($account[0] !== '@') {
			$account = '@' . ltrim($account, '@');
		}

		return $account;
	}

	private function plainText($value)
	{
		$text = html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
		$text = strip_tags($text);
		$text = preg_replace('/\s+/u', ' ', $text);

		return trim($text);
	}

	private function escape($value)
	{
		return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
	}
}
