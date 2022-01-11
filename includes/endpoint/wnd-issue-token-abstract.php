<?php
namespace Wnd\Endpoint;

use Wnd\Endpoint\Wnd_Endpoint;
use Wnd\Getway\Wnd_Login_Social;
use Wnd\Utility\Wnd_JWT_handler;

/**
 * ## 签发 Token 抽象基类
 * - 基于第三方应用 openid，在本站系统注册或登录并返回用户 token
 * - 针对不同的三方应用，应该继承本类并定义 $this->app_type、实现 get_app_openid() 方法，最终构成实际的签发节点
 *
 * @since 0.9.50
 */
abstract class Wnd_Issue_Token_Abstract extends Wnd_Endpoint {

	protected $content_type = 'json';

	/**
	 * 站外应用类型标识
	 * - 站外应用必须在子类中定义该属性
	 * - 对应社交登录中的 openid type
	 */
	protected $app_type = '';

	protected function do() {
		$user_id = $this->register_or_login();
		$jwt     = Wnd_JWT_Handler::get_instance();
		$token   = $jwt->generate_token($user_id);
		$exp     = $jwt->parse_token($token)['exp'] ?? 0;

		echo json_encode(['token' => $token, 'exp' => $exp]);
	}

	/**
	 * 获取当前用户id
	 * - 第三方应用注册/登录
	 */
	protected function register_or_login(): int{
		/**
		 * 站外应用签发 Token条件
		 * - 必须定义 $this->app_type，即对应：社交登录中的 openid type
		 * - 必须指定 openid
		 * - 必须指定 display_name
		 */
		$openid       = $this->get_app_openid();
		$display_name = $this->app_type . '_' . uniqid();
		$avatar       = '';
		$user         = Wnd_Login_Social::login_social($this->app_type, $openid, $display_name, $avatar);
		return $user->ID;
	}

	/**
	 * 获取应用openid以此作为账户标识，在本应用内注册或登录
	 * 针对不同的应用在实际场景中在子类中具体实现
	 */
	abstract protected function get_app_openid(): string;
}
