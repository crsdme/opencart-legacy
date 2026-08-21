<?php
class ControllerExtensionModuleSlideshow extends Controller
{
	public function index($setting)
	{
		if (!isset($setting['slides'])) {
			$setting['slides'] = 1;
		}

		if (!isset($setting['slides_mobile'])) {
			$setting['slides_mobile'] = 1;
		}

		if (empty($setting['width_mobile'])) {
			$setting['width_mobile'] = 375;
		}

		if (empty($setting['height_mobile'])) {
			$setting['height_mobile'] = 210;
		}

		if (!isset($setting['use_controls'])) {
			$setting['use_controls'] = 1;
		}

		if (!isset($setting['use_dots'])) {
			$setting['use_dots'] = 1;
		}

		if (!isset($setting['use_autoplay'])) {
			$setting['use_autoplay'] = 1;
		}

		if (!isset($setting['use_loop'])) {
			$setting['use_loop'] = 1;
		}

		if (!isset($setting['use_controls_mobile'])) {
			$setting['use_controls_mobile'] = $setting['use_controls'];
		}

		if (!isset($setting['use_dots_mobile'])) {
			$setting['use_dots_mobile'] = $setting['use_dots'];
		}

		if (!isset($setting['use_autoplay_mobile'])) {
			$setting['use_autoplay_mobile'] = $setting['use_autoplay'];
		}

		if (!isset($setting['use_loop_mobile'])) {
			$setting['use_loop_mobile'] = $setting['use_loop'];
		}

		return $this->load->controller('extension/module/carousel', $setting);
	}
}
