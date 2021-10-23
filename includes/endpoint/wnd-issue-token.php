<?php
namespace Wnd\Endpoint;

use Exception;
use Wndt\Utility\Wndt_JWT_handler;
use Wnd\Endpoint\Wnd_Endpoint;
use Wnd\Model\Wnd_User;

/**
 * 签发 Token
 * - 基于第三方应用 openid，在本站系统注册或登录
 * - 返回用户 token
 * - 在本应用内部调用即：通过 WP-Nonce 完成了身份认证，同样会返回对应用户的 JWT token
 */
abstract class Wnd_Issue_Token extends Wnd_Endpoint {

	protected $content_type = 'json';

	protected $app_type = '';

	protected function do() {
		$user_id = $this->get_current_user_id();
		$jwt     = Wndt_JWT_Handler::get_instance();
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

		$openid       = $this->get_app_openid();
		$display_name = $this->app_type . '_' . uniqid();
		$avatar       = '';
		$user         = Wnd_User::social_login($this->app_type, $openid, $display_name, $avatar);
		return $user->ID;
	}

	/**
	 * 获取应用openid以此作为账户标识，在本应用内注册或登录
	 * 针对不同的应用在实际场景中在子类中具体实现
	 *
	 */
	abstract protected function get_app_openid(): string;

	/**
	 * 子类必须指定 app_type 即等同于：社交登录类型
	 */
	protected function check() {
		if (!$this->app_type) {
			throw new Exception('Invalid app type');
		}
	}
}
