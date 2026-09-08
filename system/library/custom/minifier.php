<?php

class Minifier
{
  private $config;
  private $enabled;
  private $cacheDir;
  private $cacheUrlBase;
  private $documentRoot;

  public function __construct($registry)
  {
    $this->config = $registry->get('config');

    $storeId = (int) $this->config->get('config_store_id');
    $this->enabled = $this->isEnabled();
    $this->cacheDir = rtrim(DIR_IMAGE, '/\\') . '/cache/minifier/' . $storeId . '/';
    $this->cacheUrlBase = 'image/cache/minifier/' . $storeId . '/';
    $root = realpath(DIR_APPLICATION . '../');
    $this->documentRoot = $root ? str_replace('\\', '/', $root) : '';

    if ($this->enabled && !is_dir($this->cacheDir)) {
      mkdir($this->cacheDir, 0775, true);
    }
  }

  public function renderStyles(array $styles)
  {
    if (!$styles) {
      return '';
    }

    if (!$this->enabled) {
      return $this->renderOriginalStyles($styles);
    }

    return $this->renderGrouped($styles, 'css');
  }

  public function renderScripts(array $scripts, $position = 'header')
  {
    if (!$scripts) {
      return '';
    }

    if (!$this->enabled) {
      return $this->renderOriginalScripts($scripts);
    }

    return $this->renderGrouped($scripts, 'js', $position);
  }

  private function isEnabled()
  {
    return (bool) $this->config->get('config_minifier');
  }

  private function renderGrouped(array $assets, $type, $position = 'header')
  {
    $output = '';
    $localGroup = array();

    foreach ($assets as $asset) {
      $href = $this->assetHref($asset, $type);

      if ($href === '') {
        continue;
      }

      if ($this->isLocalAsset($href)) {
        $localGroup[] = $asset;
        continue;
      }

      $output .= $this->flushLocalGroup($localGroup, $type, $position);
      $localGroup = array();
      $output .=
        $type === 'css'
          ? $this->renderOriginalStyles(array($asset))
          : $this->renderOriginalScripts(array($href));
    }

    $output .= $this->flushLocalGroup($localGroup, $type, $position);

    return $output;
  }

  private function flushLocalGroup(array $group, $type, $position)
  {
    if (!$group) {
      return '';
    }

    $resolved = $this->resolveAssetFiles($group, $type === 'css' ? 'href' : null);

    if (count($resolved) !== count($group)) {
      return $type === 'css'
        ? $this->renderOriginalStyles($group)
        : $this->renderOriginalScripts($group);
    }

    $url =
      $type === 'css' ? $this->buildCssBundle($resolved) : $this->buildJsBundle($resolved, $position);

    if ($url === null) {
      return $type === 'css'
        ? $this->renderOriginalStyles($group)
        : $this->renderOriginalScripts($group);
    }

    if ($type === 'css') {
      return '<link href="' . $url . '" type="text/css" rel="stylesheet" media="screen" />' . PHP_EOL;
    }

    return '<script src="' . $url . '" type="text/javascript"></script>' . PHP_EOL;
  }

  private function buildCssBundle(array $resolved)
  {
    $hash = $this->buildHash($resolved, 'css-v3');
    $filename = 'bundle-' . $hash . '.css';
    $absoluteBundle = $this->cacheDir . $filename;

    if (!is_file($absoluteBundle)) {
      $content = '';

      foreach ($resolved as $item) {
        $css = file_get_contents($item['path']);
        if ($css === false) {
          continue;
        }

        $css = $this->rewriteCssUrls($css, $item['path']);

        if (!$this->isMinifiedName($item['path'])) {
          $css = $this->minifyCss($css);
        }

        $content .= $css;
      }

      if ($content === '') {
        return null;
      }

      file_put_contents($absoluteBundle, $this->minifyCss($content), LOCK_EX);
    }

    return $this->cacheUrlBase . $filename;
  }

