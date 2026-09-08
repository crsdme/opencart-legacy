<?php

class ControllerExtensionImportExportTemplate extends Controller
{
	public function index()
	{
		$data = $this->boot('template');
		$engine = new \import_export\Engine($this->registry);
		$data['entities'] = $this->entityOptions($engine);
		$data['formats'] = $engine->formatCodes();
		$data['action'] = $this->url->link('extension/import_export/template/download', 'user_token=' . $data['user_token'], true);

		foreach ([
			'text_template', 'help_template', 'entry_entity', 'entry_format',
			'text_entity_bundle', 'button_download',
		] as $key) {
			$data[$key] = $this->language->get($key);
		}

		$this->response->setOutput($this->load->view('extension/import_export/template', $data));
	}

	public function download()
	{
		$this->load->language('extension/import_export/import_export');

		if (!$this->user->hasPermission('access', 'extension/import_export')) {
			$this->session->data['error'] = $this->language->get('error_permission');
			$this->response->redirect($this->url->link('extension/import_export/template', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		$entity = $this->request->post['entity'] ?? $this->request->get['entity'] ?? 'product';
		$format = $this->request->post['format'] ?? $this->request->get['format'] ?? 'json';
		$engine = new \import_export\Engine($this->registry);

		try {
			$file = $engine->template($entity, $format);
		} catch (\Exception $e) {
			$this->session->data['error'] = $e->getMessage();
			$this->response->redirect($this->url->link('extension/import_export/template', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		$this->response->addHeader('Content-Type: ' . $file['mime'] . '; charset=utf-8');
		$this->response->addHeader('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
		$this->response->setOutput($file['content']);
	}

	private function entityOptions($engine)
	{
		$options = ['bundle' => $this->language->get('text_entity_bundle')];

		foreach ($engine->entityCodes() as $code) {
			$options[$code] = $this->language->get('text_entity_' . $code);
		}

		return $options;
	}

	private function boot($active)
	{
		$this->load->language('extension/import_export/import_export');
		$this->document->setTitle($this->language->get('heading_title'));
		$engine = new \import_export\Engine($this->registry);
		$token = $this->session->data['user_token'];
		$tabs = $engine->tabs($this->url, $token, $active);

		foreach ($tabs as &$tab) {
			$tab['name'] = $this->language->get($tab['text']);
		}

		$data = [];
		$data['user_token'] = $token;
		$data['tabs'] = $tabs;
		$data['heading_title'] = $this->language->get('heading_title');
		$data['breadcrumbs'] = [
			['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $token, true)],
			['text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/import_export/dashboard', 'user_token=' . $token, true)],
		];
		$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
		unset($this->session->data['success']);
		$data['error_warning'] = isset($this->session->data['error']) ? $this->session->data['error'] : '';
		unset($this->session->data['error']);
		$data['can_modify'] = $this->user->hasPermission('modify', 'extension/import_export');
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		return $data;
	}
}
