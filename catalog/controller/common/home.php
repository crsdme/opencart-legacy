<?php
class ControllerCommonHome extends Controller
{
	public function index()
	{
		$this->load->model('seo/meta');

		$seo = $this->model_seo_meta->build([], [], 'home', 'common/home');

		$this->model_seo_meta->apply($seo);

		$this->response->setOutput($this->load->controller('common/layout', ['view' => 'common/home']));
	}
}
