<?php
class ControllerDocsIndex extends Controller
{
	public function index()
	{
		$docs = \Custom\Docs::all();
		$current_id = isset($this->request->get['doc']) ? (string) $this->request->get['doc'] : '';
		$current = null;

		if ($current_id !== '') {
			$current = \Custom\Docs::get($current_id);

			if (!$current) {
				$this->response->redirect($this->url->link('docs/index'));
				return;
			}
		} elseif ($docs) {
			$current = $docs[0];
		}

		$groups = [
			[
				'title' => 'Guides',
				'items' => [],
			],
		];

		foreach ($docs as $doc) {
			$groups[0]['items'][] = [
				'title' => $doc['title'],
				'href' => $doc['id'] === 'readme'
					? $this->url->link('docs/index')
					: $this->url->link('docs/index', 'doc=' . $doc['id']),
				'active' => $current && $doc['id'] === $current['id'],
			];
		}

		$data['title'] = $current ? $current['title'] . ' — Docs' : 'Docs';
		$data['page'] = 'docs';
		$data['view'] = 'docs/guide';
		$data['groups'] = $groups;
		$data['current'] = $current;
		$data['html'] = '';
		$data['toc'] = [];
		$data['source'] = '';

		if ($current) {
			$markdown = new \Custom\Markdown();
			$self = $this;

			$data['html'] = $markdown->parse($current['markdown'], function ($href) use ($self, $docs) {
				return $self->resolveDocLink($href, $docs);
			});
			$data['html'] = preg_replace('/^<h1\b[^>]*>.*?<\/h1>\s*/s', '', $data['html'], 1);
			$data['toc'] = $markdown->toc($data['html']);
			$data['source'] = $current['relative'];
		}

		$this->response->setOutput($this->load->controller('docs/layout', $data));
	}

	private function resolveDocLink($href, array $docs)
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
				return $this->url->link('docs/index', 'doc=' . $doc['id']) . $fragment;
			}
		}

		return $href;
	}
}
