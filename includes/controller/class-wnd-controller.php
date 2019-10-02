<?php
namespace Wnd\Controller;

/**
 *@since 2019.10.02
 *控制基类
 */
abstract class Wnd_Controller {

	/**
	 *获取全局变量并选择model执行
	 */
	abstract public static function execute();
}
