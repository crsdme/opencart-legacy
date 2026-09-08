<?php
class ControllerProductManufacturer extends Controller
{
  public function index()
  {
    $this->load->language('product/manufacturer');
    $this->load->model('catalog/manufacturer');
    $this->load->model('seo/meta');

    $data['breadcrumbs'] = [];

    $data['breadcrumbs'][] = [
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home'),
    ];

    $data['breadcrumbs'][] = [
      'text' => $this->language->get('text_brand'),
      'href' => $this->url->link('product/manufacturer'),
    ];

    $seo = $this->model_seo_meta->build(
      [],
      [
        'name' => $this->language->get('heading_title'),
      ],
      'manufacturer_list',
      'product/manufacturer'
    );

    $this->model_seo_meta->apply($seo);
    $data['heading_title'] = $seo['h1'];

    $data['categories'] = [];

    $results = $this->model_catalog_manufacturer->getManufacturers();

    foreach ($results as $result) {
      if (is_numeric(utf8_substr($result['name'], 0, 1))) {
        $key = '0 - 9';
      } else {
        $key = utf8_substr(utf8_strtoupper($result['name']), 0, 1);
      }

      if (!isset($data['categories'][$key])) {
        $data['categories'][$key]['name'] = $key;
      }

      $data['categories'][$key]['manufacturer'][] = [
        'name' => $result['name'],
        'href' => $this->url->link(
          'product/manufacturer/info',
          'manufacturer_id=' . $result['manufacturer_id']
        ),
      ];
    }

    $data['continue'] = $this->url->link('common/home');

    $data['view'] = 'product/manufacturer_list';
    $this->response->setOutput($this->load->controller('common/layout', $data));
  }

  public function info()
  {
    $this->load->language('product/manufacturer');
    $this->load->model('catalog/manufacturer');
    $this->load->model('catalog/product');
    $this->load->model('tool/image');
    $this->load->model('product/helper');
    $this->load->model('seo/meta');

    $manufacturer_id = isset($this->request->get['manufacturer_id'])
      ? (int) $this->request->get['manufacturer_id']
      : 0;

    $params = $this->model_product_helper->getCatalogParams($this->request->get);

    $this->model_product_helper->applyNoindexByParams($this->request->get, ['sort', 'order', 'page', 'limit']);

    $data['breadcrumbs'] = [];

    $data['breadcrumbs'][] = [
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home'),
    ];

    $data['breadcrumbs'][] = [
      'text' => $this->language->get('text_brand'),
      'href' => $this->url->link('product/manufacturer'),
    ];

    $manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($manufacturer_id);

    if (!$manufacturer_info) {
      $this->notFound();
      return;
    }

    $data['breadcrumbs'][] = [
      'text' => $manufacturer_info['name'],
      'href' => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $manufacturer_id),
    ];

    $data['description'] = html_entity_decode($manufacturer_info['description'], ENT_QUOTES, 'UTF-8');
    $data['thumb'] = $this->getManufacturerThumb($manufacturer_info);
    $data['compare'] = $this->url->link('product/compare');
    $data['continue'] = $this->url->link('common/home');
    $data['sort'] = $params['sort'];
    $data['order'] = $params['order'];
    $data['limit'] = $params['limit'];

    $filter_data = [
      'filter_manufacturer_id' => $manufacturer_id,
      'sort' => $params['sort'],
      'order' => $params['order'],
      'start' => ($params['page'] - 1) * $params['limit'],
      'limit' => $params['limit'],
    ];

    $product_total = $this->model_catalog_product->getTotalProducts($filter_data);
    $results = $this->model_catalog_product->getProducts($filter_data);

    $product_url = $this->model_product_helper->buildUrl($this->request->get, ['sort', 'order', 'limit']);

    $data['products'] = [];

    foreach ($results as $result) {
      $href = $this->url->link(
        'product/product',
        'manufacturer_id=' . $manufacturer_id . '&product_id=' . $result['product_id'] . $product_url
      );

      $data['products'][] = $this->model_product_helper->prepareProduct($result, $href);
    }

    $base_query = 'manufacturer_id=' . $manufacturer_id;

    $data['sorts'] = $this->model_product_helper->getSorts(
      'product/manufacturer/info',
      $base_query,
      $this->model_product_helper->buildUrl($this->request->get, ['limit'])
    );

    $data['limits'] = $this->model_product_helper->getLimits(
      'product/manufacturer/info',
      $base_query,
      $this->model_product_helper->buildUrl($this->request->get, ['sort', 'order'])
    );

    $pagination_url = $this->model_product_helper->buildUrl($this->request->get, ['sort', 'order', 'limit']);

    $data['pagination_data'] = [
      'total' => $product_total,
      'page' => $params['page'],
      'limit' => $params['limit'],
      'text_prev' => $this->language->get('text_prev'),
      'text_next' => $this->language->get('text_next'),
      'url' => $this->url->link(
        'product/manufacturer/info',
        $base_query . $pagination_url . '&page={page}'
      ),
    ];

    $this->model_product_helper->addPaginationLinks(
      'product/manufacturer/info',
      $base_query,
      $params['page'],
      $params['limit'],
      $product_total
    );

    $seo = $this->model_seo_meta->build(
      $manufacturer_info,
      [
        'name' => $manufacturer_info['name'],
        'count' => $this->model_product_helper->plural(
          $product_total,
          $this->language->get('text_product_count_1'),
          $this->language->get('text_product_count_2'),
          $this->language->get('text_product_count_5')
        ),
        'page' => $params['page'],
      ],
      'manufacturer'
    );

    $this->model_seo_meta->apply($seo);
    $data['heading_title'] = $seo['h1'];

    $this->load->model('seo/faq');
    $data['faq'] = $this->model_seo_faq->attach($data, 'manufacturer', $manufacturer_id, $manufacturer_info, [
      'heading_title' => $data['heading_title'],
      'page' => $params['page'],
    ]);

    $data['view'] = 'product/manufacturer_info';
    $this->response->setOutput($this->load->controller('common/layout', $data));
  }

  private function getManufacturerThumb(array $manufacturer_info): string
  {
    if (empty($manufacturer_info['image'])) {
      return '';
    }

    return $this->model_product_helper->themeImage($manufacturer_info['image'], 'manufacturer', false);
  }

  private function notFound(): void
  {
    $this->load->language('product/manufacturer');

    $data['breadcrumbs'] = [];

    $data['breadcrumbs'][] = [
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home'),
    ];

    $data['breadcrumbs'][] = [
      'text' => $this->language->get('text_error'),
      'href' => $this->url->link(
        'product/manufacturer/info',
        isset($this->request->get['manufacturer_id'])
          ? 'manufacturer_id=' . (int) $this->request->get['manufacturer_id']
          : ''
      ),
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
