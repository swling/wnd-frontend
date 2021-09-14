<?php
namespace Wnd\Module;

/**
 * Filter 模块公共特性
 * @since 0.9.2
 */
abstract class Wnd_Module_Filter extends Wnd_Module {

	protected $type = 'filter';

	// Filter 模块暂只支持结构输出
	protected static function build(): string {
		return '';
	}
}
