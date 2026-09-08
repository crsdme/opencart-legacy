<?php

namespace Sms\Gateway;

class Http implements \Sms\Gateway
{
	private $config;
	private $log;

	public function __construct($registry)
	{
		$this->config = $registry->get('config');
		$this->log = $registry->get('log');
	}

	public function send($phone, $message)
	{
		$url = trim((string) \Custom\Setting::get($this->config, 'sms_http_url'));

		if ($url === '') {
			$this->log->write('SMS HTTP: URL is empty');

			return false;
		}

		$token = (string) \Custom\Setting::get($this->config, 'sms_http_token');
		$sender = (string) \Custom\Setting::get($this->config, 'sms_sender');
		$replaces = [
			'{phone}' => $phone,
			'{message}' => $message,
			'{sender}' => $sender,
			'{token}' => $token,
		];

		$url = strtr($url, $replaces);
		$body = trim((string) \Custom\Setting::get($this->config, 'sms_http_body'));

		if ($body === '') {
			$body = json_encode([
				'phone' => $phone,
				'message' => $message,
				'sender' => $sender,
			]);
		} else {
			$body = strtr($body, $replaces);
		}

		$headers = ['Content-Type: application/json', 'Accept: application/json'];

		if ($token !== '') {
			$headers[] = 'Authorization: Bearer ' . $token;
		}

		$result = $this->post($url, $body, $headers);

		if ($result === false) {
			return false;
		}

		$code = (int) $result['code'];

		if ($code < 200 || $code >= 300) {
			$this->log->write('SMS HTTP ' . $code . ': ' . $result['body']);

			return false;
		}

		return true;
	}

	private function post($url, $body, array $headers)
	{
		if (function_exists('curl_init')) {
			$ch = curl_init($url);

			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 15);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

			$response = curl_exec($ch);
			$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$error = curl_error($ch);

			curl_close($ch);

			if ($response === false) {
				$this->log->write('SMS HTTP curl: ' . $error);

				return false;
			}

			return [
				'code' => $code,
				'body' => $response,
			];
		}

		$context = stream_context_create([
			'http' => [
				'method' => 'POST',
				'header' => implode("\r\n", $headers),
				'content' => $body,
				'timeout' => 15,
				'ignore_errors' => true,
			],
		]);

		$response = @file_get_contents($url, false, $context);

		if ($response === false) {
			$this->log->write('SMS HTTP: request failed');

			return false;
		}

		$code = 0;

		if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
			$code = (int) $match[1];
		}

		return [
			'code' => $code,
			'body' => $response,
		];
	}
}
