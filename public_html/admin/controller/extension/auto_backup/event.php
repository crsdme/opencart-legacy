<?php

class ControllerExtensionAutoBackupEvent extends Controller
{
	public function menu(&$route, &$data)
	{
		if (!$this->user->hasPermission('access', 'extension/auto_backup')) {
			return;
		}

		$this->load->language('extension/auto_backup/auto_backup');

		$token = $this->session->data['user_token'];
		$children = [
			[
				'name' => $this->language->get('text_tab_settings'),
				'href' => $this->url->link('extension/auto_backup/setting', 'user_token=' . $token, true),
				'children' => [],
			],
			[
				'name' => $this->language->get('text_tab_history'),
				'href' => $this->url->link('extension/auto_backup/history', 'user_token=' . $token, true),
				'children' => [],
			],
		];

		$data['menus'][] = [
			'id' => 'menu-auto-backup',
			'icon' => 'fa-hdd-o',
			'name' => $this->language->get('heading_title'),
			'href' => '',
			'children' => $children,
		];
	}
}
