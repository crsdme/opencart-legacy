<?php

namespace redirect_manager;

class Engine {
	const BEGIN_MARKER = '# BEGIN REDIRECT MANAGER';
	const END_MARKER = '# END REDIRECT MANAGER';
	const TOKEN_MASK = '********';

	private $registry;
	private $db;
	private $config;
	private $keyword_cache = array();
	private $language_codes = null;

	public function __construct($registry) {
		$this->registry = $registry;
		$this->db = $registry->get('db');
		$this->config = $registry->get('config');
	}

	public function redirectCodes() {
		return array(301, 302, 303, 307, 308);
	}

	public function statusCodes() {
		return array(404, 410, 451);
	}

	public function httpCodes() {
		return array_merge($this->redirectCodes(), $this->statusCodes());
	}

	public function statusPageRoute($code) {
		$code = (int)$code;

		if ($code === 410) {
			return 'error/gone';
		}

		if ($code === 404) {
			return 'error/not_found';
		}

		return '';
	}

	public function matchRequest() {
		$request = $this->registry->get('request');
		$config = $this->config;

		$uri = isset($request->server['REQUEST_URI']) ? $request->server['REQUEST_URI'] : '/';
		$parts = parse_url($uri);
		$path = isset($parts['path']) ? $parts['path'] : '/';
		$query_string = isset($parts['query']) ? $parts['query'] : '';

		$store_id = (int)$config->get('config_store_id');
		$language_id = (int)$config->get('config_language_id');

		$paths = $this->pathVariants($path);
		$stripped = $this->stripLanguagePrefix($path);

		if ($stripped !== $path) {
			$paths = array_merge($paths, $this->pathVariants($stripped));
		}

		if (!empty($request->get['_route_'])) {
			$paths = array_merge($paths, $this->pathVariants('/' . ltrim($request->get['_route_'], '/')));
		}

		$storages = array();

		foreach (array_unique($paths) as $candidate) {
			foreach ($this->pathVariants($candidate) as $variant) {
				$storages[] = $variant;

				if ($query_string !== '') {
					$storages[] = $variant . '?' . $query_string;
				}
			}
		}

		$storages = array_values(array_unique($storages));

		if (!$storages) {
			return array();
		}

		$hashes = array();
		$store_ids = array($store_id, -1);
		$language_ids = array($language_id, 0);

		foreach ($storages as $storage) {
			foreach ($store_ids as $sid) {
				foreach ($language_ids as $lid) {
					$hashes[] = $this->sourceHash($sid, $lid, $storage);
				}
			}
		}

		$hashes = array_unique($hashes);
		$escaped = array();

		foreach ($hashes as $hash) {
			$escaped[] = "'" . $this->db->escape($hash) . "'";
		}

		$result = $this->db->query("SELECT * FROM `" . DB_PREFIX . "redirect_manager_rule` WHERE enabled = '1' AND source_hash IN (" . implode(',', $escaped) . ")");

		if (!$result || empty($result->num_rows)) {
			return array();
		}

		$best = array();
		$best_score = -1;

		foreach ($result->rows as $row) {
			$score = 0;

			if ((int)$row['store_id'] === $store_id) {
				$score += 4;
			}

			if ((int)$row['language_id'] === $language_id) {
				$score += 2;
			}

			if (strpos($row['source_url'], '?') !== false) {
				$score += 1;
			}

			$score += (int)$row['priority'];

			if ($score > $best_score) {
				$best_score = $score;
				$best = $row;
			}
		}

		return $best;
	}

	public function applyQueryMode($rule) {
		$target = isset($rule['target_url']) ? $rule['target_url'] : '';

		if ($target === '') {
			return '';
		}

		$request = $this->registry->get('request');
		$query = isset($request->server['QUERY_STRING']) ? $request->server['QUERY_STRING'] : '';
		$mode = isset($rule['query_mode']) ? $rule['query_mode'] : 'ignore';

		if ($mode === 'preserve' && $query !== '') {
			$separator = strpos($target, '?') !== false ? '&' : '?';

			return $target . $separator . $query;
		}

		return $target;
	}

