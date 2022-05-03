<?php
namespace Wnd\Model;

use Exception;

/**
 * 匿名交易 cookie 模块
 * @since 0.9.32
 */
class Wnd_Transaction_Anonymous {

	// 单个设备最多支持的匿名订单
	private static $max_anon_orders = 20;

	// 有效期（秒）
	private static $valid_period = 3600 * 24;

	/**
	 * 写入匿名支付订单 cookies
	 * - 之所以采用 LOGGED_IN_COOKIE 作为存储 cookie 的名称，在于兼容 WP 静态缓存
	 * - 缓存插件读取到 $_COOKIE[LOGGED_IN_COOKIE] 后，即认为当前请求属于登录账户，并不会去核查该 cookie 是否真实有效
	 * - 基于此，在缓存插件后台设置不缓存已登录用户，即可实现用户匿名订单支付后，禁止静态缓存
	 *
	 * order: 	 order_{$object_id} = ['value' => $transaction_slug, 'time' => time()];
	 * recharge: recharge           = ['value' => $transaction_slug, 'time' => time()];
	 */
	public static function set_anon_cookie(string $transaction_type, int $object_id, string $transaction_slug): bool{
		$cookies = static::get_anon_cookies();
		if (count($cookies) >= static::$max_anon_orders) {
			throw new Exception('Maximum ' . static::$max_anon_orders . ' anonymous orders per device');
		}

		$key           = static::generate_cookie_key($transaction_type, $object_id);
		$cookies[$key] = ['value' => $transaction_slug, 'time' => time()];

		/**
		 * 匿名订单cookie作用域名
		 *
		 * Domain 属性
		 * Domain 指定了哪些主机可以接受 Cookie。如果不指定，默认为 origin，不包含子域名。
		 * 如果指定了Domain，则一般包含子域名。因此，指定 Domain 比省略它的限制要少。但是，当子域需要共享有关用户的信息时，这可能会有所帮助。
		 * 例如，如果设置 Domain=mozilla.org，则 Cookie 也包含在子域名中（如developer.mozilla.org）。
		 * 当前大多数浏览器遵循 RFC 6265，设置 Domain 时 不需要加前导点。浏览器不遵循该规范，则需要加前导点，例如：Domain=.mozilla.org
		 * @link https://developer.mozilla.org/zh-CN/docs/Web/HTTP/Cookies#cookie_%E7%9A%84%E4%BD%9C%E7%94%A8%E5%9F%9F
		 * @since 0.9.37.1
		 */
		$domain         = static::get_anon_cookie_domain();
		$cookies_string = json_encode($cookies);
		return setcookie(LOGGED_IN_COOKIE, $cookies_string, static::$valid_period + time(), '/', $domain);
	}

	/**
	 * 生成单个 object id 对应的 cookie key
	 */
	private static function generate_cookie_key(string $transaction_type, int $object_id): string {
		return $transaction_type . ($object_id ? "_{$object_id}" : '');
	}

	/**
	 * 匿名订单 cookie 作用域
	 * @since 0.9.37
	 */
	private static function get_anon_cookie_domain(): string{
		$domain = parse_url(home_url())['host'];
		return apply_filters('wnd_anonymous_order_domain', $domain);
	}

	/**
	 * 获取匿名支付订单 Cookies Json 合集，并转为数组
	 * @link https://developer.wordpress.org/reference/functions/stripslashes_deep/
	 */
	public static function get_anon_cookies(): array{
		$cookies = $_COOKIE[LOGGED_IN_COOKIE] ?? '';
		$cookies = stripslashes_deep($cookies);
		if (!$cookies) {
			return [];
		}

		$cookies = json_decode($cookies, true);
		if (!$cookies) {
			return [];
		}

		// 清理过期记录，防止 cookie 过大
		foreach ($cookies as $key => $cookie) {
			if (time() - $cookie['time'] > static::$valid_period) {
				unset($cookies[$key]);
			}
		}

		return $cookies ?: [];
	}

	/**
	 * 根据 object id 获取对应订单 Slug 值
	 */
	public static function get_anon_cookie_value(string $transaction_type, int $object_id): string{
		$cookies = static::get_anon_cookies();
		$key     = static::generate_cookie_key($transaction_type, $object_id);
		return $cookies[$key]['value'] ?? '';
	}

	/**
	 * 删除匿名订单 cookie
	 * @since 0.9.37
	 */
	public static function delete_anon_cookie() {
		$domain = static::get_anon_cookie_domain();
		setcookie(LOGGED_IN_COOKIE, '', time() - 1, '/', $domain);
	}
}
