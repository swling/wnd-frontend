<?php
namespace Wnd\Module;

/**
 *@since 2019.10.02
 *模板基类
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
}
