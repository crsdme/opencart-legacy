<?php

/**
 * SMS facade. Controllers call $this->sms->send($phone, $message).
 *
 * To switch provider: add system/library/sms/gateway/{name}.php implementing Sms\Gateway,
 * then pick it in theme settings. HTTP gateway covers JSON APIs without new PHP.
 */
class Sms
{
	private $registry;
	private $gateway;

	public function __construct($registry)
	{
		$this->registry = $registry;
		$this->gateway = $this->makeGateway();
	}

	public function send($phone, $message)
	{
		$phone = preg_replace('/\D+/', '', (string) $phone);
		$message = trim((string) $message);

		if ($phone === '' || $message === '') {
			return false;
		}

		return (bool) $this->gateway->send($phone, $message);
	}

	private function makeGateway()
	{
		$config = $this->registry->get('config');
		$code = preg_replace('/[^a-z0-9_]/', '', strtolower((string) \Custom\Setting::get($config, 'sms_gateway', 'log')));

		if ($code === '') {
			$code = 'log';
		}

		$class = 'Sms\\Gateway\\' . str_replace('_', '', ucwords($code, '_'));

		if (!class_exists($class)) {
			$class = 'Sms\\Gateway\\Log';
		}

		return new $class($this->registry);
	}
}
