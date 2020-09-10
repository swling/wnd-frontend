<?php
namespace Wnd\Action;

use Wnd\Utility\Wnd_Form_Data;

/**
 *@since 2019.10.02
 *Ajax操作基类
 */
abstract class Wnd_Action_Ajax {

	/**
	 *获取全局变量并选择model执行
	 */
	abstract public static function execute(): array;

	/**
	 *@since 0.8.64
	 *
	 *所有表单提交均通过 Wnd_Form_Data 统一处理
	 */
	protected static function get_form_data(): array{
		return Wnd_Form_Data::get_form_data(true);
	}
}
