<?php

class ControllerExtensionModuleRedirectManager extends Controller {
	public function index() {
		$this->response->redirect($this->url->link('extension/redirect_manager/setting', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function install() {
		$this->load->model('extension/redirect_manager/rule');
		$this->load->model('setting/setting');
		$this->load->model('setting/event');
		$this->load->model('user/user_group');

		$this->model_extension_redirect_manager_rule->install();

		$group_id = $this->user->getGroupId();
		$this->model_user_user_group->addPermission($group_id, 'access', 'extension/redirect_manager');
		$this->model_user_user_group->addPermission($group_id, 'modify', 'extension/redirect_manager');

		$this->model_setting_event->deleteEventByCode('redirect_manager');
		$this->model_setting_event->addEvent('redirect_manager', 'admin/view/common/column_left/before', 'extension/redirect_manager/event/menu');
		$this->model_setting_event->addEvent('redirect_manager', 'admin/model/catalog/product/editProduct/before', 'extension/redirect_manager/event/entityEditBefore');
		$this->model_setting_event->addEvent('redirect_manager', 'admin/model/catalog/product/editProduct/after', 'extension/redirect_manager/event/productEditAfter');
		$this->model_setting_event->addEvent('redirect_manager', 'admin/model/catalog/category/editCategory/before', 'extension/redirect_manager/event/entityEditBefore');
		$this->model_setting_event->addEvent('redirect_manager', 'admin/model/catalog/category/editCategory/after', 'extension/redirect_manager/event/categoryEditAfter');
		$this->model_setting_event->addEvent('redirect_manager', 'admin/model/catalog/information/editInformation/before', 'extension/redirect_manager/event/entityEditBefore');
		$this->model_setting_event->addEvent('redirect_manager', 'admin/model/catalog/information/editInformation/after', 'extension/redirect_manager/event/informationEditAfter');
		$this->model_setting_event->addEvent('redirect_manager', 'admin/model/catalog/manufacturer/editManufacturer/before', 'extension/redirect_manager/event/entityEditBefore');
		$this->model_setting_event->addEvent('redirect_manager', 'admin/model/catalog/manufacturer/editManufacturer/after', 'extension/redirect_manager/event/manufacturerEditAfter');
		$this->model_setting_event->addEvent('redirect_manager', 'admin/controller/catalog/product/delete/before', 'extension/redirect_manager/event/productDeleteAsk');
		$this->model_setting_event->addEvent('redirect_manager', 'admin/model/catalog/product/deleteProduct/before', 'extension/redirect_manager/event/productDelete');

		$engine = new \redirect_manager\Engine($this->registry);

		$this->model_setting_setting->editSetting('module_redirect_manager', array(
			'module_redirect_manager_status'                => 1,
			'module_redirect_manager_backend'               => 'apache',
			'module_redirect_manager_apache_mode'           => 'file',
			'module_redirect_manager_apache_file'           => $engine->storageRoot() . '/apache/redirects.conf',
			'module_redirect_manager_nginx_file'            => $engine->storageRoot() . '/nginx/redirects.conf',
			'module_redirect_manager_allow_reload'          => 0,
			'module_redirect_manager_allowed_paths'         => '',
			'module_redirect_manager_cloudflare_token'      => '',
			'module_redirect_manager_cloudflare_account_id' => '',
			'module_redirect_manager_cloudflare_list_id'    => '',
			'module_redirect_manager_cloudflare_rule_id'    => '',
			'module_redirect_manager_cloudflare_domain'     => '',
			'module_redirect_manager_sync_mode'             => 'manual',
			'module_redirect_manager_auto_seo_redirect'     => 0,
			'module_redirect_manager_deleted_product_action'=> 'none',
			'module_redirect_manager_delete_data_on_uninstall' => 0,
			'module_redirect_manager_configuration_dirty'   => 0,
			'module_redirect_manager_rules_hash'            => ''
		));
	}

	public function uninstall() {
		$this->load->model('extension/redirect_manager/rule');
		$this->load->model('setting/event');

		$delete_data = (int)$this->config->get('module_redirect_manager_delete_data_on_uninstall');
		$this->model_extension_redirect_manager_rule->uninstall((bool)$delete_data);
		$this->model_setting_event->deleteEventByCode('redirect_manager');
	}
}
