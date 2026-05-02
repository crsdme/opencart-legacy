<?php
class ControllerCommonComponents extends Controller
{
  public function index(): void
  {
    $components_dir = DIR_TEMPLATE . 'tailwind/components/';
    $files = glob($components_dir . '*.twig');

    $data['components'] = array_map(function ($file) {
      return basename($file, '.twig');
    }, $files);

    $data['products'] = $this->getProducts();

    $data['categories'] = $this->getCategories();

    $data['breadcrumbs'] = $this->getBreadcrumbs();

    $data['pagination_first'] = $this->getPagination(1);
    $data['pagination_middle'] = $this->getPagination(5);
    $data['pagination_last'] = $this->getPagination(10);

    $data['carousel_slides'] = $this->getCarouselSlides();

    $this->response->setOutput($this->load->view('common/components', $data));
  }

  private function getProducts(): array
  {
    $this->load->language('product/category');

    $this->load->model('catalog/category');

    $this->load->model('catalog/product');

    $this->load->model('tool/image');

    $products = [];

    $filter_data = [
      'filter_category_id' => 0,
      'filter_filter' => '',
      'sort' => 'p.sort_order',
      'order' => 'ASC',
      'start' => 0,
      'limit' => 4,
    ];

    $results = $this->model_catalog_product->getProducts($filter_data);

    foreach ($results as $result) {
      if ($result['image']) {
        $image = $this->model_tool_image->resize(
          $result['image'],
          $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'),
          $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'),
        );
      } else {
        $image = $this->model_tool_image->resize(
          'placeholder.png',
          $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'),
          $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'),
        );
      }

      if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
        $price = $this->currency->format(
          $this->tax->calculate(
            $result['price'],
            $result['tax_class_id'],
            $this->config->get('config_tax'),
          ),
          $this->session->data['currency'],
        );
      } else {
        $price = false;
      }

      if (!is_null($result['special']) && (float) $result['special'] >= 0) {
        $special = $this->currency->format(
          $this->tax->calculate(
            $result['special'],
            $result['tax_class_id'],
            $this->config->get('config_tax'),
          ),
          $this->session->data['currency'],
        );
        $tax_price = (float) $result['special'];
      } else {
        $special = false;
        $tax_price = (float) $result['price'];
      }

      if ($this->config->get('config_tax')) {
        $tax = $this->currency->format($tax_price, $this->session->data['currency']);
      } else {
        $tax = false;
      }

      if ($this->config->get('config_review_status')) {
        $rating = (int) $result['rating'];
      } else {
        $rating = false;
      }

      $products[] = [
        'product_id' => $result['product_id'],
        'thumb' => $image,
        'name' => $result['name'],
        'description' =>
        utf8_substr(
          trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))),
          0,
          $this->config->get(
            'theme_' . $this->config->get('config_theme') . '_product_description_length',
          ),
        ) . '..',
        'price' => $price,
        'special' => $special,
        'tax' => $tax,
        'quantity' => $result['quantity'],
        'minimum' => $result['minimum'] > 0 ? $result['minimum'] : 1,
        'rating' => $result['rating'],
        'href' => $this->url->link(
          'product/product',
          'product_id=' . $result['product_id'],
        ),
      ];
    }

    return $products;
  }

  private function getCategories(): array
  {
    $categories = array();

    $response = $this->model_catalog_category->getCategories(0);

    foreach ($response as $category) {
      if ($category['top']) {
        // Level 2
        $children_data = array();

        $children = $this->model_catalog_category->getCategories($category['category_id']);

        foreach ($children as $child) {
          $filter_data = array(
            'filter_category_id'  => $child['category_id'],
            'filter_sub_category' => true
          );

          $children_data[] = array(
            'name'  => $child['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
            'href'  => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])
          );
        }

        // Level 1
        $categories[] = array(
          'name'     => $category['name'],
          'children' => $children_data,
          'column'   => $category['column'] ? $category['column'] : 1,
          'href'     => $this->url->link('product/category', 'path=' . $category['category_id'])
        );
      }
    }

    return $categories;
  }

  private function getBreadcrumbs(): array
  {
    $breadcrumbs = array();

    $breadcrumbs[] = array(
      'text' => 'Home',
      'href' => $this->url->link('common/home')
    );

    $breadcrumbs[] = array(
      'text' => 'Category',
      'href' => $this->url->link('product/category', 'path=0')
    );

    $breadcrumbs[] = array(
      'text' => 'Product',
      'href' => $this->url->link('product/product', 'product_id=1')
    );

    return $breadcrumbs;
  }

  private function getPagination($page)
  {
    $pagination = new Pagination();
    $pagination->total = 100;
    $pagination->page = $page;
    $pagination->limit = 10;
    $pagination->url = $this->url->link(
      'product/category',
      'path=0&page={page}',
    );

    return $pagination->render();
  }

  private function getCarouselSlides(): array
  {

    $this->load->model('design/banner');
    $this->load->model('tool/image');

    $slides = array();

    $results = $this->model_design_banner->getBanner(8);

    foreach ($results as $result) {
      if (is_file(DIR_IMAGE . $result['image'])) {
        $slides[] = array(
          'title' => $result['title'],
          'image_mobile' => $this->model_tool_image->resize($result['image'], 375, 210),
          'image_desktop' => $this->model_tool_image->resize($result['image'], 1920, 1080),
          'link' => $result['link'],
        );
      }
    }

    return $slides;
  }
}
