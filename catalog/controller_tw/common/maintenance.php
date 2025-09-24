<?php
class ControllerCommonMaintenance extends Controller
{
    public function index()
    {
        $this->load->language('common/maintenance');

        $this->document->setTitle($this->language->get('heading_title'));

        if ($this->request->server['SERVER_PROTOCOL'] == 'HTTP/1.1') {
            $this->response->addHeader('HTTP/1.1 503 Service Unavailable');
        } else {
            $this->response->addHeader('HTTP/1.0 503 Service Unavailable');
        }

        $this->response->addHeader('Retry-After: 3600');

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_maintenance'),
            'href' => $this->url->link('common/maintenance'),
        ];

        $data['message'] = $this->language->get('text_message');

        $data['view'] = 'common/maintenance';
        $this->response->setOutput($this->load->controller('common/layout', $data));
    }
}
