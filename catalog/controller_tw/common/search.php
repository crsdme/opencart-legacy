<?php
class ControllerCommonSearch extends Controller
{
  public function index()
  {
    $this->load->language('common/search');

    $data['text_search'] = $this->language->get('text_search');
    $data['language'] = $this->config->get('config_language');

    if (isset($this->request->get['query'])) {
      $data['query'] = $this->request->get['query'];
    } else {
      $data['query'] = '';
    }

    return $this->load->view('common/search', $data);
  }
}
