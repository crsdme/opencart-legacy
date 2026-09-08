<?php
class ControllerErrorGone extends Controller
{
	public function index()
	{
		$this->request->get['route'] = 'error/gone';

		$this->load->language('error/gone');
		$this->load->model('seo/meta');

		$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 410 Gone');

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

		$data['breadcrumbs'][] = [
			'text' => $data['heading_title'],
			'href' => $this->url->link('error/gone', '', $this->request->server['HTTPS']),
		];

		$data['continue'] = $this->url->link('common/home');
		$data['text_error'] = $this->language->get('text_error');

		$data['view'] = 'error/gone';
		$this->response->setOutput($this->load->controller('common/layout', $data));
	}
}
