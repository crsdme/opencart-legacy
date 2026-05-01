<?php

class Minifier
{
  private $config;
  private $request;
  private $enabled;
  private $cacheDir;
  private $cacheUrlBase;

  public function __construct($registry)
  {
    $this->config = $registry->get('config');
    $this->request = $registry->get('request');

    $storeId = (int)$this->config->get('config_store_id');
    $this->enabled = false;
    // Public cache directory: /image/cache is web-accessible.
    $this->cacheDir = rtrim(DIR_IMAGE, '/\\') . '/cache/minifier/' . $storeId . '/';
    $this->cacheUrlBase = 'image/cache/minifier/' . $storeId . '/';

    if (!is_dir($this->cacheDir)) {
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

    $local = array();
    $external = array();

    foreach ($styles as $style) {
      $href = isset($style['href']) ? $style['href'] : '';
      if ($href === '') {
        continue;
      }

      if ($this->isLocalAsset($href)) {
        $local[] = $style;
      } else {
        $external[] = $style;
      }
    }

    $output = '';

    if ($local) {
      $combined = $this->buildCssBundle($local);
      if ($combined !== null) {
        $output .= '<link href="' . $combined . '" type="text/css" rel="stylesheet" media="screen" />' . PHP_EOL;
      } else {
        $output .= $this->renderOriginalStyles($local);
      }
    }

    if ($external) {
      $output .= $this->renderOriginalStyles($external);
    }

    return $output;
  }

  public function renderScripts(array $scripts, $position = 'header')
  {
    if (!$scripts) {
      return '';
    }

    if (!$this->enabled) {
      return $this->renderOriginalScripts($scripts);
    }

    $local = array();
    $external = array();

    foreach ($scripts as $script) {
      if (!is_string($script) || $script === '') {
        continue;
      }

      if ($this->isLocalAsset($script)) {
        $local[] = $script;
      } else {
        $external[] = $script;
      }
    }

    $output = '';

    if ($local) {
      $combined = $this->buildJsBundle($local, $position);
      if ($combined !== null) {
        $output .= '<script src="' . $combined . '" type="text/javascript"></script>' . PHP_EOL;
      } else {
        $output .= $this->renderOriginalScripts($local);
      }
    }

    if ($external) {
      $output .= $this->renderOriginalScripts($external);
    }

    return $output;
  }

  private function buildCssBundle(array $styles)
  {
    $resolved = $this->resolveAssetFiles($styles, 'href');
    if (!$resolved) {
      return null;
    }

    $hash = $this->buildHash($resolved, 'css');
    $filename = 'bundle-' . $hash . '.css';
    $absoluteBundle = $this->cacheDir . $filename;

    if (!is_file($absoluteBundle)) {
      $content = '';

      foreach ($resolved as $item) {
        $css = file_get_contents($item['path']);
        if ($css === false) {
          continue;
        }

        $content .= $this->minifyCss($css) . PHP_EOL;
      }

      if ($content === '') {
        return null;
      }

      file_put_contents($absoluteBundle, $content, LOCK_EX);
    }

    return $this->cacheUrlBase . $filename;
  }

  private function buildJsBundle(array $scripts, $position)
  {
    $resolved = $this->resolveAssetFiles($scripts, null);
    if (!$resolved) {
      return null;
    }

    $hash = $this->buildHash($resolved, 'js-' . $position);
    $filename = 'bundle-' . $hash . '.js';
    $absoluteBundle = $this->cacheDir . $filename;

    if (!is_file($absoluteBundle)) {
      $content = '';

      foreach ($resolved as $item) {
        $js = file_get_contents($item['path']);
        if ($js === false) {
          continue;
        }

        $content .= ';' . $this->minifyJs($js) . PHP_EOL;
      }

      if ($content === '') {
        return null;
      }

      file_put_contents($absoluteBundle, $content, LOCK_EX);
    }

    return $this->cacheUrlBase . $filename;
  }

  private function resolveAssetFiles(array $assets, $key = null)
  {
    $resolved = array();

    foreach ($assets as $asset) {
      $assetPath = $key !== null
        ? (isset($asset[$key]) ? $asset[$key] : '')
        : $asset;

      $publicPath = $this->stripQueryString($assetPath);
      $publicPath = ltrim($publicPath, '/');
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
      $signature .= $file['path'] . ':' . (int)$file['mtime'] . ':' . (int)$file['size'] . '|';
    }

    return md5($signature);
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

      $output .= '<script src="' . $script . '" type="text/javascript"></script>' . PHP_EOL;
    }

    return $output;
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

  private function minifyCss($css)
  {
    $css = preg_replace('~\/\*.*?\*\/~s', '', $css);
    $css = preg_replace('/\s+/', ' ', $css);
    $css = preg_replace('/\s*([{};,:])\s*/', '$1', $css);
    return trim($css);
  }

  private function minifyJs($js)
  {
    $lines = preg_split('/\R/', $js);
    $buffer = array();

    foreach ($lines as $line) {
      $trimmed = trim($line);
      if ($trimmed === '') {
        continue;
      }
      $buffer[] = $trimmed;
    }

    return implode('', $buffer);
  }
}
