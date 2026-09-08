<?php
class ControllerDocsLayout extends Controller
{
	public function index($data)
	{
		if (empty($data['view'])) {
			$this->response->redirect($this->url->link('docs/components'));
			return '';
		}

		$theme = $this->request->cookie['theme'] ?? '';

		$data['theme'] = $theme === 'dark' ? 'dark' : '';
		$data['link_components'] = $this->url->link('docs/components');
		$data['link_docs'] = $this->url->link('docs/index');

		if (!isset($data['toc'])) {
			$data['toc'] = [];
		}
		$data['content'] = $this->load->view($data['view'], $data);

		return $this->load->view('docs/layout', $data);
	}
}
