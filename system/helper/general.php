<?php
function token($length = 32) {
	// Create random token
	$string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	
	$max = strlen($string) - 1;
	
	$token = '';
	
	for ($i = 0; $i < $length; $i++) {
		$token .= $string[mt_rand(0, $max)];
	}	
	
	return $token;
}

/**
 * Backwards support for timing safe hash string comparisons
 * 
 * http://php.net/manual/en/function.hash-equals.php
 */

if(!function_exists('hash_equals')) {
	function hash_equals($known_string, $user_string) {
		$known_string = (string)$known_string;
		$user_string = (string)$user_string;

		if(strlen($known_string) != strlen($user_string)) {
			return false;
		} else {
			$res = $known_string ^ $user_string;
			$ret = 0;

			for($i = strlen($res) - 1; $i >= 0; $i--) $ret |= ord($res[$i]);

			return !$ret;
		}
	}
}

if (!function_exists('dev_dump')) {
	function dev_dump(...$values) {
		if (!$values) {
			return;
		}

		echo '<pre>';
		call_user_func_array('var_dump', $values);
		echo '</pre>';
	}
}

if (!function_exists('timezone_identifier')) {
	function timezone_identifier($timezone) {
		$timezone = (string) $timezone;
		$list = timezone_identifiers_list();

		if ($timezone !== '' && in_array($timezone, $list, true)) {
			return $timezone;
		}

		$aliases = array(
			'Europe/Kyiv' => 'Europe/Kiev',
			'Europe/Kiev' => 'Europe/Kyiv',
		);

		if (isset($aliases[$timezone]) && in_array($aliases[$timezone], $list, true)) {
			return $aliases[$timezone];
		}

		return 'UTC';
	}
}