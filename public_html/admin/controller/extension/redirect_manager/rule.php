<?php

class ControllerExtensionRedirectManagerRule extends Controller {
	private $error = array();

	public function index() {
		$data = $this->boot('rule');
		$this->load->model('extension/redirect_manager/rule');
		$this->load->model('setting/store');

		$filter = array(
			'filter_source'      => $this->get('filter_source', ''),
			'filter_target'      => $this->get('filter_target', ''),
			'filter_http_code'   => $this->get('filter_http_code', ''),
			'filter_action'      => $this->get('filter_action', ''),
			'filter_source_type' => $this->get('filter_source_type', ''),
			'filter_enabled'     => $this->get('filter_enabled', ''),
			'filter_broken'      => $this->get('filter_broken', ''),
			'filter_store_id'    => $this->get('filter_store_id', ''),
			'filter_batch_id'    => $this->get('filter_batch_id', '')
		);

		$page = (int)$this->get('page', 1);
		$limit = (int)$this->config->get('config_limit_admin');

		if ($limit < 1) {
			$limit = 20;
		}

		$filter_data = $filter;
		$filter_data['start'] = ($page - 1) * $limit;
		$filter_data['limit'] = $limit;
		$filter_data['sort'] = $this->get('sort', 'redirect_id');
		$filter_data['order'] = $this->get('order', 'DESC');

		$results = $this->model_extension_redirect_manager_rule->getRules($filter_data);
		$total = $this->model_extension_redirect_manager_rule->getTotalRules($filter);
		$url = $this->urlSuffix($filter, $page);

		$data['rules'] = array();

		foreach ($results as $result) {
			$data['rules'][] = array(
				'redirect_id'    => $result['redirect_id'],
				'source_url'     => $result['source_url'],
				'target_url'     => $result['target_url'],
				'action'         => $result['action'],
				'http_code'      => $result['http_code'],
				'source_type'    => $result['source_type'],
				'source_entity_id' => $result['source_entity_id'],
				'enabled'        => $result['enabled'],
				'date_added'     => $result['date_added'],
				'date_modified'  => $result['date_modified'],
				'edit'           => $this->url->link('extension/redirect_manager/rule/edit', 'user_token=' . $data['user_token'] . '&redirect_id=' . $result['redirect_id'] . $url, true)
			);
		}

		foreach ($filter as $key => $value) {
			$data[$key] = $value;
		}

		$data['sort'] = $filter_data['sort'];
		$data['order'] = $filter_data['order'];
		$data['add'] = $this->url->link('extension/redirect_manager/rule/add', 'user_token=' . $data['user_token'], true);
		$data['delete'] = $this->url->link('extension/redirect_manager/rule/delete', 'user_token=' . $data['user_token'], true);
		$data['bulk'] = $this->url->link('extension/redirect_manager/rule/bulk', 'user_token=' . $data['user_token'], true);
		$data['export'] = $this->url->link('extension/redirect_manager/rule/export', 'user_token=' . $data['user_token'] . $url, true);
		$data['stores'] = $this->getStores();
		$data['engine'] = new \redirect_manager\Engine($this->registry);
		$data['dirty'] = (int)$this->config->get('module_redirect_manager_configuration_dirty');
		$data['http_codes'] = array(301, 302, 303, 307, 308, 404, 410, 451);
		$data['config_url'] = $this->url->link('extension/redirect_manager/config', 'user_token=' . $data['user_token'], true);

		$pagination = new Pagination();
		$pagination->total = $total;
		$pagination->page = $page;
		$pagination->limit = $limit;
		$pagination->url = $this->url->link('extension/redirect_manager/rule', 'user_token=' . $data['user_token'] . $this->urlSuffix($filter, false) . '&page={page}', true);
		$data['pagination'] = $pagination->render();
		$data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $limit) + 1 : 0, min(($page - 1) * $limit + $limit, $total), $total, ceil($total / $limit));

		$this->response->setOutput($this->load->view('extension/redirect_manager/rule_list', $data));
	}

	public function add() {
		$this->load->language('extension/redirect_manager/redirect_manager');
		$this->load->model('extension/redirect_manager/rule');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->saveRule();
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/redirect_manager/rule', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->form();
	}

	public function edit() {
		$this->load->language('extension/redirect_manager/redirect_manager');
		$this->load->model('extension/redirect_manager/rule');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->saveRule((int)$this->request->get['redirect_id']);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/redirect_manager/rule', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->form();
	}

	public function delete() {
		$this->guardModify();
		$this->load->model('extension/redirect_manager/rule');

		if (isset($this->request->post['selected'])) {
			$this->model_extension_redirect_manager_rule->bulkDelete($this->request->post['selected']);
			$this->model_extension_redirect_manager_rule->addLog(array('action' => 'rule_deleted', 'result' => 'ok', 'message' => count($this->request->post['selected'])));
			$this->session->data['success'] = $this->language->get('text_success');
		}

		$this->response->redirect($this->url->link('extension/redirect_manager/rule', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function bulk() {
		$this->guardModify();
		$this->load->model('extension/redirect_manager/rule');
		$ids = isset($this->request->post['selected']) ? $this->request->post['selected'] : array();
		$op = isset($this->request->post['bulk_action']) ? $this->request->post['bulk_action'] : '';

		if ($op === 'enable') {
			$this->model_extension_redirect_manager_rule->bulkUpdate($ids, array('enabled' => 1));
		} elseif ($op === 'disable') {
			$this->model_extension_redirect_manager_rule->bulkUpdate($ids, array('enabled' => 0));
		} elseif ($op === 'delete') {
			$this->model_extension_redirect_manager_rule->bulkDelete($ids);
		} elseif ($op === 'http_code' && isset($this->request->post['bulk_http_code'])) {
			$this->model_extension_redirect_manager_rule->bulkUpdate($ids, array('http_code' => (int)$this->request->post['bulk_http_code']));
		} elseif ($op === 'destination' && isset($this->request->post['bulk_target'])) {
			$this->model_extension_redirect_manager_rule->bulkUpdate($ids, array('target_url' => $this->request->post['bulk_target']));
		} elseif ($op === 'export') {
			$this->export($ids);
			return;
		}

		$this->model_extension_redirect_manager_rule->addLog(array('action' => 'bulk_' . $op, 'result' => 'ok', 'message' => count($ids)));
		$this->session->data['success'] = $this->language->get('text_success');
		$this->response->redirect($this->url->link('extension/redirect_manager/rule', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function import() {
		$data = $this->boot('import');
		$this->load->model('extension/redirect_manager/rule');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->user->hasPermission('modify', 'extension/redirect_manager')) {
			if (!empty($this->request->post['confirm']) && !empty($this->session->data['rm_import'])) {
				$result = $this->model_extension_redirect_manager_rule->addRulesBatch($this->session->data['rm_import']['rows']);
				$this->model_extension_redirect_manager_rule->addLog(array('action' => 'import', 'result' => 'ok', 'message' => $result['inserted']));
				unset($this->session->data['rm_import']);
				$this->session->data['success'] = sprintf($this->language->get('text_imported'), $result['inserted']);
				$this->response->redirect($this->url->link('extension/redirect_manager/rule', 'user_token=' . $data['user_token'], true));
			}

			$preview = $this->parseImport();
			$this->session->data['rm_import'] = $preview;
			$data['preview'] = $preview;
		} else {
			$data['preview'] = isset($this->session->data['rm_import']) ? $this->session->data['rm_import'] : array();
		}

		$data['action'] = $this->url->link('extension/redirect_manager/rule/import', 'user_token=' . $data['user_token'], true);
		$this->response->setOutput($this->load->view('extension/redirect_manager/import', $data));
	}

	public function export($ids = array()) {
		$this->load->model('extension/redirect_manager/rule');
		$format = $this->get('format', 'csv');

		if ($ids) {
			$rules = array();

			foreach ($ids as $id) {
				$rule = $this->model_extension_redirect_manager_rule->getRule($id);

				if ($rule) {
					$rules[] = $rule;
				}
			}
		} else {
			$filter = array(
				'filter_source'      => $this->get('filter_source', ''),
				'filter_target'      => $this->get('filter_target', ''),
				'filter_http_code'   => $this->get('filter_http_code', ''),
				'filter_action'      => $this->get('filter_action', ''),
				'filter_source_type' => $this->get('filter_source_type', ''),
				'filter_enabled'     => $this->get('filter_enabled', ''),
				'filter_broken'      => $this->get('filter_broken', '')
			);
			$rules = $this->model_extension_redirect_manager_rule->getRules($filter);
		}

		if ($format === 'json') {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->addHeader('Content-Disposition: attachment; filename="redirects.json"');
			$this->response->setOutput(json_encode($rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			return;
		}

		$out = "source_url,target_url,http_code\n";

		foreach ($rules as $rule) {
			$out .= $this->csvField($rule['source_url']) . ',' . $this->csvField($rule['target_url']) . ',' . (int)$rule['http_code'] . "\n";
		}

		$this->response->addHeader('Content-Type: text/csv; charset=utf-8');
		$this->response->addHeader('Content-Disposition: attachment; filename="redirects.csv"');
		$this->response->setOutput($out);
	}

	public function autocomplete() {
		$json = array();
		$type = isset($this->request->get['type']) ? $this->request->get['type'] : 'product';
		$filter_name = isset($this->request->get['filter_name']) ? $this->request->get['filter_name'] : '';
		$engine = new \redirect_manager\Engine($this->registry);
		$store_id = isset($this->request->get['store_id']) ? (int)$this->request->get['store_id'] : 0;
		$language_id = (int)$this->config->get('config_language_id');

		if ($type === 'product') {
			$this->load->model('catalog/product');
			$results = $this->model_catalog_product->getProducts(array('filter_name' => $filter_name, 'start' => 0, 'limit' => 8));

			foreach ($results as $result) {
				$json[] = array(
					'id'   => $result['product_id'],
					'name' => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
					'url'  => $engine->getProductUrl($result['product_id'], $language_id, $store_id)
				);
			}
		} elseif ($type === 'category') {
			$this->load->model('catalog/category');
			$results = $this->model_catalog_category->getCategories(array('filter_name' => $filter_name, 'start' => 0, 'limit' => 8));

			foreach ($results as $result) {
				$json[] = array(
					'id'   => $result['category_id'],
					'name' => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
					'url'  => $engine->getCategoryUrl($result['category_id'], $language_id, $store_id)
				);
			}
		} elseif ($type === 'information') {
			$this->load->model('catalog/information');
			$results = $this->model_catalog_information->getInformations(array('filter_name' => $filter_name, 'start' => 0, 'limit' => 8));

			foreach ($results as $result) {
				$json[] = array(
					'id'   => $result['information_id'],
					'name' => strip_tags(html_entity_decode($result['title'], ENT_QUOTES, 'UTF-8')),
					'url'  => $engine->getInformationUrl($result['information_id'], $language_id, $store_id)
				);
			}
		} elseif ($type === 'manufacturer') {
			$this->load->model('catalog/manufacturer');
			$results = $this->model_catalog_manufacturer->getManufacturers(array('filter_name' => $filter_name, 'start' => 0, 'limit' => 8));

			foreach ($results as $result) {
				$json[] = array(
					'id'   => $result['manufacturer_id'],
					'name' => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
					'url'  => $engine->getManufacturerUrl($result['manufacturer_id'], $language_id, $store_id)
				);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function form() {
		$data = $this->boot('rule');
		$this->load->model('extension/redirect_manager/rule');
		$info = array();

		if (isset($this->request->get['redirect_id'])) {
			$info = $this->model_extension_redirect_manager_rule->getRule($this->request->get['redirect_id']);
		}

		$fields = array('source_url', 'target_url', 'action', 'http_code', 'query_mode', 'source_type', 'source_entity_id', 'target_type', 'target_entity_id', 'store_id', 'language_id', 'enabled');
		$defaults = array('action' => 'redirect', 'http_code' => 301, 'query_mode' => 'ignore', 'source_type' => 'manual', 'target_type' => 'custom', 'store_id' => 0, 'language_id' => 0, 'enabled' => 1);

		foreach ($fields as $field) {
			if (isset($this->request->post[$field])) {
				$data[$field] = $this->request->post[$field];
			} elseif ($info) {
				$data[$field] = $info[$field];
			} else {
				$data[$field] = isset($defaults[$field]) ? $defaults[$field] : '';
			}
		}

		$data['conflict'] = isset($this->error['conflict']) ? $this->error['conflict'] : array();
		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['stores'] = $this->getStores();
		$data['languages'] = $this->getLanguages();
		$data['redirect_codes'] = array(301, 302, 303, 307, 308);
		$data['status_codes'] = array(404, 410, 451);
		$data['query_modes'] = array('ignore', 'exact', 'preserve', 'drop');
		$data['engine'] = new \redirect_manager\Engine($this->registry);
		$data['save'] = isset($this->request->get['redirect_id'])
			? $this->url->link('extension/redirect_manager/rule/edit', 'user_token=' . $data['user_token'] . '&redirect_id=' . (int)$this->request->get['redirect_id'], true)
			: $this->url->link('extension/redirect_manager/rule/add', 'user_token=' . $data['user_token'], true);
		$data['cancel'] = $this->url->link('extension/redirect_manager/rule', 'user_token=' . $data['user_token'], true);

		$this->response->setOutput($this->load->view('extension/redirect_manager/rule_form', $data));
	}

	private function saveRule($redirect_id = 0) {
		$engine = new \redirect_manager\Engine($this->registry);
		$data = $this->request->post;

		if ($data['target_type'] !== 'custom' && $data['target_type'] !== 'status' && !empty($data['target_entity_id'])) {
			$url = $engine->getEntityUrl($data['target_type'], $data['target_entity_id'], (int)$data['language_id'], max(0, (int)$data['store_id']));

			if ($url !== '') {
				$data['target_url'] = $url;
			}
		}

		if ($data['action'] === 'status') {
			$data['target_url'] = '';
		}

		if ($redirect_id) {
			$this->model_extension_redirect_manager_rule->editRule($redirect_id, $data);
			$this->model_extension_redirect_manager_rule->addLog(array('action' => 'rule_updated', 'entity' => 'rule', 'entity_id' => $redirect_id, 'after' => $data));
		} else {
			$existing = $this->model_extension_redirect_manager_rule->getRuleBySource($data['store_id'], $data['language_id'], $data['source_url']);

			if (!empty($this->request->post['replace']) && $existing) {
				$this->model_extension_redirect_manager_rule->editRule($existing['redirect_id'], $data);
				$this->model_extension_redirect_manager_rule->addLog(array('action' => 'rule_updated', 'entity' => 'rule', 'entity_id' => $existing['redirect_id'], 'after' => $data));
			} else {
				$id = $this->model_extension_redirect_manager_rule->addRule($data);
				$this->model_extension_redirect_manager_rule->addLog(array('action' => 'rule_created', 'entity' => 'rule', 'entity_id' => $id, 'after' => $data));
			}
		}
	}

	private function validateForm() {
		if (!$this->user->hasPermission('modify', 'extension/redirect_manager')) {
			$this->error['warning'] = $this->language->get('error_permission');
			return false;
		}

		$engine = new \redirect_manager\Engine($this->registry);
		$errors = $engine->validateRule($this->request->post);

		if ($errors) {
			$this->error['warning'] = $this->language->get('error_' . $errors[0]);
			return false;
		}

		$replace = !empty($this->request->post['replace']);
		$existing = $this->model_extension_redirect_manager_rule->getRuleBySource($this->request->post['store_id'], $this->request->post['language_id'], $this->request->post['source_url']);

		if ($existing && (!isset($this->request->get['redirect_id']) || (int)$existing['redirect_id'] !== (int)$this->request->get['redirect_id'])) {
			if (!$replace) {
				$this->error['conflict'] = $existing;
				$this->error['warning'] = $this->language->get('error_duplicate');
				return false;
			}
		}

		$probe = $this->request->post;
		$probe['enabled'] = 1;
		$rules = $this->model_extension_redirect_manager_rule->getEnabledRules();

		if (isset($this->request->get['redirect_id'])) {
			foreach ($rules as $i => $rule) {
				if ((int)$rule['redirect_id'] === (int)$this->request->get['redirect_id']) {
					$rules[$i] = array_merge($rule, $probe);
				}
			}
		} else {
			$rules[] = $probe;
		}

		if ($engine->detectLoops($rules)) {
			$this->error['warning'] = $this->language->get('error_loop');
			return false;
		}

		return true;
	}

	private function parseImport() {
		$engine = new \redirect_manager\Engine($this->registry);
		$valid = array();
		$invalid = array();
		$duplicates = array();
		$seen = array();
		$lines = array();

		if (!empty($this->request->files['import']['tmp_name']) && is_uploaded_file($this->request->files['import']['tmp_name'])) {
			$lines = file($this->request->files['import']['tmp_name'], FILE_IGNORE_NEW_LINES);
		} elseif (!empty($this->request->post['csv_text'])) {
			$lines = preg_split("/\\r\\n|\\n|\\r/", $this->request->post['csv_text']);
		}

		foreach ($lines as $i => $line) {
			$line = trim($line);

			if ($line === '' || ($i === 0 && stripos($line, 'source_url') === 0)) {
				continue;
			}

			$parts = str_getcsv($line);
			$source = isset($parts[0]) ? $parts[0] : '';
			$target = isset($parts[1]) ? $parts[1] : '';
			$code = isset($parts[2]) ? (int)$parts[2] : 301;
			$action = in_array($code, $engine->statusCodes(), true) ? 'status' : 'redirect';
			$row = array(
				'store_id'    => 0,
				'language_id' => 0,
				'source_url'  => $source,
				'target_url'  => $target,
				'action'      => $action,
				'http_code'   => $code,
				'query_mode'  => 'ignore',
				'source_type' => 'import',
				'target_type' => $action === 'status' ? 'status' : 'custom',
				'enabled'     => 1
			);
			$errors = $engine->validateRule($row);

			if ($errors) {
				$invalid[] = array('line' => $i + 1, 'source' => $source, 'error' => $errors[0]);
				continue;
			}

			$norm = $engine->normalize($source);

			if (isset($seen[$norm['storage']])) {
				$duplicates[] = $norm['storage'];
				continue;
			}

			$seen[$norm['storage']] = true;
			$valid[] = $row;
		}

		$conflicts = array();

		foreach ($valid as $row) {
			$existing = $this->model_extension_redirect_manager_rule->getRuleBySource(0, 0, $row['source_url']);

			if ($existing) {
				$conflicts[] = array('source_url' => $row['source_url'], 'current_target' => $existing['target_url'], 'new_target' => $row['target_url']);
			}
		}

		return array(
			'valid'      => count($valid),
			'duplicates' => count($duplicates),
			'invalid'    => count($invalid),
			'conflicts'  => count($conflicts),
			'invalid_rows' => array_slice($invalid, 0, 50),
			'conflict_rows' => array_slice($conflicts, 0, 50),
			'preview'    => array_slice($valid, 0, 50),
			'rows'       => $valid
		);
	}

	private function csvField($value) {
		if (strpos($value, ',') !== false || strpos($value, '"') !== false) {
			return '"' . str_replace('"', '""', $value) . '"';
		}

		return $value;
	}

	private function getStores() {
		$this->load->model('setting/store');
		$stores = array(array('store_id' => 0, 'name' => $this->language->get('text_default')));

		foreach ($this->model_setting_store->getStores() as $store) {
			$stores[] = $store;
		}

		$stores[] = array('store_id' => -1, 'name' => $this->language->get('text_all_stores'));

		return $stores;
	}

	private function getLanguages() {
		$this->load->model('localisation/language');
		$languages = array(array('language_id' => 0, 'name' => $this->language->get('text_all_languages')));

		return array_merge($languages, $this->model_localisation_language->getLanguages());
	}

	private function get($key, $default = '') {
		return isset($this->request->get[$key]) ? $this->request->get[$key] : $default;
	}

	private function urlSuffix($filter, $page) {
		$url = '';

		foreach ($filter as $key => $value) {
			if ($value !== '') {
				$url .= '&' . $key . '=' . urlencode($value);
			}
		}

		if ($page && $page !== false && isset($this->request->get['page'])) {
			$url .= '&page=' . (int)$this->request->get['page'];
		}

		return $url;
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
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/redirect_manager/rule', 'user_token=' . $token, true))
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
			$this->response->redirect($this->url->link('extension/redirect_manager/rule', 'user_token=' . $this->session->data['user_token'], true));
		}
	}
}
