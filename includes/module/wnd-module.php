<?php
namespace Wnd\Module;

/**
 *@since 2019.10.02
 *UI模块基类
 */
abstract class Wnd_Module {

	/**
	 *构建Html
	 */
	abstract public static function build();

	/**
	 *构建提示信息
	 */
	public static function build_message($message) {
		if (!$message) {
			return;
		}

		return wnd_message($message, 'is-primary', true);
	}

	/**
	 *构建错误提示信息
	 */
	public static function build_error_message($message) {
		if (!$message) {
			return;
		}

		return wnd_message($message, 'is-warning', true);
	}

	/**
	 *构建系统通知
	 */
	public static function build_notification($notification, $is_centered = false) {
		if (!$notification) {
			return;
		}

		$class = $is_centered ? 'is-primary has-text-centered' : 'is-primary';
		return wnd_notification($notification, $class, false);
	}

	/**
	 *构建系统错误通知
	 */
	public static function build_error_notification($notification, $is_centered = false) {
		if (!$notification) {
			return;
		}

		$class = $is_centered ? 'is-danger has-text-centered' : 'is-primary';
		return wnd_notification($notification, 'is-danger', false);
	}
}
