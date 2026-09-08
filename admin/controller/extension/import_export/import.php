<?php

class ControllerExtensionImportExportImport extends Controller
{
	public function index()
	{
		$data = $this->boot('import');
		$engine = new \import_export\Engine($this->registry);

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->user->hasPermission('modify', 'extension/import_export')) {
			if (!empty($this->request->post['confirm']) && !empty($this->session->data['ie_import'])) {
				$this->runImport($engine);
				return;
			}

			$preview = $this->buildPreview($engine);
			$data['preview'] = $preview;
		} else {
			$data['preview'] = isset($this->session->data['ie_import']) ? $this->session->data['ie_import']['preview'] : [];
		}

		$data['entities'] = $this->entityOptions($engine);
		$data['action'] = $this->url->link('extension/import_export/import', 'user_token=' . $data['user_token'], true);

		foreach ([
			'text_import', 'help_import', 'entry_entity', 'entry_file', 'text_or',
			'text_entity_auto', 'text_entity_bundle', 'button_preview', 'button_import',
			'text_create', 'text_update', 'text_skip', 'text_error', 'text_total',
			'column_row', 'column_entity', 'column_identity', 'column_action', 'column_message',
			'text_csv_hint',
		] as $key) {
			$data[$key] = $this->language->get($key);
		}

		$this->response->setOutput($this->load->view('extension/import_export/import', $data));
	}

	private function buildPreview($engine)
	{
		$content = '';
		$filename = '';

		if (!empty($this->request->files['import']['tmp_name']) && is_uploaded_file($this->request->files['import']['tmp_name'])) {
			$filename = $this->request->files['import']['name'];
			$content = file_get_contents($this->request->files['import']['tmp_name']);
		} elseif (!empty($this->request->post['import_text'])) {
			$filename = 'paste.json';
			$content = $this->request->post['import_text'];
		}

		if ($content === '') {
			$this->session->data['error'] = $this->language->get('error_file');
			$this->response->redirect($this->url->link('extension/import_export/import', 'user_token=' . $this->session->data['user_token'], true));
			exit;
		}

		$entity = isset($this->request->post['entity']) ? $this->request->post['entity'] : 'auto';
		$format = $engine->detectFormat($filename, $content);
		$hint = ($entity && $entity !== 'auto' && $entity !== 'bundle') ? $entity : '';

		try {
			$parsed = $engine->parse($content, $format, $hint);
			$preview = $engine->preview($parsed);
		} catch (\Exception $e) {
			$this->session->data['error'] = $e->getMessage();
			$this->response->redirect($this->url->link('extension/import_export/import', 'user_token=' . $this->session->data['user_token'], true));
			exit;
		}

		$dir = $engine->context()->storageDir();
		$path = $dir . '/pending-' . $this->session->data['user_token'] . '.' . $format;
		file_put_contents($path, $content);

		$payload = [
			'file' => $path,
			'format' => $format,
			'entity' => $hint,
			'filename' => $filename,
			'preview' => $preview,
		];
		$this->session->data['ie_import'] = $payload;

		return $preview;
	}

	private function runImport($engine)
	{
		$state = $this->session->data['ie_import'];

		if (empty($state['file']) || !is_file($state['file'])) {
			$this->session->data['error'] = $this->language->get('error_file');
			$this->response->redirect($this->url->link('extension/import_export/import', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		@set_time_limit(0);
		$content = file_get_contents($state['file']);
		$parsed = $engine->parse($content, $state['format'], $state['entity']);
		$result = $engine->import($parsed);
		@unlink($state['file']);
		unset($this->session->data['ie_import']);

		$status = $result['errors'] ? 'error' : 'success';
		$job = new \import_export\Job($this->db);
		$job->add([
			'trigger' => 'admin',
			'action' => 'import',
			'entity' => $parsed['entity'],
			'format' => $state['format'],
			'filename' => $state['filename'],
			'status' => $status,
			'created' => $result['created'],
			'updated' => $result['updated'],
			'skipped' => $result['skipped'],
			'errors' => $result['errors'],
			'message' => $result['message'],
		]);

		$this->session->data['success'] = sprintf(
			$this->language->get('text_imported'),
			$result['created'],
			$result['updated'],
			$result['skipped'],
			$result['errors']
		);

		if ($result['errors']) {
			$this->session->data['error'] = $result['message'];
		}

		$this->response->redirect($this->url->link('extension/import_export/job', 'user_token=' . $this->session->data['user_token'], true));
	}

	private function entityOptions($engine)
	{
		$options = [
			'auto' => $this->language->get('text_entity_auto'),
			'bundle' => $this->language->get('text_entity_bundle'),
		];

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
