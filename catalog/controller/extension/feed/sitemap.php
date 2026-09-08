<?php

class ControllerExtensionFeedSitemap extends Controller // CHECK VALIDATION AND LANGUAGE URLS AND NOINDEX
{
	private $cacheDir = DIR_CACHE . 'sitemap/';

	public function index()
	{
		if (!$this->settingOn('feed_sitemap_status')) {
			return $this->load->controller('error/not_found');
		}

		$language = isset($this->request->get['language']) ? (string) $this->request->get['language'] : '';

		if (!$this->validation($language)) {
			return $this->load->controller('error/not_found');
		}

		if ($language !== '') {
			$this->applyLanguage($language);
		}

		return $this->renderSitemap();
	}

	private function renderSitemap()
	{
		$this->load->model('extension/feed/sitemap');

		$type = isset($this->request->get['type']) ? (string) $this->request->get['type'] : 'sitemap';
		$branch = $this->typeBranch($type);

		if ($branch !== '' && !$this->branchEnabled($branch)) {
			return $this->load->controller('error/not_found');
		}

		$xml = '';

		switch ($type) {
			case 'sitemap-main':
				$xml .= $this->sitemapMain();
				break;
			case 'sitemap-categories':
				$xml .= $this->sitemapCategories();
				break;
			case 'sitemap-products':
				$xml .= $this->sitemapProducts();
				break;
			case 'sitemap-information':
				$xml .= $this->sitemapInformation();
				break;
			case 'sitemap-manufacturers':
				$xml .= $this->sitemapManufacturers();
				break;
			case 'sitemap-blog-categories':
				$xml .= $this->sitemapBlogCategories();
				break;
			case 'sitemap-blog-articles':
				$xml .= $this->sitemapBlogArticles();
				break;
			case 'sitemap-blog-authors':
				$xml .= $this->sitemapBlogAuthors();
				break;
			default:
				$xml .= $this->sitemap();
				break;
		}

		$this->response->addHeader('Content-Type: text/xml; charset=UTF-8');
		$this->response->setOutput($xml);
	}

	private function sitemap()
	{
		$cacheKey = $this->cacheFile(empty($this->request->get['language']) ? 'index' : 'lang');
		if (($cached = $this->cacheRead($cacheKey)) !== false) {
			return $cached;
		}

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		if (empty($this->request->get['language'])) {
			$this->load->model('localisation/language');
			$results = $this->model_localisation_language->getLanguages();
			foreach ($results as $result) {
				$xml .= '<sitemap>' . '<loc>' . $this->branchLink('', 1, $result['code']) . '</loc>' . '</sitemap>';
			}
		}

		if (!empty($this->request->get['language'])) {
			foreach ($this->enabledBranches() as $branch) {
				$xml .=
					'<sitemap>' .
					'<loc>' .
					$this->branchLink($branch, 1, $this->request->get['language']) .
					'</loc>' .
					'</sitemap>';
			}
		}

		$xml .= '</sitemapindex>' . PHP_EOL;

		$this->cacheWrite($cacheKey, $xml);
		return $xml;
	}

	private function sitemapMain()
	{
		$cacheKey = $this->cacheFile('main');
		if (($cached = $this->cacheRead($cacheKey)) !== false) {
			return $cached;
		}

		$pages = new \Custom\Pages($this->config);
		$lastmod = date('Y-m-d\TH:i:sP', time());

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		$xml .= $this->urlNode($this->url->link('common/home'), $lastmod, '1.0');

		foreach ($this->mainPages($pages) as $page) {
			$xml .= $this->urlNode($this->url->link($page['route']), $lastmod, $page['priority']);
		}

		$xml .= '</urlset>' . PHP_EOL;

		$this->cacheWrite($cacheKey, $xml);
		return $xml;
	}

