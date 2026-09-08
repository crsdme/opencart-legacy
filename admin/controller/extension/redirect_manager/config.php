<?php

class ControllerExtensionRedirectManagerConfig extends Controller {
	public function index() {
		$data = $this->boot('config');
		$this->load->model('extension/redirect_manager/rule');
		$backend_name = (string)$this->config->get('module_redirect_manager_backend');

		if ($backend_name === '') {
			$backend_name = 'apache';
		}

		$engine = new \redirect_manager\Engine($this->registry);
		$backend = new \redirect_manager\Backend($this->registry, $backend_name);
		$sync = $this->model_extension_redirect_manager_rule->getSync($backend_name);
		$rules = $this->model_extension_redirect_manager_rule->getEnabledRules();
		$generated = $backend->generate($rules, $this->backendSettings());

		$data['backend'] = $backend_name;
		$data['sync'] = $sync;
		$data['dirty'] = (int)$this->config->get('module_redirect_manager_configuration_dirty');
		$data['generated'] = $generated;
		$data['path'] = $backend->defaultPath();
		$data['include_hint'] = $this->config->get('module_redirect_manager_nginx_file');
		$data['generate'] = $this->url->link('extension/redirect_manager/config/generate', 'user_token=' . $data['user_token'], true);
		$data['sync_url'] = $this->url->link('extension/redirect_manager/config/sync', 'user_token=' . $data['user_token'], true);
		$data['download'] = $this->url->link('extension/redirect_manager/config/download', 'user_token=' . $data['user_token'], true);
		$data['read'] = $this->url->link('extension/redirect_manager/config/read', 'user_token=' . $data['user_token'], true);
		$data['compare'] = isset($this->session->data['rm_compare']) ? $this->session->data['rm_compare'] : array();
		$data['storage_root'] = $engine->storageRoot();
		$data['loops'] = $engine->detectLoops($rules);
		$data['chains'] = $engine->detectChains($rules);

		$this->response->setOutput($this->load->view('extension/redirect_manager/config', $data));
	}

	public function generate() {
		$this->runDeploy(false);
	}

	public function sync() {
		$this->runDeploy(true);
	}

	public function download() {
		$backend_name = (string)$this->config->get('module_redirect_manager_backend');
		$backend = new \redirect_manager\Backend($this->registry, $backend_name ? $backend_name : 'apache');
		$this->load->model('extension/redirect_manager/rule');
		$result = $backend->generate($this->model_extension_redirect_manager_rule->getEnabledRules(), $this->backendSettings());
		$name = basename($result['path'] ? $result['path'] : 'redirects.conf');
		$this->response->addHeader('Content-Type: application/octet-stream');
		$this->response->addHeader('Content-Disposition: attachment; filename="' . $name . '"');
		$this->response->setOutput(isset($result['content']) ? $result['content'] : '');
	}

	public function read() {
		$this->load->language('extension/redirect_manager/redirect_manager');
		$this->load->model('extension/redirect_manager/rule');
		$backend_name = (string)$this->config->get('module_redirect_manager_backend');
		$backend = new \redirect_manager\Backend($this->registry, $backend_name ? $backend_name : 'apache');
		$existing = $backend->readExisting($this->backendSettings());
		$db_rules = $this->model_extension_redirect_manager_rule->getEnabledRules();
		$db_map = array();

		foreach ($db_rules as $rule) {
			$db_map[$rule['source_url']] = $rule;
		}

		$backend_map = array();

		foreach ($existing as $rule) {
			$backend_map[$rule['source_url']] = $rule;
		}

		$compare = array('db_only' => array(), 'backend_only' => array(), 'different' => array(), 'synced' => array());

		foreach ($db_map as $source => $rule) {
			if (!isset($backend_map[$source])) {
				$compare['db_only'][] = $rule;
			} elseif ((string)$backend_map[$source]['target_url'] !== (string)$rule['target_url'] || (int)$backend_map[$source]['http_code'] !== (int)$rule['http_code']) {
				$compare['different'][] = array('db' => $rule, 'backend' => $backend_map[$source]);
			} else {
				$compare['synced'][] = $rule;
			}
		}

		foreach ($backend_map as $source => $rule) {
			if (!isset($db_map[$source])) {
				$compare['backend_only'][] = $rule;
			}
		}

		$this->session->data['rm_compare'] = array(
			'db_only'      => count($compare['db_only']),
			'backend_only' => count($compare['backend_only']),
			'different'    => count($compare['different']),
			'synced'       => count($compare['synced']),
			'sample'       => array(
				'db_only'      => array_slice($compare['db_only'], 0, 50),
				'backend_only' => array_slice($compare['backend_only'], 0, 50),
				'different'    => array_slice($compare['different'], 0, 50)
			)
		);

		if (!empty($this->request->get['import_backend']) && $this->user->hasPermission('modify', 'extension/redirect_manager')) {
			$rows = array();

			foreach ($compare['backend_only'] as $rule) {
				$rows[] = array(
					'store_id'    => 0,
					'language_id' => 0,
					'source_url'  => $rule['source_url'],
					'target_url'  => $rule['target_url'],
					'action'      => !empty($rule['action']) ? $rule['action'] : 'redirect',
					'http_code'   => $rule['http_code'],
					'query_mode'  => 'ignore',
					'source_type' => 'import',
					'target_type' => 'custom',
					'enabled'     => 1
				);
			}

			$this->model_extension_redirect_manager_rule->addRulesBatch($rows);
			$this->model_extension_redirect_manager_rule->addLog(array('action' => 'import_backend', 'result' => 'ok', 'backend' => $backend_name));
		}

		$this->response->redirect($this->url->link('extension/redirect_manager/config', 'user_token=' . $this->session->data['user_token'], true));
	}

