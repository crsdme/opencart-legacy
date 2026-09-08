<?php

namespace redirect_manager;

class Backend {
	private $registry;
	private $config;
	private $engine;
	private $type;

	public function __construct($registry, $type = 'apache') {
		$this->registry = $registry;
		$this->config = $registry->get('config');
		$this->engine = new Engine($registry);
		$this->type = $this->normalizeType($type);
	}

	public static function factory($registry, $type) {
		return new self($registry, $type);
	}

	public function getType() {
		return $this->type;
	}

	public function generate($rules, $settings = array()) {
		$errors = $this->validateRules($rules);

		if ($errors) {
			return array(
				'ok'      => false,
				'errors'  => $errors,
				'path'    => '',
				'hash'    => '',
				'count'   => 0,
				'content' => '',
				'warning' => array()
			);
		}

		$method = 'compile' . ucfirst($this->type);

		return $this->{$method}($rules, $settings);
	}

	public function validateRules($rules) {
		$errors = array();
		$sources = array();

		foreach ($rules as $i => $rule) {
			if (empty($rule['enabled'])) {
				continue;
			}

			$rule_errors = $this->engine->validateRule($rule);

			foreach ($rule_errors as $error) {
				$errors[] = array('index' => $i, 'source' => isset($rule['source_url']) ? $rule['source_url'] : '', 'error' => $error);
			}

			$key = ((int)$rule['store_id']) . '|' . ((int)$rule['language_id']) . '|' . $rule['source_url'];

			if (isset($sources[$key])) {
				$errors[] = array('index' => $i, 'source' => $rule['source_url'], 'error' => 'duplicate_source');
			}

			$sources[$key] = true;
		}

		$loops = $this->engine->detectLoops($rules);

		foreach ($loops as $loop) {
			$errors[] = array('index' => 0, 'source' => implode(' -> ', $loop), 'error' => 'redirect_loop');
		}

		return $errors;
	}

	public function deploy($rules, $settings = array()) {
		$result = $this->generate($rules, $settings);

		if (!$result['ok']) {
			return $result;
		}

		if ($this->type === 'cloudflare') {
			return $this->deployCloudflare($result, $settings);
		}

		$written = $this->writeGenerated($result['files']);

		if (!$written['ok']) {
			$result['ok'] = false;
			$result['errors'] = $written['errors'];

			return $result;
		}

		if (!empty($settings['allow_reload']) && $this->type === 'nginx') {
			$result['reload'] = $this->reloadNginx();
		}

		$result['path'] = $written['path'];
		$result['hash'] = $written['hash'];

		return $result;
	}

	public function readExisting($settings = array()) {
		$method = 'read' . ucfirst($this->type);

		if (!method_exists($this, $method)) {
			return array();
		}

		return $this->{$method}($settings);
	}

	public function defaultPath() {
		$root = $this->engine->storageRoot();

		switch ($this->type) {
			case 'nginx':
				return $root . '/nginx/redirects.conf';
			case 'cloudflare':
				return $root . '/cloudflare/redirects.json';
			case 'php':
				return $root . '/php/map.php';
			case 'apache':
			default:
				$mode = $this->config->get('module_redirect_manager_apache_mode');

				if ($mode === 'htaccess') {
					return $this->engine->documentRoot() . '/.htaccess';
				}

				return $root . '/apache/redirects.conf';
		}
	}

	private function compileApache($rules, $settings) {
		$mode = isset($settings['apache_mode']) ? $settings['apache_mode'] : $this->config->get('module_redirect_manager_apache_mode');

		if ($mode === 'htaccess') {
			return $this->compileApacheHtaccess($rules, $settings);
		}

		return $this->compileApacheMap($rules, $settings);
	}

