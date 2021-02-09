<?php
namespace Wnd\Module;

/**
 *表单模块公共特性：表单模块同时支持结构数据输出前端渲染或直接PHP渲染
 *@since 0.9.2
 */
abstract class Wnd_Module_Form extends Wnd_Module {

	protected $type = 'form';

	// HTML 输出
	protected static function build($args = []): string {
		return static::configure_form($args)->build();
	}

	// 结构输出 JavaScript 渲染
	protected function structure(): array{
		return static::configure_form($this->args)->get_structure();
	}

	// 配置表单：返回对象为表单类实例
	abstract protected static function configure_form(): object;
}
