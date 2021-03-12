<?php
namespace Wnd\Endpoint;

use Wnd\Utility\Wnd_Login_Social;

/**
 *@since 0.9.26
 *社交登录节点
 *
 */
class Wnd_Social_Login extends Wnd_Endpoint {

	protected $content_type = 'html';

	protected function do() {
		if (empty($this->data)) {
			return;
		}

		$domain       = Wnd_Login_Social::parse_state($this->data['state'])['domain'];
		$Login_Social = Wnd_Login_Social::get_instance($domain);
		$Login_Social->login();
	}
}
