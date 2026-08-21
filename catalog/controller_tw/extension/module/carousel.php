<?php
class ControllerExtensionModuleCarousel extends Controller
{
	public function index($setting)
	{
		static $module = 0;

		if (empty($setting['banner_id'])) {
			return '';
		}

		$this->load->model('design/banner');
		$this->load->model('tool/image');

		$this->document->addScript('catalog/view/theme/tailwind/javascript/embla-carousel.js');

		$width = (int) $setting['width'];
		$height = (int) $setting['height'];
		$width_mobile = !empty($setting['width_mobile']) ? (int) $setting['width_mobile'] : $width;
		$height_mobile = !empty($setting['height_mobile']) ? (int) $setting['height_mobile'] : $height;
		$slides = !empty($setting['slides']) ? max(1, (int) $setting['slides']) : 5;
		$slides_mobile = !empty($setting['slides_mobile']) ? max(1, (int) $setting['slides_mobile']) : $slides;
		$gap = isset($setting['gap']) && $setting['gap'] !== '' ? max(0, (int) $setting['gap']) : 8;
		$breakpoint = !empty($setting['breakpoint']) ? max(1, (int) $setting['breakpoint']) : 768;
		$autoplay_delay = !empty($setting['autoplay_delay']) ? max(1, (int) $setting['autoplay_delay']) : 3;

		$data['banners'] = [];

		$results = $this->model_design_banner->getBanner($setting['banner_id']);

		foreach ($results as $result) {
			if (!is_file(DIR_IMAGE . $result['image'])) {
				continue;
			}

			$mobile_image = !empty($result['mobile_image']) && is_file(DIR_IMAGE . $result['mobile_image'])
				? $result['mobile_image']
				: $result['image'];

			$data['banners'][] = [
				'title' => $result['title'],
				'link' => $result['link'],
				'image' => $this->model_tool_image->resize($result['image'], $width, $height),
				'image_mobile' => $this->model_tool_image->resize($mobile_image, $width_mobile, $height_mobile),
			];
		}

		if (!$data['banners']) {
			return '';
		}

		$use_autoplay = $this->isEnabled($setting, 'use_autoplay', true);
		$use_controls = $this->isEnabled($setting, 'use_controls', false);
		$use_dots = $this->isEnabled($setting, 'use_dots', false);
		$use_loop = $this->isEnabled($setting, 'use_loop', true);

		$data['config'] = [
			'id' => 'carousel-' . $module,
			'use_autoplay' => $use_autoplay,
			'use_controls' => $use_controls,
			'use_dots' => $use_dots,
			'use_loop' => $use_loop,
			'use_autoplay_mobile' => $this->isEnabled($setting, 'use_autoplay_mobile', $use_autoplay),
			'use_controls_mobile' => $this->isEnabled($setting, 'use_controls_mobile', $use_controls),
			'use_dots_mobile' => $this->isEnabled($setting, 'use_dots_mobile', $use_dots),
			'use_loop_mobile' => $this->isEnabled($setting, 'use_loop_mobile', $use_loop),
			'use_picture' => true,
			'slides' => $slides,
			'slides_mobile' => $slides_mobile,
			'gap' => $gap,
			'breakpoint' => $breakpoint,
			'autoplay_delay' => $autoplay_delay,
		];

		$data['module'] = $module++;

		return $this->load->view('extension/module/carousel', $data);
	}

	private function isEnabled($setting, $key, $default)
	{
		if (!isset($setting[$key]) || $setting[$key] === '') {
			return $default;
		}

		return (bool) (int) $setting[$key];
	}
}
