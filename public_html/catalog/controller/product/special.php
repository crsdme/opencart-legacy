<?php
class ControllerProductSpecial extends Controller
{
  public function index()
  {
    $this->load->language('product/special');

    $this->load->model('catalog/product');
    $this->load->model('tool/image');
    $this->load->model('product/helper');
    $this->load->model('seo/meta');

    $params = $this->model_product_helper->getCatalogParams($this->request->get);

    $this->model_product_helper->applyNoindexByParams($this->request->get, ['sort', 'order', 'page', 'limit']);

    $data['breadcrumbs'] = [];

    $data['breadcrumbs'][] = [
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home'),
    ];

    $data['breadcrumbs'][] = [
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('product/special'),
    ];

    $data['compare'] = $this->url->link('product/compare');
    $data['continue'] = $this->url->link('common/home');
    $data['sort'] = $params['sort'];
    $data['order'] = $params['order'];
    $data['limit'] = $params['limit'];

    $filter_data = [
      'sort' => $params['sort'],
      'order' => $params['order'],
      'start' => ($params['page'] - 1) * $params['limit'],
      'limit' => $params['limit'],
    ];

    $product_total = $this->model_catalog_product->getTotalProductSpecials();
    $results = $this->model_catalog_product->getProductSpecials($filter_data);

    $product_url = $this->model_product_helper->buildUrl($this->request->get, ['sort', 'order', 'limit']);

    $data['products'] = [];

    foreach ($results as $result) {
      $href = $this->url->link('product/product', 'product_id=' . $result['product_id'] . $product_url);
      $data['products'][] = $this->model_product_helper->prepareProduct($result, $href);
    }

    $data['sorts'] = $this->model_product_helper->getSorts(
      'product/special',
      '',
      $this->model_product_helper->buildUrl($this->request->get, ['limit']),
      'ps.price'
    );

    $data['limits'] = $this->model_product_helper->getLimits(
      'product/special',
      '',
      $this->model_product_helper->buildUrl($this->request->get, ['sort', 'order'])
    );

    $pagination_url = $this->model_product_helper->buildUrl($this->request->get, ['sort', 'order', 'limit']);

    $data['pagination_data'] = [
      'total' => $product_total,
      'page' => $params['page'],
      'limit' => $params['limit'],
      'text_prev' => $this->language->get('text_prev'),
      'text_next' => $this->language->get('text_next'),
      'url' => $this->url->link('product/special', $pagination_url . '&page={page}'),
    ];

    $this->model_product_helper->addPaginationLinks(
      'product/special',
      '',
      $params['page'],
      $params['limit'],
      $product_total
    );

    $seo = $this->model_seo_meta->build(
      [],
      [
        'name' => $this->language->get('heading_title'),
        'count' => $this->model_product_helper->plural(
          $product_total,
          $this->language->get('text_product_count_1'),
          $this->language->get('text_product_count_2'),
          $this->language->get('text_product_count_5')
        ),
        'page' => $params['page'],
      ],
      'special'
    );

    $this->model_seo_meta->apply($seo);
    $data['heading_title'] = $seo['h1'];

    $data['view'] = 'product/special';
    $this->response->setOutput($this->load->controller('common/layout', $data));
  }
}
