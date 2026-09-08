<?php

namespace Custom;

class Setting
{
	public static function get($config, $key, $default = '')
	{
		return self::value($config, ['config_' . $key, 'theme_default_' . $key], $default);
	}

	public static function theme($config, $key, $default = '')
	{
		return self::value($config, ['theme_default_' . $key, 'config_' . $key], $default);
	}

	private static function value($config, $names, $default)
	{
		foreach ($names as $name) {
			if ($config->has($name)) {
				return $config->get($name);
			}
		}

		return $default;
	}
}
