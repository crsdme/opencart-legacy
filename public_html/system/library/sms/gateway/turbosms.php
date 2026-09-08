<?php

namespace Sms\Gateway;

class Turbosms implements \Sms\Gateway
{
	const URL = 'https://api.turbosms.ua/message/send.json';

	private $config;
	private $log;

	public function __construct($registry)
	{
		$this->config = $registry->get('config');
		$this->log = $registry->get('log');
	}

	public function send($phone, $message)
	{
		$token = trim((string) \Custom\Setting::get($this->config, 'sms_http_token'));
		$sender = trim((string) \Custom\Setting::get($this->config, 'sms_sender'));

		if ($token === '') {
			$this->log->write('SMS TurboSMS: token is empty');

			return false;
		}

		if ($sender === '') {
			$this->log->write('SMS TurboSMS: sender is empty');

			return false;
		}

		$body = json_encode([
			'recipients' => [$phone],
			'sms' => [
				'sender' => $sender,
				'text' => $message,
			],
		]);

		$result = $this->post($body, $token);

		if ($result === false) {
			return false;
		}

		$decoded = json_decode($result['body'], true);

		if (!is_array($decoded)) {
			$this->log->write('SMS TurboSMS: invalid JSON: ' . $result['body']);

			return false;
		}

		$code = isset($decoded['response_code']) ? (int) $decoded['response_code'] : -1;

		if (!$this->accepted($code) || !$this->recipientAccepted($decoded, $phone)) {
			$this->log->write(
				'SMS TurboSMS ' . $code . ' ' . (isset($decoded['response_status']) ? $decoded['response_status'] : '') . ': ' . $result['body'],
			);

			return false;
		}

		return true;
	}

	private function accepted($code)
	{
		return in_array($code, [0, 800, 801, 802, 803], true);
	}

	private function recipientAccepted(array $decoded, $phone)
	{
		if (!isset($decoded['response_result']) || !is_array($decoded['response_result']) || !$decoded['response_result']) {
			return true;
		}

		$matched = null;

		foreach ($decoded['response_result'] as $row) {
			if (!is_array($row)) {
				continue;
			}

			if (!isset($row['phone']) || (string) $row['phone'] === (string) $phone) {
				$matched = $row;
				break;
			}
		}

		if ($matched === null && count($decoded['response_result']) === 1) {
			$matched = $decoded['response_result'][0];
		}

		if (!is_array($matched)) {
			return false;
		}

		if (!empty($matched['message_id'])) {
			return true;
		}

		return isset($matched['response_code']) && (int) $matched['response_code'] === 0;
	}

	private function post($body, $token)
	{
		$headers = [
			'Content-Type: application/json',
			'Accept: application/json',
			'Authorization: Bearer ' . $token,
		];

		if (function_exists('curl_init')) {
			$ch = curl_init(self::URL);

			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

			$response = curl_exec($ch);
			$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$error = curl_error($ch);

			curl_close($ch);

			if ($response === false) {
				$this->log->write('SMS TurboSMS curl: ' . $error);

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
				'ignore_errors' => true,
			],
		]);

		$response = @file_get_contents(self::URL, false, $context);

		if ($response === false) {
			$this->log->write('SMS TurboSMS: request failed');

			return false;
		}

		return [
			'code' => 200,
			'body' => $response,
		];
	}
}
