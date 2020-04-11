<?php
namespace Wnd\Model;

/**
 *@since 2020.04.11
 *插件配置
 */
class Wnd_Config {

	public static function get($option) {
		if (0 !== stripos($option, 'wnd_')) {
			$option = 'wnd_' . $option;
		}

		$config = get_option('wnd', []);
		$value  = $config[$option] ?? false;

		return apply_filters($option, $value);
	}
}
