<?php
class ControllerCommonComponents extends Controller
{
	public function index()
	{
		$this->response->redirect($this->url->link('docs/components'));
	}
}
