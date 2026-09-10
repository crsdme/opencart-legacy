<?php

namespace import_export;

class RemoteImage
{
	const MAX_BYTES = 16777216;
	const MAX_REDIRECTS = 5;
	const MAX_ATTEMPTS = 3;

	private $ctx;
	private $cache = [];
	private $hostCache = [];
	private $curl;

	public function __construct(Context $ctx)
	{
		$this->ctx = $ctx;
	}

	public function __destruct()
	{
		if (is_resource($this->curl)) {
			curl_close($this->curl);
			$this->curl = null;
		}
	}

	public function fetch($url)
	{
		$candidates = $this->candidates($url);

		if (!$candidates) {
			return false;
		}

		$key = sha1($candidates[0]);

		if (array_key_exists($key, $this->cache)) {
			return $this->cache[$key];
		}

		$existing = $this->existing($key);

		if ($existing) {
			return $this->cache[$key] = $existing;
		}

		if (!$this->ctx->downloadImages()) {
			return $this->cache[$key] = $this->fail('download disabled', $candidates[0]);
		}

		foreach ($candidates as $candidate) {
			$path = $this->download($candidate, $key);

			if ($path) {
				return $this->cache[$key] = $path;
			}
		}

		return $this->cache[$key] = false;
	}

	private function candidates($url)
	{
		$plain = $this->normalize($url, false);

		if ($plain === '') {
			return [];
		}

		$origin = $this->unwrap($plain);
		$out = [];

		if ($origin !== '') {
			$out[] = $origin;
		}

		if (!in_array($plain, $out, true)) {
			$out[] = $plain;
		}

		return $out;
	}

	private function download($url, $key)
	{
		$current = $url;

		for ($redirect = 0; $redirect < self::MAX_REDIRECTS; $redirect++) {
			if (!$this->assertPublicUrl($current)) {
				return $this->fail('blocked host', $current);
			}

			$result = $this->requestWithRetry($current);

			if (!$result) {
				return $this->fail('request failed', $current);
			}

			if (!empty($result['error']) && (int) $result['code'] !== 200) {
				$reason = $result['error'] === 'too large' ? 'too large' : ('curl ' . $result['error']);

				return $this->fail($reason, $current);
			}

			$code = (int) $result['code'];

			if ($code >= 300 && $code < 400 && $result['location'] !== '') {
				$current = $this->absoluteUrl($current, $result['location']);
				continue;
			}

			if ($code !== 200 || $result['body'] === '') {
				return $this->fail('http ' . $code, $current);
			}

			$ext = $this->extension($result['body']);

			if ($ext === '') {
				return $this->fail('not an image', $current);
			}

			$dir = rtrim(DIR_IMAGE, '/\\') . '/catalog/import';

			if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
				return $this->fail('cannot write directory', $url);
			}

			$file = $dir . '/' . $key . '.' . $ext;

			if (@file_put_contents($file, $result['body'], LOCK_EX) === false) {
				return $this->fail('cannot write file', $url);
			}

			return 'catalog/import/' . $key . '.' . $ext;
		}

