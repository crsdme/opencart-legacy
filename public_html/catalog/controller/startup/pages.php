<?php
class ControllerStartupPages extends Controller
{
	public function index()
	{
		if (PHP_SAPI === 'cli') {
			return;
		}

		$route = isset($this->request->get['route']) ? $this->request->get['route'] : '';

		$pages = new \Custom\Pages($this->config);

		if (!$pages->routeAllowed($route)) {
			$this->request->get['route'] = 'error/not_found';
			return;
		}

		if (\Custom\Setting::theme($this->config, 'account_auth', 'password') === 'phone') {
			$normalized = strtolower(trim((string) $route, '/'));

			if (substr($normalized, -6) === '/index') {
				$normalized = substr($normalized, 0, -6);
			}

			if ($normalized === 'account/register' || strpos($normalized, 'account/register/') === 0) {
				$this->response->redirect($this->url->link('account/login', '', true));
			}

			if (
				$normalized === 'account/forgotten' ||
				strpos($normalized, 'account/forgotten/') === 0 ||
				$normalized === 'account/reset' ||
				strpos($normalized, 'account/reset/') === 0
			) {
				$this->response->redirect($this->url->link('account/login', '', true));
			}

			if ($normalized === 'account/password' || strpos($normalized, 'account/password/') === 0) {
				if ($this->customer->isLogged()) {
					$this->response->redirect($this->url->link('account/account', '', true));
				} else {
					$this->response->redirect($this->url->link('account/login', '', true));
				}
			}
		}
	}
}
