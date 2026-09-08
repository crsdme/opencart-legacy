<?php
class ControllerStartupRedirectManager extends Controller
{
	public function index()
	{
		if (!$this->config->get('module_redirect_manager_status')) {
			return;
		}

		if (PHP_SAPI === 'cli') {
			return;
		}

		if (!empty($this->request->post)) {
			return;
		}

		if (
			!empty($this->request->server['HTTP_X_REQUESTED_WITH']) &&
			strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
		) {
			return;
		}

		$route = isset($this->request->get['route']) ? $this->request->get['route'] : '';

		if (
			in_array(
				$route,
				array(
					'error/gone',
					'extension/feed/sitemap',
					'extension/feed/google_sitemap',
					'extension/feed/google_base',
					'extension/feed/sitemap_pro',
				),
				true
			)
		) {
			return;
		}

		$engine = new \redirect_manager\Engine($this->registry);
		$rule = $engine->matchRequest();

		if (!$rule) {
			return;
		}

		if ((isset($rule['action']) ? $rule['action'] : '') === 'redirect') {
			$target = $engine->applyQueryMode($rule);

			if ($target === '') {
				return;
			}

			$store_id = isset($rule['store_id']) ? (int)$rule['store_id'] : (int)$this->config->get('config_store_id');

			if ($store_id < 0) {
				$store_id = (int)$this->config->get('config_store_id');
			}

			$this->response->redirect($engine->absoluteUrl($target, $store_id), (int)$rule['http_code']);
		}

		$code = isset($rule['http_code']) ? (int)$rule['http_code'] : 0;

		if ($code === 410) {
			$this->request->get['route'] = 'error/gone';
			return;
		}

		if ($code === 404) {
			$this->request->get['route'] = 'error/not_found';
		}
	}
}
