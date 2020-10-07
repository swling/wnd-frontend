<?php
namespace Wnd\JsonGet;

/**
 *@since 2020.04.24
 *Json输出基类
 */
abstract class Wnd_JsonGet {

	/**
	 *获取Json Data
	 *
	 *@param $args 	传参数组，对象，或http请求字符
	 *@param $force 是否强制传参，忽略 GET 请求参数
	 *@return array 数据
	 *
	 */
	public static function get($args = '', $force = false) {
		/**
		 *默认 $_GET 参数优先，若设置 $force = true 则忽略 $_GET
		 */
		$args = $force ? wp_parse_args($args) : wp_parse_args($_GET, $args);

		return static::query($args);
	}

	/**
	 *@since 0.8.74
	 *查询数据
	 */
	abstract protected static function query($args);
}
