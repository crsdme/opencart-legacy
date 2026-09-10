<?php
class ControllerDocsIndex extends Controller
{
	public function index()
	{
		$lang = $this->docsLanguage();
		$docs = \Custom\Docs::all($lang);
		$current_id = isset($this->request->get['doc']) ? (string) $this->request->get['doc'] : '';
		$current = null;

		if ($current_id !== '') {
			$current = \Custom\Docs::get($current_id, $lang);

			if (!$current) {
				$this->response->redirect($this->docsLink('', $lang));
				return;
			}
		} elseif ($docs) {
			$current = $docs[0];
		}

		$group_labels = \Custom\Docs::groups($lang);
		$groups = [];

		foreach ($group_labels as $group_id => $title) {
			$groups[$group_id] = [
				'title' => $title,
				'items' => [],
			];
		}

		foreach ($docs as $doc) {
			$group_id = isset($doc['group']) && isset($groups[$doc['group']]) ? $doc['group'] : 'general';

			if (!isset($groups[$group_id])) {
				$groups[$group_id] = [
					'title' => $group_id,
					'items' => [],
				];
			}

			$groups[$group_id]['items'][] = [
				'title' => $doc['title'],
				'href' => $this->docsLink($doc['id'], $lang),
				'active' => $current && $doc['id'] === $current['id'],
			];
		}

		$groups = array_values(array_filter($groups, function ($group) {
			return !empty($group['items']);
		}));

		$data['title'] = $current ? $current['title'] . ' — Docs' : 'Docs';
		$data['page'] = 'docs';
		$data['view'] = 'docs/guide';
		$data['groups'] = $groups;
		$data['current'] = $current;
		$data['html'] = '';
		$data['toc'] = [];
		$data['source'] = '';
		$data['docs_lang'] = $lang;
		$data['toc_title'] = $lang === 'ru' ? 'На странице' : 'On this page';
		$data['languages'] = $this->languageLinks($current ? $current['id'] : '', $lang);

		if ($current) {
			$markdown = new \Custom\Markdown();
			$self = $this;

			$data['html'] = $markdown->parse(
				$current['markdown'],
				function ($href) use ($self, $docs, $lang) {
					return $self->resolveDocLink($href, $docs, $lang);
				},
				function ($src) {
					return \Custom\Docs::mediaSrc($src);
				}
			);
			$data['html'] = preg_replace('/^<h1\b[^>]*>.*?<\/h1>\s*/s', '', $data['html'], 1);
			$data['toc'] = $markdown->toc($data['html']);
			$data['source'] = $current['relative'];
		}

		$this->response->setOutput($this->load->controller('docs/layout', $data));
	}

	private function docsLanguage()
	{
		$requested = '';

		if (isset($this->request->get['lang'])) {
			$requested = (string) $this->request->get['lang'];
		} elseif (isset($this->request->cookie['docs_lang'])) {
			$requested = (string) $this->request->cookie['docs_lang'];
		}

		$lang = \Custom\Docs::normalizeLanguage($requested);

		if (!isset($this->request->cookie['docs_lang']) || $this->request->cookie['docs_lang'] !== $lang) {
			setcookie('docs_lang', $lang, time() + 365 * 86400, '/', '', false, true);
		}

		return $lang;
	}

	private function docsLink($id, $lang)
	{
		$args = 'lang=' . $lang;

		if ($id !== '') {
			$args = 'doc=' . $id . '&' . $args;
		}

		return $this->url->link('docs/index', $args);
	}

	private function languageLinks($doc_id, $current_lang)
	{
		$links = [];

		foreach (\Custom\Docs::languages() as $code => $label) {
			$links[] = [
				'code' => $code,
				'label' => $label,
				'href' => $this->docsLink($doc_id, $code),
				'active' => $code === $current_lang,
			];
		}

		return $links;
	}

	private function resolveDocLink($href, array $docs, $lang)
	{
		$href = trim((string) $href);

		if ($href === '' || preg_match('~^(https?:|mailto:|#)~i', $href)) {
			return $href;
		}

		$path = $href;
		$fragment = '';

		if (strpos($href, '#') !== false) {
			$parts = explode('#', $href, 2);
			$path = $parts[0];
			$fragment = '#' . $parts[1];
		}

		if (!preg_match('/\.md$/i', $path)) {
			return $href;
		}

		$id = strtolower(basename($path, '.md'));

		if ($id === 'documentation') {
			$id = 'overview';
		}

		foreach ($docs as $doc) {
			if ($doc['id'] === $id) {
				return $this->docsLink($doc['id'], $lang) . $fragment;
			}
		}

		return $href;
	}
}