	private function compileApacheMap($rules, $settings) {
		$redirects = array();
		$status = array();
		$count = 0;

		foreach ($this->enabled($rules) as $rule) {
			$path = $this->pathOnly($rule['source_url']);

			if ($rule['action'] === 'status') {
				$status[] = $path . ' ' . (int)$rule['http_code'];
			} else {
				$target = $this->apacheTarget($rule);
				$redirects[] = $path . ' ' . (int)$rule['http_code'] . '|' . $target;
			}

			$count++;
		}

		$map_dir = $this->engine->storageRoot() . '/apache';
		$redirect_map = $map_dir . '/redirects.map';
		$status_map = $map_dir . '/status.map';
		$conf_path = isset($settings['apache_file']) && $settings['apache_file'] !== '' ? $settings['apache_file'] : $map_dir . '/redirects.conf';

		$conf = "# Redirect Manager generated " . date('c') . "\n";
		$conf .= "# Add to the VirtualHost (RewriteMap cannot live in .htaccess):\n";
		$conf .= "# RewriteMap rm_redirects txt:" . $redirect_map . "\n";
		$conf .= "# RewriteMap rm_status txt:" . $status_map . "\n\n";
		$conf .= "RewriteEngine On\n\n";
		$conf .= "RewriteCond %{ENV:RM_SKIP} =1\n";
		$conf .= "RewriteRule ^ - [L]\n\n";
		$conf .= 'RewriteCond ${rm_status:%{REQUEST_URI}} ^410$' . "\n";
		$conf .= "RewriteRule ^ index.php?route=error/gone [L,QSA]\n\n";
		$conf .= 'RewriteCond ${rm_status:%{REQUEST_URI}} ^404$' . "\n";
		$conf .= "RewriteRule ^ index.php?route=error/not_found [L,QSA]\n\n";
		$conf .= 'RewriteCond ${rm_status:%{REQUEST_URI}} ^451$' . "\n";
		$conf .= "RewriteRule ^ - [R=451,L]\n\n";
		$conf .= 'RewriteCond ${rm_redirects:%{REQUEST_URI}} ^(.+)$' . "\n";
		$conf .= "RewriteRule ^ - [E=RM_HIT:%1]\n";
		$conf .= "RewriteCond %{ENV:RM_HIT} ^([0-9]+)\\|(.*)$\n";
		$conf .= "RewriteRule ^ %2 [R=%1,L]\n";

		return array(
			'ok'      => true,
			'errors'  => array(),
			'warning' => array(),
			'count'   => $count,
			'hash'    => $this->engine->rulesHash($this->enabled($rules)),
			'path'    => $conf_path,
			'content' => $conf,
			'files'   => array(
				$redirect_map => implode("\n", $redirects) . ($redirects ? "\n" : ''),
				$status_map   => implode("\n", $status) . ($status ? "\n" : ''),
				$conf_path    => $conf
			)
		);
	}

	private function compileApacheHtaccess($rules, $settings) {
		$lines = array();
		$count = 0;

		foreach ($this->enabled($rules) as $rule) {
			$path = $this->pathOnly($rule['source_url']);
			$quoted = $this->apachePath($path);

			if ($rule['action'] === 'status') {
				$code = (int)$rule['http_code'];

				if ($code === 410) {
					$lines[] = 'Redirect gone ' . $quoted;
				} else {
					$lines[] = 'RedirectMatch ' . $code . ' ^' . preg_quote($path, '#') . '/?$';
				}
			} else {
				$target = $this->apacheTarget($rule);
				$lines[] = 'Redirect ' . (int)$rule['http_code'] . ' ' . $quoted . ' ' . $target;
			}

			$count++;
		}

		$block = Engine::BEGIN_MARKER . "\nErrorDocument 410 /index.php?route=error/gone\n" . implode("\n", $lines) . "\n" . Engine::END_MARKER . "\n";
		$path = isset($settings['apache_file']) && $settings['apache_file'] !== '' ? $settings['apache_file'] : $this->engine->documentRoot() . '/.htaccess';

		return array(
			'ok'      => true,
			'errors'  => array(),
			'warning' => array('htaccess_scale'),
			'count'   => $count,
			'hash'    => $this->engine->rulesHash($this->enabled($rules)),
			'path'    => $path,
			'content' => $block,
			'files'   => array(
				$path => array('mode' => 'htaccess', 'block' => $block)
			)
		);
	}