  private function buildJsBundle(array $resolved, $position)
  {
    $hash = $this->buildHash($resolved, 'js-v4-' . $position);
    $filename = 'bundle-' . $hash . '.js';
    $absoluteBundle = $this->cacheDir . $filename;

    if (!is_file($absoluteBundle)) {
      $parts = array();

      foreach ($resolved as $item) {
        $js = file_get_contents($item['path']);
        if ($js === false) {
          continue;
        }

        if (!$this->isMinifiedName($item['path'])) {
          $js = $this->minifyJs($js);
        }

        $parts[] = rtrim($js, " \t\n\r;");
      }

      if (!$parts) {
        return null;
      }

      file_put_contents($absoluteBundle, implode(";\n", $parts) . ";\n", LOCK_EX);
    }

    return $this->cacheUrlBase . $filename;
  }

  private function resolveAssetFiles(array $assets, $key = null)
  {
    $resolved = array();

    foreach ($assets as $asset) {
      $assetPath = $key !== null ? (isset($asset[$key]) ? $asset[$key] : '') : $asset;

      $publicPath = ltrim($this->stripQueryString($assetPath), '/');
      $absolute = realpath(DIR_APPLICATION . '../' . $publicPath);

      if (!$absolute || !is_file($absolute)) {
        continue;
      }

      $resolved[] = array(
        'path' => $absolute,
        'mtime' => filemtime($absolute),
        'size' => filesize($absolute),
      );
    }

    return $resolved;
  }

  private function buildHash(array $files, $prefix)
  {
    $signature = $prefix . '|';

    foreach ($files as $file) {
      $signature .= $file['path'] . ':' . (int) $file['mtime'] . ':' . (int) $file['size'] . '|';
    }

    return md5($signature);
  }

