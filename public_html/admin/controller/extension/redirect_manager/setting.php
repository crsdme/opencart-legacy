<?php

class ControllerExtensionRedirectManagerSetting extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/redirect_manager/redirect_manager');
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$post = $this->request->post;
			$engine = new \redirect_manager\Engine($this->registry);
			$current = (string)$this->config->get('module_redirect_manager_cloudflare_token');

			if (empty($post['module_redirect_manager_cloudflare_token']) || $post['module_redirect_manager_cloudflare_token'] === \redirect_manager\Engine::TOKEN_MASK) {
				$post['module_redirect_manager_cloudflare_token'] = $current;
			} else {
				$post['module_redirect_manager_cloudflare_token'] = $engine->encryptValue($post['module_redirect_manager_cloudflare_token']);
			}

			$keep = array(
				'module_redirect_manager_configuration_dirty' => $this->config->get('module_redirect_manager_configuration_dirty'),
				'module_redirect_manager_rules_hash'          => $this->config->get('module_redirect_manager_rules_hash')
			);

			$post = array_merge($keep, $post);
			$this->model_setting_setting->editSetting('module_redirect_manager', $post);
			$this->load->model('extension/redirect_manager/rule');
			$this->model_extension_redirect_manager_rule->addLog(array('action' => 'settings_saved', 'result' => 'ok'));
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/redirect_manager/setting', 'user_token=' . $this->session->data['user_token'], true));
		}

		$data = $this->boot('setting');
		$keys = array(
			'module_redirect_manager_status',
			'module_redirect_manager_backend',
			'module_redirect_manager_apache_mode',
			'module_redirect_manager_apache_file',
			'module_redirect_manager_nginx_file',
			'module_redirect_manager_allow_reload',
			'module_redirect_manager_allowed_paths',
			'module_redirect_manager_cloudflare_account_id',
			'module_redirect_manager_cloudflare_list_id',
			'module_redirect_manager_cloudflare_rule_id',
			'module_redirect_manager_cloudflare_domain',
			'module_redirect_manager_sync_mode',
			'module_redirect_manager_auto_seo_redirect',
			'module_redirect_manager_deleted_product_action',
			'module_redirect_manager_delete_data_on_uninstall'
		);

		foreach ($keys as $key) {
			$data[$key] = isset($this->request->post[$key]) ? $this->request->post[$key] : $this->config->get($key);
		}

		$token = (string)$this->config->get('module_redirect_manager_cloudflare_token');
		$engine = new \redirect_manager\Engine($this->registry);
		$data['module_redirect_manager_cloudflare_token'] = $engine->maskToken($token);
		$data['has_token'] = ($token !== '');
		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : $data['error_warning'];
		$data['action'] = $this->url->link('extension/redirect_manager/setting', 'user_token=' . $data['user_token'], true);
		$data['storage_root'] = $engine->storageRoot();
		$data['htaccess_path'] = $engine->documentRoot() . '/.htaccess';

		$this->response->setOutput($this->load->view('extension/redirect_manager/setting', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/redirect_manager')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		$backend = isset($this->request->post['module_redirect_manager_backend']) ? $this->request->post['module_redirect_manager_backend'] : '';

		if (!in_array($backend, array('apache', 'nginx', 'cloudflare', 'php'), true)) {
			$this->error['warning'] = $this->language->get('error_backend');
		}

		$engine = new \redirect_manager\Engine($this->registry);
		$backend_obj = new \redirect_manager\Backend($this->registry, $backend);

		if ($backend === 'apache' && !empty($this->request->post['module_redirect_manager_apache_file'])) {
			$allow_htaccess = (!empty($this->request->post['module_redirect_manager_apache_mode']) && $this->request->post['module_redirect_manager_apache_mode'] === 'htaccess');

			if (!$backend_obj->pathAllowed($this->request->post['module_redirect_manager_apache_file'], $allow_htaccess)) {
				$this->error['warning'] = $this->language->get('error_path');
			}
		}

		if ($backend === 'nginx' && !empty($this->request->post['module_redirect_manager_nginx_file'])) {
			if (!$backend_obj->pathAllowed($this->request->post['module_redirect_manager_nginx_file'])) {
				$this->error['warning'] = $this->language->get('error_path');
			}
		}

		return !$this->error;
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
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/redirect_manager/setting', 'user_token=' . $token, true))
		);
		$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
		unset($this->session->data['success']);
		$data['error_warning'] = '';
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$data['can_modify'] = $this->user->hasPermission('modify', 'extension/redirect_manager');

		return $data;
	}
}
