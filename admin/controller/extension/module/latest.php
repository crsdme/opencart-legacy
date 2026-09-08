<?php
class ControllerExtensionModuleLatest extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/latest');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/module');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			if (!isset($this->request->get['module_id'])) {
				$this->model_setting_module->addModule('latest', $this->request->post);
			} else {
				$this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
			}

			$this->cache->delete('product');

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

		if (isset($this->error['gap'])) {
			$data['error_gap'] = $this->error['gap'];
		} else {
			$data['error_gap'] = '';
		}

		if (isset($this->error['gap_mobile'])) {
			$data['error_gap_mobile'] = $this->error['gap_mobile'];
		} else {
			$data['error_gap_mobile'] = '';
		}

		if (isset($this->error['breakpoint'])) {
			$data['error_breakpoint'] = $this->error['breakpoint'];
		} else {
			$data['error_breakpoint'] = '';
		}

		if (isset($this->error['autoplay_delay'])) {
			$data['error_autoplay_delay'] = $this->error['autoplay_delay'];
		} else {
			$data['error_autoplay_delay'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		if (!isset($this->request->get['module_id'])) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/latest', 'user_token=' . $this->session->data['user_token'], true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/latest', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
			);
		}

		if (!isset($this->request->get['module_id'])) {
			$data['action'] = $this->url->link('extension/module/latest', 'user_token=' . $this->session->data['user_token'], true);
		} else {
			$data['action'] = $this->url->link('extension/module/latest', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
		}

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$module_info = array();

		if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
		}

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($this->request->post['module_description'])) {
			$data['module_description'] = $this->request->post['module_description'];
		} elseif (!empty($module_info['module_description'])) {
			$data['module_description'] = $module_info['module_description'];
		} else {
			$data['module_description'] = array();
		}

		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($module_info)) {
			$data['name'] = $module_info['name'];
		} else {
			$data['name'] = '';
		}

		if (isset($this->request->post['limit'])) {
			$data['limit'] = $this->request->post['limit'];
		} elseif (!empty($module_info)) {
			$data['limit'] = $module_info['limit'];
		} else {
			$data['limit'] = 5;
		}

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($module_info)) {
			$data['status'] = $module_info['status'];
		} else {
			$data['status'] = '';
		}

		$data['use_autoplay'] = $this->getField($module_info, 'use_autoplay', 0);
		$data['use_controls'] = $this->getField($module_info, 'use_controls', 1);
		$data['use_loop'] = $this->getField($module_info, 'use_loop', 1);
		$data['use_autoplay_mobile'] = $this->getField($module_info, 'use_autoplay_mobile', $data['use_autoplay']);
		$data['use_controls_mobile'] = $this->getField($module_info, 'use_controls_mobile', $data['use_controls']);
		$data['use_loop_mobile'] = $this->getField($module_info, 'use_loop_mobile', $data['use_loop']);
		$data['gap'] = $this->getField($module_info, 'gap', 16);
		$data['gap_mobile'] = $this->getField($module_info, 'gap_mobile', $data['gap']);
		$data['breakpoint'] = $this->getField($module_info, 'breakpoint', 1024);
		$data['autoplay_delay'] = $this->getField($module_info, 'autoplay_delay', 3);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/latest', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/latest')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if (!isset($this->request->post['gap']) || $this->request->post['gap'] === '' || (int)$this->request->post['gap'] < 0) {
			$this->error['gap'] = $this->language->get('error_gap');
		}

		if (!isset($this->request->post['gap_mobile']) || $this->request->post['gap_mobile'] === '' || (int)$this->request->post['gap_mobile'] < 0) {
			$this->error['gap_mobile'] = $this->language->get('error_gap');
		}

		if (!isset($this->request->post['breakpoint']) || (int)$this->request->post['breakpoint'] < 1) {
			$this->error['breakpoint'] = $this->language->get('error_breakpoint');
		}

		if (!isset($this->request->post['autoplay_delay']) || (int)$this->request->post['autoplay_delay'] < 1) {
			$this->error['autoplay_delay'] = $this->language->get('error_autoplay_delay');
		}

		return !$this->error;
	}

	private function getField($module_info, $key, $default = '') {
		if (isset($this->request->post[$key])) {
			return $this->request->post[$key];
		}

		if (!empty($module_info) && isset($module_info[$key]) && $module_info[$key] !== '') {
			return $module_info[$key];
		}

		return $default;
	}
}