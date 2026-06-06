<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerProductCategory extends Controller
{
  public function index()
  {
    $this->load->language('product/category');

    $this->load->model('catalog/category');
    $this->load->model('catalog/product');
    $this->load->model('tool/image');
    $this->load->model('product/helper');

    $params = $this->model_product_helper->getCatalogParams($this->request->get);

    $this->model_product_helper->applyNoindexByParams($this->request->get, ['filter', 'sort', 'order', 'page', 'limit']);

    $data['breadcrumbs'] = [];

    $data['breadcrumbs'][] = [
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home'),
    ];

    $path = '';
    $path_param = $this->request->get['path'] ?? '';
    $category_id = 0;

    if ($path_param) {
      $breadcrumb_url = $this->model_product_helper->buildUrl(
        $this->request->get,
        ['sort', 'order', 'limit']
      );

      $parts = explode('_', (string) $path_param);
      $category_id = (int) array_pop($parts);

      foreach ($parts as $path_id) {
        $path = $path ? $path . '_' . (int) $path_id : (int) $path_id;

        $category_info = $this->model_catalog_category->getCategory($path_id);

        if ($category_info) {
          $data['breadcrumbs'][] = [
            'text' => $category_info['name'],
            'href' => $this->url->link('product/category', 'path=' . $path . $breadcrumb_url),
          ];
        }
      }
    }

    $category_info = $this->model_catalog_category->getCategory($category_id);

    if (!$category_info) {
      $this->notFound();
      return;
    }

    $this->setCategoryMeta($category_info, $data);

    $product_url = $this->model_product_helper->buildUrl($this->request->get, ['filter', 'sort', 'order', 'limit']);

    $data['breadcrumbs'][] = [
      'text' => $category_info['name'],
      'href' => $this->url->link('product/category', 'path=' . $path_param),
    ];

    $data['sort'] = $params['sort'];
    $data['order'] = $params['order'];
    $data['limit'] = $params['limit'];

    $data['thumb'] = $this->getCategoryThumb($category_info);
    $data['description'] = html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8');
    $data['compare'] = $this->url->link('product/compare');
    $data['categories'] = $this->getChildCategories($category_id, $path_param, $product_url);

    $filter_data = [
      'filter_category_id' => $category_id,
      'filter_filter' => $params['filter'],
      'sort' => $params['sort'],
      'order' => $params['order'],
      'start' => ($params['page'] - 1) * $params['limit'],
      'limit' => $params['limit'],
    ];

    $product_total = $this->model_catalog_product->getTotalProducts($filter_data);
    $results = $this->model_catalog_product->getProducts($filter_data);

    $data['products'] = [];

    foreach ($results as $result) {
      $href = $this->url->link(
        'product/product',
        'path=' . $path_param . '&product_id=' . $result['product_id'] . $product_url
      );

      $data['products'][] = $this->model_product_helper->prepareProduct($result, $href);
    }

    $data['sorts'] = $this->model_product_helper->getSorts(
      'product/category',
      'path=' . $path_param,
      $this->model_product_helper->buildUrl($this->request->get, ['filter', 'limit'])
    );

    $data['limits'] = $this->model_product_helper->getLimits(
      'product/category',
      'path=' . $path_param,
      $this->model_product_helper->buildUrl($this->request->get, ['filter', 'sort', 'order'])
    );

    $pagination_url = $this->model_product_helper->buildUrl($this->request->get, ['filter', 'sort', 'order', 'limit']);

    $data['pagination_data'] = [
      'total' => $product_total,
      'page' => $params['page'],
      'limit' => $params['limit'],
      'text_prev' => $this->language->get('text_prev'),
      'text_next' => $this->language->get('text_next'),
      'url' => $this->url->link(
        'product/category',
        'path=' . $path_param . $pagination_url . '&page={page}'
      ),
    ];

    $this->model_product_helper->addPaginationLinks('product/category', 'path=' . $category_info['category_id'], $params['page'], $params['limit'], $product_total);

    $data['text_total_products'] = $this->model_product_helper->plural($product_total, $this->language->get('text_product_count_1'), $this->language->get('text_product_count_2'), $this->language->get('text_product_count_5'));

    $data['view'] = 'product/category';
    $this->response->setOutput($this->load->controller('common/layout', $data));
  }

  private function setCategoryMeta(array $category_info, array &$data): void
  {
    if ($category_info['meta_title']) {
      $this->document->setTitle($category_info['meta_title']);
    } else {
      $this->document->setTitle($category_info['name']);
    }

    if ($category_info['noindex'] <= 0 && $this->config->get('config_noindex_status')) {
      $this->document->setRobots('noindex,follow');
    }

    if ($category_info['meta_h1']) {
      $data['heading_title'] = $category_info['meta_h1'];
    } else {
      $data['heading_title'] = $category_info['name'];
    }

    $this->document->setDescription($category_info['meta_description']);
    $this->document->setKeywords($category_info['meta_keyword']);
  }

  private function getCategoryThumb(array $category_info): string
  {
    if (!$category_info['image']) {
      return '';
    }

    $theme = $this->config->get('config_theme');

    return $this->model_tool_image->resize(
      $category_info['image'],
      $this->config->get('theme_' . $theme . '_image_category_width'),
      $this->config->get('theme_' . $theme . '_image_category_height')
    );
  }

  private function getChildCategories(int $category_id, string $path_param, string $url): array
  {
    $data = [];

    $results = $this->model_catalog_category->getCategories($category_id);

    foreach ($results as $result) {
      $filter_data = [
        'filter_category_id' => $result['category_id'],
        'filter_sub_category' => true,
      ];

      $name = $result['name'];

      if ($this->config->get('config_product_count')) {
        $name .= ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')';
      }

      $data[] = [
        'name' => $name,
        'href' => $this->url->link(
          'product/category',
          'path=' . $path_param . '_' . $result['category_id'] . $url
        ),
      ];
    }

    return $data;
  }

  private function notFound(): void
  {
    $this->load->language('product/category');
    $this->load->model('product/helper');

    $url = $this->model_product_helper->buildUrl(
      $this->request->get,
      ['path', 'filter', 'sort', 'order', 'page', 'limit']
    );

    $data['breadcrumbs'] = [];

    $data['breadcrumbs'][] = [
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home'),
    ];

    $data['breadcrumbs'][] = [
      'text' => $this->language->get('text_error'),
      'href' => $this->url->link('product/category', $url),
    ];

    $this->document->setTitle($this->language->get('text_error'));

    $data['continue'] = $this->url->link('common/home');

    $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

    $data['column_left'] = $this->load->controller('common/column_left');
    $data['column_right'] = $this->load->controller('common/column_right');
    $data['content_top'] = $this->load->controller('common/content_top');
    $data['content_bottom'] = $this->load->controller('common/content_bottom');
    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');

    $this->response->setOutput($this->load->view('error/not_found', $data));
  }
}
