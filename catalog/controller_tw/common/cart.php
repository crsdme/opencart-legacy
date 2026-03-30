<?php
class ControllerCommonCart extends Controller
{
  public function index()
  {
    $this->load->language('common/cart');

    $this->load->model('setting/extension');
    $this->load->model('tool/image');
    $this->load->model('tool/upload');

    $currency = $this->session->data['currency'] ?? $this->config->get('config_currency');
    $imageWidth = (int) $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width');
    $imageHeight = (int) $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height');

    [$totals, $grandTotal] = $this->getCartTotals();

    $voucherCount = !empty($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0;

    $data['text_items'] = sprintf(
      $this->language->get('text_items'),
      $this->cart->countProducts() + $voucherCount,
      $this->currency->format($grandTotal, $currency),
    );

    $data['products'] = [];
    $data['productCount'] = $this->cart->countProducts();
    foreach ($this->cart->getProducts() as $product) {
      $image = $this->model_tool_image->resize($product['image'] ?? 'placeholder.png', $imageWidth, $imageHeight);
      $priceFormatted = $this->currency->format($product['price'], $currency);
      $lineTotalFormatted = $this->currency->format($product['price'] * $product['quantity'], $currency);

      $data['products'][] = [
        'cart_id' => $product['cart_id'],
        'thumb' => $image,
        'name' => $product['name'],
        'quantity' => (int) $product['quantity'],
        'price' => $priceFormatted,
        'total' => $lineTotalFormatted,
        'href' => $this->url->link('product/product', 'product_id=' . (int) $product['product_id']),
      ];
    }

    $data['totals'] = [];
    foreach ($totals as $total) {
      $data['totals'][] = [
        'title' => $total['title'],
        'text' => $this->currency->format($total['value'], $currency),
      ];
    }

    $data['cart'] = $this->url->link('checkout/cart');
    $data['checkout'] = $this->url->link('checkout/checkout', '', true);

    return $this->load->view('common/cart', $data);
  }

  private function renderCartModal()
  {
    $this->load->language('common/cart');

    $this->load->model('setting/extension');
    $this->load->model('tool/image');
    $this->load->model('tool/upload');

    $currency = $this->session->data['currency'] ?? $this->config->get('config_currency');
    $imageWidth = (int) $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width');
    $imageHeight = (int) $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height');

    [$totals, $grandTotal] = $this->getCartTotals();

    $voucherCount = !empty($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0;

    $data['text_items'] = sprintf(
      $this->language->get('text_items'),
      $this->cart->countProducts() + $voucherCount,
      $this->currency->format($grandTotal, $currency),
    );

    $data['products'] = [];
    $data['productCount'] = $this->cart->countProducts();
    foreach ($this->cart->getProducts() as $product) {
      $image = $this->model_tool_image->resize($product['image'] ?? 'placeholder.png', $imageWidth, $imageHeight);
      $priceFormatted = $this->currency->format($product['price'], $currency);
      $lineTotalFormatted = $this->currency->format($product['price'] * $product['quantity'], $currency);

      $data['products'][] = [
        'cart_id' => $product['cart_id'],
        'thumb' => $image,
        'name' => $product['name'],
        'quantity' => (int) $product['quantity'],
        'price' => $priceFormatted,
        'total' => $lineTotalFormatted,
        'href' => $this->url->link('product/product', 'product_id=' . (int) $product['product_id']),
      ];
    }

    $data['totals'] = [];
    foreach ($totals as $total) {
      $data['totals'][] = [
        'title' => $total['title'],
        'text' => $this->currency->format($total['value'], $currency),
      ];
    }

    $data['cart'] = $this->url->link('checkout/cart');
    $data['checkout'] = $this->url->link('checkout/checkout', '', true);

    return $this->load->view('common/cart_modal', $data);
  }

  public function info()
  {
    $this->response->setOutput($this->renderCartModal());
  }

  public function add()
  {
    $this->load->language('common/cart');

    $this->load->model('catalog/product');

    $json = [];

    $product_id = (int) $this->request->post['product_id'] ?? 0;
    $product_info = $this->model_catalog_product->getProduct($product_id);

    $quantity = (int) ($this->request->post['quantity'] ?? 1);
    $this->cart->add($this->request->post['product_id'], $quantity);

    $json['success'] = sprintf(
      $this->language->get('text_success'),
      $this->url->link('product/product', 'product_id=' . $this->request->post['product_id']),
      $product_info['name'],
      $this->url->link('checkout/cart'),
    );

    $json['total'] = $this->cart->countProducts();

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function edit()
  {
    $this->load->language('common/cart');

    $json = array();

    // Update
    if (!empty($this->request->post['quantity'])) {
      foreach ($this->request->post['quantity'] as $key => $value) {
        $this->cart->update($key, $value);
      }

      $this->session->data['success'] = $this->language->get('text_remove');

      unset($this->session->data['shipping_method']);
      unset($this->session->data['shipping_methods']);
      unset($this->session->data['payment_method']);
      unset($this->session->data['payment_methods']);
      unset($this->session->data['reward']);

      $this->response->redirect($this->url->link('checkout/cart'));
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  public function remove()
  {
    $this->load->language('common/cart');

    $json = [];

    if (isset($this->request->post['key'])) {
      $this->cart->remove($this->request->post['key']);

      unset($this->session->data['vouchers'][$this->request->post['key']]);
      unset($this->session->data['shipping_method']);
      unset($this->session->data['shipping_methods']);
      unset($this->session->data['payment_method']);
      unset($this->session->data['payment_methods']);
      unset($this->session->data['reward']);
    }

    $json['total'] = $this->cart->countProducts();
    $json['html'] = $this->renderCartModal();

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  private function getCartTotals()
  {

    // Важно: модель setting/extension должна быть загружена до вызова
    // $this->load->model('setting/extension');

    $totals = [];
    $taxes  = $this->cart->getTaxes();
    $total  = 0;

    $total_data = [
      'totals' => &$totals,
      'taxes'  => &$taxes,
      'total'  => &$total,
    ];


    $extensions = $this->model_setting_extension->getExtensions('total');

    usort($extensions, function ($a, $b) {
      $aOrder = (int)$this->config->get('total_' . $a['code'] . '_sort_order');
      $bOrder = (int)$this->config->get('total_' . $b['code'] . '_sort_order');
      return $aOrder <=> $bOrder;
    });

    foreach ($extensions as $ext) {
      if ($this->config->get('total_' . $ext['code'] . '_status')) {
        $this->load->model('extension/total/' . $ext['code']);

        $model = 'model_extension_total_' . $ext['code'];

        $this->{$model}->getTotal($total_data);
      }
    }

    usort($totals, function ($a, $b) {
      return ((int)$a['sort_order']) <=> ((int)$b['sort_order']);
    });

    return [$totals, $total];
  }
}
