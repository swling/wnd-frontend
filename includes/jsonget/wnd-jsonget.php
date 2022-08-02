<?php
namespace Wnd\JsonGet;

/**
 * Json输出基类
 * @since 2020.04.24
 */
abstract class Wnd_JsonGet {

	/**
	 * 获取Json Data
	 *
	 * @param  $args  	传参数组，对象，或http请求字符
	 * @param  $force 是否强制传参，忽略                    GET 请求参数
	 * @return array  数据
	 */
	final public static function get($args = '', $force = false): array{
		/**
		 * 默认 $_GET 参数优先，若设置 $force = true 则忽略 $_GET
		 */
		$args = $force ? wp_parse_args($args) : wp_parse_args($_GET, $args);

		static::check();

		return static::query($args);
	}

	protected static function check() {}

	/**
	 * 查询数据
	 * 此处不添加 $args 参数，子类可自行添加带默认值的传参如 $args = [] 即可接收传参
	 * @since 0.8.74
	 */
	abstract protected static function query(): array;
}