	private function runDeploy($force_sync) {
		$this->load->language('extension/redirect_manager/redirect_manager');

		if (!$this->user->hasPermission('modify', 'extension/redirect_manager')) {
			$this->session->data['error'] = $this->language->get('error_permission');
			$this->response->redirect($this->url->link('extension/redirect_manager/config', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		$this->load->model('extension/redirect_manager/rule');
		$backend_name = (string)$this->config->get('module_redirect_manager_backend');

		if ($backend_name === '') {
			$backend_name = 'apache';
		}

		$engine = new \redirect_manager\Engine($this->registry);
		$backend = new \redirect_manager\Backend($this->registry, $backend_name);
		$rules = $this->model_extension_redirect_manager_rule->getEnabledRules();
		$hash = $engine->rulesHash($rules);
		$sync = $this->model_extension_redirect_manager_rule->getSync($backend_name);

		if (!$force_sync && $sync && $sync['rules_hash'] === $hash && $sync['status'] === 'synced') {
			$this->session->data['success'] = $this->language->get('text_hash_unchanged');
			$this->response->redirect($this->url->link('extension/redirect_manager/config', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		$this->model_extension_redirect_manager_rule->addLog(array('action' => $backend_name === 'cloudflare' ? 'cloudflare_sync_started' : 'config_generated', 'backend' => $backend_name, 'result' => 'ok'));
		$result = $backend->deploy($rules, $this->backendSettings());

		if (!empty($result['ok'])) {
			$this->model_extension_redirect_manager_rule->saveSync(array(
				'backend'       => $backend_name,
				'status'        => 'synced',
				'rules_hash'    => $hash,
				'file_hash'     => isset($result['hash']) ? $result['hash'] : '',
				'file_path'     => isset($result['path']) ? $result['path'] : '',
				'rules_count'   => isset($result['count']) ? $result['count'] : 0,
				'operation_id'  => isset($result['operation_id']) ? $result['operation_id'] : '',
				'error_message' => '',
				'date_generated'=> 1,
				'date_synced'   => 1
			));
			$this->model_extension_redirect_manager_rule->markClean($hash);
			$this->model_extension_redirect_manager_rule->addLog(array('action' => $backend_name === 'cloudflare' ? 'cloudflare_sync_completed' : 'config_generated', 'backend' => $backend_name, 'result' => 'ok', 'message' => isset($result['count']) ? $result['count'] : 0));
			$this->session->data['success'] = $this->language->get('text_generated');
		} else {
			$message = isset($result['errors'][0]['error']) ? $result['errors'][0]['error'] : 'error';
			$detail = isset($result['errors'][0]['detail']) ? $result['errors'][0]['detail'] : $message;
			$this->model_extension_redirect_manager_rule->saveSync(array(
				'backend'       => $backend_name,
				'status'        => 'error',
				'rules_hash'    => $hash,
				'error_message' => $detail,
				'operation_id'  => isset($result['operation_id']) ? $result['operation_id'] : ''
			));
			$this->model_extension_redirect_manager_rule->addLog(array('action' => $backend_name === 'cloudflare' ? 'cloudflare_sync_failed' : 'validation_error', 'backend' => $backend_name, 'result' => 'error', 'message' => $detail));
			$this->session->data['error'] = $this->language->get('error_generate') . ' ' . $detail;
		}

		$this->response->redirect($this->url->link('extension/redirect_manager/config', 'user_token=' . $this->session->data['user_token'], true));
	}

	private function backendSettings() {
		return array(
			'apache_mode'            => $this->config->get('module_redirect_manager_apache_mode'),
			'apache_file'            => $this->config->get('module_redirect_manager_apache_file'),
			'nginx_file'             => $this->config->get('module_redirect_manager_nginx_file'),
			'allow_reload'           => (int)$this->config->get('module_redirect_manager_allow_reload'),
			'cloudflare_token'       => $this->config->get('module_redirect_manager_cloudflare_token'),
			'cloudflare_account_id'  => $this->config->get('module_redirect_manager_cloudflare_account_id'),
			'cloudflare_list_id'     => $this->config->get('module_redirect_manager_cloudflare_list_id'),
			'cloudflare_domain'      => $this->config->get('module_redirect_manager_cloudflare_domain')
		);
	}

	private function boot($active) {
		$this->load->language('extension/redirect_manager/redirect_manager');
		$this->document->setTitle($this->language->get('heading_title'));
		$engine = new \redirect_manager\Engine($this->registry);
		$token = $this->session->data['user_token'];
		$tabs = $engine->getTabs($this->url, $token, $active);

		foreach ($tabs as &$tab) {
			$tab['name'] = $this->language->get($tab['text']);
		}

		$data = array();
		$data['user_token'] = $token;
		$data['tabs'] = $tabs;
		$data['heading_title'] = $this->language->get('heading_title');
		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $token, true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/redirect_manager/config', 'user_token=' . $token, true))
		);
		$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
		unset($this->session->data['success']);
		$data['error_warning'] = isset($this->session->data['error']) ? $this->session->data['error'] : '';
		unset($this->session->data['error']);
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$data['can_modify'] = $this->user->hasPermission('modify', 'extension/redirect_manager');

		return $data;
	}
}
