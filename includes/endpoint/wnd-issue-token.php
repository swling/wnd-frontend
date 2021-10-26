<?php
namespace Wnd\Endpoint;

use Wnd\Endpoint\Wnd_Endpoint;
use Wnd\Model\Wnd_User;
use Wnd\Utility\Wnd_JWT_handler;

/**
 * ## 签发 Token
 * - 基于第三方应用 openid，在本站系统注册或登录并返回用户 token
 * - 在本应用内部调用即：通过 WP-Nonce 或 Cookies 完成了身份认证，同样会返回当前用户的 JWT token
 *
 * ### 注意事项
 * - 本节点仅可针对站内已登录用户签发 token
 * - 第三方应用中，应该继承本类并定义 $this->app_type、完成对 openid 的获取，最终构成实际的签发节点
 *
 * @since 0.9.50
 */
class Wnd_Issue_Token extends Wnd_Endpoint {

	protected $content_type = 'json';

	/**
	 * 站外应用类型标识
	 * - 站外应用必须在子类中定义该属性
	 * - 对应社交登录中的 openid type
	 */
	protected $app_type = '';

	protected function do() {
		$user_id = $this->get_current_user_id();
		$jwt     = Wnd_JWT_Handler::get_instance();
		$token   = $jwt->generate_token($user_id);
		$exp     = $jwt->parse_token($token)['exp'] ?? 0;

		echo json_encode(['token' => $token, 'exp' => $exp]);
	}

	/**
	 * 获取当前用户id
	 * - 已登录，如浏览器端通过 WP-Nonce 完成了身份认证，直接返回
	 * - 第三方应用注册/登录
	 */
	private function get_current_user_id(): int{
		$user_id = get_current_user_id();
		if ($user_id) {
			return $user_id;
		}

		/**
		 * 站外应用签发 Token条件
		 * - 必须定义 $this->app_type，即对应：社交登录中的 openid type
		 * - 必须指定 openid
		 * - 必须指定 display_name
		 */
		$openid       = $this->get_app_openid();
		$display_name = $this->app_type . '_' . uniqid();
		$avatar       = '';
		$user         = Wnd_User::social_login($this->app_type, $openid, $display_name, $avatar);
		return $user->ID;
	}

	/**
	 * 获取应用openid以此作为账户标识，在本应用内注册或登录
	 * 针对不同的应用在实际场景中在子类中具体实现
	 */
	protected function get_app_openid(): string {
		return '';
	}
}
