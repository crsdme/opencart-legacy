<?php
class ModelProductHelper extends Model
{
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
    $theme = $this->config->get('config_theme');

    return [
      'filter' => $get['filter'] ?? '',
      'sort' => $get['sort'] ?? 'p.sort_order',
      'order' => $get['order'] ?? 'ASC',
      'page' => isset($get['page']) ? (int) $get['page'] : 1,
      'limit' => isset($get['limit'])
        ? (int) $get['limit']
        : (int) $this->config->get('theme_' . $theme . '_product_limit'),
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

  public function prepareProduct(array $product, string $href): array
  {
    $this->load->model('tool/image');

    $theme = $this->config->get('config_theme');

    $image_width = (int) $this->config->get('theme_' . $theme . '_image_product_width');
    $image_height = (int) $this->config->get('theme_' . $theme . '_image_product_height');

    $image = $this->model_tool_image->resize(
      !empty($product['image']) ? $product['image'] : 'placeholder.png',
      $image_width,
      $image_height
    );

    if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
      $price = $this->currency->format(
        $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')),
        $this->session->data['currency']
      );
    } else {
      $price = false;
    }

    if (!is_null($product['special']) && (float) $product['special'] >= 0) {
      $special = $this->currency->format(
        $this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')),
        $this->session->data['currency']
      );

      $tax_price = (float) $product['special'];
    } else {
      $special = false;
      $tax_price = (float) $product['price'];
    }

    $tax = $this->config->get('config_tax')
      ? $this->currency->format($tax_price, $this->session->data['currency'])
      : false;

    $description_length = (int) $this->config->get('theme_' . $theme . '_product_description_length');

    return [
      'product_id' => $product['product_id'],
      'thumb' => $image,
      'name' => $product['name'],
      'description' => utf8_substr(
        trim(strip_tags(html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8'))),
        0,
        $description_length
      ) . '..',
      'price' => $price,
      'special' => $special,
      'tax' => $tax,
      'quantity' => $product['quantity'] ?? 0,
      'minimum' => $product['minimum'] > 0 ? $product['minimum'] : 1,
      'rating' => $this->config->get('config_review_status') ? (int) $product['rating'] : false,
      'href' => $href,
    ];
  }

  public function getSorts(string $route, string $base_query, string $url = ''): array
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
        'value' => 'p.price-ASC',
        'sort' => 'p.price',
        'order' => 'ASC',
      ],
      [
        'text' => $this->language->get('text_price_desc'),
        'value' => 'p.price-DESC',
        'sort' => 'p.price',
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
        $base_query . '&sort=' . $sort['sort'] . '&order=' . $sort['order'] . $url
      );

      unset($sort['sort'], $sort['order']);
    }

    unset($sort);

    return $sorts;
  }

  public function getLimits(string $route, string $base_query, string $url = ''): array
  {
    $limits = array_unique([
      (int) $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit'),
      25,
      50,
      75,
      100,
    ]);

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

  public function addPaginationLinks(
    string $route,
    string $base_query,
    int $page,
    int $limit,
    int $total
  ): void {
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

    $server = isset($this->request->server['HTTPS']) &&
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
