<?php


// Берёт `_route_`. Если первый сегмент — код языка: снимает его из `_route_`, выставляет session/config/language/cookie. 
// Если нет префикса — ставит основной язык. Пустой путь → `_route_` сбрасывает (главная).

class ControllerStartupMultilang extends Controller
{
  public function index()
  {
    if (defined('DIR_APPLICATION') && strpos(DIR_APPLICATION, 'admin') !== false)
      return;

    $uri = isset($this->request->server['REQUEST_URI']) ? $this->request->server['REQUEST_URI'] : '';
    $path = trim(parse_url($uri, PHP_URL_PATH), '/');
    $ext = pathinfo($path, PATHINFO_EXTENSION);

    if (in_array(strtolower($ext), ['js', 'css', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'ico', 'woff', 'woff2', 'ttf', 'svg']))
      return;

    if (!empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
      return;

    $route = isset($this->request->get['_route_']) ? trim($this->request->get['_route_'], '/') : $path;
    $parts = $route === '' ? [] : explode('/', $route);
    $prefix = isset($parts[0]) ? strtolower($parts[0]) : '';

    $this->load->model('localisation/language');

    $languages = $this->model_localisation_language->getLanguages();
    $codes = array_keys($languages);
    $main = $this->config->get('config_language_main');

    if ($prefix === '' || !in_array($prefix, $codes, true) || $prefix === $main) {
      if (!isset($languages[$main]) || empty($languages[$main]['status']))
        return;

      $this->session->data['language'] = $main;
      $this->config->set('config_language', $main);
      $this->config->set('config_language_id', (int)$languages[$main]['language_id']);
      setcookie('language', $main, time() + 60 * 60 * 24 * 30, '/', $this->request->server['HTTP_HOST']);
      $language = new Language($main);
      $language->load($main);
      $this->registry->set('language', $language);

      return;
    }

    if (empty($languages[$prefix]['status']))
      return;

    // Strip language prefix from _route_
    array_shift($parts);
    $new_route = implode('/', $parts);

    if ($new_route === '') {
      unset($this->request->get['_route_']);
    } else {
      $this->request->get['_route_'] = $new_route;
    }

    $this->session->data['language'] = $prefix;
    $this->config->set('config_language', $prefix);
    $this->config->set('config_language_id', (int)$languages[$prefix]['language_id']);
    setcookie('language', $prefix, time() + 60 * 60 * 24 * 30, '/', $this->request->server['HTTP_HOST']);
    $language = new Language($prefix);
    $language->load($prefix);
    $this->registry->set('language', $language);
  }
}
