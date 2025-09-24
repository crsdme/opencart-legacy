<?php

class ControllerExtensionFeedSitemap extends Controller // CHECK VALIDATION AND LANGUAGE URLS AND NOINDEX
{
    public $langFlags = [
        1 => 'en',
        2 => 'uk-ua',
    ];

    private $languages = ['en', 'uk-ua', ''];

    private $cacheTtl = 86400;
    private $cacheDir = DIR_CACHE . 'sitemap/';

    public function index()
    {
        if (!$this->validation($this->request->get['language'])) {
            return $this->load->controller('error/not_found');
        }

        return $this->renderSitemap();
    }

    private function renderSitemap()
    {
        $this->load->model('extension/feed/sitemap');

        $xml = '';

        switch ($this->request->get['type']) {
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
            default:
                $xml .= $this->sitemap();
                break;
        }

        $this->response->addHeader('Content-Type: text/xml; charset=UTF-8');
        $this->response->setOutput($xml);
    }

    private function sitemap()
    {
        $cacheKey = $this->cacheFile('main');
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
            $xml .=
                '<sitemap>' .
                '<loc>' .
                $this->branchLink('main', 1, $this->request->get['language']) .
                '</loc>' .
                '</sitemap>';

            $xml .=
                '<sitemap>' .
                '<loc>' .
                $this->branchLink('categories', 1, $this->request->get['language']) .
                '</loc>' .
                '</sitemap>';

            $xml .=
                '<sitemap>' .
                '<loc>' .
                $this->branchLink('products', 1, $this->request->get['language']) .
                '</loc>' .
                '</sitemap>';

            $xml .=
                '<sitemap>' .
                '<loc>' .
                $this->branchLink('information', 1, $this->request->get['language']) .
                '</loc>' .
                '</sitemap>';

            $xml .=
                '<sitemap>' .
                '<loc>' .
                $this->branchLink('manufacturers', 1, $this->request->get['language']) .
                '</loc>' .
                '</sitemap>';

            $xml .=
                '<sitemap>' .
                '<loc>' .
                $this->branchLink('blog-categories', 1, $this->request->get['language']) .
                '</loc>' .
                '</sitemap>';

            $xml .=
                '<sitemap>' .
                '<loc>' .
                $this->branchLink('blog-articles', 1, $this->request->get['language']) .
                '</loc>' .
                '</sitemap>';
        }

        $xml .= '</sitemapindex>' . PHP_EOL;

        $this->cacheWrite($cacheKey, $xml);
        return $xml;
    }

    private function sitemapMain()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        $xml .= '<url>';
        $xml .= '<loc>' . $this->url->link('common/home') . $this->request->get['language'] . '</loc>';
        $xml .= '<lastmod>' . date('Y-m-d\TH:i:sP', time()) . '</lastmod>';
        $xml .= '<priority>1.0</priority>';
        $xml .= '</url>';

        $xml .= '</urlset>' . PHP_EOL;

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

        $lang = $lang ?? ($this->langFlags[(int) $this->config->get('config_language_id')] ?? '');
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
        if (!in_array($language, $this->languages)) {
            return false;
        }
        return true;
    }

    /* FILE CACHE HELPERS */

    private function cacheFile(string $name): string
    {
        $store = (int) $this->config->get('config_store_id');
        $lang = (int) $this->config->get('config_language_id');
        return DIR_CACHE . "sitemap_{$name}_store{$store}_lang{$lang}.xml";
    }

    private function cacheRead(string $file)
    {
        if ($this->cacheTtl <= 0) {
            return false;
        }
        if (!is_file($file)) {
            return false;
        }

        $mtime = @filemtime($file);
        if (!$mtime || time() - $mtime > $this->cacheTtl) {
            @unlink($file);
            return false;
        }

        $data = @file_get_contents($file);
        return $data !== false && $data !== '' ? $data : false;
    }

    private function cacheWrite(string $file, string $data): void
    {
        if ($this->cacheTtl <= 0) {
            return;
        }
        @file_put_contents($file, $data);
    }
}
