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

    public function info()
    {
        $this->response->setOutput($this->index());
    }

    private function getCartTotals()
    {
        $totals = [];
        $grandTotal = 0.0;

        if (!($this->customer->isLogged() || !$this->config->get('config_customer_price'))) {
            return [$totals, $grandTotal];
        }

        $extensions = $this->model_setting_extension->getExtensions('total');

        usort($extensions, function ($a, $b) {
            $aOrder = (int) $this->config->get('total_' . $a['code'] . '_sort_order');
            $bOrder = (int) $this->config->get('total_' . $b['code'] . '_sort_order');
            return $aOrder <=> $bOrder;
        });

        $total_data = [
            'totals' => &$totals,
            'total' => &$grandTotal,
        ];

        foreach ($extensions as $ext) {
            if ($this->config->get('total_' . $ext['code'] . '_status')) {
                $this->load->model('extension/total/' . $ext['code']);
            }
        }

        usort($totals, function ($a, $b) {
            return ((int) $a['sort_order']) <=> ((int) $b['sort_order']);
        });

        return [$totals, $grandTotal];
    }
}
