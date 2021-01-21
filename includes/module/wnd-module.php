<?php
namespace Wnd\Module;

/**
 *@since 2019.10.02
 *UI模块基类
 */
abstract class Wnd_Module {

	/**
	 *渲染
	 *
	 *@param $args 	传参数组，对象，或http请求字符
	 *@param $force 是否强制传参，忽略 GET 请求参数
	 *@return string HTML 字符串
	 */
	public static function render($args = '', $force = false) {
		/**
		 *默认 $_GET 参数优先，若设置 $force = true 则忽略 $_GET
		 */
		$args = $force ? wp_parse_args($args) : wp_parse_args($_GET, $args);

		// 权限检测
		static::check($args);

		// 生成 Html
		return static::build($args);
	}

	/**
	 *权限核查
	 */
	protected static function check($args) {
		return;
	}

	/**
	 *构建
	 * - 此处不添加 $args 参数，因为如果父类添加，则所有子类必须添加会导致大量无需传参的 Module 鼻血设置无需传参
	 * - 接受传参子类可自行添加带默认值的传参 $args = [] 即可
	 * - 如果设置传参，则必须设置参数的默认值，否则无法匹配本类方法一致性
	 */
	abstract protected static function build(): string;

	/**
	 *构建提示信息
	 */
	public static function build_message($message): string {
		if (!$message) {
			return '';
		}

		return wnd_message($message, 'is-primary', true);
	}

	/**
	 *构建错误提示信息
	 */
	public static function build_error_message($message): string {
		if (!$message) {
			return '';
		}

		return wnd_message($message, 'is-warning', true);
	}

	/**
	 *构建系统通知
	 */
	public static function build_notification($notification, $is_centered = false): string {
		if (!$notification) {
			return '';
		}

		$class = $is_centered ? 'is-primary has-text-centered' : 'is-primary';
		return wnd_notification($notification, $class, false);
	}

	/**
	 *构建系统错误通知
	 */
	public static function build_error_notification($notification, $is_centered = false): string {
		if (!$notification) {
			return '';
		}

		$class = $is_centered ? 'is-danger has-text-centered' : 'is-danger';
		return wnd_notification($notification, $class, false);
	}
}
