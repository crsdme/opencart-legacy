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
		$lang = \Custom\Docs::normalizeLanguage(isset($this->request->cookie['docs_lang']) ? $this->request->cookie['docs_lang'] : '');

		$data['theme'] = $theme === 'dark' ? 'dark' : '';
		$data['docs_lang'] = isset($data['docs_lang']) ? $data['docs_lang'] : $lang;
		$data['link_components'] = $this->url->link('docs/components');
		$data['link_docs'] = $this->url->link('docs/index', 'lang=' . $data['docs_lang']);

		if (!isset($data['toc'])) {
			$data['toc'] = [];
		}

		if (!isset($data['languages'])) {
			$data['languages'] = [];
		}

		if (!isset($data['toc_title'])) {
			$data['toc_title'] = 'On this page';
		}

		$data['content'] = $this->load->view($data['view'], $data);

		return $this->load->view('docs/layout', $data);
	}
}
