<?php
namespace Wnd\Utility;

/**
 *单例模式实例化部分公共代码
 *
 *@since 2020.04.25
 */
trait Wnd_Singleton_Trait {

	private static $instance;

	public static function instance() {
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
