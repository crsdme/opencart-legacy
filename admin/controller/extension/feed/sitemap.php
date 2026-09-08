<?php

class ControllerExtensionFeedSitemap extends Controller
{
	private $error = [];

	public function index()
	{
		$this->load->language('extension/feed/sitemap');
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');

		$this->document->setTitle($this->language->get('heading_title'));

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
			$this->model_setting_setting->editSetting('feed_sitemap', $this->collectSettings());
			$this->clearSitemapCacheFiles();
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect(
				$this->url->link('extension/feed/sitemap', 'user_token=' . $this->session->data['user_token'], true)
			);
		}

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_settings'] = $this->language->get('text_settings');
		$data['text_branches'] = $this->language->get('text_branches');
		$data['text_feeds'] = $this->language->get('text_feeds');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_cache_hours'] = $this->language->get('entry_cache_hours');
		$data['entry_indexed_only'] = $this->language->get('entry_indexed_only');
		$data['entry_multishop'] = $this->language->get('entry_multishop');
		$data['entry_data_feed'] = $this->language->get('entry_data_feed');
		$data['help_status'] = $this->language->get('help_status');
		$data['help_cache_hours'] = $this->language->get('help_cache_hours');
		$data['help_indexed_only'] = $this->language->get('help_indexed_only');
		$data['help_multishop'] = $this->language->get('help_multishop');
		$data['help_branch'] = $this->language->get('help_branch');
		$data['help_data_feed'] = $this->language->get('help_data_feed');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_clear_cache'] = $this->language->get('button_clear_cache');

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

		if (isset($this->session->data['error'])) {
			$data['error_warning'] = $this->session->data['error'];
			unset($this->session->data['error']);
		}

		$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
		unset($this->session->data['success']);

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link(
				'marketplace/extension',
				'user_token=' . $this->session->data['user_token'] . '&type=feed',
				true
			),
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/feed/sitemap', 'user_token=' . $this->session->data['user_token'], true),
		];

		$data['action'] = $this->url->link(
			'extension/feed/sitemap',
			'user_token=' . $this->session->data['user_token'],
			true
		);
		$data['cancel'] = $this->url->link(
			'marketplace/extension',
			'user_token=' . $this->session->data['user_token'] . '&type=feed',
			true
		);
		$data['clear_cache'] = $this->url->link(
			'extension/feed/sitemap/clearCache',
			'user_token=' . $this->session->data['user_token'],
			true
		);

		$defaults = $this->defaultSettings();

		foreach ($defaults as $key => $default) {
			if (isset($this->request->post[$key])) {
				$data[$key] = $this->request->post[$key];
			} else {
				$value = $this->config->get($key);
				$data[$key] = $value !== null && $value !== '' ? $value : $default;
			}
		}

		$data['branches'] = [];

		foreach ($this->branchKeys() as $key => $entry) {
			$data['branches'][] = [
				'key' => $key,
				'value' => $data[$key],
				'entry' => $this->language->get($entry),
			];
		}

		$catalog = rtrim(HTTP_CATALOG, '/');
		$data['data_feed'] = $catalog . '/sitemap.xml';
		$data['data_feeds'] = [
			[
				'name' => $this->language->get('text_index'),
				'href' => $data['data_feed'],
			],
		];

		foreach ($this->model_localisation_language->getLanguages() as $language) {
			if (empty($language['status'])) {
				continue;
			}

			$data['data_feeds'][] = [
				'name' => $language['name'],
				'href' => $catalog . '/' . $language['code'] . '/sitemap.xml',
			];
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/feed/sitemap', $data));
	}

	public function install()
	{
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('feed_sitemap', $this->defaultSettings());
	}

	public function uninstall()
	{
		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('feed_sitemap');
		$this->clearSitemapCacheFiles();
	}

	public function clearCache()
	{
		$this->load->language('extension/feed/sitemap');

		if (!$this->user->hasPermission('modify', 'extension/feed/sitemap')) {
			$this->session->data['error'] = $this->language->get('error_permission');
		} else {
			$this->clearSitemapCacheFiles();
			$this->session->data['success'] = $this->language->get('text_cache_cleared');
		}

		$this->response->redirect(
			$this->url->link('extension/feed/sitemap', 'user_token=' . $this->session->data['user_token'], true)
		);
	}

	protected function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/feed/sitemap')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	private function collectSettings()
	{
		$settings = [];

		foreach ($this->defaultSettings() as $key => $default) {
			if ($key === 'feed_sitemap_cache_hours') {
				$hours = isset($this->request->post[$key]) ? (int) $this->request->post[$key] : $default;
				$settings[$key] = max(0, $hours);
				continue;
			}

			$settings[$key] = isset($this->request->post[$key]) ? (int) $this->request->post[$key] : 0;
		}

		return $settings;
	}

	private function defaultSettings()
	{
		return [
			'feed_sitemap_status' => 1,
			'feed_sitemap_main' => 1,
			'feed_sitemap_categories' => 1,
			'feed_sitemap_products' => 1,
			'feed_sitemap_information' => 1,
			'feed_sitemap_manufacturers' => 1,
			'feed_sitemap_blog_categories' => 1,
			'feed_sitemap_blog_articles' => 1,
			'feed_sitemap_blog_authors' => 1,
			'feed_sitemap_indexed_only' => 1,
			'feed_sitemap_multishop' => 0,
			'feed_sitemap_cache_hours' => 24,
		];
	}

	private function branchKeys()
	{
		return [
			'feed_sitemap_main' => 'entry_main',
			'feed_sitemap_categories' => 'entry_categories',
			'feed_sitemap_products' => 'entry_products',
			'feed_sitemap_information' => 'entry_information',
			'feed_sitemap_manufacturers' => 'entry_manufacturers',
			'feed_sitemap_blog_categories' => 'entry_blog_categories',
			'feed_sitemap_blog_articles' => 'entry_blog_articles',
			'feed_sitemap_blog_authors' => 'entry_blog_authors',
		];
	}

	private function clearSitemapCacheFiles()
	{
		$files = glob(DIR_CACHE . 'sitemap_*.xml');

		if (!$files) {
			return;
		}

		foreach ($files as $file) {
			if (is_file($file)) {
				@unlink($file);
			}
		}
	}
}
