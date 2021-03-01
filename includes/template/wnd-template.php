<?php
namespace Wnd\Template;

/**
 *模板类
 *@since 0.9.25
 */
abstract class Wnd_Template {

	public static function render(array $args = []) {
		static::check($args);
		static::build($args);
	}

	/**
	 *权限核查请复写本方法
	 */
	protected static function check($args) {
		return;
	}

	abstract protected static function build();
}
