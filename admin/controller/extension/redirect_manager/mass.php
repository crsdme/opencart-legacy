<?php

class ControllerExtensionRedirectManagerMass extends Controller {
	public function category() {
		$data = $this->boot('category');
		$this->load->model('extension/redirect_manager/rule');
		$data['stores'] = $this->getStores();
		$data['languages'] = $this->getLanguages();
		$data['action'] = $this->url->link('extension/redirect_manager/mass/category', 'user_token=' . $data['user_token'], true);
		$data['preview_url'] = $this->url->link('extension/redirect_manager/mass/categoryPreview', 'user_token=' . $data['user_token'], true);
		$data['groups'] = $this->model_extension_redirect_manager_rule->getBatches(0, 20);

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->user->hasPermission('modify', 'extension/redirect_manager') && !empty($this->request->post['create'])) {
			$this->createFromPreview('category');
			return;
		}

		$this->response->setOutput($this->load->view('extension/redirect_manager/mass', $data));
	}

	public function product() {
		$data = $this->boot('product');
		$this->load->model('extension/redirect_manager/rule');
		$data['stores'] = $this->getStores();
		$data['languages'] = $this->getLanguages();
		$data['action'] = $this->url->link('extension/redirect_manager/mass/product', 'user_token=' . $data['user_token'], true);
		$data['preview_url'] = $this->url->link('extension/redirect_manager/mass/productPreview', 'user_token=' . $data['user_token'], true);
		$data['is_product'] = true;
		$data['groups'] = $this->model_extension_redirect_manager_rule->getBatches(0, 20);

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->user->hasPermission('modify', 'extension/redirect_manager') && !empty($this->request->post['create'])) {
			$this->createFromPreview('product');
			return;
		}

		$this->response->setOutput($this->load->view('extension/redirect_manager/mass', $data));
	}

	public function categoryPreview() {
		$this->load->model('extension/redirect_manager/rule');
		$engine = new \redirect_manager\Engine($this->registry);
		$category_id = (int)$this->request->get['category_id'];
		$language_id = isset($this->request->get['language_id']) ? (int)$this->request->get['language_id'] : (int)$this->config->get('config_language_id');
		$store_id = isset($this->request->get['store_id']) ? (int)$this->request->get['store_id'] : 0;
		$options = array(
			'include_category'      => !empty($this->request->get['include_category']),
			'include_products'      => !empty($this->request->get['include_products']),
			'include_subcategories' => !empty($this->request->get['include_subcategories']),
			'include_sub_products'  => !empty($this->request->get['include_sub_products'])
		);
		$preview = $this->model_extension_redirect_manager_rule->getCategoryPreview($category_id, $language_id, max(0, $store_id), $options);
		$preview['sample'] = array_slice($preview['urls'], 0, 50);
		$preview['destination'] = $this->resolveDestination($engine, $store_id, $language_id);
		$this->session->data['rm_mass_preview'] = $preview;
		$this->json($preview);
	}

	public function productPreview() {
		$this->load->model('extension/redirect_manager/rule');
		$engine = new \redirect_manager\Engine($this->registry);
		$language_id = isset($this->request->get['language_id']) ? (int)$this->request->get['language_id'] : (int)$this->config->get('config_language_id');
		$store_id = isset($this->request->get['store_id']) ? (int)$this->request->get['store_id'] : 0;
		$filter = array(
			'product_ids'     => isset($this->request->get['product_ids']) ? explode(',', $this->request->get['product_ids']) : array(),
			'category_id'     => isset($this->request->get['category_id']) ? (int)$this->request->get['category_id'] : 0,
			'sub_category'    => !empty($this->request->get['sub_category']),
			'manufacturer_id' => isset($this->request->get['manufacturer_id']) ? (int)$this->request->get['manufacturer_id'] : 0,
			'disabled'        => !empty($this->request->get['disabled']),
			'out_of_stock'    => !empty($this->request->get['out_of_stock']),
			'filter_name'     => isset($this->request->get['filter_name']) ? $this->request->get['filter_name'] : ''
		);
		$urls = $this->model_extension_redirect_manager_rule->getProductSelection($filter, $language_id, max(0, $store_id));
		$preview = array(
			'urls'        => $urls,
			'total'       => count($urls),
			'sample'      => array_slice($urls, 0, 50),
			'destination' => $this->resolveDestination($engine, $store_id, $language_id)
		);
		$this->session->data['rm_mass_preview'] = $preview;
		$this->json($preview);
	}

	public function deleted() {
		$data = $this->boot('product');
		$data['product_ids'] = isset($this->session->data['rm_delete_products']) ? $this->session->data['rm_delete_products'] : array();
		$data['action'] = $this->url->link('extension/redirect_manager/mass/deleted', 'user_token=' . $data['user_token'], true);

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->user->hasPermission('modify', 'extension/redirect_manager')) {
			$action = isset($this->request->post['delete_action']) ? $this->request->post['delete_action'] : 'none';
			$ids = isset($this->request->post['product_id']) ? $this->request->post['product_id'] : $data['product_ids'];
			$this->session->data['rm_delete_action'] = $action;
			$this->session->data['rm_delete_target'] = isset($this->request->post['target_url']) ? $this->request->post['target_url'] : '';

			if ($action === '410' || $action === 'redirect') {
				$this->load->model('extension/redirect_manager/rule');
				$engine = new \redirect_manager\Engine($this->registry);

				foreach ((array)$ids as $product_id) {
					$rows = $this->model_extension_redirect_manager_rule->getEntityUrls('product', $product_id);

					foreach ($rows as $row) {
						$url = $engine->getProductUrl($product_id, $row['language_id'], $row['store_id']);

						if ($url === '') {
							continue;
						}

						$rule = array(
							'store_id'         => $row['store_id'],
							'language_id'      => $row['language_id'],
							'source_url'       => $url,
							'target_url'       => $action === 'redirect' ? $this->session->data['rm_delete_target'] : '',
							'action'           => $action === '410' ? 'status' : 'redirect',
							'http_code'        => $action === '410' ? 410 : 301,
							'query_mode'       => 'ignore',
							'source_type'      => 'product',
							'source_entity_id' => $product_id,
							'target_type'      => $action === '410' ? 'status' : 'custom',
							'enabled'          => 1
						);

						if (!$this->model_extension_redirect_manager_rule->getRuleBySource($rule['store_id'], $rule['language_id'], $url)) {
							$this->model_extension_redirect_manager_rule->addRule($rule);
						}
					}
				}
			}

			unset($this->session->data['rm_delete_products']);
			$this->request->post['selected'] = $ids;
			$this->request->post['rm_confirmed'] = 1;
			$this->load->controller('catalog/product/delete');
			return;
		}

		$this->response->setOutput($this->load->view('extension/redirect_manager/deleted', $data));
	}

	public function undo() {
		$this->load->language('extension/redirect_manager/redirect_manager');

		if (!$this->user->hasPermission('modify', 'extension/redirect_manager')) {
			$this->session->data['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('extension/redirect_manager/rule');
			$this->model_extension_redirect_manager_rule->undoBatch((int)$this->request->get['batch_id']);
			$this->model_extension_redirect_manager_rule->addLog(array('action' => 'undo_batch', 'entity' => 'batch', 'entity_id' => (int)$this->request->get['batch_id']));
			$this->session->data['success'] = $this->language->get('text_success');
		}

		$this->response->redirect($this->url->link('extension/redirect_manager/mass/category', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function batchStatus() {
		$this->load->language('extension/redirect_manager/redirect_manager');

		if ($this->user->hasPermission('modify', 'extension/redirect_manager')) {
			$this->load->model('extension/redirect_manager/rule');
			$this->model_extension_redirect_manager_rule->setBatchEnabled((int)$this->request->get['batch_id'], (int)$this->request->get['enabled']);
			$this->session->data['success'] = $this->language->get('text_success');
		}

		$this->response->redirect($this->url->link('extension/redirect_manager/dashboard', 'user_token=' . $this->session->data['user_token'], true));
	}

	private function createFromPreview($type) {
		$preview = isset($this->session->data['rm_mass_preview']) ? $this->session->data['rm_mass_preview'] : array();
		$urls = isset($preview['urls']) ? $preview['urls'] : array();
		$engine = new \redirect_manager\Engine($this->registry);
		$action = isset($this->request->post['action']) ? $this->request->post['action'] : 'redirect';
		$code = isset($this->request->post['http_code']) ? (int)$this->request->post['http_code'] : 301;
		$store_id = isset($this->request->post['store_id']) ? (int)$this->request->post['store_id'] : 0;
		$language_id = isset($this->request->post['language_id']) ? (int)$this->request->post['language_id'] : 0;
		$destination = $this->resolveDestination($engine, $store_id, $language_id);

		if ($action === 'status') {
			$destination = '';
		}

		$title = isset($this->request->post['title']) ? $this->request->post['title'] : $type;
		$batch_id = $this->model_extension_redirect_manager_rule->addBatch(array(
			'store_id'    => $store_id,
			'type'        => $type,
			'title'       => $title,
			'payload'     => $this->request->post,
			'rules_count' => count($urls),
			'user_id'     => $this->user->getId()
		));

		$rows = array();

		foreach ($urls as $item) {
			$rows[] = array(
				'store_id'         => $store_id,
				'language_id'      => $language_id,
				'source_url'       => $item['url'],
				'target_url'       => $destination,
				'action'           => $action,
				'http_code'        => $code,
				'query_mode'       => 'ignore',
				'source_type'      => $item['type'],
				'source_entity_id' => $item['entity_id'],
				'target_type'      => isset($this->request->post['target_type']) ? $this->request->post['target_type'] : 'custom',
				'target_entity_id' => isset($this->request->post['target_entity_id']) ? $this->request->post['target_entity_id'] : '',
				'batch_id'         => $batch_id,
				'enabled'          => 1
			);
		}

		$result = $this->model_extension_redirect_manager_rule->addRulesBatch($rows);
		$this->model_extension_redirect_manager_rule->addLog(array('action' => 'mass_' . $type, 'entity' => 'batch', 'entity_id' => $batch_id, 'message' => $result['inserted']));
		unset($this->session->data['rm_mass_preview']);
		$this->session->data['success'] = sprintf($this->language->get('text_created_rules'), $result['inserted'], count($result['conflicts']));
		$this->response->redirect($this->url->link('extension/redirect_manager/rule', 'user_token=' . $this->session->data['user_token'] . '&filter_batch_id=' . $batch_id, true));
	}

	private function resolveDestination($engine, $store_id, $language_id) {
		$type = isset($this->request->post['target_type']) ? $this->request->post['target_type'] : (isset($this->request->get['target_type']) ? $this->request->get['target_type'] : 'custom');
		$entity_id = isset($this->request->post['target_entity_id']) ? $this->request->post['target_entity_id'] : (isset($this->request->get['target_entity_id']) ? $this->request->get['target_entity_id'] : '');
		$custom = isset($this->request->post['target_url']) ? $this->request->post['target_url'] : (isset($this->request->get['target_url']) ? $this->request->get['target_url'] : '');

		if ($type === 'custom' || $type === 'status') {
			return $custom;
		}

		$url = $engine->getEntityUrl($type, $entity_id, $language_id ? $language_id : (int)$this->config->get('config_language_id'), max(0, (int)$store_id));

		return $url !== '' ? $url : $custom;
	}

	private function json($payload) {
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($payload));
	}

	private function getStores() {
		$this->load->model('setting/store');
		$stores = array(array('store_id' => 0, 'name' => $this->config->get('config_name')));

		foreach ($this->model_setting_store->getStores() as $store) {
			$stores[] = $store;
		}

		return $stores;
	}

	private function getLanguages() {
		$this->load->model('localisation/language');
		$list = array(array('language_id' => 0, 'name' => $this->language->get('text_all_languages')));

		foreach ($this->model_localisation_language->getLanguages() as $language) {
			$list[] = $language;
		}

		return $list;
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

		$data = array(
			'user_token'     => $token,
			'tabs'           => $tabs,
			'heading_title'  => $this->language->get('heading_title'),
			'is_product'     => false,
			'breadcrumbs'    => array(
				array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $token, true)),
				array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/redirect_manager/dashboard', 'user_token=' . $token, true))
			),
			'success'        => isset($this->session->data['success']) ? $this->session->data['success'] : '',
			'error_warning'  => isset($this->session->data['error']) ? $this->session->data['error'] : '',
			'header'         => $this->load->controller('common/header'),
			'column_left'    => $this->load->controller('common/column_left'),
			'footer'         => $this->load->controller('common/footer'),
			'can_modify'     => $this->user->hasPermission('modify', 'extension/redirect_manager')
		);
		unset($this->session->data['success'], $this->session->data['error']);

		return $data;
	}
}