	private function pathVariants($path) {
		$normalized = $this->normalize($path);

		if (!$normalized['ok']) {
			return array();
		}

		$path = $normalized['path'];
		$variants = array($path);

		if ($path !== '/') {
			$trimmed = rtrim($path, '/');
			$variants[] = $trimmed;
			$variants[] = $trimmed . '/';
		}

		return $variants;
	}

	private function stripLanguagePrefix($path) {
		$path = '/' . ltrim(str_replace('\\', '/', (string)$path), '/');
		$parts = explode('/', trim($path, '/'));

		if ($parts === array('') || $parts === array()) {
			return '/';
		}

		$prefix = strtolower($parts[0]);
		$codes = $this->languageCodes();

		if (!in_array($prefix, $codes, true)) {
			return $path === '' ? '/' : $path;
		}

		array_shift($parts);
		$rest = implode('/', $parts);

		return $rest === '' ? '/' : '/' . $rest;
	}

	private function languageCodes() {
		if ($this->language_codes !== null) {
			return $this->language_codes;
		}

		$query = $this->db->query("SELECT code FROM `" . DB_PREFIX . "language` WHERE status = '1'");
		$this->language_codes = array();

		foreach ($query->rows as $row) {
			$this->language_codes[] = strtolower($row['code']);
		}

		return $this->language_codes;
	}

	public function queryModes() {
		return array('ignore', 'exact', 'preserve', 'drop');
	}

	public function sourceTypes() {
		return array('manual', 'product', 'category', 'information', 'manufacturer', 'import', 'seo_history');
	}

	public function normalize($url) {
		$url = trim((string)$url);

		if ($url === '') {
			return array(
				'ok'      => false,
				'error'   => 'empty',
				'storage' => '',
				'path'    => '',
				'query'   => '',
				'host'    => '',
				'is_full' => false
			);
		}

		$is_full = (bool)preg_match('#^https?://#i', $url);
		$query = '';
		$host = '';
		$path = $url;
		$scheme = 'https';

		if ($is_full) {
			$parts = parse_url($url);

			if ($parts === false || empty($parts['host'])) {
				return array(
					'ok'      => false,
					'error'   => 'invalid',
					'storage' => $url,
					'path'    => '',
					'query'   => '',
					'host'    => '',
					'is_full' => true
				);
			}

			$host = strtolower($parts['host']);
			$path = isset($parts['path']) ? $parts['path'] : '/';
			$query = isset($parts['query']) ? $parts['query'] : '';
			$scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : 'https';
		} else {
			if (strpos($path, '?') !== false) {
				list($path, $query) = explode('?', $path, 2);
			}

			$path = preg_replace('#^https?://[^/]+#i', '', $path);
		}

		$path = $this->normalizePath($path);

		if ($is_full) {
			$storage = $scheme . '://' . $host . $path;

			if ($query !== '') {
				$storage .= '?' . $query;
			}
		} else {
			$storage = $path;

			if ($query !== '') {
				$storage .= '?' . $query;
			}
		}

		return array(
			'ok'      => true,
			'error'   => '',
			'storage' => $storage,
			'path'    => $path,
			'query'   => $query,
			'host'    => $host,
			'is_full' => $is_full
		);
	}

	public function normalizePath($path) {
		$path = str_replace('\\', '/', (string)$path);

		if ($path === '') {
			return '/';
		}

		$hash_pos = strpos($path, '#');

		if ($hash_pos !== false) {
			$path = substr($path, 0, $hash_pos);
		}

		$segments = explode('/', $path);
		$clean = array();

		foreach ($segments as $i => $segment) {
			if ($segment === '' && $i !== 0) {
				continue;
			}

			if ($segment === '.' || $segment === '..') {
				continue;
			}

			if ($segment === '') {
				$clean[] = '';
				continue;
			}

			$decoded = rawurldecode($segment);

			if (function_exists('normalizer_normalize')) {
				$normalized = normalizer_normalize($decoded, \Normalizer::FORM_C);

				if ($normalized !== false) {
					$decoded = $normalized;
				}
			}

			$clean[] = $this->encodeSegment($decoded);
		}

		$result = implode('/', $clean);

		if ($result === '' || $result[0] !== '/') {
			$result = '/' . ltrim($result, '/');
		}

		if ($result !== '/' && substr($result, -1) === '/') {
			$result = rtrim($result, '/') . '/';
		}

		return $result;
	}

