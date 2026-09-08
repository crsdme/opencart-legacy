<?php

namespace Backup;

class Http
{
	public function request($method, $url, $body = null, $headers = [], $raw = false)
	{
		if (!function_exists('curl_init')) {
			throw new \RuntimeException('cURL is required for Google Drive.');
		}

		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, true);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
		curl_setopt($curl, CURLOPT_TIMEOUT, 300);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);

		if ($headers) {
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		}

		if ($body !== null) {
			curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
		}

		$response = curl_exec($curl);

		if ($response === false) {
			$error = curl_error($curl);
			curl_close($curl);
			throw new \RuntimeException('HTTP error: ' . $error);
		}

		$status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$header_size = (int) curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		curl_close($curl);

		$header_blob = substr($response, 0, $header_size);
		$body_out = substr($response, $header_size);
		$parsed = [];

		foreach (preg_split("/\r\n|\n|\r/", $header_blob) as $line) {
			$pos = strpos($line, ':');

			if ($pos === false) {
				continue;
			}

			$parsed[strtolower(trim(substr($line, 0, $pos)))] = trim(substr($line, $pos + 1));
		}

		if ($status >= 400 && !$raw) {
			throw new \RuntimeException('HTTP ' . $status . ': ' . $body_out);
		}

		return [
			'status' => $status,
			'headers' => $parsed,
			'body' => $body_out,
		];
	}

	public function json($method, $url, $payload = null, $headers = [])
	{
		$body = null;

		if ($payload !== null) {
			$headers[] = 'Content-Type: application/json; charset=UTF-8';
			$body = json_encode($payload);
		}

		$result = $this->request($method, $url, $body, $headers);
		$decoded = json_decode($result['body'], true);

		return is_array($decoded) ? $decoded : [];
	}

	public function form($url, array $fields)
	{
		$result = $this->request(
			'POST',
			$url,
			http_build_query($fields),
			['Content-Type: application/x-www-form-urlencoded']
		);
		$decoded = json_decode($result['body'], true);

		return is_array($decoded) ? $decoded : [];
	}
}
