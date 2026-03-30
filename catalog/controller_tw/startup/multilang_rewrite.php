<?php
class ControllerStartupMultilangRewrite extends Controller
{
  public function index()
  {
    if (defined('DIR_APPLICATION') && strpos(DIR_APPLICATION, 'admin') !== false)
      return;

    $this->url->addRewrite($this);
  }

  // ADDING LANGUAGE PREFIX TO THIS->URL
  public function rewrite($link)
  {
    $code = $this->config->get('config_language');
    $is_main = ($code === $this->config->get('config_language_main'));
    $parsed = parse_url(str_replace('&amp;', '&', $link));
    $path = isset($parsed['path']) ? trim($parsed['path'], '/') : '';
    $query = isset($parsed['query']) ? $parsed['query'] : '';
    $base = (isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '') . (isset($parsed['host']) ? $parsed['host'] : '') . (isset($parsed['port']) ? ':' . $parsed['port'] : '');

    if ($is_main) {
      if (($path === '' || $path === 'index.php') && $query === 'route=common/home')
        return $base . '/';

      return $link;
    }

    if (!isset($parsed['path']))
      return $link;

    $this->load->model('localisation/language');

    $codes = array_keys($this->model_localisation_language->getLanguages());
    $segments = explode('/', $path);

    if (isset($segments[0]) && in_array(strtolower($segments[0]), $codes, true))
      return $link;


    $new_path = '/' . $code . ($path !== '' ? '/' . $path : '');
    $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
    $frag = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

    // var_dump($new_path);
    // var_dump($query);
    // var_dump($frag);
    // var_dump($base . $new_path . $query . $frag);
    // die;

    return $base . $new_path . $query . $frag;
  }
}
