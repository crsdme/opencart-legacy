<?php

namespace Sms\Gateway;

class Log implements \Sms\Gateway
{
	private $log;

	public function __construct($registry)
	{
		$this->log = $registry->get('log');
	}

	public function send($phone, $message)
	{
		$this->log->write('SMS to ' . $phone . ': ' . $message);

		return true;
	}
}
