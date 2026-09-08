<?php
class ControllerCommonHome extends Controller
{
	public function index()
	{
		$this->load->model('seo/meta');

		$seo = $this->model_seo_meta->build(
			[
				'meta_title' => (string) $this->config->get('config_meta_title'),
				'meta_description' => (string) $this->config->get('config_meta_description'),
			],
			[],
			'home',
			'common/home'
		);

		$this->model_seo_meta->apply($seo);

		$this->response->setOutput($this->load->controller('common/layout', ['view' => 'common/home']));
	}
}
