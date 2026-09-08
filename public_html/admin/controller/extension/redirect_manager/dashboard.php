<?php

class ControllerExtensionRedirectManagerDashboard extends Controller {
	public function index() {
		$data = $this->boot('dashboard');
		$this->load->model('extension/redirect_manager/rule');
		$this->model_extension_redirect_manager_rule->ensureErrorLayouts();
		$this->model_extension_redirect_manager_rule->refreshBrokenTargets();

		$dash = $this->model_extension_redirect_manager_rule->getDashboard();
		$backend = (string)$this->config->get('module_redirect_manager_backend');

		if ($backend === '') {
			$backend = 'apache';
		}

		$sync = $this->model_extension_redirect_manager_rule->getSync($backend);
		$dirty = (int)$this->config->get('module_redirect_manager_configuration_dirty');

		$data['stats'] = $dash['stats'];
		$data['loops'] = $dash['loops'];
		$data['chains'] = $dash['chains'];
		$data['backend'] = $backend;
		$data['dirty'] = $dirty;
		$data['sync'] = $sync;
		$data['rules_hash'] = (string)$this->config->get('module_redirect_manager_rules_hash');
		$data['generate'] = $this->url->link('extension/redirect_manager/config/generate', 'user_token=' . $data['user_token'], true);
		$data['sync_url'] = $this->url->link('extension/redirect_manager/config/sync', 'user_token=' . $data['user_token'], true);
		$data['flatten'] = $this->url->link('extension/redirect_manager/dashboard/flatten', 'user_token=' . $data['user_token'], true);
		$data['batches'] = $this->model_extension_redirect_manager_rule->getBatches(0, 10);

		$this->response->setOutput($this->load->view('extension/redirect_manager/dashboard', $data));
	}

	public function log() {
		$data = $this->boot('log');
		$this->load->model('extension/redirect_manager/rule');

		$page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
		$limit = 50;
		$start = ($page - 1) * $limit;
		$data['logs'] = $this->model_extension_redirect_manager_rule->getLogs($start, $limit);
		$total = $this->model_extension_redirect_manager_rule->getTotalLogs();

		$pagination = new Pagination();
		$pagination->total = $total;
		$pagination->page = $page;
		$pagination->limit = $limit;
		$pagination->url = $this->url->link('extension/redirect_manager/dashboard/log', 'user_token=' . $data['user_token'] . '&page={page}', true);
		$data['pagination'] = $pagination->render();
		$data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? $start + 1 : 0, min($start + $limit, $total), $total, ceil($total / $limit));

		$this->response->setOutput($this->load->view('extension/redirect_manager/log', $data));
	}

	public function flatten() {
		$this->guardModify();
		$this->load->model('extension/redirect_manager/rule');
		$engine = new \redirect_manager\Engine($this->registry);
		$chains = $engine->detectChains($this->model_extension_redirect_manager_rule->getEnabledRules());

		foreach ($chains as $chain) {
			$rule = $this->model_extension_redirect_manager_rule->getRuleBySource(0, 0, $chain['source']);

			if ($rule) {
				$data = $rule;
				$data['target_url'] = $chain['final'];
				$this->model_extension_redirect_manager_rule->editRule($rule['redirect_id'], $data);
			}
		}

		$this->model_extension_redirect_manager_rule->addLog(array('action' => 'flatten_chains', 'result' => 'ok', 'message' => count($chains)));
		$this->session->data['success'] = $this->language->get('text_success');
		$this->response->redirect($this->url->link('extension/redirect_manager/dashboard', 'user_token=' . $this->session->data['user_token'], true));
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
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/redirect_manager/dashboard', 'user_token=' . $token, true))
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

	private function guardModify() {
		$this->load->language('extension/redirect_manager/redirect_manager');

		if (!$this->user->hasPermission('modify', 'extension/redirect_manager')) {
			$this->session->data['error'] = $this->language->get('error_permission');
			$this->response->redirect($this->url->link('extension/redirect_manager/dashboard', 'user_token=' . $this->session->data['user_token'], true));
		}
	}
}