	private function encodeSegment($segment) {
		if ($segment === '') {
			return '';
		}

		return preg_replace_callback('/[^\w\-\.~%]/u', function ($m) {
			return rawurlencode($m[0]);
		}, $segment);
	}

	public function sourceHash($store_id, $language_id, $source_url) {
		return hash('sha256', (int)$store_id . '|' . (int)$language_id . '|' . $source_url);
	}

	public function rulesHash($rows) {
		$parts = array();

		foreach ($rows as $row) {
			$parts[] = implode('|', array(
				isset($row['store_id']) ? $row['store_id'] : 0,
				isset($row['language_id']) ? $row['language_id'] : 0,
				isset($row['source_url']) ? $row['source_url'] : '',
				isset($row['target_url']) ? $row['target_url'] : '',
				isset($row['action']) ? $row['action'] : '',
				isset($row['http_code']) ? $row['http_code'] : '',
				isset($row['query_mode']) ? $row['query_mode'] : '',
				isset($row['enabled']) ? $row['enabled'] : 1
			));
		}

		sort($parts);

		return hash('sha256', implode("\n", $parts));
	}

	public function validateRule($data) {
		$errors = array();
		$source = $this->normalize(isset($data['source_url']) ? $data['source_url'] : '');

		if (!$source['ok']) {
			$errors[] = 'empty_source';
		}

		$action = isset($data['action']) ? $data['action'] : 'redirect';
		$code = isset($data['http_code']) ? (int)$data['http_code'] : 301;

		if ($action === 'redirect') {
			if (!in_array($code, $this->redirectCodes(), true)) {
				$errors[] = 'invalid_status';
			}

			$target = $this->normalize(isset($data['target_url']) ? $data['target_url'] : '');

			if (!$target['ok']) {
				$errors[] = 'missing_target';
			}
		} else {
			if (!in_array($code, $this->statusCodes(), true)) {
				$errors[] = 'invalid_status';
			}
		}

		if (isset($data['query_mode']) && !in_array($data['query_mode'], $this->queryModes(), true)) {
			$errors[] = 'invalid_query_mode';
		}

		return $errors;
	}

	public function detectLoops($rules) {
		$map = $this->sourceTargetMap($rules);
		$loops = array();
		$state = array();

		foreach ($map as $source => $target) {
			$path = array();
			$current = $source;

			while (isset($map[$current])) {
				if (isset($state[$current]) && $state[$current] === 1) {
					break;
				}

				if (in_array($current, $path, true)) {
					$loop = array_slice($path, array_search($current, $path, true));
					$loop[] = $current;
					$loops[] = $loop;
					break;
				}

				$path[] = $current;
				$current = $map[$current];
			}

			foreach ($path as $node) {
				$state[$node] = 1;
			}
		}

		return $loops;
	}

	public function detectChains($rules) {
		$map = $this->sourceTargetMap($rules);
		$chains = array();

		foreach ($map as $source => $target) {
			if (isset($map[$target])) {
				$final = $target;
				$guard = 0;

				while (isset($map[$final]) && $guard < 20) {
					$final = $map[$final];
					$guard++;

					if ($final === $source) {
						$final = '';
						break;
					}
				}

				if ($final !== '' && $final !== $target) {
					$chains[] = array(
						'source' => $source,
						'mid'    => $target,
						'final'  => $final
					);
				}
			}
		}

		return $chains;
	}

	private function sourceTargetMap($rules) {
		$map = array();

		foreach ($rules as $rule) {
			if (empty($rule['enabled'])) {
				continue;
			}

			if ((isset($rule['action']) ? $rule['action'] : 'redirect') !== 'redirect') {
				continue;
			}

			$source = isset($rule['source_url']) ? $rule['source_url'] : '';
			$target = isset($rule['target_url']) ? $rule['target_url'] : '';

			if ($source !== '' && $target !== '') {
				$map[$source] = $target;
			}
		}

		return $map;
	}