	private function mainPages(\Custom\Pages $pages): array
	{
		$items = [];

		if ($pages->enabled('contact')) {
			$items[] = ['route' => 'information/contact', 'priority' => '0.5'];
		}

		if ($pages->enabled('sitemap')) {
			$items[] = ['route' => 'information/sitemap', 'priority' => '0.3'];
		}

		if ($pages->enabled('product')) {
			$items[] = ['route' => 'product/special', 'priority' => '0.6'];
		}

		if ($pages->enabled('manufacturer')) {
			$items[] = ['route' => 'product/manufacturer', 'priority' => '0.6'];
		}

		if ($pages->enabled('blog')) {
			$items[] = ['route' => 'blog/latest', 'priority' => '0.7'];
		}

		return $items;
	}

	private function urlNode(string $loc, string $lastmod, string $priority): string
	{
		$xml = '<url>';
		$xml .= '<loc>' . $loc . '</loc>';
		$xml .= '<lastmod>' . $lastmod . '</lastmod>';
		$xml .= '<priority>' . $priority . '</priority>';
		$xml .= '</url>';

		return $xml;
	}

	private function sitemapCategories()
	{
		$categories = $this->model_extension_feed_sitemap->getCategories();

		$cacheKey = $this->cacheFile('categories');
		if (($cached = $this->cacheRead($cacheKey)) !== false) {
			return $cached;
		}

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		foreach ($categories as $category) {
			$xml .= '<url>';
			$xml .=
				'<loc>' .
				$this->url->link(
					'product/category',
					'path=' . $this->model_extension_feed_sitemap->getPathByCategory($category['category_id']),
				) .
				'</loc>' .
				PHP_EOL;
			$xml .= '<lastmod>' . date('Y-m-d\TH:i:sP', strtotime($category['date_modified'])) . '</lastmod>';
			$xml .= '<priority>0.8</priority>';
			$xml .= '</url>';
		}

		$xml .= '</urlset>' . PHP_EOL;

		$this->cacheWrite($cacheKey, $xml);
		return $xml;
	}

	private function sitemapProducts()
	{
		$products = $this->model_extension_feed_sitemap->getProducts();

		$cacheKey = $this->cacheFile('products');
		if (($cached = $this->cacheRead($cacheKey)) !== false) {
			return $cached;
		}

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		foreach ($products as $product) {
			$xml .= '<url>';
			$xml .=
				'<loc>' .
				$this->url->link(
					'product/product',
					'path=' .
						$this->model_extension_feed_sitemap->getPathByProduct($product['product_id']) .
						'&product_id=' .
						$product['product_id'],
				) .
				'</loc>' .
				PHP_EOL;
			$xml .= '<lastmod>' . date('Y-m-d\TH:i:sP', strtotime($product['date_added'])) . '</lastmod>';
			$xml .= '<priority>0.6</priority>';
			$xml .= '</url>';
		}

		$xml .= '</urlset>' . PHP_EOL;

		$this->cacheWrite($cacheKey, $xml);
		return $xml;
	}

	private function sitemapInformation()
	{
		$information = $this->model_extension_feed_sitemap->getInformation();

		$cacheKey = $this->cacheFile('information');
		if (($cached = $this->cacheRead($cacheKey)) !== false) {
			return $cached;
		}

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		foreach ($information as $information) {
			$xml .= '<url>';
			$xml .=
				'<loc>' .
				$this->url->link('information/information', 'information_id=' . $information['information_id']) .
				'</loc>' .
				PHP_EOL;
			$xml .= '<lastmod>' . date('Y-m-d\TH:i:sP') . '</lastmod>';
			$xml .= '<priority>0.4</priority>';
			$xml .= '</url>';
		}

		$xml .= '</urlset>' . PHP_EOL;

		$this->cacheWrite($cacheKey, $xml);
		return $xml;
	}

