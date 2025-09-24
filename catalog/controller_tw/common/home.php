<?php
class ControllerCommonHome extends Controller
{
    public function index()
    {
        $this->document->setTitle($this->config->get('config_meta_title'));
        $this->document->setDescription($this->config->get('config_meta_description'));
        $this->document->setKeywords($this->config->get('config_meta_keyword'));

        if (isset($this->request->get['route'])) {
            $canonical = $this->url->link('common/home');
            if ($this->config->get('config_seo_pro') && !$this->config->get('config_seopro_addslash')) {
                $canonical = rtrim($canonical, '/');
            }
            $this->document->addLink($canonical, 'canonical');
        }

        $this->response->setOutput($this->load->controller('common/layout', ['view' => 'common/home']));
    }
}
