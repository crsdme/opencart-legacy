<?php

class ControllerExtensionModuleFaq extends Controller
{
	public function index()
	{
		$this->response->redirect(
			$this->url->link('extension/faq/setting', 'user_token=' . $this->session->data['user_token'], true)
		);
	}

	public function install()
	{
		$this->load->model('extension/faq/faq');
		$this->load->model('setting/setting');
		$this->load->model('setting/event');
		$this->load->model('user/user_group');

		$this->model_extension_faq_faq->install();

		$group_id = $this->user->getGroupId();
		$this->model_user_user_group->addPermission($group_id, 'access', 'extension/faq');
		$this->model_user_user_group->addPermission($group_id, 'modify', 'extension/faq');

		$this->model_setting_event->deleteEventByCode('faq');

		$events = [
			['admin/view/catalog/product_form/after', 'extension/faq/event/productFormAfter'],
			['admin/view/catalog/category_form/after', 'extension/faq/event/categoryFormAfter'],
			['admin/view/catalog/manufacturer_form/after', 'extension/faq/event/manufacturerFormAfter'],
			['admin/model/catalog/product/addProduct/after', 'extension/faq/event/productAddAfter'],
			['admin/model/catalog/product/editProduct/after', 'extension/faq/event/productEditAfter'],
			['admin/model/catalog/product/deleteProduct/before', 'extension/faq/event/productDelete'],
			['admin/model/catalog/category/addCategory/after', 'extension/faq/event/categoryAddAfter'],
			['admin/model/catalog/category/editCategory/after', 'extension/faq/event/categoryEditAfter'],
			['admin/model/catalog/category/deleteCategory/before', 'extension/faq/event/categoryDelete'],
			['admin/model/catalog/manufacturer/addManufacturer/after', 'extension/faq/event/manufacturerAddAfter'],
			['admin/model/catalog/manufacturer/editManufacturer/after', 'extension/faq/event/manufacturerEditAfter'],
			['admin/model/catalog/manufacturer/deleteManufacturer/before', 'extension/faq/event/manufacturerDelete'],
		];

		foreach ($events as $event) {
			$this->model_setting_event->addEvent('faq', $event[0], $event[1]);
		}

		$this->model_setting_setting->editSetting('module_faq', [
			'module_faq_status' => 1,
			'module_faq_product' => 1,
			'module_faq_category' => 1,
			'module_faq_manufacturer' => 1,
			'module_faq_first_page_only' => 1,
			'module_faq_delete_data_on_uninstall' => 0,
		]);
	}

	public function uninstall()
	{
		$this->load->model('extension/faq/faq');
		$this->load->model('setting/event');

		$delete_data = (int) $this->config->get('module_faq_delete_data_on_uninstall');
		$this->model_extension_faq_faq->uninstall((bool) $delete_data);
		$this->model_setting_event->deleteEventByCode('faq');
	}
}
