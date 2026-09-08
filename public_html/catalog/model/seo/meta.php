<?php
class ModelSeoMeta extends Model
{
  const DESCRIPTION_LIMIT = 160;

  public function build(array $entity, array $vars = [], string $prefix = '', string $route = '', string $query = ''): array
  {
    $this->load->language('seo/meta');

    $vars = $this->normalizeVars($vars, $entity);

    return [
      'title' => $this->title($entity, $vars, $prefix),
      'description' => $this->description($entity, $vars, $prefix),
      'h1' => $this->h1($entity, $vars, $prefix),
      'canonical' => $this->canonical($route, $query),
      'robots' => $this->robots($entity),
    ];
  }

  public function apply(array $seo): void
  {
    $this->document->setTitle($seo['title'] ?? '');
    $this->document->setDescription($seo['description'] ?? '');

    if (!empty($seo['canonical'])) {
      $this->document->addLink($seo['canonical'], 'canonical');
    }

    if (!empty($seo['robots'])) {
      $this->document->setRobots($seo['robots']);
    }
  }

  private function canonical(string $route, string $query = ''): string
  {
    if ($route === '') {
      return '';
    }

    return $this->url->link($route, $query);
  }

  private function robots(array $entity): string
  {
    if (!empty($entity['robots'])) {
      return $entity['robots'];
    }

    if (!isset($entity['noindex']) || (int) $entity['noindex'] > 0) {
      return '';
    }

    if (!$this->config->get('config_noindex_status')) {
      return '';
    }

    return 'noindex,follow';
  }

  private function title(array $entity, array $vars, string $prefix): string
  {
    if (!empty($entity['meta_title'])) {
      $title = $this->plain($entity['meta_title']);
    } else {
      $title = $this->template($prefix . '_title', $vars);
    }

    if ($title === '') {
      $title = $this->template('fallback_title', $vars);
    }

    $page = (int) ($vars['page'] ?? 1);

    if ($page > 1) {
      $title = $this->template('title_page', ['title' => $title, 'page' => $page]);
    }

    return $title;
  }

  private function description(array $entity, array $vars, string $prefix): string
  {
    if (!empty($entity['meta_description'])) {
      $description = $this->plain($entity['meta_description']);
    } else {
      $description = $this->template($prefix . '_description', $vars);
    }

    if ($description === '' && !empty($entity['description'])) {
      $description = $this->plain($entity['description']);
    }

    return $this->limit($description, self::DESCRIPTION_LIMIT);
  }

  private function h1(array $entity, array $vars, string $prefix): string
  {
    if (!empty($entity['meta_h1'])) {
      return $this->plain($entity['meta_h1']);
    }

    $h1 = $this->template($prefix . '_h1', $vars);

    if ($h1 !== '') {
      return $h1;
    }

    return $vars['name'];
  }

  private function template(string $key, array $vars): string
  {
    if ($key === '' || $key === '_title' || $key === '_description' || $key === '_h1') {
      return '';
    }

    $template = $this->text($key);

    if ($template === '') {
      return '';
    }

    $replace = [];

    foreach ($vars as $name => $value) {
      if (!is_scalar($value)) {
        continue;
      }

      $replace['{' . $name . '}'] = (string) $value;
    }

    $text = strtr($template, $replace);
    $text = preg_replace('/\{[a-z0-9_]+\}/i', '', $text);
    $text = preg_replace('/\s+/u', ' ', (string) $text);
    $text = preg_replace('/\.{2,}/u', '.', (string) $text);

    return trim((string) $text, " \t\n\r-—|,");
  }

  private function normalizeVars(array $vars, array $entity): array
  {
    if (!isset($vars['name']) || $vars['name'] === '') {
      $vars['name'] = $entity['name'] ?? $entity['title'] ?? '';
    }

    if (!isset($vars['shop']) || $vars['shop'] === '') {
      $vars['shop'] = (string) $this->config->get('config_name');
    }

    foreach ($vars as $key => $value) {
      if (!is_scalar($value)) {
        continue;
      }

      $vars[$key] = $this->plain((string) $value);
    }

    return $vars;
  }

  private function text(string $key): string
  {
    $value = $this->language->get($key);

    return $value === $key ? '' : $value;
  }

  private function plain(string $value): string
  {
    $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    $value = strip_tags($value);
    $value = preg_replace('/\s+/u', ' ', $value);

    return trim((string) $value);
  }

  private function limit(string $text, int $max): string
  {
    $text = $this->plain($text);

    if ($text === '' || utf8_strlen($text) <= $max) {
      return $text;
    }

    $cut = utf8_substr($text, 0, $max);
    $space = utf8_strrpos($cut, ' ');

    if ($space !== false && $space > (int) ($max * 0.6)) {
      $cut = utf8_substr($cut, 0, $space);
    }

    return rtrim($cut, '.,;:- ') . '…';
  }
}
