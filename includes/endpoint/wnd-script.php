<?php
namespace Wnd\Endpoint;

/**
 *@since 0.9.25
 *响应 JavaScript 脚本
 */
class Wnd_Script extends Wnd_Endpoint {

	protected $content_type = 'script';

	protected function do() {
		echo 'console.log("测试输出");';
	}
}
