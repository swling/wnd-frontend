<?php
namespace Wnd\Query;

/**
 * Json输出基类
 * @since 2020.04.24
 */
abstract class Wnd_Query {

	/**
	 * 获取Json Data
	 *
	 * @param  $args  rest api 查询参数
	 * @return array  数据
	 */
	final public static function get(array $args = []): array {
		static::check($args);

		return static::query($args);
	}

	/**
	 * 权限检测
	 * 此处不添加 $args 参数，子类可自行添加带默认值的传参如 $args = [] 即可接收传参
	 * @since 0.8.74
	 */
	protected static function check() {}

	/**
	 * 查询数据
	 * 此处不添加 $args 参数，子类可自行添加带默认值的传参如 $args = [] 即可接收传参
	 * @since 0.8.74
	 */
	abstract protected static function query(): array;

}
