<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Image class
*/
class Image {
	private $file;
	private $image;
	private $width;
	private $height;
	private $bits;
	private $mime;

	/**
	 * Constructor
	 *
	 * @param	string	$file
	 *
 	*/
	public function __construct($file) {
		if (!extension_loaded('gd')) {
			exit('Error: PHP GD is not installed!');
		}
		
		if (is_file($file)) {
			$this->file = $file;

			$info = getimagesize($file);

			$this->width  = $info[0];
			$this->height = $info[1];
			$this->bits = isset($info['bits']) ? $info['bits'] : '';
			$this->mime = isset($info['mime']) ? $info['mime'] : '';

			if ($this->mime == 'image/gif') {
				$this->image = imagecreatefromgif($file);
			} elseif ($this->mime == 'image/png') {
				$this->image = imagecreatefrompng($file);
			} elseif ($this->mime == 'image/jpeg') {
				$this->image = imagecreatefromjpeg($file);
			} elseif ($this->mime == 'image/webp') {
				$this->image = imagecreatefromwebp($file);
			}
		} else {
			error_log('Error: Could not load image ' . $file . '!');
		}
	}
	
	/**
     * 
	 * 
	 * @return	string
     */
	public function getFile() {
		return $this->file;
	}

	/**
     * 
	 * 
	 * @return	array
     */
	public function getImage() {
		return $this->image;
	}
	
	/**
     * 
	 * 
	 * @return	string
     */
	public function getWidth() {
		return $this->width;
	}
	
	/**
     * 
	 * 
	 * @return	string
     */
	public function getHeight() {
		return $this->height;
	}
	
	/**
     * 
	 * 
	 * @return	string
     */
	public function getBits() {
		return $this->bits;
	}
	
	/**
     * 
	 * 
	 * @return	string
     */
	public function getMime() {
		return $this->mime;
	}
	
	/**
     * 
     *
     * @param	string	$file
	 * @param	int		$quality
     */
	public function save($file, int $quality = 90) {
		$info = pathinfo($file);

		$extension = strtolower($info['extension']);

		if (is_object($this->image) || is_resource($this->image)) {
			if ($extension == 'jpeg' || $extension == 'jpg') {
				imagejpeg($this->image, $file, $quality);
			} elseif ($extension == 'png') {
				imagepng($this->image, $file);
			} elseif ($extension == 'gif') {
				imagegif($this->image, $file);
			} elseif ($extension == 'webp') {
				imagewebp($this->image, $file);
			}

			imagedestroy($this->image);
		}
	}
	
	/**
     * 
     *
     * @param	int	$width
	 * @param	int	$height
	 * @param	string	$default
     */
	public function resize(int $width = 0, int $height = 0, $default = '') {
		if (!$this->width || !$this->height) {
			return;
		}

		$xpos = 0;
		$ypos = 0;
		$scale = 1;

		$scale_w = $width / $this->width;
		$scale_h = $height / $this->height;

		if ($default == 'w') {
			$scale = $scale_w;
		} elseif ($default == 'h') {
			$scale = $scale_h;
		} else {
			$scale = min($scale_w, $scale_h);
		}

		if ($scale == 1 && $scale_h == $scale_w && ($this->mime != 'image/png' && $this->mime != 'image/webp')) {
			return;
		}

		$new_width = (int)($this->width * $scale);
		$new_height = (int)($this->height * $scale);
		$xpos = (int)(($width - $new_width) / 2);
		$ypos = (int)(($height - $new_height) / 2);

		$image_old = $this->image;
		$this->image = imagecreatetruecolor($width, $height);

		if ($this->mime == 'image/png') {
			imagealphablending($this->image, false);
			imagesavealpha($this->image, true);

			$background = imagecolorallocatealpha($this->image, 255, 255, 255, 127);

			imagecolortransparent($this->image, $background);

		} else if ($this->mime == 'image/webp') {
			imagealphablending($this->image, false);
			imagesavealpha($this->image, true);

			$background = imagecolorallocatealpha($this->image, 255, 255, 255, 127);

			imagecolortransparent($this->image, $background);
		} else {
			$background = imagecolorallocate($this->image, 255, 255, 255);
		}

		imagefilledrectangle($this->image, 0, 0, $width, $height, $background);

		imagecopyresampled($this->image, $image_old, $xpos, $ypos, 0, 0, $new_width, $new_height, $this->width, $this->height);
		imagedestroy($image_old);

		$this->width = $width;
		$this->height = $height;
	}
	
