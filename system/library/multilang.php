<?php
class multilang
{
  /** @var Registry */
  private $registry;

  public function __construct($registry)
  {
    $this->registry = $registry;
  }

  /**
   * Build URL for a given language (respects main language = no prefix).
   *
   * @param string $code Language code (e.g. 'ru', 'en')
   * @param string $route Route (e.g. 'common/home', 'product/product')
   * @param array|string $args Query args (array or string like '&product_id=1')
   * @param bool $secure Use HTTPS
   * @param array|null $languages Optional: pre-fetched from getLanguages() to avoid repeated cache/DB in loops
   * @return string
   */
  public function linkForLanguage($code, $route, $args = [], $secure = false, $languages = null)
  {
    $config = $this->registry->get('config');
    $url = $this->registry->get('url');

    $saved_lang = $config->get('config_language');
    $saved_lang_id = $config->get('config_language_id');

    if ($languages === null) {
      $this->registry->get('load')->model('localisation/language');
      $languages = $this->registry->get('model_localisation_language')->getLanguages();
    }
    if (!isset($languages[$code]) || empty($languages[$code]['status'])) {
      $code = $saved_lang;
    }
    $lang = $languages[$code];
    $config->set('config_language', $code);
    $config->set('config_language_id', (int)$lang['language_id']);

    $link = $url->link($route, $args, $secure);

    $config->set('config_language', $saved_lang);
    $config->set('config_language_id', $saved_lang_id);

    return $link;
  }

  /**
   * Get links for all active languages for the current route/args.
   *
   * @param string $route
   * @param array $args
   * @param bool $secure
   * @return array [ 'ru' => '...', 'en' => '...', ... ]
   */
  public function linksForAllLanguages($route, $args = [], $secure = false)
  {
    $this->registry->get('load')->model('localisation/language');
    $languages = $this->registry->get('model_localisation_language')->getLanguages();
    $out = [];
    foreach ($languages as $code => $lang) {
      if (empty($lang['status'])) {
        continue;
      }
      $out[$code] = $this->linkForLanguage($code, $route, $args, $secure);
    }
    return $out;
  }
}