	private function compileNginx($rules, $settings) {
		$targets = array();
		$codes = array();
		$count = 0;

		foreach ($this->enabled($rules) as $rule) {
			$path = $this->nginxPath($this->pathOnly($rule['source_url']));

			if ($rule['action'] === 'status') {
				$codes[] = '    ' . $path . ' ' . (int)$rule['http_code'] . ';';
			} else {
				$target = $this->nginxTarget($rule);
				$targets[] = '    ' . $path . ' ' . $target . ';';
				$codes[] = '    ' . $path . ' ' . (int)$rule['http_code'] . ';';
			}

			$count++;
		}

		$conf = "# Redirect Manager generated " . date('c') . "\n";
		$conf .= "# Place inside http {}:\n";
		$conf .= "#   include " . $this->nginxIncludePath($settings) . ";\n";
		$conf .= "# Place inside server {}:\n";
		$conf .= "#   error_page 410 =410 /index.php?route=error/gone;\n";
		$conf .= "#   error_page 404 =404 /index.php?route=error/not_found;\n";
		$conf .= "#   if (\$rm_code = 410) { return 410; }\n";
		$conf .= "#   if (\$rm_code = 404) { return 404; }\n";
		$conf .= "#   if (\$rm_code = 451) { return 451; }\n";
		$conf .= "#   if (\$rm_target != \"\") { return \$rm_code \$rm_target; }\n\n";
		$conf .= "map \$uri \$rm_target {\n";
		$conf .= "    default \"\";\n";
		$conf .= implode("\n", $targets) . ($targets ? "\n" : '');
		$conf .= "}\n\n";
		$conf .= "map \$uri \$rm_code {\n";
		$conf .= "    default 0;\n";
		$conf .= implode("\n", $codes) . ($codes ? "\n" : '');
		$conf .= "}\n";

		$path = $this->nginxIncludePath($settings);

		return array(
			'ok'      => true,
			'errors'  => array(),
			'warning' => array(),
			'count'   => $count,
			'hash'    => $this->engine->rulesHash($this->enabled($rules)),
			'path'    => $path,
			'content' => $conf,
			'files'   => array(
				$path => $conf
			)
		);
	}

	private function compileCloudflare($rules, $settings) {
		$items = array();
		$skipped = array();
		$count = 0;
		$domain = isset($settings['cloudflare_domain']) ? rtrim($settings['cloudflare_domain'], '/') : '';

		foreach ($this->enabled($rules) as $rule) {
			if ($rule['action'] !== 'redirect') {
				$skipped[] = $rule['source_url'];
				continue;
			}

			$store_id = isset($rule['store_id']) ? (int)$rule['store_id'] : 0;
			$source = $this->engine->absoluteUrl($rule['source_url'], $store_id < 0 ? 0 : $store_id);
			$target = $this->engine->absoluteUrl($rule['target_url'], $store_id < 0 ? 0 : $store_id);

			if ($domain && strpos($source, $domain) === false && !$this->engine->normalize($rule['source_url'])['is_full']) {
				$source = rtrim($domain, '/') . $this->pathOnly($rule['source_url']);
			}

			$items[] = array(
				'source_url'             => $source,
				'target_url'             => $target,
				'status_code'            => (int)$rule['http_code'],
				'preserve_query_string'  => ($rule['query_mode'] === 'preserve'),
				'include_subdomains'     => false,
				'subpath_matching'       => false,
				'preserve_path_suffix'   => false
			);
			$count++;
		}

		$payload = array(
			'generated' => date('c'),
			'count'     => $count,
			'items'     => $items
		);

		$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$path = $this->engine->storageRoot() . '/cloudflare/redirects.json';
		$warning = $skipped ? array('cloudflare_status_unsupported') : array();

		return array(
			'ok'       => true,
			'errors'   => array(),
			'warning'  => $warning,
			'count'    => $count,
			'hash'     => $this->engine->rulesHash($this->enabled($rules)),
			'path'     => $path,
			'content'  => $json,
			'skipped'  => $skipped,
			'files'    => array(
				$path => $json
			),
			'payload'  => $payload
		);
	}