	/**
	 * Apply a PNG watermark.
	 *
	 * Modes:
	 * - corner positions: topleft, topcenter, topright, middleleft, middlecenter, middleright, bottomleft, bottomcenter, bottomright
	 * - tile: upright duplicates across the image
	 * - diagonal: duplicates rotated at an angle across the image
	 *
	 * @param	Image	$watermark
	 * @param	string	$mode
	 * @param	int		$opacity	0-100
	 * @param	int		$angle		degrees for diagonal (e.g. -30)
	 * @param	int		$spacing	gap between tiles as % of watermark size
	 */
	public function watermark($watermark, $mode = 'bottomright', $opacity = 70, $angle = -30, $spacing = 50) {
		if (!$this->image || !$watermark || !$watermark->getImage()) {
			return;
		}

		$opacity = max(0, min(100, (int)$opacity));
		$spacing = max(0, (int)$spacing);
		$angle = (int)$angle;

		$stamp = $this->cloneGdImage($watermark->getImage());

		if ($opacity < 100) {
			$this->applyOpacity($stamp, $opacity);
		}

		imagealphablending($this->image, true);
		imagesavealpha($this->image, true);

		if ($mode === 'tile' || $mode === 'diagonal') {
			if ($mode === 'diagonal' && $angle !== 0) {
				$rotated = $this->rotateGdTransparent($stamp, $angle);
				imagedestroy($stamp);
				$stamp = $rotated;
			}

			$this->tileStamp($stamp, $spacing);
			imagedestroy($stamp);
			return;
		}

		$wm_width = imagesx($stamp);
		$wm_height = imagesy($stamp);
		$margin = max(5, (int)($this->width * 0.02));

		switch ($mode) {
			case 'topleft':
				$x = $margin;
				$y = $margin;
				break;
			case 'topcenter':
				$x = (int)(($this->width - $wm_width) / 2);
				$y = $margin;
				break;
			case 'topright':
				$x = $this->width - $wm_width - $margin;
				$y = $margin;
				break;
			case 'middleleft':
				$x = $margin;
				$y = (int)(($this->height - $wm_height) / 2);
				break;
			case 'middlecenter':
				$x = (int)(($this->width - $wm_width) / 2);
				$y = (int)(($this->height - $wm_height) / 2);
				break;
			case 'middleright':
				$x = $this->width - $wm_width - $margin;
				$y = (int)(($this->height - $wm_height) / 2);
				break;
			case 'bottomleft':
				$x = $margin;
				$y = $this->height - $wm_height - $margin;
				break;
			case 'bottomcenter':
				$x = (int)(($this->width - $wm_width) / 2);
				$y = $this->height - $wm_height - $margin;
				break;
			case 'bottomright':
			default:
				$x = $this->width - $wm_width - $margin;
				$y = $this->height - $wm_height - $margin;
				break;
		}

		imagecopy($this->image, $stamp, $x, $y, 0, 0, $wm_width, $wm_height);
		imagedestroy($stamp);
	}

	private function tileStamp($stamp, $spacing) {
		$wm_width = imagesx($stamp);
		$wm_height = imagesy($stamp);

		if ($wm_width < 1 || $wm_height < 1) {
			return;
		}

		$step_x = $wm_width + (int)($wm_width * $spacing / 100);
		$step_y = $wm_height + (int)($wm_height * $spacing / 100);

		if ($step_x < 1) {
			$step_x = $wm_width;
		}

		if ($step_y < 1) {
			$step_y = $wm_height;
		}

		$offset_y = 0;

		for ($y = -$step_y; $y < $this->height + $step_y; $y += $step_y) {
			$offset_x = ($offset_y % 2 === 0) ? 0 : (int)($step_x / 2);

			for ($x = -$step_x + $offset_x; $x < $this->width + $step_x; $x += $step_x) {
				imagecopy($this->image, $stamp, $x, $y, 0, 0, $wm_width, $wm_height);
			}

			$offset_y++;
		}
	}

