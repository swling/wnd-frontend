<?php
namespace Wnd\Model;

/**
 *@since 2020.04.11
 *插件配置
 */
class Wnd_Config {

	/**
	 *WP option name
	 */
	protected static $wp_option_name = 'wnd';

	/**
	 *option数组键名统一前缀
	 */
	protected static $config_key_prefix = 'wnd_';

	/**
	 *获取指定option数组中的指定键值
	 */
	public static function get($config_key) {
		if (0 !== stripos($config_key, self::$config_key_prefix)) {
			$config_key = self::$config_key_prefix . $config_key;
		}

		$config       = get_option(self::$wp_option_name, []);
		$config_value = $config[$config_key] ?? false;

		return apply_filters($config_key, $config_value);
	}
}
