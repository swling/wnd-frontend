<?php
namespace Wnd\Module;

/**
 * 表单模块公共特性：表单模块同时支持结构数据输出前端渲染或直接PHP渲染
 * 注意：
 * - 前端 JavaScript 渲染与后端 PHP 渲染可能存在差异
 * - 后端渲染仅支持标准 HTML 表单，不含各类依赖 JavaScript 的自定义字段如：富文本编辑器，标签编辑器等
 * @since 0.9.2
 */
abstract class Wnd_Module_Form extends Wnd_Module {

	protected $type = 'form';

	// HTML 输出
	protected static function build(array $args = []): string {
		return static::configure_form($args)->build();
	}

	// 结构输出 JavaScript 渲染
	protected function structure(): array{
		return static::configure_form($this->args)->get_structure();
	}

	/**
	 * 配置表单：返回对象为表单类实例
	 * - 此处不添加 $args 参数，因为如果父类添加，则所有子类必须添加会导致大量无需传参的 Module 必须设置无效传参
	 */
	abstract protected static function configure_form(): object;

	/**
	 * 获取表单实例
	 */
	public static function get_form_instance(array $args = []): object {
		return static::configure_form($args);
	}
}
