<?php

namespace Custom;

class Phone
{
	public static function digits($value)
	{
		return preg_replace('/\D+/', '', (string) $value);
	}

	public static function prefix($config)
	{
		$prefix = preg_replace('/\D+/', '', (string) \Custom\Setting::get($config, 'phone_prefix', '380'));

		return $prefix !== '' ? $prefix : '380';
	}

	public static function normalize($value, $prefix = '380')
	{
		$digits = self::digits($value);

		if ($digits === '') {
			return '';
		}

		if (strpos($digits, $prefix) === 0) {
			return $digits;
		}

		if (isset($digits[0]) && $digits[0] === '0') {
			$digits = substr($digits, 1);
		}

		return $prefix . $digits;
	}

	public static function valid($value, $prefix = '380')
	{
		$phone = self::normalize($value, $prefix);

		return (bool) preg_match('/^' . preg_quote($prefix, '/') . '\d{9}$/', $phone);
	}
}