	private function sitemapManufacturers()
	{
		$manufacturers = $this->model_extension_feed_sitemap->getManufacturers();

		$cacheKey = $this->cacheFile('manufacturers');
		if (($cached = $this->cacheRead($cacheKey)) !== false) {
			return $cached;
		}

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		foreach ($manufacturers as $manufacturer) {
			$xml .= '<url>';
			$xml .=
				'<loc>' .
				$this->url->link('product/manufacturer', 'manufacturer_id=' . $manufacturer['manufacturer_id']) .
				'</loc>' .
				PHP_EOL;
			$xml .= '<lastmod>' . date('Y-m-d\TH:i:sP') . '</lastmod>';
			$xml .= '<priority>0.7</priority>';
			$xml .= '</url>';
		}

		$xml .= '</urlset>' . PHP_EOL;

		$this->cacheWrite($cacheKey, $xml);
		return $xml;
	}

	private function sitemapBlogCategories()
	{
		$blogCategories = $this->model_extension_feed_sitemap->getBlogCategories();

		$cacheKey = $this->cacheFile('blog-categories');
		if (($cached = $this->cacheRead($cacheKey)) !== false) {
			return $cached;
		}

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		foreach ($blogCategories as $blogCategory) {
			$xml .= '<url>';
			$xml .=
				'<loc>' .
				$this->url->link('blog/category', 'blog_category_id=' . $blogCategory['blog_category_id']) .
				'</loc>' .
				PHP_EOL;
			$xml .= '<lastmod>' . date('Y-m-d\TH:i:sP') . '</lastmod>';
			$xml .= '<priority>0.7</priority>';
			$xml .= '</url>';
		}

		$xml .= '</urlset>' . PHP_EOL;

		$this->cacheWrite($cacheKey, $xml);
		return $xml;
	}

	private function sitemapBlogArticles()
	{
		$blogArticles = $this->model_extension_feed_sitemap->getBlogArticles();

		$cacheKey = $this->cacheFile('blog-articles');
		if (($cached = $this->cacheRead($cacheKey)) !== false) {
			return $cached;
		}

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		foreach ($blogArticles as $blogArticle) {
			$xml .= '<url>';
			$xml .=
				'<loc>' .
				$this->url->link('blog/article', 'article_id=' . $blogArticle['article_id']) .
				'</loc>' .
				PHP_EOL;
			$xml .= '<lastmod>' . date('Y-m-d\TH:i:sP') . '</lastmod>';
			$xml .= '<priority>0.7</priority>';
			$xml .= '</url>';
		}

		$xml .= '</urlset>' . PHP_EOL;

		$this->cacheWrite($cacheKey, $xml);
		return $xml;
	}

	private function sitemapBlogAuthors()
	{
		$this->load->model('blog/helper');
		$this->model_blog_helper->ensureAuthorSchema();

		$blogAuthors = $this->model_extension_feed_sitemap->getBlogAuthors();

		$cacheKey = $this->cacheFile('blog-authors');
		if (($cached = $this->cacheRead($cacheKey)) !== false) {
			return $cached;
		}

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		foreach ($blogAuthors as $blogAuthor) {
			$xml .= '<url>';
			$xml .=
				'<loc>' .
				$this->url->link('blog/author', 'author_id=' . $blogAuthor['author_id']) .
				'</loc>' .
				PHP_EOL;
			$xml .= '<lastmod>' . date('Y-m-d\TH:i:sP') . '</lastmod>';
			$xml .= '<priority>0.6</priority>';
			$xml .= '</url>';
		}

		$xml .= '</urlset>' . PHP_EOL;

		$this->cacheWrite($cacheKey, $xml);
		return $xml;
	}

	/* HELPERS */

	private function branchLink(string $essence, int $page = 1, $lang = ''): string
	{
		$baseName = (string) 'sitemap.xml';
		$baseName = preg_replace('~\.xml$~i', '', $baseName);

		$isHttps =
			!empty($this->request->server['HTTPS']) &&
			($this->request->server['HTTPS'] === 'on' || $this->request->server['HTTPS'] === '1');
		$server = $isHttps ? $this->config->get('config_ssl') : $this->config->get('config_url');
		$server = rtrim($server, '/');

		$lang = $lang !== '' && $lang !== null ? $lang : (string) $this->config->get('config_language');
		$langPrefix = $lang ? $lang . '/' : '';

		$pageSuffix = $page > 1 ? '-' . $page : '';

		$url = $server . '/' . $langPrefix . $baseName;

		if ($essence) {
			$url .= '-' . $essence;
			if ($page && $page > 1) {
				$url .= '-' . (int) $page;
			}
		}

		$url .= '.xml';

		return $url;
	}

