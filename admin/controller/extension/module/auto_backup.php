<?php

class ControllerExtensionModuleAutoBackup extends Controller
{
	public function index()
	{
		$this->response->redirect(
			$this->url->link('extension/auto_backup/setting', 'user_token=' . $this->session->data['user_token'], true)
		);
	}

	public function install()
	{
		$this->load->model('setting/setting');
		$this->load->model('setting/event');
		$this->load->model('user/user_group');

		$history = new \Backup\History($this->db);
		$history->install();

		$store = new \Backup\Store($this->registry);
		$defaults = $store->defaults();
		$defaults['module_auto_backup_email'] = (string) $this->config->get('config_email');

		$group_id = $this->user->getGroupId();
		$this->model_user_user_group->addPermission($group_id, 'access', 'extension/auto_backup');
		$this->model_user_user_group->addPermission($group_id, 'modify', 'extension/auto_backup');

		$this->model_setting_event->deleteEventByCode('auto_backup');
		$this->model_setting_event->addEvent(
			'auto_backup',
			'admin/view/common/column_left/before',
			'extension/auto_backup/event/menu'
		);

		$this->model_setting_setting->editSetting('module_auto_backup', $defaults);
	}

	public function uninstall()
	{
		$this->load->model('setting/event');

		$delete_data = (int) $this->config->get('module_auto_backup_delete_data_on_uninstall');
		$history = new \Backup\History($this->db);
		$history->uninstall((bool) $delete_data);
		$this->model_setting_event->deleteEventByCode('auto_backup');
	}
}
