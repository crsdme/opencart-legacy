<?php
class ControllerCommonDocs extends Controller
{
	public function index()
	{
		$query = isset($this->request->get['doc']) ? 'doc=' . $this->request->get['doc'] : '';

		$this->response->redirect($this->url->link('docs/index', $query));
	}
}
