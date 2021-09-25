<?php
namespace Wnd\Utility;

use Wnd\Utility\Wnd_JWT;

/**
 * 将WordPress 账户体系与 JWT Token 绑定
 * 本插件并未启用 jWT，如需启用 JWT，请在主题或插件中，继承本抽象类创建子类并在 WP init Hook 中实例化
 * 子类需要完成客户端处理 Token 的具体方法，包含：存储、获取、删除
 * @since 0.9.18
 */
abstract class Wnd_JWT_Handler {

	protected $domain;

	protected $exp;

	// Token 验证后得到的用户 ID
	protected $verified_user_id;

	public function __construct() {
		$this->domain = parse_url(home_url())['host'];
		$this->exp    = time() + 3600 * 24 * 90;

		add_action('wp_login', [$this, 'handle_login'], 10, 2);
		add_action('init', [$this, 'set_current_user'], 10);
		add_action('wp_logout', [$this, 'handle_logout'], 10);
		add_filter('rest_authentication_errors', [$this, 'rest_token_check_errors'], 10, 1);
	}

	/**
	 * 登录时设置 JWT Token
	 *
	 * - iss (issuer)：签发人
	 * - exp (expiration time)：过期时间
	 * - sub (subject)：主题
	 * - aud (audience)：受众
	 * - nbf (Not Before)：生效时间
	 * - iat (Issued At)：签发时间
	 * - jti (JWT ID)：编号
	 */
	private function generate_token(int $user_id): string{
		$payload = [
			'iss' => $this->domain,
			'iat' => time(),
			'exp' => $this->exp,
			'nbf' => time(),
			'sub' => $user_id,
		];

		return Wnd_JWT::getToken($payload);
	}

	/**
	 * 处理用户登录
	 */
	public function handle_login($user_name, $user) {
		$this->save_client_token($this->generate_token($user->ID));
	}

	/**
	 * 处理用户登录
	 */
	public function set_current_user() {
		// 未能获取到 Token 保持现有账户状态
		$this->verified_user_id = $this->verify_client_token();
		if (-1 === $this->verified_user_id) {
			return;
		}

		// 无效 Token
		if (0 === $this->verified_user_id) {
			wp_logout();
			return;
		}

		/**
		 * - 如果 Token 有效，而当前账户未登录，则设置同步设置 Cookie @since 0.9.32
		 * - 根据 Token 设置当前账户 ID （过期为 0）
		 */
		if (!is_user_logged_in()) {
			wp_set_auth_cookie($this->verified_user_id, true);
		}
		wp_set_current_user($this->verified_user_id);
	}

	/**
	 * 验证客户端 Token
	 */
	private function verify_client_token(): int{
		// 未能获取 Token
		$token = $this->get_client_token();
		if (!$token) {
			return -1;
		}

		// Token 失效
		$getPayload = Wnd_JWT::verifyToken($token);
		if (!$getPayload) {
			$this->clean_client_token();
			return 0;
		}

		// Token 认证成功，返回用户 ID
		return (int) $getPayload['sub'];
	}

	/**
	 * 处理账户退出
	 */
	public function handle_logout() {
		$this->clean_client_token();
	}

	/**
	 * Filters REST authentication errors.
	 *
	 * This is used to pass a WP_Error from an authentication method back to
	 * the API.
	 *
	 * Authentication methods should check first if they're being used, as
	 * multiple authentication methods can be enabled on a site (cookies,
	 * HTTP basic auth, OAuth). If the authentication method hooked in is
	 * not actually being attempted, null should be returned to indicate
	 * another authentication method should check instead. Similarly,
	 * callbacks should ensure the value is `null` before checking for
	 * errors.
	 *
	 * A WP_Error instance can be returned if an error occurs, and this should
	 * match the format used by API methods internally (that is, the `status`
	 * data should be used). A callback can return `true` to indicate that
	 * the authentication method was used, and it succeeded.
	 *
	 * method wasn't used, true if authentication succeeded.
	 * @since 4.4.0
	 *
	 * @param WP_Error|null|true $errors WP_Error if authentication error, null if authentication
	 */
	public function rest_token_check_errors($result) {
		if (!empty($result)) {
			return $result;
		}

		// No Token
		if (-1 === $this->verified_user_id) {
			return $result;
		}

		return true;
	}

	/**
	 * 客户端存储 Token
	 */
	abstract protected function save_client_token($token);

	/**
	 * 获取客户端 JWT Token
	 */
	abstract protected function get_client_token();

	/**
	 * 删除客户端 Token
	 */
	abstract protected function clean_client_token();
}
