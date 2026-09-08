<?php

namespace Custom;

class Otp
{
	const SESSION_KEY = 'account_otp';
	const TTL = 300;
	const RESEND = 60;
	const MAX_SEND_PHONE = 5;
	const MAX_SEND_IP = 10;
	const MAX_VERIFY = 5;
	const HOUR = 3600;
	const CODE_LENGTH = 4;

	private $session;
	private $request;
	private $config;

	public function __construct($registry)
	{
		$this->session = $registry->get('session');
		$this->request = $registry->get('request');
		$this->config = $registry->get('config');
	}

	public function issue($phone)
	{
		$wait = $this->retryAfter($phone);

		if ($wait > 0) {
			return [
				'error' => 'wait',
				'retry_after' => $wait,
			];
		}

		if ($this->sendCount('phone', $phone) >= self::MAX_SEND_PHONE) {
			return ['error' => 'limit'];
		}

		if ($this->sendCount('ip', $this->ip()) >= self::MAX_SEND_IP) {
			return ['error' => 'limit'];
		}

		$code = $this->code();
		$now = time();

		$this->session->data[self::SESSION_KEY] = [
			'phone' => $phone,
			'hash' => hash_hmac('sha256', $code, $this->secret()),
			'expires' => $now + self::TTL,
			'attempts' => 0,
			'sent_at' => $now,
		];

		return [
			'code' => $code,
			'retry_after' => self::RESEND,
		];
	}

	public function recordSend($phone)
	{
		$this->trackSend($phone);
	}

	public function verify($phone, $code)
	{
		$data = $this->current();

		if (!$data) {
			return 'expired';
		}

		if ($data['phone'] !== $phone) {
			return 'expired';
		}

		if ((int) $data['expires'] < time()) {
			$this->clear();

			return 'expired';
		}

		if ((int) $data['attempts'] >= self::MAX_VERIFY) {
			$this->clear();

			return 'otp_attempts';
		}

		$this->session->data[self::SESSION_KEY]['attempts'] = (int) $data['attempts'] + 1;

		$expected = hash_hmac('sha256', preg_replace('/\D+/', '', (string) $code), $this->secret());

		if (!hash_equals($data['hash'], $expected)) {
			return 'code';
		}

		$this->clear();

		return '';
	}

	public function retryAfter($phone)
	{
		$data = $this->current();

		if (!$data || $data['phone'] !== $phone) {
			return 0;
		}

		$left = (int) $data['sent_at'] + self::RESEND - time();

		return $left > 0 ? $left : 0;
	}

	public function clear()
	{
		unset($this->session->data[self::SESSION_KEY]);
	}

	private function current()
	{
		return isset($this->session->data[self::SESSION_KEY]) && is_array($this->session->data[self::SESSION_KEY])
			? $this->session->data[self::SESSION_KEY]
			: [];
	}

	private function code()
	{
		$max = (int) str_repeat('9', self::CODE_LENGTH);

		return str_pad((string) random_int(0, $max), self::CODE_LENGTH, '0', STR_PAD_LEFT);
	}

	private function secret()
	{
		$secret = (string) $this->config->get('config_encryption');

		return $secret !== '' ? $secret : 'otp';
	}

	private function ip()
	{
		return isset($this->request->server['REMOTE_ADDR']) ? (string) $this->request->server['REMOTE_ADDR'] : '';
	}

	private function trackSend($phone)
	{
		$now = time();
		$sends = $this->sends();
		$sends[] = [
			'phone' => $phone,
			'ip' => $this->ip(),
			'time' => $now,
		];

		$this->session->data[self::SESSION_KEY . '_sends'] = $sends;
	}

	private function sendCount($field, $value)
	{
		$count = 0;
		$since = time() - self::HOUR;

		foreach ($this->sends() as $row) {
			if ((int) $row['time'] >= $since && isset($row[$field]) && $row[$field] === $value) {
				$count++;
			}
		}

		return $count;
	}

	private function sends()
	{
		$key = self::SESSION_KEY . '_sends';
		$rows = isset($this->session->data[$key]) && is_array($this->session->data[$key])
			? $this->session->data[$key]
			: [];
		$since = time() - self::HOUR;
		$kept = [];

		foreach ($rows as $row) {
			if (isset($row['time']) && (int) $row['time'] >= $since) {
				$kept[] = $row;
			}
		}

		$this->session->data[$key] = $kept;

		return $kept;
	}
}
