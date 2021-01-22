<?php
namespace Wnd\Utility;

use Wnd\Utility\Wnd_JWT;

/**
 *@since 0.9.18
 * 将WordPress 账户体系与 JWT Token 绑定
 *
 * 本插件并未启用 jWT，如需启用 JWT，请在主题或插件中，继承本抽象类创建子类并在 WP init Hook 中实例化
 * 子类需要完成客户端处理 Token 的具体方法，包含：存储、获取、删除
 */
abstract class Wnd_JWT_Handler {

	protected $Wnd_JWT;

	protected $domain;

	protected $exp;

	public function __construct() {
		$this->Wnd_JWT = new Wnd_JWT;
		$this->domain  = parse_url(home_url())['host'];
		$this->exp     = time() + 3600 * 30;

		add_action('wp_login', [$this, 'handle_login'], 10, 2);
		add_action('init', [$this, 'verify_client_token'], 10);
		add_action('wp_logout', [$this, 'handle_logout'], 10);
	}

	/**
	 *登录时设置 JWT Token
	 *
	 * - iss (issuer)：签发人
	 * - exp (expiration time)：过期时间
	 * - sub (subject)：主题
	 * - aud (audience)：受众
	 * - nbf (Not Before)：生效时间
	 * - iat (Issued At)：签发时间
	 * - jti (JWT ID)：编号
	 */
	protected function generate_token(int $user_id): string{
		$payload = [
			'iss' => $this->domain,
			'iat' => time(),
			'exp' => $this->exp,
			'nbf' => time(),
			'sub' => $user_id,
		];

		return $this->Wnd_JWT::getToken($payload);
	}

	/**
	 *处理用户登录
	 */
	public function handle_login($user_name, $user) {
		$this->save_client_token($this->generate_token($user->ID));
	}

	/**
	 *验证客户端 Token
	 */
	public function verify_client_token() {
		if (is_user_logged_in()) {
			return;
		}

		// 未能获取 Token
		$token = $this->get_client_token();
		if (!$token) {
			return;
		}

		// Token 失效
		$getPayload = $this->Wnd_JWT::verifyToken($token);
		if (!$getPayload) {
			$this->clean_client_token();
			return;
		}

		// Token 认证成功，设定当前用户状态
		wp_set_current_user($getPayload['sub']);
	}

	/**
	 *处理账户退出
	 */
	public function handle_logout() {
		$this->clean_client_token();
	}

	/**
	 *客户端存储 Token
	 */
	abstract protected function save_client_token($token);

	/**
	 *获取客户端 JWT Token
	 */
	abstract protected function get_client_token();

	/**
	 *删除客户端 Token
	 */
	abstract protected function clean_client_token();
}
