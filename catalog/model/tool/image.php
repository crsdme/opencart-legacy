<?php
class ModelToolImage extends Model
{
  public function resize($filename, $width, $height)
  {
    if (!is_file(DIR_IMAGE . $filename) || substr(str_replace('\\', '/', realpath(DIR_IMAGE . $filename)), 0, strlen(DIR_IMAGE)) != str_replace('\\', '/', DIR_IMAGE)) {
      return;
    }

    $extension = pathinfo($filename, PATHINFO_EXTENSION);

    $image_old = $filename;
    $image_new = 'cache/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . '.' . $extension;
    $image_new_webp = 'cachewebp/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . '.webp';

    if (!is_file(DIR_IMAGE . $image_new) || (filemtime(DIR_IMAGE . $image_old) > filemtime(DIR_IMAGE . $image_new))) {
      list($width_orig, $height_orig, $image_type) = getimagesize(DIR_IMAGE . $image_old);

      if (!in_array($image_type, array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF))) {
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

      if ($width_orig != $width || $height_orig != $height) {
        $image = new Image(DIR_IMAGE . $image_old);
        $image->resize($width, $height);
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
}
