<?php
namespace Wnd\Controller;

/**
 *@since 2019.10.02
 *Ajax控制基类
 */
abstract class Wnd_Controller_Ajax {

	/**
	 *获取全局变量并选择model执行
	 */
	abstract public static function execute(): array;
}