	public function getKeyword($query, $store_id, $language_id) {
		$key = $store_id . ':' . $language_id . ':' . $query;

		if (isset($this->keyword_cache[$key])) {
			return $this->keyword_cache[$key];
		}

		$result = $this->db->query("SELECT keyword FROM `" . DB_PREFIX . "seo_url` WHERE `query` = '" . $this->db->escape($query) . "' AND store_id = '" . (int)$store_id . "' AND language_id = '" . (int)$language_id . "' LIMIT 1");
		$keyword = ($result->num_rows) ? trim($result->row['keyword']) : '';

		if ($keyword === '' && (int)$store_id !== 0) {
			$result = $this->db->query("SELECT keyword FROM `" . DB_PREFIX . "seo_url` WHERE `query` = '" . $this->db->escape($query) . "' AND store_id = '0' AND language_id = '" . (int)$language_id . "' LIMIT 1");
			$keyword = ($result->num_rows) ? trim($result->row['keyword']) : '';
		}

		$this->keyword_cache[$key] = $keyword;

		return $keyword;
	}

	public function getCategoryPathIds($category_id) {
		$query = $this->db->query("SELECT path_id FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$category_id . "' ORDER BY `level` ASC");
		$ids = array();

		foreach ($query->rows as $row) {
			$ids[] = (int)$row['path_id'];
		}

		return $ids;
	}

	public function getCategoryUrl($category_id, $language_id, $store_id) {
		$ids = $this->getCategoryPathIds($category_id);

		if (!$ids) {
			$keyword = $this->getKeyword('category_id=' . (int)$category_id, $store_id, $language_id);

			return $keyword !== '' ? $this->withSlash($keyword) : '';
		}

		$parts = array();

		foreach ($ids as $id) {
			$keyword = $this->getKeyword('category_id=' . (int)$id, $store_id, $language_id);

			if ($keyword === '') {
				return '';
			}

			$parts[] = trim($keyword, '/');
		}

		return $this->withSlash(implode('/', $parts));
	}

	public function getProductUrl($product_id, $language_id, $store_id) {
		$keyword = $this->getKeyword('product_id=' . (int)$product_id, $store_id, $language_id);

		if ($keyword === '') {
			return '';
		}

		$path = '';

		if ($this->config->get('config_seo_url_include_path')) {
			$main = $this->db->query("SELECT category_id FROM `" . DB_PREFIX . "product_to_category` WHERE product_id = '" . (int)$product_id . "' ORDER BY main_category DESC LIMIT 1");

			if ($main->num_rows) {
				$path = trim($this->getCategoryUrl((int)$main->row['category_id'], $language_id, $store_id), '/');
			}
		}

		$product = trim($keyword, '/');

		if ($path !== '') {
			return $this->withSlash($path . '/' . $product);
		}

		return $this->withSlash($product);
	}

	public function getInformationUrl($information_id, $language_id, $store_id) {
		$keyword = $this->getKeyword('information_id=' . (int)$information_id, $store_id, $language_id);

		return $keyword !== '' ? $this->withSlash($keyword) : '';
	}

	public function getManufacturerUrl($manufacturer_id, $language_id, $store_id) {
		$keyword = $this->getKeyword('manufacturer_id=' . (int)$manufacturer_id, $store_id, $language_id);

		return $keyword !== '' ? $this->withSlash($keyword) : '';
	}

	public function getHomeUrl() {
		return '/';
	}

	public function getEntityUrl($type, $entity_id, $language_id, $store_id) {
		switch ($type) {
			case 'product':
				return $this->getProductUrl($entity_id, $language_id, $store_id);
			case 'category':
				return $this->getCategoryUrl($entity_id, $language_id, $store_id);
			case 'information':
				return $this->getInformationUrl($entity_id, $language_id, $store_id);
			case 'manufacturer':
				return $this->getManufacturerUrl($entity_id, $language_id, $store_id);
			case 'home':
				return $this->getHomeUrl();
			default:
				return '';
		}
	}

	private function withSlash($path) {
		$path = '/' . ltrim($path, '/');

		if ($path !== '/' && substr($path, -1) !== '/') {
			$postfix = (string)$this->config->get('config_seo_url_postfix');

			if ($postfix === '') {
				$path .= '/';
			}
		}

		return $path;
	}

	public function documentRoot() {
		return rtrim(str_replace('\\', '/', dirname(DIR_APPLICATION)), '/');
	}

	public function storageRoot() {
		return rtrim(str_replace('\\', '/', DIR_STORAGE), '/') . '/redirect_manager';
	}

	public function maskToken($token) {
		$token = (string)$token;

		if ($token === '') {
			return '';
		}

		return self::TOKEN_MASK;
	}

	public function encryptValue($value) {
		if ($value === '' || $value === self::TOKEN_MASK) {
			return $value;
		}

		$key = (string)$this->config->get('config_encryption');

		if ($key === '') {
			return base64_encode($value);
		}

		$encryption = new \Encryption();

		return $encryption->encrypt($key, $value);
	}

	public function decryptValue($value) {
		$value = (string)$value;

		if ($value === '' || $value === self::TOKEN_MASK) {
			return '';
		}

		$key = (string)$this->config->get('config_encryption');

		if ($key === '') {
			$decoded = base64_decode($value, true);

			return ($decoded !== false) ? $decoded : $value;
		}

		$encryption = new \Encryption();
		$decrypted = $encryption->decrypt($key, $value);

		return ($decrypted !== false && $decrypted !== null) ? $decrypted : '';
	}

	public function getTabs($url, $token, $active) {
		$pages = array(
			'dashboard' => array('text' => 'text_tab_dashboard', 'route' => 'extension/redirect_manager/dashboard'),
			'rule'      => array('text' => 'text_tab_rules', 'route' => 'extension/redirect_manager/rule'),
			'category'  => array('text' => 'text_tab_category', 'route' => 'extension/redirect_manager/mass/category'),
			'product'   => array('text' => 'text_tab_product', 'route' => 'extension/redirect_manager/mass/product'),
			'import'    => array('text' => 'text_tab_import', 'route' => 'extension/redirect_manager/rule/import'),
			'config'    => array('text' => 'text_tab_config', 'route' => 'extension/redirect_manager/config'),
			'log'       => array('text' => 'text_tab_logs', 'route' => 'extension/redirect_manager/dashboard/log'),
			'setting'   => array('text' => 'text_tab_settings', 'route' => 'extension/redirect_manager/setting')
		);

		$tabs = array();

		foreach ($pages as $id => $page) {
			$tabs[] = array(
				'id'     => $id,
				'href'   => $url->link($page['route'], 'user_token=' . $token, true),
				'active' => ($id === $active),
				'text'   => $page['text']
			);
		}

		return $tabs;
	}

	public function storeUrl($store_id) {
		if ((int)$store_id === 0) {
			$ssl = $this->config->get('config_ssl');
			$url = $ssl ? $ssl : $this->config->get('config_url');

			if (!$url && defined('HTTPS_CATALOG')) {
				$url = HTTPS_CATALOG;
			}

			if (!$url && defined('HTTP_CATALOG')) {
				$url = HTTP_CATALOG;
			}

			return rtrim((string)$url, '/');
		}

		$query = $this->db->query("SELECT ssl, url FROM `" . DB_PREFIX . "store` WHERE store_id = '" . (int)$store_id . "' LIMIT 1");

		if (!$query->num_rows) {
			return $this->storeUrl(0);
		}

		$url = $query->row['ssl'] ? $query->row['ssl'] : $query->row['url'];

		return rtrim((string)$url, '/');
	}

	public function absoluteUrl($path, $store_id) {
		$normalized = $this->normalize($path);

		if ($normalized['is_full']) {
			return $normalized['storage'];
		}

		return $this->storeUrl($store_id) . $normalized['path'];
	}
}
