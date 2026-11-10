<?php
class ModelToolImage extends Model
{
  public function resize($filename, $width, $height, $watermark = false)
  {
    if (!is_file(DIR_IMAGE . $filename) || substr(str_replace('\\', '/', realpath(DIR_IMAGE . $filename)), 0, strlen(DIR_IMAGE)) != str_replace('\\', '/', DIR_IMAGE)) {
      return;
    }

    $extension = pathinfo($filename, PATHINFO_EXTENSION);

    $image_old = $filename;
    $apply_watermark = $watermark && $this->shouldApplyWatermark($filename);
    $wm_suffix = $apply_watermark ? $this->getWatermarkCacheSuffix() : '';

    $image_new = 'cache/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . $wm_suffix . '.' . $extension;
    $image_new_webp = 'cache/webp/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . $wm_suffix . '.webp';

    $need_rebuild = !is_file(DIR_IMAGE . $image_new) || (filemtime(DIR_IMAGE . $image_old) > filemtime(DIR_IMAGE . $image_new));

    if (!$need_rebuild && $apply_watermark) {
      $wm_file = DIR_IMAGE . $this->config->get('config_watermark_image');

      if (is_file($wm_file) && filemtime($wm_file) > filemtime(DIR_IMAGE . $image_new)) {
        $need_rebuild = true;
      }
    }

    if ($need_rebuild) {
      list($width_orig, $height_orig, $image_type) = getimagesize(DIR_IMAGE . $image_old);

      if (!in_array($image_type, array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_WEBP))) {
        return DIR_IMAGE . $image_old;
      }

      $path = '';

      $directories = explode('/', dirname($image_new));

      foreach ($directories as $directory) {
        $path = $path . '/' . $directory;

        if (!is_dir(DIR_IMAGE . $path)) {
          @mkdir(DIR_IMAGE . $path, 0777);
        }
      }

      if ($width_orig != $width || $height_orig != $height || $apply_watermark) {
        $image = new Image(DIR_IMAGE . $image_old);

        if ($width_orig != $width || $height_orig != $height) {
          $image->resize($width, $height);
        }

        if ($apply_watermark) {
          $this->applyWatermark($image);
        }

        $image->save(DIR_IMAGE . $image_new);
      } else {
        copy(DIR_IMAGE . $image_old, DIR_IMAGE . $image_new);
      }
    }

    $can_webp = function_exists('imagewebp') && isset($this->request->server['HTTP_ACCEPT']) && strpos($this->request->server['HTTP_ACCEPT'], 'image/webp') !== false;

    if ($can_webp) {
      $need_webp_rebuild = !is_file(DIR_IMAGE . $image_new_webp);

      if (!$need_webp_rebuild && is_file(DIR_IMAGE . $image_new)) {
        $need_webp_rebuild = filemtime(DIR_IMAGE . $image_new) > filemtime(DIR_IMAGE . $image_new_webp);
      }

      if ($need_webp_rebuild && is_file(DIR_IMAGE . $image_new)) {
        $path_webp = '';
        $directories_webp = explode('/', dirname($image_new_webp));

        foreach ($directories_webp as $directory_webp) {
          $path_webp = $path_webp . '/' . $directory_webp;

          if (!is_dir(DIR_IMAGE . $path_webp)) {
            @mkdir(DIR_IMAGE . $path_webp, 0777, true);
          }
        }

        $image_webp = new Image(DIR_IMAGE . $image_new);
        $image_webp->save(DIR_IMAGE . $image_new_webp, 85);
      }

      if (is_file(DIR_IMAGE . $image_new_webp)) {
        $image_new = $image_new_webp;
      }
    }

    $image_new = str_replace(' ', '%20', $image_new);  // fix bug when attach image on email (gmail.com). it is automatic changing space " " to +

    if ($this->request->server['HTTPS']) {
      return $this->config->get('config_ssl') . 'image/' . $image_new;
    } else {
      return $this->config->get('config_url') . 'image/' . $image_new;
    }
  }

  private function shouldApplyWatermark($filename)
  {
    if (!$this->config->get('config_watermark_status')) {
      return false;
    }

    $wm_image = $this->config->get('config_watermark_image');

    if (!$wm_image || !is_file(DIR_IMAGE . $wm_image)) {
      return false;
    }

    $basename = basename($filename);

    if (in_array($basename, array('placeholder.png', 'no_image.png', 'no_image.jpg'), true)) {
      return false;
    }

    $extension = strtolower(pathinfo($wm_image, PATHINFO_EXTENSION));

    return $extension === 'png';
  }

  private function getWatermarkCacheSuffix()
  {
    $parts = array(
      (string)$this->config->get('config_watermark_image'),
      (string)$this->config->get('config_watermark_style'),
      (string)$this->config->get('config_watermark_position'),
      (string)$this->config->get('config_watermark_scale'),
      (string)$this->config->get('config_watermark_opacity'),
      (string)$this->config->get('config_watermark_angle'),
      (string)$this->config->get('config_watermark_spacing'),
    );

    return '-wm' . substr(md5(implode('|', $parts)), 0, 8);
  }

  private function applyWatermark(Image $image)
  {
    $wm_path = DIR_IMAGE . $this->config->get('config_watermark_image');

    if (!is_file($wm_path)) {
      return;
    }

    $stamp = new Image($wm_path);

    if (!$stamp->getImage() || !$stamp->getWidth() || !$stamp->getHeight()) {
      return;
    }

    $scale = (int)$this->config->get('config_watermark_scale');

    if ($scale < 1) {
      $scale = 25;
    } elseif ($scale > 100) {
      $scale = 100;
    }

    $target_w = max(1, (int)($image->getWidth() * $scale / 100));
    $target_h = max(1, (int)($stamp->getHeight() * $target_w / $stamp->getWidth()));

    if ($stamp->getWidth() != $target_w || $stamp->getHeight() != $target_h) {
      $stamp->resize($target_w, $target_h);
    }

    $style = $this->config->get('config_watermark_style');

    if (!in_array($style, array('corner', 'tile', 'diagonal'), true)) {
      $style = 'corner';
    }

    $opacity = (int)$this->config->get('config_watermark_opacity');

    if ($opacity < 1) {
      $opacity = 70;
    } elseif ($opacity > 100) {
      $opacity = 100;
    }

    $angle = (int)$this->config->get('config_watermark_angle');

    if ($angle === 0 && $style === 'diagonal') {
      $angle = -30;
    }

    $spacing = (int)$this->config->get('config_watermark_spacing');

    if ($spacing < 0) {
      $spacing = 50;
    }

    if ($style === 'tile') {
      $mode = 'tile';
    } elseif ($style === 'diagonal') {
      $mode = 'diagonal';
    } else {
      $mode = $this->config->get('config_watermark_position');

      $allowed = array(
        'topleft', 'topcenter', 'topright',
        'middleleft', 'middlecenter', 'middleright',
        'bottomleft', 'bottomcenter', 'bottomright',
      );

      if (!in_array($mode, $allowed, true)) {
        $mode = 'bottomright';
      }
    }

    $image->watermark($stamp, $mode, $opacity, $angle, $spacing);
  }
}
