<?php
class ControllerErrorNotFound extends Controller
{
	public function index()
	{
		$this->request->get['route'] = 'error/not_found';

		$this->load->language('error/not_found');
		$this->load->model('seo/meta');

		$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

		$seo = $this->model_seo_meta->build([
			'meta_title' => $this->language->get('meta_title'),
			'meta_description' => $this->language->get('meta_description'),
			'meta_h1' => $this->language->get('meta_title'),
			'robots' => 'noindex,follow',
		]);

		$this->model_seo_meta->apply($seo);
		$data['heading_title'] = $seo['h1'];

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home'),
		];

		if (isset($this->request->get['route'])) {
			$url_data = $this->request->get;

			unset($url_data['_route_']);

			$route = $url_data['route'];

			unset($url_data['route']);

			$url = '';

			if ($url_data) {
				$url = '&' . urldecode(http_build_query($url_data, '', '&'));
			}

			$data['breadcrumbs'][] = [
				'text' => $data['heading_title'],
				'href' => $this->url->link($route, $url, $this->request->server['HTTPS']),
			];
		}

		$data['continue'] = $this->url->link('common/home');

		$data['view'] = 'error/not_found';
		$this->response->setOutput($this->load->controller('common/layout', $data));
	}
}
