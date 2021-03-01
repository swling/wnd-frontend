<?php
namespace Wnd\Module;

/**
 *HTML 模块公共特性
 *@since 0.9.2
 */
abstract class Wnd_Module_Html extends Wnd_Module {

	protected $type = 'html';

	// Html 模块无需结构输出
	protected function structure(): array{
		return [];
	}
}
