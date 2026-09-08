<?php
class ControllerProductSearch extends Controller
{
  public function index()
  {
    $this->load->language('product/search');

    $this->load->model('catalog/category');
    $this->load->model('catalog/product');
    $this->load->model('tool/image');
    $this->load->model('product/helper');
    $this->load->model('seo/meta');

    $params = $this->model_product_helper->getCatalogParams($this->request->get);

    $search = $this->request->get['search'] ?? '';
    $tag = $this->request->get['tag'] ?? $search;
    $description = $this->request->get['description'] ?? '';
    $category_id = isset($this->request->get['category_id']) ? (int) $this->request->get['category_id'] : 0;
    $sub_category = $this->request->get['sub_category'] ?? '';

    $query = $search !== '' ? $search : $tag;
    $search_name = $query !== ''
      ? $this->language->get('heading_title') . ' - ' . $query
      : $this->language->get('heading_title');

    $seo = $this->model_seo_meta->build(
      [
        'robots' => 'noindex,follow',
      ],
      [
        'name' => $search_name,
        'query' => $query,
        'page' => $params['page'],
      ],
      'search'
    );

    $this->model_seo_meta->apply($seo);
    $data['heading_title'] = $seo['h1'];

    $data['breadcrumbs'] = [];

    $data['breadcrumbs'][] = [
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home'),
    ];

    $search_url = $this->model_product_helper->buildUrl(
      $this->request->get,
      ['search', 'tag', 'description', 'category_id', 'sub_category', 'sort', 'order', 'limit'],
      ['search', 'tag']
    );

    $data['breadcrumbs'][] = [
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('product/search', $search_url),
    ];

    $data['compare'] = $this->url->link('product/compare');
    $data['continue'] = $this->url->link('common/home');
    $data['sort'] = $params['sort'];
    $data['order'] = $params['order'];
    $data['limit'] = $params['limit'];
    $data['search'] = $search;
    $data['description'] = $description;
    $data['category_id'] = $category_id;
    $data['sub_category'] = $sub_category;
    $data['categories'] = $this->getSearchCategories();
    $data['products'] = [];

    $product_total = 0;

    if ($search !== '' || isset($this->request->get['tag'])) {
      $filter_data = [
        'filter_name' => $search,
        'filter_tag' => $tag,
        'filter_description' => $description,
        'filter_category_id' => $category_id,
        'filter_sub_category' => $sub_category,
        'sort' => $params['sort'],
        'order' => $params['order'],
        'start' => ($params['page'] - 1) * $params['limit'],
        'limit' => $params['limit'],
      ];

      $product_total = $this->model_catalog_product->getTotalProducts($filter_data);
      $results = $this->model_catalog_product->getProducts($filter_data);

      $product_url = $this->model_product_helper->buildUrl(
        $this->request->get,
        ['search', 'tag', 'description', 'category_id', 'sub_category', 'sort', 'order', 'limit'],
        ['search', 'tag']
      );

      foreach ($results as $result) {
        $href = $this->url->link('product/product', 'product_id=' . $result['product_id'] . $product_url);
        $data['products'][] = $this->model_product_helper->prepareProduct($result, $href);
      }

      $base_query = $this->model_product_helper->buildUrl(
        $this->request->get,
        ['search', 'tag', 'description', 'category_id', 'sub_category'],
        ['search', 'tag']
      );
      $base_query = ltrim($base_query, '&');

      $data['sorts'] = $this->model_product_helper->getSorts(
        'product/search',
        $base_query,
        $this->model_product_helper->buildUrl($this->request->get, ['limit'])
      );

      $data['limits'] = $this->model_product_helper->getLimits(
        'product/search',
        $base_query,
        $this->model_product_helper->buildUrl($this->request->get, ['sort', 'order'])
      );

      $pagination_url = $this->model_product_helper->buildUrl(
        $this->request->get,
        ['search', 'tag', 'description', 'category_id', 'sub_category', 'sort', 'order', 'limit'],
        ['search', 'tag']
      );

      $data['pagination_data'] = [
        'total' => $product_total,
        'page' => $params['page'],
        'limit' => $params['limit'],
        'text_prev' => $this->language->get('text_prev'),
        'text_next' => $this->language->get('text_next'),
        'url' => $this->url->link('product/search', $pagination_url . '&page={page}'),
      ];

      if ($search !== '' && $this->config->get('config_customer_search')) {
        $this->addSearchLog($search, $category_id, $sub_category, $description, $product_total);
      }
    }

    $data['view'] = 'product/search';
    $this->response->setOutput($this->load->controller('common/layout', $data));
  }

  private function getSearchCategories(): array
  {
    $data = [];
    $categories_1 = $this->model_catalog_category->getCategories(0);

    foreach ($categories_1 as $category_1) {
      $level_2_data = [];
      $categories_2 = $this->model_catalog_category->getCategories($category_1['category_id']);

      foreach ($categories_2 as $category_2) {
        $level_3_data = [];
        $categories_3 = $this->model_catalog_category->getCategories($category_2['category_id']);

        foreach ($categories_3 as $category_3) {
          $level_3_data[] = [
            'category_id' => $category_3['category_id'],
            'name' => $category_3['name'],
          ];
        }

        $level_2_data[] = [
          'category_id' => $category_2['category_id'],
          'name' => $category_2['name'],
          'children' => $level_3_data,
        ];
      }

      $data[] = [
        'category_id' => $category_1['category_id'],
        'name' => $category_1['name'],
        'children' => $level_2_data,
      ];
    }

    return $data;
  }

  private function addSearchLog(
    string $search,
    int $category_id,
    $sub_category,
    $description,
    int $product_total
  ): void {
    $this->load->model('account/search');

    $this->model_account_search->addSearch([
      'keyword' => $search,
      'category_id' => $category_id,
      'sub_category' => $sub_category,
      'description' => $description,
      'products' => $product_total,
      'customer_id' => $this->customer->isLogged() ? $this->customer->getId() : 0,
      'ip' => $this->request->server['REMOTE_ADDR'] ?? '',
    ]);
  }
}
