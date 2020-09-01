<?php
namespace Wnd\Utility;

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
	 *获取指定option数组中的指定键值
	 *
	 *此处引用静态变量应该使用 static::关键词，否则继承子类无法重写静态变量
	 */
	public static function get($config_key) {
		$config       = get_option(static::$wp_option_name, []);
		$config_value = $config[$config_key] ?? false;

		return apply_filters($config_key, $config_value);
	}
}