	private function compilePhp($rules, $settings) {
		$map = array();
		$count = 0;

		foreach ($this->enabled($rules) as $rule) {
			$path = $this->pathOnly($rule['source_url']);
			$map[$path] = array(
				'action' => $rule['action'],
				'code'   => (int)$rule['http_code'],
				'target' => $rule['action'] === 'redirect' ? $rule['target_url'] : '',
				'query'  => $rule['query_mode']
			);
			$count++;
		}

		$export = var_export($map, true);
		$content = "<?php\nreturn " . $export . ";\n";
		$path = $this->engine->storageRoot() . '/php/map.php';

		$boot = "<?php\n";
		$boot .= "if (PHP_SAPI === 'cli') {\n    return;\n}\n";
		$boot .= "\$map = include __DIR__ . '/map.php';\n";
		$boot .= "\$uri = parse_url(isset(\$_SERVER['REQUEST_URI']) ? \$_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH);\n";
		$boot .= "if (!is_string(\$uri) || !isset(\$map[\$uri])) {\n    return;\n}\n";
		$boot .= "\$rule = \$map[\$uri];\n";
		$boot .= "if (\$rule['action'] === 'status') {\n";
		$boot .= "    \$code = (int)\$rule['code'];\n";
		$boot .= "    if (\$code === 410) {\n";
		$boot .= "        \$_GET['route'] = 'error/gone';\n";
		$boot .= "        unset(\$_GET['_route_']);\n";
		$boot .= "        return;\n";
		$boot .= "    }\n";
		$boot .= "    if (\$code === 404) {\n";
		$boot .= "        \$_GET['route'] = 'error/not_found';\n";
		$boot .= "        unset(\$_GET['_route_']);\n";
		$boot .= "        return;\n";
		$boot .= "    }\n";
		$boot .= "    http_response_code(\$code);\n";
		$boot .= "    exit;\n";
		$boot .= "}\n";
		$boot .= "header('Location: ' . \$rule['target'], true, (int)\$rule['code']);\n";
		$boot .= "exit;\n";

		return array(
			'ok'      => true,
			'errors'  => array(),
			'warning' => array(),
			'count'   => $count,
			'hash'    => $this->engine->rulesHash($this->enabled($rules)),
			'path'    => $path,
			'content' => $content,
			'files'   => array(
				$path => $content,
				$this->engine->storageRoot() . '/php/bootstrap.php' => $boot
			)
		);
	}

	private function deployCloudflare($result, $settings) {
		$written = $this->writeGenerated($result['files']);

		if (!$written['ok']) {
			$result['ok'] = false;
			$result['errors'] = $written['errors'];

			return $result;
		}

		$token = $this->engine->decryptValue(isset($settings['cloudflare_token']) ? $settings['cloudflare_token'] : '');
		$account_id = isset($settings['cloudflare_account_id']) ? $settings['cloudflare_account_id'] : '';
		$list_id = isset($settings['cloudflare_list_id']) ? $settings['cloudflare_list_id'] : '';

		if ($token === '' || $account_id === '' || $list_id === '') {
			$result['ok'] = false;
			$result['errors'][] = array('error' => 'cloudflare_credentials');

			return $result;
		}

		$items = isset($result['payload']['items']) ? $result['payload']['items'] : array();
		$batches = array_chunk($items, 1000);
		$operation_ids = array();

		foreach ($batches as $batch) {
			$body = array();

			foreach ($batch as $item) {
				$body[] = array('redirect' => $item);
			}

			$response = $this->cloudflareRequest('PUT', '/accounts/' . rawurlencode($account_id) . '/rules/lists/' . rawurlencode($list_id) . '/items', $token, $body);

			if (empty($response['success'])) {
				$result['ok'] = false;
				$result['errors'][] = array('error' => 'cloudflare_api', 'detail' => $this->cloudflareError($response));
				$result['operation_id'] = isset($operation_ids[0]) ? $operation_ids[0] : '';

				return $result;
			}

			if (!empty($response['result']['operation_id'])) {
				$operation_ids[] = $response['result']['operation_id'];
				$poll = $this->cloudflarePoll($account_id, $response['result']['operation_id'], $token);

				if (!$poll['ok']) {
					$result['ok'] = false;
					$result['errors'][] = array('error' => 'cloudflare_operation', 'detail' => $poll['error']);
					$result['operation_id'] = $response['result']['operation_id'];

					return $result;
				}
			}
		}

		$result['path'] = $written['path'];
		$result['hash'] = $written['hash'];
		$result['operation_id'] = implode(',', $operation_ids);

		return $result;
	}

