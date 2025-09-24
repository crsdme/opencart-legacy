<?php
class ControllerCommonLayout extends Controller
{
    public function index($data)
    {
        $data['theme'] = $this->request->cookie['theme'] ?? null;

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer', $data);
        $data['header'] = $this->load->controller('common/header', $data);
        $data['head'] = $this->load->controller('common/head', $data);
        $data['microdata'] = $this->load->controller('common/microdata', $data);

        $data['content'] = $this->load->view($data['view'], $data);
        return $this->load->view('common/layout', $data);
    }
}
