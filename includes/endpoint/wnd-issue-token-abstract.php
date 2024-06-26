<?php
namespace Wnd\Endpoint;

use Exception;
use Wnd\Endpoint\Wnd_Endpoint;
use Wnd\Model\Wnd_Social_Login_Handler;
use Wnd\Utility\Wnd_JWT_handler;
use Wnd\Utility\Wnd_Qrcode_Login;

/**
 * ## 签发 Token 抽象基类
 * - 基于第三方应用 openid，在本站系统注册或登录并返回用户 token
 * - 如果请求数据中包含账号：'user_login' 密码：'user_pass'，则尝试将对应 openid 绑定到指定账号
 * - 如果请求数据中包含 scene，表示为随机二维码扫描授权登录，则尝试将 scene 与 user_id 绑定 @see Wnd\Utility\Wnd_Qrcode_Login
 * - 针对不同的三方应用，应该继承本类并定义 $this->app_type、实现 get_app_openid() 方法，最终构成实际的签发节点
 *
 * @since 0.9.50
 */
abstract class Wnd_Issue_Token_Abstract extends Wnd_Endpoint {

	// 注册用户需设置防抖，防止用户短期重复提交写入
	public $period      = 5;
	public $max_actions = 1;

	protected $content_type = 'json';

	protected $user_login;

	protected $user_pass;

	protected $display_name;

	protected $scene;

	/**
	 * 站外应用类型标识
	 * - 站外应用必须在子类中定义该属性
	 * - 对应社交登录中的 openid type
	 */
	protected $app_type = '';
	protected $openid   = '';

	final protected function do() {
		$user_id = $this->register_or_login();
		$jwt     = Wnd_JWT_Handler::get_instance();
		$token   = $jwt->generate_token($user_id);
		$exp     = $jwt->parse_token($token)['exp'] ?? 0;

		// 请求数据包含 scene，表示二维码授权登录：绑定 scene 与 user_id
		$this->scene = $this->data['scene'] ?? '';
		if ($this->scene) {
			Wnd_Qrcode_Login::bind($this->scene, $user_id);
		}

		echo json_encode(['token' => $token, 'exp' => $exp, 'user_id' => $user_id]);
	}

	/**
	 * 获取当前用户id
	 * - 第三方应用注册/登录
	 */
	protected function register_or_login(): int {
		$this->user_login = $this->data['user_login'] ?? '';
		$this->user_pass  = $this->data['user_pass'] ?? '';
		if ($this->user_login and $this->user_pass) {
			$this->set_current_user_by_user_login();
		}

		/**
		 * 站外应用签发 Token条件
		 * - 必须定义 $this->app_type，即对应：社交登录中的 openid type
		 * - 必须指定 openid
		 * - 必须指定 display_name
		 */
		$openid       = $this->get_openid();
		$display_name = $this->display_name ?: $this->app_type . '_' . uniqid();
		$avatar       = '';
		$user         = Wnd_Social_Login_Handler::login($this->app_type, $openid, $display_name, $avatar);

		// 设置当前用户
		wp_set_current_user($user->ID);
		return $user->ID;
	}

	/**
	 * 设定当前账户
	 */
	protected function set_current_user_by_user_login() {
		// 可根据邮箱，手机，或用户名查询用户
		$target_user = wnd_get_user_by($this->user_login);
		if (!$target_user) {
			throw new Exception(__('用户不存在', 'wnd'));
		}

		if (!wp_check_password($this->user_pass, $target_user->data->user_pass, $target_user->ID)) {
			throw new Exception(__('密码错误', 'wnd'));
		}

		/**
		 * 设置当前用户
		 */
		wp_set_current_user($target_user->ID);
	}

	/**
	 * 防止重复获取远程 openid
	 *
	 */
	final protected function get_openid(): string {
		$this->openid = $this->openid ?: $this->get_app_openid();
		return $this->openid;
	}

	/**
	 * 获取应用openid以此作为账户标识，在本应用内注册或登录
	 * 针对不同的应用在实际场景中在子类中具体实现
	 */
	abstract protected function get_app_openid(): string;

}
