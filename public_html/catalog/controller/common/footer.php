<?php
class ControllerCommonFooter extends Controller
{
	public function index($data)
	{
		$this->load->language('common/footer');

		$pages = new \Custom\Pages($this->config);

		$data['home'] = $this->url->link('common/home');
		$data['name'] = $this->config->get('config_name');
		$data['telephone'] = trim((string) $this->config->get('config_telephone'));
		$data['email'] = trim((string) $this->config->get('config_email'));
		$data['address'] = trim((string) $this->config->get('config_address'));
		$data['telephone_href'] =
			$data['telephone'] !== '' ? 'tel:' . preg_replace('/[^\d+]/', '', $data['telephone']) : '';
		$data['email_href'] = $data['email'] !== '' ? 'mailto:' . $data['email'] : '';
		$data['text_home'] = $this->language->get('text_home');

		$data['columns'] = [];

		$information_links = [];

		if ($pages->enabled('information')) {
			$this->load->model('catalog/information');

			foreach ($this->model_catalog_information->getInformations() as $result) {
				if ($result['bottom']) {
					$information_links[] = [
						'name' => $result['title'],
						'href' => $this->url->link(
							'information/information',
							'information_id=' . $result['information_id'],
						),
					];
				}
			}
		}

		$this->addFooterColumn($data, $this->language->get('text_information'), $information_links);

		$service_links = [];

		if ($pages->enabled('contact')) {
			$service_links[] = [
				'name' => $this->language->get('text_contact'),
				'href' => $this->url->link('information/contact'),
			];
		}

		if ($pages->enabled('account_return')) {
			$service_links[] = [
				'name' => $this->language->get('text_return'),
				'href' => $this->url->link('account/return/add', '', true),
			];
		}

		if ($pages->enabled('sitemap')) {
			$service_links[] = [
				'name' => $this->language->get('text_sitemap'),
				'href' => $this->url->link('information/sitemap'),
			];
		}

		$this->addFooterColumn($data, $this->language->get('text_service'), $service_links);

		$extra_links = [];

		if ($pages->enabled('manufacturer')) {
			$extra_links[] = [
				'name' => $this->language->get('text_manufacturer'),
				'href' => $this->url->link('product/manufacturer'),
			];
		}

		if ($pages->enabled('product')) {
			$extra_links[] = [
				'name' => $this->language->get('text_special'),
				'href' => $this->url->link('product/special'),
			];
		}

		if ($pages->enabled('blog')) {
			$blog_name = trim((string) $this->config->get('configblog_name'));

			$extra_links[] = [
				'name' => $blog_name !== '' ? $blog_name : $this->language->get('text_blog'),
				'href' => $this->url->link('blog/latest'),
			];
		}

		if ($pages->enabled('account_voucher')) {
			$extra_links[] = [
				'name' => $this->language->get('text_voucher'),
				'href' => $this->url->link('account/voucher', '', true),
			];
		}

		$this->addFooterColumn($data, $this->language->get('text_extra'), $extra_links);

		$account_links = [
			[
				'name' => $this->language->get('text_account'),
				'href' => $this->url->link('account/account', '', true),
			],
		];

		if ($pages->enabled('account_order')) {
			$account_links[] = [
				'name' => $this->language->get('text_order'),
				'href' => $this->url->link('account/order', '', true),
			];
		}

		if ($pages->enabled('account_wishlist')) {
			$account_links[] = [
				'name' => $this->language->get('text_wishlist'),
				'href' => $this->url->link('account/wishlist', '', true),
			];
		}

		if ($pages->enabled('account_newsletter')) {
			$account_links[] = [
				'name' => $this->language->get('text_newsletter'),
				'href' => $this->url->link('account/newsletter', '', true),
			];
		}

		$this->addFooterColumn($data, $this->language->get('text_account'), $account_links);

		$data['powered'] = sprintf(
			$this->language->get('text_powered'),
			$this->config->get('config_name'),
			date('Y', time()),
		);

		if ($this->config->get('config_customer_online')) {
			$this->load->model('tool/online');

			if (isset($this->request->server['REMOTE_ADDR'])) {
				$ip = $this->request->server['REMOTE_ADDR'];
			} else {
				$ip = '';
			}

			if (isset($this->request->server['HTTP_HOST']) && isset($this->request->server['REQUEST_URI'])) {
				$url =
					($this->request->server['HTTPS'] ? 'https://' : 'http://') .
					$this->request->server['HTTP_HOST'] .
					$this->request->server['REQUEST_URI'];
			} else {
				$url = '';
			}

			if (isset($this->request->server['HTTP_REFERER'])) {
				$referer = $this->request->server['HTTP_REFERER'];
			} else {
				$referer = '';
			}

			$this->model_tool_online->addOnline($ip, $this->customer->getId(), $url, $referer);
		}

		$this->document->addScript('catalog/view/theme/default/javascript/jquery.min.js', 'footer', true);
		$this->document->addScript('catalog/view/theme/default/javascript/ui.js', 'footer', true);
		$this->document->addScript('catalog/view/theme/default/javascript/catalog.js', 'footer', true);

		$data['scripts'] = $this->document->getScripts('footer');
		$data['styles'] = $this->document->getStyles('footer');
		$data['rendered_footer_styles'] = '';
		$data['rendered_footer_scripts'] = '';

		if ($this->registry->has('minifier')) {
			$minifier = $this->registry->get('minifier');
			$data['rendered_footer_styles'] = $minifier->renderStyles($data['styles']);
			$data['rendered_footer_scripts'] = $minifier->renderScripts($data['scripts'], 'footer');
		}

		$data['route'] = $this->request->get['route'] ?? 'common/home';

		return $this->load->view('common/footer', $data);
	}

	private function addFooterColumn(array &$data, $title, array $links)
	{
		if (!$links) {
			return;
		}

		$data['columns'][] = [
			'title' => $title,
			'links' => $links,
		];
	}
}
