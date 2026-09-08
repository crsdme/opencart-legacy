<?php

class ControllerExtensionRedirectManagerEvent extends Controller {
	public function menu(&$route, &$data) {
		if (!$this->user->hasPermission('access', 'extension/redirect_manager')) {
			return;
		}

		$this->load->language('extension/redirect_manager/redirect_manager');

		$token = $this->session->data['user_token'];
		$children = array(
			array('name' => $this->language->get('text_tab_dashboard'), 'href' => $this->url->link('extension/redirect_manager/dashboard', 'user_token=' . $token, true), 'children' => array()),
			array('name' => $this->language->get('text_tab_rules'), 'href' => $this->url->link('extension/redirect_manager/rule', 'user_token=' . $token, true), 'children' => array()),
			array('name' => $this->language->get('text_tab_category'), 'href' => $this->url->link('extension/redirect_manager/mass/category', 'user_token=' . $token, true), 'children' => array()),
			array('name' => $this->language->get('text_tab_product'), 'href' => $this->url->link('extension/redirect_manager/mass/product', 'user_token=' . $token, true), 'children' => array()),
			array('name' => $this->language->get('text_tab_config'), 'href' => $this->url->link('extension/redirect_manager/config', 'user_token=' . $token, true), 'children' => array()),
			array('name' => $this->language->get('text_tab_settings'), 'href' => $this->url->link('extension/redirect_manager/setting', 'user_token=' . $token, true), 'children' => array())
		);

		$data['menus'][] = array(
			'id'       => 'menu-redirect-manager',
			'icon'     => 'fa-exchange',
			'name'     => $this->language->get('heading_title'),
			'href'     => '',
			'children' => $children
		);
	}

	public function entityEditBefore(&$route, &$args) {
		$entity_id = isset($args[0]) ? (int)$args[0] : 0;

		if (!$entity_id) {
			return;
		}

		$type = $this->entityTypeFromRoute($route);

		if ($type === '') {
			return;
		}

		$this->load->model('extension/redirect_manager/rule');

		$engine = new \redirect_manager\Engine($this->registry);
		$snapshot = array();

		foreach ($this->model_extension_redirect_manager_rule->getEntityUrls($type, $entity_id) as $row) {
			$snapshot[] = array(
				'store_id'    => $row['store_id'],
				'language_id' => $row['language_id'],
				'url'         => $engine->getEntityUrl($type, $entity_id, $row['language_id'], $row['store_id'])
			);
		}

		$this->session->data['rm_entity_urls'] = $snapshot;
	}

	public function productEditAfter(&$route, &$args, &$output) {
		$this->entityEditAfter('product', isset($args[0]) ? (int)$args[0] : 0);
	}

	public function categoryEditAfter(&$route, &$args, &$output) {
		$this->entityEditAfter('category', isset($args[0]) ? (int)$args[0] : 0);
	}

	public function informationEditAfter(&$route, &$args, &$output) {
		$this->entityEditAfter('information', isset($args[0]) ? (int)$args[0] : 0);
	}

	public function manufacturerEditAfter(&$route, &$args, &$output) {
		$this->entityEditAfter('manufacturer', isset($args[0]) ? (int)$args[0] : 0);
	}

	private function entityEditAfter($type, $entity_id) {
		if (!$entity_id || empty($this->session->data['rm_entity_urls'])) {
			return;
		}

		$this->load->model('extension/redirect_manager/rule');
		$engine = new \redirect_manager\Engine($this->registry);
		$old_rows = $this->session->data['rm_entity_urls'];
		unset($this->session->data['rm_entity_urls']);
		$auto = (int)$this->config->get('module_redirect_manager_auto_seo_redirect');

		foreach ($old_rows as $old) {
			$old_url = isset($old['url']) ? $old['url'] : '';
			$new_url = $engine->getEntityUrl($type, $entity_id, $old['language_id'], $old['store_id']);

			if ($old_url === '' || $old_url === $new_url) {
				continue;
			}

			$this->model_extension_redirect_manager_rule->addHistory(array(
				'store_id'    => $old['store_id'],
				'language_id' => $old['language_id'],
				'entity_type' => $type,
				'entity_id'   => $entity_id,
				'url'         => $old_url
			));

			if ($auto && $new_url !== '') {
				$exists = $this->model_extension_redirect_manager_rule->getRuleBySource($old['store_id'], $old['language_id'], $old_url);

				if (!$exists) {
					$this->model_extension_redirect_manager_rule->addRule(array(
						'store_id'         => $old['store_id'],
						'language_id'      => $old['language_id'],
						'source_url'       => $old_url,
						'target_url'       => $new_url,
						'action'           => 'redirect',
						'http_code'        => 301,
						'query_mode'       => 'ignore',
						'source_type'      => 'seo_history',
						'source_entity_id' => $entity_id,
						'target_type'      => $type,
						'target_entity_id' => $entity_id,
						'enabled'          => 1
					));
				}
			}
		}
	}

	public function productDeleteAsk(&$route, &$args) {
		$action = (string)$this->config->get('module_redirect_manager_deleted_product_action');

		if ($action !== 'ask' || !empty($this->request->post['rm_confirmed'])) {
			return;
		}

		if (empty($this->request->post['selected'])) {
			return;
		}

		$this->session->data['rm_delete_products'] = $this->request->post['selected'];
		$this->response->redirect($this->url->link('extension/redirect_manager/mass/deleted', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function productDelete(&$route, &$args) {
		$product_id = isset($args[0]) ? (int)$args[0] : 0;

		if (!$product_id) {
			return;
		}

		$this->load->model('extension/redirect_manager/rule');
		$engine = new \redirect_manager\Engine($this->registry);
		$rows = $this->model_extension_redirect_manager_rule->getEntityUrls('product', $product_id);

		foreach ($rows as $row) {
			$url = $engine->getProductUrl($product_id, $row['language_id'], $row['store_id']);

			if ($url === '') {
				continue;
			}

			$this->model_extension_redirect_manager_rule->addHistory(array(
				'store_id'    => $row['store_id'],
				'language_id' => $row['language_id'],
				'entity_type' => 'product',
				'entity_id'   => $product_id,
				'url'         => $url
			));

			$action = (string)$this->config->get('module_redirect_manager_deleted_product_action');

			if ($action === '410') {
				$exists = $this->model_extension_redirect_manager_rule->getRuleBySource($row['store_id'], $row['language_id'], $url);

				if (!$exists) {
					$this->model_extension_redirect_manager_rule->addRule(array(
						'store_id'         => $row['store_id'],
						'language_id'      => $row['language_id'],
						'source_url'       => $url,
						'target_url'       => '',
						'action'           => 'status',
						'http_code'        => 410,
						'query_mode'       => 'ignore',
						'source_type'      => 'product',
						'source_entity_id' => $product_id,
						'target_type'      => 'status',
						'target_entity_id' => '',
						'enabled'          => 1
					));
				}
			}
		}
	}

	private function entityTypeFromRoute($route) {
		if (strpos($route, 'product') !== false) {
			return 'product';
		}

		if (strpos($route, 'category') !== false) {
			return 'category';
		}

		if (strpos($route, 'information') !== false) {
			return 'information';
		}

		if (strpos($route, 'manufacturer') !== false) {
			return 'manufacturer';
		}

		return '';
	}
}
