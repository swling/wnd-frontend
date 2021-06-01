<?php
namespace Wnd\Endpoint;

use Exception;
use Wnd\Getway\Wnd_Login_Social;

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

		/**
		 *@since 0.9.27
		 *本节点需做常规页面供外部回调。
		 *故此手动添加通过 Cookie 设置当前用户，以维持账户登录状态，确保社交账号绑定，WP Nonce 校验等相关操作有效性
		 */
		$user_id = wp_validate_logged_in_cookie(0);
		wp_set_current_user($user_id);

		try {
			$domain       = Wnd_Login_Social::parse_state($this->data['state'])['domain'];
			$Login_Social = Wnd_Login_Social::get_instance($domain);
			$Login_Social->login();
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
}