		return $this->fail('too many redirects', $url);
	}

	private function fail($reason, $url)
	{
		$log = $this->ctx->registry()->get('log');

		if ($log) {
			$log->write('import_export image skipped (' . $reason . '): ' . $url);
		}

		return false;
	}

	private function existing($key)
	{
		foreach (['jpg', 'png', 'gif', 'webp'] as $ext) {
			$relative = 'catalog/import/' . $key . '.' . $ext;

			if (is_file(rtrim(DIR_IMAGE, '/\\') . '/' . $relative)) {
				return $relative;
			}
		}

		return '';
	}

	private function unwrap($url)
	{
		$path = parse_url($url, PHP_URL_PATH);

		if (!$path || stripos($path, '/cdn-cgi/image/') === false) {
			return '';
		}

		if (!preg_match('#/cdn-cgi/image/[^/]+/(.+)$#i', $path, $match)) {
			return '';
		}

		$inner = rawurldecode($match[1]);
		$inner = html_entity_decode(trim($inner), ENT_QUOTES, 'UTF-8');

		if (strpos($inner, '//') === 0) {
			$inner = 'https:' . $inner;
		}

		return $this->normalize($inner, false);
	}

	private function normalize($url, $unwrap = true)
	{
		$url = html_entity_decode(trim((string) $url), ENT_QUOTES, 'UTF-8');

		if (strpos($url, '//') === 0) {
			$url = 'https:' . $url;
		}

		if ($unwrap) {
			$origin = $this->unwrap($url);

			if ($origin !== '') {
				$url = $origin;
			}
		}

		$parts = parse_url($url);

		if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
			return '';
		}

		unset($parts['fragment']);

		return $this->buildUrl($parts);
	}

	private function assertPublicUrl($url)
	{
		$parts = parse_url($url);

		if (!$parts) {
			return false;
		}

		$scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';

		if ($scheme !== 'http' && $scheme !== 'https') {
			return false;
		}

		if (!empty($parts['user']) || !empty($parts['pass'])) {
			return false;
		}

		$host = isset($parts['host']) ? strtolower(trim($parts['host'], '[]')) : '';

		if ($host === '' || $host === 'localhost' || substr($host, -6) === '.local' || substr($host, -4) === '.internal') {
			return false;
		}

		$port = isset($parts['port']) ? (int) $parts['port'] : 0;

		if ($port && $port !== 80 && $port !== 443) {
			return false;
		}

		$ips = $this->hostIps($host);

		if (!$ips) {
			return false;
		}

		foreach ($ips as $ip) {
			if ($this->isPublicIp($ip)) {
				return true;
			}
		}

		return false;
	}

	private function hostIps($host)
	{
		$host = strtolower(trim((string) $host, '[]'));

		if (isset($this->hostCache[$host])) {
			return $this->hostCache[$host];
		}

		if (filter_var($host, FILTER_VALIDATE_IP)) {
			return $this->hostCache[$host] = [$host];
		}

		$ips = [];
		$type = defined('DNS_A') ? DNS_A : 1;

		if (defined('DNS_AAAA')) {
			$type = $type | DNS_AAAA;
		}

		$records = @dns_get_record($host, $type);

		if (is_array($records)) {
			foreach ($records as $row) {
				if (!empty($row['ip'])) {
					$ips[] = $row['ip'];
				}

				if (!empty($row['ipv6'])) {
					$ips[] = $row['ipv6'];
				}
			}
		}

		if (!$ips) {
			$fallback = @gethostbynamel($host);
			$ips = $fallback ? $fallback : [];
		}

		return $this->hostCache[$host] = array_values(array_unique($ips));
	}

	private function publicIpv4($url)
	{
		$host = parse_url($url, PHP_URL_HOST);

		if (!$host) {
			return '';
		}

		$host = strtolower(trim($host, '[]'));

		if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $this->isPublicIp($host)) {
			return $host;
		}

		foreach ($this->hostIps($host) as $ip) {
			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $this->isPublicIp($ip)) {
				return $ip;
			}
		}

		return '';
	}

	private function origin($url)
	{
		$parts = parse_url($url);

		if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
			return '';
		}

		$port = !empty($parts['port']) ? ':' . $parts['port'] : '';

		return strtolower($parts['scheme']) . '://' . $parts['host'] . $port . '/';
	}

	private function isPublicIp($ip)
	{
		$ip = (string) $ip;

		if ($ip === '::1' || $ip === '0.0.0.0' || strpos($ip, '127.') === 0) {
			return false;
		}

		$long = ip2long($ip);

		if ($long !== false && ($long & 0xFFC00000) === 0x64400000) {
			return false;
		}

		$flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

		return (bool) filter_var($ip, FILTER_VALIDATE_IP, $flags);
	}

	private function requestWithRetry($url)
	{
		$delay = 250000;

		for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
			$result = $this->request($url);

			if ($result && (int) $result['code'] === 200 && $result['body'] !== '') {
				return $result;
			}

			$code = $result ? (int) $result['code'] : 0;
			$retry = !$result || $code === 0 || $code === 429 || $code === 502 || $code === 503;

			if (!$retry || $attempt === self::MAX_ATTEMPTS) {
				return $result;
			}

			usleep($delay);
			$delay *= 2;
		}

		return null;
	}

	private function request($url)
	{
		if (function_exists('curl_init')) {
			return $this->requestCurl($url);
		}

		return $this->requestStream($url);
	}

	private function curlHandle()
	{
		if (is_resource($this->curl)) {
			curl_reset($this->curl);
		} else {
			$this->curl = curl_init();
		}

		return $this->curl;
	}

	private function requestCurl($url)
	{
		$handle = $this->curlHandle();

		if (!$handle) {
			return null;
		}

		curl_setopt($handle, CURLOPT_URL, $url);
		curl_setopt_array($handle, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_HEADER => true,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => 25,
			CURLOPT_PROTOCOLS => defined('CURLPROTO_HTTP') ? (CURLPROTO_HTTP | CURLPROTO_HTTPS) : 3,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_ENCODING => '',
			CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
			CURLOPT_HTTPHEADER => [
				'Accept: image/jpeg,image/png,image/gif,image/webp,*/*;q=0.8',
				'Accept-Language: en-US,en;q=0.9',
			],
			CURLOPT_REFERER => $this->origin($url),
		]);

		$pinned = $this->publicIpv4($url);

		if ($pinned) {
			$host = parse_url($url, PHP_URL_HOST);
			$port = (int) parse_url($url, PHP_URL_PORT);
			$scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

			if (!$port) {
				$port = $scheme === 'http' ? 80 : 443;
			}

			curl_setopt($handle, CURLOPT_RESOLVE, [$host . ':' . $port . ':' . $pinned]);
		}

		$raw = curl_exec($handle);
		$error = curl_error($handle);
		$code = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
		$header_size = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);

		if ($raw === false) {
			return [
				'code' => 0,
				'location' => '',
				'body' => '',
				'error' => $error,
			];
		}

		$headers = substr($raw, 0, $header_size);
		$body = substr($raw, $header_size);

		if (strlen($body) > self::MAX_BYTES) {
			return [
				'code' => 413,
				'location' => '',
				'body' => '',
				'error' => 'too large',
			];
		}

		$location = '';

		if (preg_match('/^Location:\s*(.+)$/im', $headers, $match)) {
			$location = trim($match[1]);
		}

		return [
			'code' => $code,
			'location' => $location,
			'body' => $body,
		];
	}

	private function requestStream($url)
	{
		$context = stream_context_create([
			'http' => [
				'method' => 'GET',
				'timeout' => 20,
				'follow_location' => 0,
				'ignore_errors' => true,
				'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\nAccept: image/jpeg,image/png,image/gif,image/webp,*/*;q=0.8\r\n",
			],
			'ssl' => [
				'verify_peer' => true,
				'verify_peer_name' => true,
			],
		]);

		$handle = @fopen($url, 'rb', false, $context);

		if (!$handle) {
			return null;
		}

		$meta = stream_get_meta_data($handle);
		$lines = isset($meta['wrapper_data']) && is_array($meta['wrapper_data']) ? $meta['wrapper_data'] : [];
		$code = 0;
		$location = '';

		foreach ($lines as $line) {
			if (!is_string($line)) {
				continue;
			}

			if (preg_match('#^HTTP/\S+\s+(\d+)#', $line, $match)) {
				$code = (int) $match[1];
			}

			if (stripos($line, 'Location:') === 0) {
				$location = trim(substr($line, 9));
			}
		}

		$body = '';

		while (!feof($handle)) {
			$chunk = fread($handle, 8192);

			if ($chunk === false) {
				break;
			}

			$body .= $chunk;

			if (strlen($body) > self::MAX_BYTES) {
				fclose($handle);

				return [
					'code' => 413,
					'location' => '',
					'body' => '',
					'error' => 'too large',
				];
			}
		}

		fclose($handle);

		return [
			'code' => $code,
			'location' => $location,
			'body' => $body,
		];
	}

	private function extension($body)
	{
		$info = @getimagesizefromstring($body);

		if (!$info || empty($info[2])) {
			return '';
		}

		$map = [
			IMAGETYPE_JPEG => 'jpg',
			IMAGETYPE_PNG => 'png',
			IMAGETYPE_GIF => 'gif',
		];

		if (defined('IMAGETYPE_WEBP')) {
			$map[IMAGETYPE_WEBP] = 'webp';
		}

		return isset($map[$info[2]]) ? $map[$info[2]] : '';
	}

	private function absoluteUrl($base, $location)
	{
		$location = trim($location);

		if ($location === '') {
			return '';
		}

		if (preg_match('#^https?://#i', $location)) {
			return $location;
		}

		$parts = parse_url($base);

		if (!$parts || empty($parts['host'])) {
			return '';
		}

		$scheme = isset($parts['scheme']) ? $parts['scheme'] : 'https';
		$host = $parts['host'];
		$port = !empty($parts['port']) ? ':' . $parts['port'] : '';

		if (strpos($location, '//') === 0) {
			return $scheme . ':' . $location;
		}

		if (isset($location[0]) && $location[0] === '/') {
			return $scheme . '://' . $host . $port . $location;
		}

		$path = isset($parts['path']) ? $parts['path'] : '/';
		$dir = preg_replace('#/[^/]*$#', '/', $path);

		return $scheme . '://' . $host . $port . $dir . $location;
	}

	private function buildUrl(array $parts)
	{
		$scheme = strtolower($parts['scheme']);
		$host = $parts['host'];
		$port = !empty($parts['port']) ? ':' . $parts['port'] : '';
		$path = isset($parts['path']) ? $parts['path'] : '/';
		$query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';

		return $scheme . '://' . $host . $port . $path . $query;
	}
}
