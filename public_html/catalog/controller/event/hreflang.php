<?php
class ControllerEventHreflang extends Controller
{
	public function index(&$route, &$data, &$code)
	{
		if ($route !== 'common/head') {
			return;
		}

		$this->load->model('localisation/language');
		$this->load->library('multilang');

		$languages = $this->model_localisation_language->getLanguages();
		$hreflang = [];

		$route_for_link = isset($this->request->get['route']) ? $this->request->get['route'] : 'common/home';

		if (in_array($route_for_link, ['error/not_found', 'error/gone'], true)) {
			$data['hreflang'] = [];
			return;
		}

		$args = $this->request->get;
		unset($args['route'], $args['_route_']);

		$default_code = $this->config->get('config_language_main') ?: $this->config->get('config_language');

		$secure = !empty($this->request->server['HTTPS']) && $this->request->server['HTTPS'] !== 'off';

		$hreflang[] = [
			'href' => $this->multilang->linkForLanguage($default_code, $route_for_link, $args, $secure, $languages),
			'hreflang' => 'x-default',
		];

		foreach ($languages as $lang) {
			if (empty($lang['status'])) {
				continue;
			}

			$hreflang[] = [
				'href' => $this->multilang->linkForLanguage($lang['code'], $route_for_link, $args, $secure, $languages),
				'hreflang' => $this->multilang->hreflangCode($lang),
			];
		}

		$data['hreflang'] = $hreflang;
	}
}
