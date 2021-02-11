<?php
namespace Wnd\Endpoint;

/**
 *@since 0.9.25
 *响应人机校验脚本代码
 */
class Wnd_Captcha_Script extends Wnd_Endpoint {

	protected $content_type = 'script';

	protected function do() {
		echo 'console.log("测试输出");';
	}
}
