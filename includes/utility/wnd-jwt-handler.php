<?php
namespace Wnd\Utility;

use Wnd\Component\JWT\JWTAuth;
use WP_Error;

/**
 * ## 将WordPress 账户体系与 JWT Token 绑定
 *
 * Token 可通过如下方式传递 @since 0.9.50
 * - Header 传递：'Authorization'  	（用于App，小程序等第三方账户对接： "Authorization": "Bearer " + token）
 * - Cookie 传递：'wnd_token'		（用于web端浏览器跨域操作，获取其他特定情况）
 *
 * @since 0.9.18
 */
class Wnd_JWT_Handler {

	public static $cookie_name = 'wnd_token';

	public static $header_name = 'Authorization';

	public $domain;

	public $exp;

	//使用HMAC生成信息摘要时所使用的密钥
	private static $secret_key = LOGGED_IN_KEY;

	// Token 验证后得到的用户 ID
	private $verified_user_id;

	use Wnd_Singleton_Trait;

	private function __construct() {
		$this->domain = parse_url(home_url())['host'];
		$this->exp    = time() + 3600 * 24 * 90;

		add_action('init', [$this, 'parse_token_user_id'], 10);
		add_action('init', [$this, 'set_current_user'], 10);
		add_filter('rest_authentication_errors', [$this, 'rest_token_check_errors'], 10, 1);
	}

	/**
	 * 签发 JWT Token
	 *
	 * - iss (issuer)：签发人
	 * - exp (expiration time)：过期时间
	 * - sub (subject)：主题
	 * - aud (audience)：受众
	 * - nbf (Not Before)：生效时间
	 * - iat (Issued At)：签发时间
	 * - jti (JWT ID)：编号
	 */
	public function generate_token(int $user_id): string {
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
	 * 解析 JWT Token
	 * @since 0.9.39
	 */
	public static function parse_token(string $token) {
		return JWTAuth::parseToken($token, static::$secret_key);
	}

	/**
	 * 解析 Token user id 并设置到实例属性
	 *  - 之所以封装此方法在构造函数中通过添加 init 钩子挂载，而不是在构造函数中直接执行是因为
	 *    本类的构造函数直接在插件初始化即加载完成，若直接在构造函数中执行会导致 'wnd_get_client_token' 钩子执行过早，
	 *    即：无法在主题添加过滤器
	 */
	public function parse_token_user_id() {
		$this->verified_user_id = $this->verify_client_token();
	}

	/**
	 * 验证客户端 Token
	 */
	private function verify_client_token(): int {
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
	 */
	private function get_client_token(): string {
		$token = $this->get_token_form_cookie() ?: $this->get_token_form_header();

		return apply_filters('wnd_get_client_token', $token);
	}

	/**
	 * 从 Cookie 中读取 Token
	 */
	private function get_token_form_cookie(): string {
		return $_COOKIE[static::$cookie_name] ?? '';
	}

	/**
	 * 从 Header 头中读取 Token
	 */
	private function get_token_form_header(): string {
		$token = '';

		$headers       = getallheaders();
		$authorization = $headers[static::$header_name] ?? '';
		if ($authorization) {
			$bearer_token = explode(' ', $authorization);
			if ('bearer' == strtolower($bearer_token[0])) {
				$token = $bearer_token[1] ?? '';
			}
		}

		return $token;
	}

	/**
	 * 处理用户登录
	 */
	public function set_current_user() {
		// 未能获取到 Token 保持现有账户状态
		if (-1 === $this->verified_user_id) {
			return;
		}

		// 无效 Token 或者对应的 User ID 已无效（用户被删除等）
		if (0 === $this->verified_user_id or !get_userdata($this->verified_user_id)) {
			wp_logout();
			return;
		}

		// 根据 Token 设置当前账户 ID
		wp_set_current_user($this->verified_user_id);
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

		// Invalid Token
		if (0 === $this->verified_user_id) {
			return new WP_Error('invalid_token', 'Invalid Token.', ['status' => 401]);
		}

		// Token 有效，但对应的 user id 无效
		if (!get_userdata($this->verified_user_id)) {
			return new WP_Error('invalid_user_id', 'Invalid User ID.', ['status' => 401]);
		}

		return true;
	}

	/**
	 * 为指定账户生成 Token 并设置 Cookie
	 */
	public function set_user_token_cookie(int $user_id, bool $secure = false, bool $httponly = false) {
		$token = $this->generate_token($user_id);
		$this->set_token_cookie($token, $secure, $httponly);
	}

	/**
	 * 设置 Token Cookie
	 */
	public function set_token_cookie(string $token, bool $secure = false, bool $httponly = false) {
		$exp = $this->parse_token($token)['exp'] ?? 0;
		setcookie(static::$cookie_name, $token, $exp, '/', $this->domain, $secure, $httponly);
	}

	/**
	 * 清理 Token Cookie
	 */
	public function delete_token_cookie(string $domain = '') {
		if ($domain) {
			setcookie(static::$cookie_name, '', time(), '/', $domain);
		}

		setcookie(static::$cookie_name, '', time(), '/', $this->domain);
	}
}
