<?php
namespace Wnd\Module;

/**
 * Module 其他非核心功能公共特性
 * - 为保持 Module 基类的简洁易读性，故此将一些非核心方法剥离至此
 * @since 0.9.29
 */
trait Wnd_Module_Trait {
	/**
	 * 构建提示信息
	 */
	public static function build_message(string $message, bool $is_centered = true): string {
		if (!$message) {
			return '';
		}

		return wnd_message($message, 'is-primary', $is_centered);
	}

	/**
	 * 构建错误提示信息
	 */
	public static function build_error_message(string $message, bool $is_centered = true): string {
		if (!$message) {
			return '';
		}

		return wnd_message($message, 'is-warning', $is_centered);
	}

	/**
	 * 构建系统通知
	 */
	public static function build_notification(string $notification, bool $is_centered = false): string {
		if (!$notification) {
			return '';
		}

		$class = $is_centered ? 'is-primary has-text-centered' : 'is-primary';
		return wnd_notification($notification, $class, false);
	}

	/**
	 * 构建系统错误通知
	 */
	public static function build_error_notification(string $notification, bool $is_centered = false): string {
		if (!$notification) {
			return '';
		}

		$class = $is_centered ? 'is-danger has-text-centered' : 'is-danger';
		return wnd_notification($notification, $class, false);
	}
}
