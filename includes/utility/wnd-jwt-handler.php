<?php
namespace Wnd\Utility;

use Wnd\Component\JWT\JWTAuth;

/**
 * 将WordPress 账户体系与 JWT Token 绑定
 * Token 可通过如下方式传递 @since 0.9.50
 * - Header 传递：'Authorization'  	（用于App，小程序等第三方账户对接： "Authorization": "Bearer " + token）
 * - Cookie 传递：'wnd_token'		（用于web端浏览器跨域操作，获取其他特定情况）
 *
 * @since 0.9.18
 */
class Wnd_JWT_Handler {

	//使用HMAC生成信息摘要时所使用的密钥
	private static $secret_key = LOGGED_IN_KEY;

	protected $domain;

	protected $exp;

	public static $cookie_name = 'wnd_token';

	public static $header_name = 'Authorization';

	// Token 验证后得到的用户 ID
	protected $verified_user_id;

	use Wnd_Singleton_Trait;

	private function __construct() {
		$this->domain = parse_url(home_url())['host'];
		$this->exp    = time() + 3600 * 24 * 90;

		add_action('init', [$this, 'set_current_user'], 10);
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
	public function generate_token(int $user_id): string{
		$payload = [
			'iss' => $this->domain,
			'iat' => time(),
			'exp' => $this->exp,
			'nbf' => time(),
			'sub' => $user_id,
		];

		return JWTAuth::generateToken($payload, static::$secret_key);
	}

	/**
	 * 解析本插件生成的 JWT Token
	 * @since 0.9.39
	 */
	public static function parse_token(string $token) {
		return JWTAuth::parseToken($token, static::$secret_key);
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
		$getPayload = static::parse_token($token);
		if (!$getPayload) {
			return 0;
		}

		// Token 认证成功，返回用户 ID
		return (int) $getPayload['sub'];
	}

	/**
	 * 获取客户端 JWT Token
	 * - 获取方式需要与前端请求保持一致
	 * - axios.defaults.headers["Authorization"] = "Bearer " + getCookie("wnd_token");
	 */
	private function get_client_token() {
		// 从 Cookie 中读取
		$token = $_COOKIE[static::$cookie_name] ?? '';
		if ($token) {
			return $token;
		}

		// 从 header 请求中获取
		$headers       = getallheaders();
		$authorization = $headers[static::$header_name] ?? '';
		if (!$authorization) {
			return '';
		}
		$bearer_token = explode(' ', $authorization);
		return $bearer_token[1] ?? '';
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
}
