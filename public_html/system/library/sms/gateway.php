<?php

namespace Sms;

interface Gateway
{
	/**
	 * @param string $phone   Digits only, international (e.g. 380991234567)
	 * @param string $message
	 *
	 * @return bool
	 */
	public function send($phone, $message);
}