	private function cloneGdImage($source) {
		$width = imagesx($source);
		$height = imagesy($source);
		$clone = imagecreatetruecolor($width, $height);

		imagealphablending($clone, false);
		imagesavealpha($clone, true);

		$transparent = imagecolorallocatealpha($clone, 0, 0, 0, 127);
		imagefilledrectangle($clone, 0, 0, $width, $height, $transparent);
		imagecopy($clone, $source, 0, 0, 0, 0, $width, $height);

		return $clone;
	}

	private function rotateGdTransparent($source, $angle) {
		$transparent = imagecolorallocatealpha($source, 0, 0, 0, 127);
		$rotated = imagerotate($source, $angle, $transparent);

		imagealphablending($rotated, false);
		imagesavealpha($rotated, true);

		return $rotated;
	}

	private function applyOpacity($image, $opacity) {
		$width = imagesx($image);
		$height = imagesy($image);
		$factor = $opacity / 100;

		imagealphablending($image, false);
		imagesavealpha($image, true);

		for ($x = 0; $x < $width; $x++) {
			for ($y = 0; $y < $height; $y++) {
				$rgba = imagecolorat($image, $x, $y);
				$alpha = ($rgba & 0x7F000000) >> 24;

				if ($alpha >= 127) {
					continue;
				}

				$r = ($rgba >> 16) & 0xFF;
				$g = ($rgba >> 8) & 0xFF;
				$b = $rgba & 0xFF;
				$new_alpha = 127 - (int)((127 - $alpha) * $factor);
				imagesetpixel($image, $x, $y, imagecolorallocatealpha($image, $r, $g, $b, $new_alpha));
			}
		}
	}
	
	/**
     * 
     *
     * @param	int		$top_x
	 * @param	int		$top_y
	 * @param	int		$bottom_x
	 * @param	int		$bottom_y
     */
	public function crop($top_x, $top_y, $bottom_x, $bottom_y) {
		$image_old = $this->image;
		$this->image = imagecreatetruecolor($bottom_x - $top_x, $bottom_y - $top_y);

		imagecopy($this->image, $image_old, 0, 0, $top_x, $top_y, $this->width, $this->height);
		imagedestroy($image_old);

		$this->width = $bottom_x - $top_x;
		$this->height = $bottom_y - $top_y;
	}
	
	/**
     * 
     *
     * @param	int		$degree
	 * @param	string	$color
     */
	public function rotate($degree, $color = 'FFFFFF') {
		$rgb = $this->html2rgb($color);

		$this->image = imagerotate($this->image, $degree, imagecolorallocate($this->image, $rgb[0], $rgb[1], $rgb[2]));

		$this->width = imagesx($this->image);
		$this->height = imagesy($this->image);
	}
	
	/**
     * 
     *
     */
	private function filter() {
        $args = func_get_args();

        call_user_func_array('imagefilter', $args);
	}
	
	/**
     * 
     *
     * @param	string	$text
	 * @param	int		$x
	 * @param	int		$y 
	 * @param	int		$size
	 * @param	string	$color
     */
	private function text($text, $x = 0, $y = 0, $size = 5, $color = '000000') {
		$rgb = $this->html2rgb($color);

		imagestring($this->image, $size, $x, $y, $text, imagecolorallocate($this->image, $rgb[0], $rgb[1], $rgb[2]));
	}
	
	/**
     * 
     *
     * @param	object	$merge
	 * @param	object	$x
	 * @param	object	$y
	 * @param	object	$opacity
     */
	private function merge($merge, $x = 0, $y = 0, $opacity = 100) {
		imagecopymerge($this->image, $merge->getImage(), $x, $y, 0, 0, $merge->getWidth(), $merge->getHeight(), $opacity);
	}
	
	/**
     * 
     *
     * @param	string	$color
	 * 
	 * @return	array
     */
	private function html2rgb($color) {
		if ($color[0] == '#') {
			$color = substr($color, 1);
		}

		if (strlen($color) == 6) {
			list($r, $g, $b) = [$color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]];
		} elseif (strlen($color) == 3) {
			list($r, $g, $b) = [$color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]];
		} else {
			return false;
		}

		$r = hexdec($r);
		$g = hexdec($g);
		$b = hexdec($b);

		return [$r, $g, $b];
	}
}
