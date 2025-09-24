<?php
class ControllerErrorNotFound extends Controller
{
    public function index()
    {
        $this->load->language('error/not_found');

        $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home'),
        ];

        if (isset($this->request->get['route'])) {
            $url_data = $this->request->get;

            unset($url_data['_route_']);

            $route = $url_data['route'];

            unset($url_data['route']);

            $url = '';

            if ($url_data) {
                $url = '&' . urldecode(http_build_query($url_data, '', '&'));
            }

            $data['breadcrumbs'][] = [
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link($route, $url, $this->request->server['HTTPS']),
            ];
        }

        $data['continue'] = $this->url->link('common/home');

        $data['view'] = 'error/not_found';
        $this->response->setOutput($this->load->controller('common/layout', $data));
    }
}
