<?php
class ControllerExtensionModuleCarousel extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/carousel');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/module');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			if (!isset($this->request->get['module_id'])) {
				$this->model_setting_module->addModule('carousel', $this->request->post);
			} else {
				$this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
			}

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

		if (isset($this->error['width'])) {
			$data['error_width'] = $this->error['width'];
		} else {
			$data['error_width'] = '';
		}

		if (isset($this->error['height'])) {
			$data['error_height'] = $this->error['height'];
		} else {
			$data['error_height'] = '';
		}

		if (isset($this->error['slides'])) {
			$data['error_slides'] = $this->error['slides'];
		} else {
			$data['error_slides'] = '';
		}

		if (isset($this->error['width_mobile'])) {
			$data['error_width_mobile'] = $this->error['width_mobile'];
		} else {
			$data['error_width_mobile'] = '';
		}

		if (isset($this->error['height_mobile'])) {
			$data['error_height_mobile'] = $this->error['height_mobile'];
		} else {
			$data['error_height_mobile'] = '';
		}

		if (isset($this->error['slides_mobile'])) {
			$data['error_slides_mobile'] = $this->error['slides_mobile'];
		} else {
			$data['error_slides_mobile'] = '';
		}

		if (isset($this->error['gap'])) {
			$data['error_gap'] = $this->error['gap'];
		} else {
			$data['error_gap'] = '';
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
				'href' => $this->url->link('extension/module/carousel', 'user_token=' . $this->session->data['user_token'], true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/carousel', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
			);
		}

		if (!isset($this->request->get['module_id'])) {
			$data['action'] = $this->url->link('extension/module/carousel', 'user_token=' . $this->session->data['user_token'], true);
		} else {
			$data['action'] = $this->url->link('extension/module/carousel', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
		}

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$module_info = array();

		if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
		}

		$is_new = empty($module_info) && ($this->request->server['REQUEST_METHOD'] != 'POST');

		$this->load->model('design/banner');

		$data['banners'] = $this->model_design_banner->getBanners();

		$data['name'] = $this->getField($module_info, 'name', '');
		$data['banner_id'] = $this->getField($module_info, 'banner_id', '');
		$data['width'] = $this->getField($module_info, 'width', $is_new ? 1140 : 130);
		$data['height'] = $this->getField($module_info, 'height', $is_new ? 380 : 100);
		$data['slides'] = $this->getField($module_info, 'slides', $is_new ? 1 : 5);
		$data['width_mobile'] = $this->getField($module_info, 'width_mobile', $is_new ? 375 : $data['width']);
		$data['height_mobile'] = $this->getField($module_info, 'height_mobile', $is_new ? 210 : $data['height']);
		$data['slides_mobile'] = $this->getField($module_info, 'slides_mobile', $data['slides']);
		$data['use_autoplay'] = $this->getField($module_info, 'use_autoplay', 1);
		$data['use_controls'] = $this->getField($module_info, 'use_controls', $is_new ? 1 : 0);
		$data['use_dots'] = $this->getField($module_info, 'use_dots', $is_new ? 1 : 0);
		$data['use_loop'] = $this->getField($module_info, 'use_loop', 1);
		$data['use_autoplay_mobile'] = $this->getField($module_info, 'use_autoplay_mobile', $data['use_autoplay']);
		$data['use_controls_mobile'] = $this->getField($module_info, 'use_controls_mobile', $data['use_controls']);
		$data['use_dots_mobile'] = $this->getField($module_info, 'use_dots_mobile', $data['use_dots']);
		$data['use_loop_mobile'] = $this->getField($module_info, 'use_loop_mobile', $data['use_loop']);
		$data['gap'] = $this->getField($module_info, 'gap', 8);
		$data['breakpoint'] = $this->getField($module_info, 'breakpoint', 768);
		$data['autoplay_delay'] = $this->getField($module_info, 'autoplay_delay', 3);
		$data['status'] = $this->getField($module_info, 'status', '');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/carousel', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/carousel')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if (!$this->request->post['width']) {
			$this->error['width'] = $this->language->get('error_width');
		}

		if (!$this->request->post['height']) {
			$this->error['height'] = $this->language->get('error_height');
		}

		if ((int)$this->request->post['slides'] < 1) {
			$this->error['slides'] = $this->language->get('error_slides');
		}

		if (!$this->request->post['width_mobile']) {
			$this->error['width_mobile'] = $this->language->get('error_width_mobile');
		}

		if (!$this->request->post['height_mobile']) {
			$this->error['height_mobile'] = $this->language->get('error_height_mobile');
		}

		if ((int)$this->request->post['slides_mobile'] < 1) {
			$this->error['slides_mobile'] = $this->language->get('error_slides_mobile');
		}

		if (!isset($this->request->post['gap']) || $this->request->post['gap'] === '' || (int)$this->request->post['gap'] < 0) {
			$this->error['gap'] = $this->language->get('error_gap');
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