<?php

namespace Custom;

class Pages
{
	private $config;

	public function __construct($config)
	{
		$this->config = $config;
	}

	/**
	 * Storefront page families. Missing config = enabled.
	 *
	 * group = 'account' is rendered as a separate settings fieldset.
	 * sitemap may be a string or a list of XML sitemap branches.
	 * Empty sitemap: the family has no own XML file. Static URLs (contact, HTML sitemap)
	 * are emitted from sitemap-main and must not map to that branch — otherwise
	 * sitemapAllowed('main') would hide the whole file, including home.
	 */
	public static function families()
	{
		return [
			'product' => [
				'config' => 'config_pages_product',
				'routes' => [
					'product/product',
					'product/compare',
					'product/search',
					'product/special',
					'common/search',
				],
				'except' => [],
				'sitemap' => 'products',
			],
			'category' => [
				'config' => 'config_pages_category',
				'routes' => [
					'product/category',
				],
				'except' => [],
				'sitemap' => 'categories',
			],
			'manufacturer' => [
				'config' => 'config_pages_manufacturer',
				'routes' => [
					'product/manufacturer',
				],
				'except' => [],
				'sitemap' => 'manufacturers',
			],
			'information' => [
				'config' => 'config_pages_information',
				'routes' => [
					'information/information',
				],
				'except' => [
					'information/information/agree',
				],
				'sitemap' => 'information',
			],
			'contact' => [
				'config' => 'config_pages_contact',
				'routes' => [
					'information/contact',
				],
				'except' => [],
				'sitemap' => '',
			],
			'sitemap' => [
				'config' => 'config_pages_sitemap',
				'routes' => [
					'information/sitemap',
				],
				'except' => [],
				'sitemap' => '',
			],
			'blog' => [
				'config' => 'config_pages_blog',
				'routes' => [
					'blog',
				],
				'except' => [],
				'sitemap' => ['blog-categories', 'blog-articles', 'blog-authors'],
			],
			'account_register' => [
				'config' => 'config_pages_account_register',
				'group' => 'account',
				'routes' => [
					'account/register',
				],
			],
			'account_edit' => [
				'config' => 'config_pages_account_edit',
				'group' => 'account',
				'routes' => [
					'account/edit',
				],
			],
			'account_password' => [
				'config' => 'config_pages_account_password',
				'group' => 'account',
				'routes' => [
					'account/password',
				],
			],
			'account_address' => [
				'config' => 'config_pages_account_address',
				'group' => 'account',
				'routes' => [
					'account/address',
				],
			],
			'account_wishlist' => [
				'config' => 'config_pages_account_wishlist',
				'group' => 'account',
				'routes' => [
					'account/wishlist',
				],
			],
			'account_order' => [
				'config' => 'config_pages_account_order',
				'group' => 'account',
				'routes' => [
					'account/order',
				],
			],
			'account_download' => [
				'config' => 'config_pages_account_download',
				'group' => 'account',
				'routes' => [
					'account/download',
				],
			],
			'account_reward' => [
				'config' => 'config_pages_account_reward',
				'group' => 'account',
				'routes' => [
					'account/reward',
				],
			],
			'account_return' => [
				'config' => 'config_pages_account_return',
				'group' => 'account',
				'routes' => [
					'account/return',
				],
			],
			'account_transaction' => [
				'config' => 'config_pages_account_transaction',
				'group' => 'account',
				'routes' => [
					'account/transaction',
				],
			],
			'account_newsletter' => [
				'config' => 'config_pages_account_newsletter',
				'group' => 'account',
				'routes' => [
					'account/newsletter',
				],
			],
			'account_recurring' => [
				'config' => 'config_pages_account_recurring',
				'group' => 'account',
				'routes' => [
					'account/recurring',
				],
			],
			'account_affiliate' => [
				'config' => 'config_pages_account_affiliate',
				'group' => 'account',
				'routes' => [
					'account/affiliate',
				],
			],
			'account_tracking' => [
				'config' => 'config_pages_account_tracking',
				'group' => 'account',
				'routes' => [
					'account/tracking',
				],
			],
			'account_voucher' => [
				'config' => 'config_pages_account_voucher',
				'group' => 'account',
				'routes' => [
					'account/voucher',
				],
			],
		];
	}

	public function flags()
	{
		$flags = [];

		foreach (array_keys(self::families()) as $code) {
			$flags[$code] = $this->enabled($code);
		}

		return $flags;
	}

	public function enabled($family)
	{
		$families = self::families();

		if (!isset($families[$family])) {
			return true;
		}

		$value = $this->config->get($families[$family]['config']);

		return $value === null || $value === '' || (int) $value === 1;
	}

	public function sitemapAllowed($branch)
	{
		foreach (self::families() as $code => $def) {
			if (!isset($def['sitemap']) || $def['sitemap'] === '' || $def['sitemap'] === null) {
				continue;
			}

			$branches = is_array($def['sitemap']) ? $def['sitemap'] : [$def['sitemap']];

			if (in_array($branch, $branches, true)) {
				return $this->enabled($code);
			}
		}

		return true;
	}

	public function routeAllowed($route)
	{
		$route = $this->normalizeRoute($route);

		if ($route === '') {
			return true;
		}

		foreach (self::families() as $code => $def) {
			if ($this->enabled($code)) {
				continue;
			}

			if ($this->routeListed($route, isset($def['except']) ? $def['except'] : [])) {
				continue;
			}

			if ($this->routeListed($route, $def['routes'])) {
				return false;
			}
		}

		return true;
	}

	private function normalizeRoute($route)
	{
		$route = strtolower(trim((string) $route, '/'));

		if (substr($route, -6) === '/index') {
			$route = substr($route, 0, -6);
		}

		return $route;
	}

	private function routeListed($route, array $list)
	{
		foreach ($list as $item) {
			$item = $this->normalizeRoute($item);

			if ($item === '') {
				continue;
			}

			if ($route === $item || strpos($route, $item . '/') === 0) {
				return true;
			}
		}

		return false;
	}
}
