<?php
class ControllerCommonHome extends Controller
{
  public function index()
  {
    $this->document->setTitle($this->config->get('config_meta_title'));
    $this->document->setDescription($this->config->get('config_meta_description'));
    $this->document->setKeywords($this->config->get('config_meta_keyword'));
    $this->document->addLink($this->url->link('common/home'), 'canonical');

    $this->response->setOutput($this->load->controller('common/layout', ['view' => 'common/home']));
  }
}