  private function rewriteCssUrls($css, $sourcePath)
  {
    $sourceDir = dirname($sourcePath);
    $cacheAbsolute = realpath($this->cacheDir);
    $cachePublic = $cacheAbsolute ? $this->publicPath($cacheAbsolute) : null;

    if ($cachePublic === null) {
      return $css;
    }

    return preg_replace_callback(
      '/url\(\s*([\'"]?)([^\'")]+)\1\s*\)/i',
      function ($match) use ($sourceDir, $cachePublic) {
        $raw = trim($match[2]);

        if ($raw === '' || preg_match('~^(data:|https?:|//|/|#)~i', $raw)) {
          return $match[0];
        }

        $hash = '';
        $query = '';
        $path = $raw;

        if (strpos($path, '#') !== false) {
          $parts = explode('#', $path, 2);
          $path = $parts[0];
          $hash = '#' . $parts[1];
        }

        if (strpos($path, '?') !== false) {
          $parts = explode('?', $path, 2);
          $path = $parts[0];
          $query = '?' . $parts[1];
        }

        $absolute = realpath($sourceDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));

        if (!$absolute) {
          return $match[0];
        }

        $targetPublic = $this->publicPath($absolute);

        if ($targetPublic === null) {
          return $match[0];
        }

        $relative = $this->relativePath($cachePublic, $targetPublic);

        return 'url(' . $relative . $query . $hash . ')';
      },
      $css,
    );
  }

  private function publicPath($absolute)
  {
    if ($this->documentRoot === '' || !$absolute) {
      return null;
    }

    $path = str_replace('\\', '/', $absolute);
    $root = rtrim($this->documentRoot, '/');

    if (strpos($path, $root) !== 0) {
      return null;
    }

    return ltrim(substr($path, strlen($root)), '/');
  }

  private function relativePath($fromDir, $toFile)
  {
    $from = $fromDir !== '' ? explode('/', $fromDir) : array();
    $to = $toFile !== '' ? explode('/', $toFile) : array();

    while ($from && $to && $from[0] === $to[0]) {
      array_shift($from);
      array_shift($to);
    }

    return str_repeat('../', count($from)) . implode('/', $to);
  }

  private function renderOriginalStyles(array $styles)
  {
    $output = '';

    foreach ($styles as $style) {
      $href = isset($style['href']) ? $style['href'] : '';
      $rel = isset($style['rel']) ? $style['rel'] : 'stylesheet';
      $media = isset($style['media']) ? $style['media'] : 'screen';

      if ($href === '') {
        continue;
      }

      $output .= '<link href="' . $href . '" type="text/css" rel="' . $rel . '" media="' . $media . '" />' . PHP_EOL;
    }

    return $output;
  }

  private function renderOriginalScripts(array $scripts)
  {
    $output = '';

    foreach ($scripts as $script) {
      if (!is_string($script) || $script === '') {
        continue;
      }

      $attrs = $this->scriptAttributes($script);
      $extra = '';

      foreach ($attrs as $name => $value) {
        $extra .= ' ' . $name . '="' . $value . '"';
      }

      $output .= '<script src="' . $script . '" type="text/javascript"' . $extra . '></script>' . PHP_EOL;
    }

    return $output;
  }

  private function scriptAttributes($src)
  {
    static $known = array(
      'https://code.jquery.com/jquery-3.7.1.min.js' => array(
        'integrity' => 'sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=',
        'crossorigin' => 'anonymous',
      ),
    );

    return isset($known[$src]) ? $known[$src] : array();
  }

  private function assetHref($asset, $type)
  {
    if ($type === 'css') {
      return isset($asset['href']) ? $asset['href'] : '';
    }

    return is_string($asset) ? $asset : '';
  }

  private function isLocalAsset($path)
  {
    return !preg_match('#^(https?:)?//#i', $path);
  }

  private function stripQueryString($path)
  {
    $parts = explode('?', $path, 2);
    return $parts[0];
  }

  private function isMinifiedName($path)
  {
    return (bool) preg_match('/\.min\.(js|css)$/i', $path);
  }

  private function minifyCss($css)
  {
    $length = strlen($css);
    $output = '';
    $state = 'code';
    $i = 0;

    while ($i < $length) {
      $char = $css[$i];
      $next = $i + 1 < $length ? $css[$i + 1] : '';

      if ($state === 'block_comment') {
        if ($char === '*' && $next === '/') {
          $i += 2;
          $state = 'code';
          continue;
        }
        $i++;
        continue;
      }

      if ($state === 'sq' || $state === 'dq') {
        $output .= $char;
        if ($char === '\\' && $next !== '') {
          $output .= $next;
          $i += 2;
          continue;
        }
        if (($state === 'sq' && $char === "'") || ($state === 'dq' && $char === '"')) {
          $state = 'code';
        }
        $i++;
        continue;
      }

      if ($char === '/' && $next === '*') {
        $state = 'block_comment';
        $i += 2;
        continue;
      }

      if ($char === "'") {
        $state = 'sq';
        $output .= $char;
        $i++;
        continue;
      }

      if ($char === '"') {
        $state = 'dq';
        $output .= $char;
        $i++;
        continue;
      }

      if ($char === "\t" || $char === "\n" || $char === "\r" || $char === ' ') {
        while ($i < $length && strpos(" \t\n\r", $css[$i]) !== false) {
          $i++;
        }
        $prev = $output !== '' ? $output[strlen($output) - 1] : '';
        $peek = $i < $length ? $css[$i] : '';
        if ($prev !== '' && $peek !== '' && strpos('{};:,', $prev) === false && strpos('{};:,', $peek) === false) {
          $output .= ' ';
        }
        continue;
      }

      $output .= $char;
      $i++;
    }

    return trim($output);
  }

  private function minifyJs($js)
  {
    $length = strlen($js);
    $output = '';
    $state = 'code';
    $exprDepth = 0;
    $i = 0;

    while ($i < $length) {
      $char = $js[$i];
      $next = $i + 1 < $length ? $js[$i + 1] : '';

      if ($state === 'line_comment') {
        if ($char === "\n" || $char === "\r") {
          $state = 'code';
          continue;
        }
        $i++;
        continue;
      }

      if ($state === 'block_comment') {
        if ($char === '*' && $next === '/') {
          $i += 2;
          $state = 'code';
          continue;
        }
        $i++;
        continue;
      }

      if ($state === 'sq' || $state === 'dq' || $state === 'regex') {
        $output .= $char;
        if ($char === '\\' && $next !== '') {
          $output .= $next;
          $i += 2;
          continue;
        }
        if ($state === 'sq' && $char === "'") {
          $state = $exprDepth > 0 ? 'expr' : 'code';
        } elseif ($state === 'dq' && $char === '"') {
          $state = $exprDepth > 0 ? 'expr' : 'code';
        } elseif ($state === 'regex' && $char === '/') {
          $state = $exprDepth > 0 ? 'expr' : 'code';
        }
        $i++;
        continue;
      }

      if ($state === 'template') {
        $output .= $char;
        if ($char === '\\' && $next !== '') {
          $output .= $next;
          $i += 2;
          continue;
        }
        if ($char === '`') {
          $state = 'code';
          $i++;
          continue;
        }
        if ($char === '$' && $next === '{') {
          $output .= '{';
          $i += 2;
          $state = 'expr';
          $exprDepth = 1;
          continue;
        }
        $i++;
        continue;
      }

      if ($char === '/' && $next === '/') {
        $state = 'line_comment';
        $i += 2;
        continue;
      }

      if ($char === '/' && $next === '*') {
        $state = 'block_comment';
        $i += 2;
        continue;
      }

      if ($char === '/' && $this->jsAllowsRegex($output)) {
        $output .= $char;
        $state = 'regex';
        $i++;
        continue;
      }

      if ($char === "'") {
        $output .= $char;
        $state = 'sq';
        $i++;
        continue;
      }

      if ($char === '"') {
        $output .= $char;
        $state = 'dq';
        $i++;
        continue;
      }

      if ($char === '`') {
        $output .= $char;
        $state = 'template';
        $i++;
        continue;
      }

      if ($state === 'expr') {
        if ($char === '{') {
          $exprDepth++;
        } elseif ($char === '}') {
          $exprDepth--;
          $output .= $char;
          $i++;
          if ($exprDepth === 0) {
            $state = 'template';
          }
          continue;
        } elseif ($char === '`') {
          $output .= $char;
          $state = 'template';
          $i++;
          continue;
        }
      }

      if ($char === "\t" || $char === "\n" || $char === "\r" || $char === ' ') {
        $hadNewline = false;
        while ($i < $length && strpos(" \t\n\r", $js[$i]) !== false) {
          if ($js[$i] === "\n" || $js[$i] === "\r") {
            $hadNewline = true;
          }
          $i++;
        }
        $prev = $output !== '' ? $output[strlen($output) - 1] : '';
        $peek = $i < $length ? $js[$i] : '';
        if ($prev === '' || $peek === '') {
          continue;
        }
        if ($hadNewline && $this->jsNeedsSemicolon($prev, $peek, substr($js, $i))) {
          $output .= ';';
          continue;
        }
        if ($this->jsNeedsSpace($prev, $peek)) {
          $output .= ' ';
        }
        continue;
      }

      $output .= $char;
      $i++;
    }

    return trim($output);
  }

  private function jsAllowsRegex($output)
  {
    $trim = rtrim($output);
    if ($trim === '') {
      return true;
    }

    $last = $trim[strlen($trim) - 1];

    if (strpos('(,=:[!&|?{};~^*%<>+-', $last) !== false) {
      return true;
    }

    return (bool) preg_match(
      '/(?:^|[^A-Za-z0-9_$])(?:return|throw|case|typeof|void|delete|new|in|of)$/',
      $trim,
    );
  }

  private function jsNeedsSpace($prev, $next)
  {
    return (bool) preg_match('/[A-Za-z0-9_$]/', $prev) && preg_match('/[A-Za-z0-9_$]/', $next);
  }

  private function jsNeedsSemicolon($prev, $next, $rest)
  {
    if (strpos(';{})(,', $prev) !== false) {
      return false;
    }

    if (strpos(')]}', $next) !== false) {
      return false;
    }

    if (preg_match('/^(else|catch|finally|while|of|in)(?:[^A-Za-z0-9_$]|$)/', $rest)) {
      return false;
    }

    if ($next === '+' || $next === '-') {
      return true;
    }

    return (bool) preg_match('/[A-Za-z0-9_$]/', $prev) && preg_match('/[A-Za-z0-9_$]/', $next);
  }
}
