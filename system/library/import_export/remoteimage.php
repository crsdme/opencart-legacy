<?php

namespace import_export;

class RemoteImage
{
	const MAX_BYTES = 8388608;
	const MAX_REDIRECTS = 5;

	private $ctx;
	private $cache = [];

	public function __construct(Context $ctx)
	{
		$this->ctx = $ctx;
	}

	public function fetch($url)
	{
		$url = $this->normalize($url);

		if ($url === '') {
			return false;
		}

		$key = sha1($url);

		if (array_key_exists($key, $this->cache)) {
			return $this->cache[$key];
		}

		$existing = $this->existing($key);

		if ($existing) {
			return $this->cache[$key] = $existing;
		}

		if (!$this->ctx->downloadImages()) {
			return $this->cache[$key] = false;
		}

		$current = $url;

		for ($i = 0; $i < self::MAX_REDIRECTS; $i++) {
			if (!$this->assertPublicUrl($current)) {
				return $this->cache[$key] = false;
			}

			$result = $this->request($current);

			if (!$result) {
				return $this->cache[$key] = false;
			}

			$code = (int) $result['code'];

			if ($code >= 300 && $code < 400 && $result['location'] !== '') {
				$current = $this->absoluteUrl($current, $result['location']);
				continue;
			}

			if ($code !== 200 || $result['body'] === '') {
				return $this->cache[$key] = false;
			}

			$ext = $this->extension($result['body']);

			if ($ext === '') {
				return $this->cache[$key] = false;
			}

			$dir = rtrim(DIR_IMAGE, '/\\') . '/catalog/import';

			if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
				return $this->cache[$key] = false;
			}

			$file = $dir . '/' . $key . '.' . $ext;

			if (@file_put_contents($file, $result['body'], LOCK_EX) === false) {
				return $this->cache[$key] = false;
			}

			return $this->cache[$key] = 'catalog/import/' . $key . '.' . $ext;
		}

		return $this->cache[$key] = false;
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

	private function normalize($url)
	{
		$url = html_entity_decode(trim((string) $url), ENT_QUOTES, 'UTF-8');

		if (strpos($url, '//') === 0) {
			$url = 'https:' . $url;
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
			if (!$this->isPublicIp($ip)) {
				return false;
			}
		}

		return true;
	}

	private function hostIps($host)
	{
		if (filter_var($host, FILTER_VALIDATE_IP)) {
			return [$host];
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

		return array_values(array_unique($ips));
	}

	private function isPublicIp($ip)
	{
		$ip = (string) $ip;

		if ($ip === '::1' || $ip === '0.0.0.0' || strpos($ip, '127.') === 0) {
			return false;
		}

		$flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

		return (bool) filter_var($ip, FILTER_VALIDATE_IP, $flags);
	}

	private function request($url)
	{
		if (function_exists('curl_init')) {
			return $this->requestCurl($url);
		}

		return $this->requestStream($url);
	}

	private function requestCurl($url)
	{
		$handle = curl_init($url);

		if (!$handle) {
			return null;
		}

		$body = '';
		$headers = '';

		curl_setopt_array($handle, [
			CURLOPT_RETURNTRANSFER => false,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_HEADER => false,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => 20,
			CURLOPT_PROTOCOLS => defined('CURLPROTO_HTTP') ? (CURLPROTO_HTTP | CURLPROTO_HTTPS) : 3,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_USERAGENT => 'OpenTail-ImportExport',
			CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$headers) {
				$headers .= $line;

				return strlen($line);
			},
			CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use (&$body) {
				$body .= $chunk;

				if (strlen($body) > self::MAX_BYTES) {
					return 0;
				}

				return strlen($chunk);
			},
		]);

		$ok = curl_exec($handle);
		$code = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
		curl_close($handle);

		if ($ok === false) {
			return null;
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
				'header' => "User-Agent: OpenTail-ImportExport\r\n",
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

				return null;
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
