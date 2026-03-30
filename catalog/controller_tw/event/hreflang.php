<?php
class ControllerEventHreflang extends Controller
{
  public function index(&$route, &$data, &$code)
  {
    if ($route !== 'common/head') return;

    $this->load->model('localisation/language');

    $languages = $this->model_localisation_language->getLanguages();
    $hreflang = [];

    $route_for_link = isset($this->request->get['route']) ? $this->request->get['route'] : 'common/home';
    $args = $this->request->get;
    unset($args['route'], $args['_route_']);

    $hreflang[] = [
      'href' => $this->multilang->linkForLanguage(
        '',
        $route_for_link,
        $args,
        false,
        $languages
      ),
      'hreflang' => 'x-default'
    ];

    foreach ($languages as $lang) {
      if (empty($lang['status'])) continue;
      $hreflang[] = [
        'href' => $this->multilang->linkForLanguage(
          $lang['code'],
          $route_for_link,
          $args,
          false,
          $languages
        ),
        'hreflang' => $lang['code']
      ];
    }

    $data['hreflang'] = $hreflang;
  }
}
