<?php

class ControllerExtensionModuleImportExport extends Controller
{
	public function index()
	{
		$this->response->redirect(
			$this->url->link('extension/import_export/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
	}

	public function install()
	{
		$this->load->model('setting/setting');
		$this->load->model('setting/event');
		$this->load->model('user/user_group');

		$job = new \import_export\Job($this->db);
		$job->install();

		$engine = new \import_export\Engine($this->registry);
		$group_id = $this->user->getGroupId();
		$this->model_user_user_group->addPermission($group_id, 'access', 'extension/module/import_export');
		$this->model_user_user_group->addPermission($group_id, 'modify', 'extension/module/import_export');
		$this->model_user_user_group->addPermission($group_id, 'access', 'extension/import_export');
		$this->model_user_user_group->addPermission($group_id, 'modify', 'extension/import_export');

		$this->model_setting_event->deleteEventByCode('import_export');
		$this->model_setting_event->addEvent(
			'import_export',
			'admin/view/common/column_left/before',
			'extension/import_export/event/menu'
		);

		$this->model_setting_setting->editSetting('module_import_export', $engine->defaults());
	}

	public function uninstall()
	{
		$this->load->model('setting/event');

		$delete_data = (int) $this->config->get('module_import_export_delete_data_on_uninstall');
		$job = new \import_export\Job($this->db);
		$job->uninstall((bool) $delete_data);
		$this->model_setting_event->deleteEventByCode('import_export');
	}
}