	private function cleanup($str)
	{
		//htmlentities($product['name'], ENT_QUOTES, "UTF-8"); // &laquo; - not valid char - see protocol...
		return str_replace(['&', '\'', '"', '>', '<'], ['&amp;', '&apos;', '&quot;', '&gt;', '&lt;'], $str);
	}

	private function validation($language)
	{
		return in_array((string) $language, $this->allowedLanguageCodes(), true);
	}

	private function settingOn($key)
	{
		$value = $this->config->get($key);

		return $value === null || $value === '' || (int) $value === 1;
	}

	private function branchMap()
	{
		return [
			'main' => 'feed_sitemap_main',
			'categories' => 'feed_sitemap_categories',
			'products' => 'feed_sitemap_products',
			'information' => 'feed_sitemap_information',
			'manufacturers' => 'feed_sitemap_manufacturers',
			'blog-categories' => 'feed_sitemap_blog_categories',
			'blog-articles' => 'feed_sitemap_blog_articles',
			'blog-authors' => 'feed_sitemap_blog_authors',
		];
	}

	private function typeBranch($type)
	{
		if (strpos($type, 'sitemap-') !== 0) {
			return '';
		}

		return substr($type, 8);
	}

	private function branchEnabled($branch)
	{
		$map = $this->branchMap();

		if (!isset($map[$branch])) {
			return false;
		}

		if (!$this->settingOn($map[$branch])) {
			return false;
		}

		$pages = new \Custom\Pages($this->config);

		return $pages->sitemapAllowed($branch);
	}

	private function enabledBranches()
	{
		$enabled = [];

		foreach (array_keys($this->branchMap()) as $branch) {
			if ($this->branchEnabled($branch)) {
				$enabled[] = $branch;
			}
		}

		return $enabled;
	}

	private function cacheTtl()
	{
		$hours = $this->config->get('feed_sitemap_cache_hours');

		if ($hours === null || $hours === '') {
			return 86400;
		}

		return max(0, (int) $hours) * 3600;
	}

	private function allowedLanguageCodes()
	{
		$this->load->model('localisation/language');
		$codes = array_keys($this->model_localisation_language->getLanguages());
		$codes[] = '';

		return $codes;
	}

	private function applyLanguage($code)
	{
		$this->load->model('localisation/language');
		$languages = $this->model_localisation_language->getLanguages();

		if (empty($languages[$code]['status'])) {
			return;
		}

		$this->session->data['language'] = $code;
		$this->config->set('config_language', $code);
		$this->config->set('config_language_id', (int) $languages[$code]['language_id']);
		$language = new Language($code);
		$language->load($code);
		$this->registry->set('language', $language);
	}

	/* FILE CACHE HELPERS */

	private function cacheFile(string $name): string
	{
		$store = (int) $this->config->get('config_store_id');
		$lang = (int) $this->config->get('config_language_id');
		$code = isset($this->request->get['language']) ? strtolower((string) $this->request->get['language']) : '';
		$code = preg_replace('/[^a-z0-9_-]/', '', $code);
		if ($code === '') {
			$code = 'all';
		}

		return DIR_CACHE . "sitemap_{$name}_store{$store}_lang{$lang}_{$code}.xml";
	}

	private function cacheRead(string $file)
	{
		if ($this->cacheTtl() <= 0) {
			return false;
		}
		if (!is_file($file)) {
			return false;
		}

		$mtime = @filemtime($file);
		if (!$mtime || time() - $mtime > $this->cacheTtl()) {
			@unlink($file);
			return false;
		}

		$data = @file_get_contents($file);
		return $data !== false && $data !== '' ? $data : false;
	}

	private function cacheWrite(string $file, string $data): void
	{
		if ($this->cacheTtl() <= 0) {
			return;
		}
		@file_put_contents($file, $data);
	}
}