	private function cloudflarePoll($account_id, $operation_id, $token) {
		$attempts = 0;

		while ($attempts < 20) {
			$response = $this->cloudflareRequest('GET', '/accounts/' . rawurlencode($account_id) . '/rules/lists/bulk_operations/' . rawurlencode($operation_id), $token, null);

			if (empty($response['success'])) {
				return array('ok' => false, 'error' => $this->cloudflareError($response));
			}

			$status = isset($response['result']['status']) ? $response['result']['status'] : '';

			if ($status === 'completed') {
				return array('ok' => true, 'error' => '');
			}

			if ($status === 'failed') {
				return array('ok' => false, 'error' => isset($response['result']['error']) ? $response['result']['error'] : 'failed');
			}

			$attempts++;
			usleep(500000);
		}

		return array('ok' => false, 'error' => 'timeout');
	}

	public function cloudflareListItems($settings) {
		$token = $this->engine->decryptValue(isset($settings['cloudflare_token']) ? $settings['cloudflare_token'] : '');
		$account_id = isset($settings['cloudflare_account_id']) ? $settings['cloudflare_account_id'] : '';
		$list_id = isset($settings['cloudflare_list_id']) ? $settings['cloudflare_list_id'] : '';

		if ($token === '' || $account_id === '' || $list_id === '') {
			return array();
		}

		$response = $this->cloudflareRequest('GET', '/accounts/' . rawurlencode($account_id) . '/rules/lists/' . rawurlencode($list_id) . '/items?per_page=500', $token, null);

		if (empty($response['success']) || empty($response['result'])) {
			return array();
		}

		$out = array();

		foreach ($response['result'] as $row) {
			$redirect = isset($row['redirect']) ? $row['redirect'] : $row;
			$out[] = array(
				'source_url' => isset($redirect['source_url']) ? $redirect['source_url'] : '',
				'target_url' => isset($redirect['target_url']) ? $redirect['target_url'] : '',
				'http_code'  => isset($redirect['status_code']) ? (int)$redirect['status_code'] : 301,
				'backend'    => 'cloudflare'
			);
		}

		return $out;
	}

	private function cloudflareRequest($method, $path, $token, $body) {
		$url = 'https://api.cloudflare.com/client/v4' . $path;
		$headers = array(
			'Authorization: Bearer ' . $token,
			'Content-Type: application/json'
		);

		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_TIMEOUT, 60);

