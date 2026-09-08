<?php

class ControllerExtensionImportExportEvent extends Controller
{
	public function menu(&$route, &$data)
	{
		if (!$this->user->hasPermission('access', 'extension/import_export')) {
			return;
		}

		$this->load->language('extension/import_export/import_export');

		$token = $this->session->data['user_token'];
		$children = [
			[
				'name' => $this->language->get('text_tab_dashboard'),
				'href' => $this->url->link('extension/import_export/dashboard', 'user_token=' . $token, true),
				'children' => [],
			],
			[
				'name' => $this->language->get('text_tab_template'),
				'href' => $this->url->link('extension/import_export/template', 'user_token=' . $token, true),
				'children' => [],
			],
			[
				'name' => $this->language->get('text_tab_export'),
				'href' => $this->url->link('extension/import_export/export', 'user_token=' . $token, true),
				'children' => [],
			],
			[
				'name' => $this->language->get('text_tab_import'),
				'href' => $this->url->link('extension/import_export/import', 'user_token=' . $token, true),
				'children' => [],
			],
			[
				'name' => $this->language->get('text_tab_job'),
				'href' => $this->url->link('extension/import_export/job', 'user_token=' . $token, true),
				'children' => [],
			],
		];

		$data['menus'][] = [
			'id' => 'menu-import-export',
			'icon' => 'fa-exchange',
			'name' => $this->language->get('heading_title'),
			'href' => '',
			'children' => $children,
		];
	}
}
