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

  /**
   * BCP 47 / hreflang for a language row.
   * Order: language.hreflang → first token of locale → code.
   *
   * @param array|string $language Language row or raw tag/code
   * @return string
   */
  public function hreflangCode($language)
  {
    if (is_string($language)) {
      return $this->normalizeLanguageTag($language);
    }

    if (!empty($language['hreflang'])) {
      return $this->normalizeLanguageTag($language['hreflang']);
    }

    if (!empty($language['locale'])) {
      $first = trim(explode(',', (string) $language['locale'])[0]);
      $from_locale = $this->normalizeLanguageTag($first);

      if ($from_locale !== '') {
        return $from_locale;
      }
    }

    if (!empty($language['code'])) {
      return $this->normalizeLanguageTag($language['code']);
    }

    return '';
  }

  /**
   * ISO 639-1 primary subtag for html lang.
   *
   * @param array|string $language Language row or code
   * @return string
   */
  public function htmlLang($language)
  {
    $hreflang = $this->hreflangCode($language);
    $parts = preg_split('/[-_]/', $hreflang, 2);

    return strtolower($parts[0] ?? '');
  }

  /**
   * Normalize any locale-like token to BCP 47 (uk_UA.UTF-8 → uk-UA, en → en).
   *
   * @param string $value
   * @return string
   */
  public function normalizeLanguageTag($value)
  {
    $value = trim((string) $value);

    if ($value === '') {
      return '';
    }

    // Drop charset suffix: uk_UA.UTF-8 → uk_UA
    $value = preg_replace('/\..*$/', '', $value);
    $parts = preg_split('/[-_]/', $value);

    if (!$parts || $parts[0] === '') {
      return '';
    }

    $language = strtolower($parts[0]);

    if (isset($parts[1]) && $parts[1] !== '' && preg_match('/^[A-Za-z]{2,4}$/', $parts[1])) {
      return $language . '-' . strtoupper($parts[1]);
    }

    return $language;
  }
}