			if ($body !== null) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
			}

			$raw = curl_exec($ch);
			curl_close($ch);
		} else {
			$opts = array(
				'http' => array(
					'method'  => $method,
					'header'  => implode("\r\n", $headers),
					'timeout' => 60
				)
			);

			if ($body !== null) {
				$opts['http']['content'] = json_encode($body);
			}

			$raw = @file_get_contents($url, false, stream_context_create($opts));
		}

		$decoded = json_decode((string)$raw, true);

		return is_array($decoded) ? $decoded : array('success' => false, 'errors' => array(array('message' => 'invalid_response')));
	}

	private function cloudflareError($response) {
		if (!empty($response['errors'][0]['message'])) {
			return $response['errors'][0]['message'];
		}

		return 'cloudflare_error';
	}

	private function writeGenerated($files) {
		$last_path = '';
		$hashes = array();

		foreach ($files as $path => $content) {
			if (is_array($content) && isset($content['mode']) && $content['mode'] === 'htaccess') {
				$written = $this->writeHtaccessBlock($path, $content['block']);
			} else {
				$written = $this->atomicWrite($path, $content);
			}

			if (!$written['ok']) {
				return $written;
			}

			$last_path = $path;
			$hashes[] = $written['hash'];
		}

		return array(
			'ok'     => true,
			'errors' => array(),
			'path'   => $last_path,
			'hash'   => hash('sha256', implode('', $hashes))
		);
	}

	public function atomicWrite($path, $content) {
		$path = $this->sanitizePath($path);

		if ($path === '' || !$this->pathAllowed($path)) {
			return array('ok' => false, 'errors' => array(array('error' => 'path_not_allowed', 'path' => $path)), 'hash' => '');
		}

		$dir = dirname($path);

		if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
			return array('ok' => false, 'errors' => array(array('error' => 'mkdir_failed', 'path' => $dir)), 'hash' => '');
		}

		$tmp = $path . '.tmp';
		$written = @file_put_contents($tmp, $content, LOCK_EX);

		if ($written === false) {
			return array('ok' => false, 'errors' => array(array('error' => 'write_failed', 'path' => $tmp)), 'hash' => '');
		}

		if (is_file($path)) {
			@copy($path, $path . '.bak');
		}

		if (!@rename($tmp, $path)) {
			@unlink($path);

			if (!@rename($tmp, $path)) {
				@unlink($tmp);

				return array('ok' => false, 'errors' => array(array('error' => 'rename_failed', 'path' => $path)), 'hash' => '');
			}
		}

		return array('ok' => true, 'errors' => array(), 'hash' => hash('sha256', $content), 'path' => $path);
	}

	public function writeHtaccessBlock($path, $block) {
		$path = $this->sanitizePath($path);

		if (!$this->pathAllowed($path, true)) {
			return array('ok' => false, 'errors' => array(array('error' => 'path_not_allowed', 'path' => $path)), 'hash' => '');
		}

		$existing = is_file($path) ? (string)file_get_contents($path) : '';
		$begin = Engine::BEGIN_MARKER;
		$end = Engine::END_MARKER;

		if (strpos($existing, $begin) !== false && strpos($existing, $end) !== false) {
			$updated = preg_replace(
				'/' . preg_quote($begin, '/') . '.*?' . preg_quote($end, '/') . '\s*/s',
				rtrim($block) . "\n",
				$existing,
				1
			);
		} else {
			$updated = rtrim($existing) . "\n\n" . rtrim($block) . "\n";
		}

		return $this->atomicWrite($path, $updated);
	}

	public function readHtaccessBlock($path = '') {
		if ($path === '') {
			$path = $this->engine->documentRoot() . '/.htaccess';
		}

		if (!is_file($path)) {
			return array();
		}

		$content = (string)file_get_contents($path);
		$begin = Engine::BEGIN_MARKER;
		$end = Engine::END_MARKER;

		if (!preg_match('/' . preg_quote($begin, '/') . '(.*?)' . preg_quote($end, '/') . '/s', $content, $m)) {
			return array();
		}

		$rules = array();

		foreach (preg_split("/\\r\\n|\\n|\\r/", trim($m[1])) as $line) {
			$line = trim($line);

			if ($line === '' || $line[0] === '#') {
				continue;
			}

			if (preg_match('/^Redirect\s+gone\s+(\S+)/i', $line, $mm)) {
				$rules[] = array('source_url' => $mm[1], 'target_url' => '', 'http_code' => 410, 'action' => 'status', 'backend' => 'apache');
			} elseif (preg_match('/^Redirect\s+(\d+)\s+(\S+)\s+(\S+)/i', $line, $mm)) {
				$rules[] = array('source_url' => $mm[2], 'target_url' => $mm[3], 'http_code' => (int)$mm[1], 'action' => 'redirect', 'backend' => 'apache');
			} elseif (preg_match('/^RedirectMatch\s+(\d+)\s+\^(.+?)\/\?\$/i', $line, $mm)) {
				$source = stripslashes($mm[2]);
				$rules[] = array('source_url' => $source, 'target_url' => '', 'http_code' => (int)$mm[1], 'action' => 'status', 'backend' => 'apache');
			}
		}

		return $rules;
	}

	private function readApache($settings) {
		$mode = isset($settings['apache_mode']) ? $settings['apache_mode'] : $this->config->get('module_redirect_manager_apache_mode');

		if ($mode === 'htaccess') {
			$path = isset($settings['apache_file']) ? $settings['apache_file'] : $this->engine->documentRoot() . '/.htaccess';

			return $this->readHtaccessBlock($path);
		}

		$map = $this->engine->storageRoot() . '/apache/redirects.map';
		$status = $this->engine->storageRoot() . '/apache/status.map';
		$out = array();

		if (is_file($map)) {
			foreach (file($map, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
				if (preg_match('/^(\S+)\s+(\d+)\|(\S+)/', $line, $m)) {
					$out[] = array('source_url' => $m[1], 'target_url' => $m[3], 'http_code' => (int)$m[2], 'action' => 'redirect', 'backend' => 'apache');
				}
			}
		}

		if (is_file($status)) {
			foreach (file($status, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
				if (preg_match('/^(\S+)\s+(\d+)/', $line, $m)) {
					$out[] = array('source_url' => $m[1], 'target_url' => '', 'http_code' => (int)$m[2], 'action' => 'status', 'backend' => 'apache');
				}
			}
		}

		return $out;
	}

	private function readNginx($settings) {
		$path = $this->nginxIncludePath($settings);

		if (!is_file($path)) {
			return array();
		}

		$content = (string)file_get_contents($path);
		$targets = array();
		$codes = array();

		if (preg_match('/map \$uri \$rm_target \{(.*?)\}/s', $content, $m)) {
			foreach (preg_split("/\\r\\n|\\n|\\r/", $m[1]) as $line) {
				$line = trim($line);

				if (preg_match('/^(\S+)\s+(\S+);$/', $line, $mm) && $mm[1] !== 'default') {
					$targets[trim($mm[1], '"')] = trim($mm[2], '"');
				}
			}
		}

		if (preg_match('/map \$uri \$rm_code \{(.*?)\}/s', $content, $m)) {
			foreach (preg_split("/\\r\\n|\\n|\\r/", $m[1]) as $line) {
				$line = trim($line);

				if (preg_match('/^(\S+)\s+(\d+);$/', $line, $mm) && $mm[1] !== 'default') {
					$codes[trim($mm[1], '"')] = (int)$mm[2];
				}
			}
		}

		$out = array();
		$keys = array_unique(array_merge(array_keys($targets), array_keys($codes)));

		foreach ($keys as $source) {
			$code = isset($codes[$source]) ? $codes[$source] : 301;
			$target = isset($targets[$source]) ? $targets[$source] : '';
			$out[] = array(
				'source_url' => $source,
				'target_url' => $target,
				'http_code'  => $code,
				'action'     => $target === '' ? 'status' : 'redirect',
				'backend'    => 'nginx'
			);
		}

		return $out;
	}

	private function readCloudflare($settings) {
		return $this->cloudflareListItems($settings);
	}

	private function readPhp($settings) {
		$path = $this->engine->storageRoot() . '/php/map.php';

		if (!is_file($path)) {
			return array();
		}

		$map = include $path;
		$out = array();

		if (!is_array($map)) {
			return $out;
		}

		foreach ($map as $source => $rule) {
			$out[] = array(
				'source_url' => $source,
				'target_url' => isset($rule['target']) ? $rule['target'] : '',
				'http_code'  => isset($rule['code']) ? (int)$rule['code'] : 301,
				'action'     => isset($rule['action']) ? $rule['action'] : 'redirect',
				'backend'    => 'php'
			);
		}

		return $out;
	}

	public function pathAllowed($path, $allow_htaccess = false) {
		$path = $this->sanitizePath($path);

		if ($path === '' || strpos($path, "\0") !== false) {
			return false;
		}

		$real_parent = realpath(dirname($path));
		$base = $this->sanitizePath($this->engine->storageRoot());

		if ($real_parent) {
			$candidate = $this->sanitizePath($real_parent . '/' . basename($path));
		} else {
			$candidate = $path;
		}

		if ($this->isInside($candidate, $base) || $this->isInside($path, $base)) {
			return true;
		}

		if ($allow_htaccess) {
			$htaccess = $this->sanitizePath($this->engine->documentRoot() . '/.htaccess');

			if ($path === $htaccess) {
				return true;
			}
		}

		$extra = (string)$this->config->get('module_redirect_manager_allowed_paths');

		if ($extra !== '') {
			foreach (preg_split("/\\r\\n|\\n|\\r/", $extra) as $prefix) {
				$prefix = $this->sanitizePath(trim($prefix));

				if ($prefix !== '' && $this->isInside($path, $prefix)) {
					return true;
				}
			}
		}

		return false;
	}

	private function isInside($path, $root) {
		$path = $this->sanitizePath($path);
		$root = $this->sanitizePath($root);

		if ($root === '' || $path === '') {
			return false;
		}

		return ($path === $root || strpos($path, $root . '/') === 0);
	}

	private function sanitizePath($path) {
		$path = str_replace('\\', '/', (string)$path);
		$path = str_replace(array("\0", '..'), '', $path);

		return rtrim($path, '/');
	}

	private function reloadNginx() {
		$test = @shell_exec('nginx -t 2>&1');

		if ($test === null || stripos($test, 'successful') === false && stripos($test, 'ok') === false) {
			return array('ok' => false, 'output' => (string)$test);
		}

		$reload = @shell_exec('systemctl reload nginx 2>&1');

		return array('ok' => true, 'output' => trim((string)$test . "\n" . $reload));
	}

	private function enabled($rules) {
		$out = array();

		foreach ($rules as $rule) {
			if (!empty($rule['enabled'])) {
				$out[] = $rule;
			}
		}

		return $out;
	}

	private function pathOnly($url) {
		$normalized = $this->engine->normalize($url);

		return $normalized['path'] !== '' ? $normalized['path'] : '/';
	}

	private function apachePath($path) {
		return $path;
	}

	private function apacheTarget($rule) {
		$target = $rule['target_url'];
		$normalized = $this->engine->normalize($target);

		if ($normalized['is_full']) {
			return $normalized['storage'];
		}

		return $normalized['path'];
	}

	private function nginxPath($path) {
		return json_encode($path, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	private function nginxTarget($rule) {
		$normalized = $this->engine->normalize($rule['target_url']);
		$target = $normalized['is_full'] ? $normalized['storage'] : $normalized['path'];

		if ($rule['query_mode'] === 'preserve') {
			$target .= '$is_args$args';
		}

		return json_encode($target, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	private function nginxIncludePath($settings) {
		if (!empty($settings['nginx_file'])) {
			return $settings['nginx_file'];
		}

		$configured = (string)$this->config->get('module_redirect_manager_nginx_file');

		if ($configured !== '') {
			return $configured;
		}

		return $this->engine->storageRoot() . '/nginx/redirects.conf';
	}

	private function normalizeType($type) {
		$type = strtolower((string)$type);

		if (!in_array($type, array('apache', 'nginx', 'cloudflare', 'php'), true)) {
			return 'apache';
		}

		return $type;
	}
}
