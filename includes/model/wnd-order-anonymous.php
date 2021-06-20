<?php
namespace Wnd\Model;

use Exception;

/**
 * 匿名订单模块
 * @since 0.9.32
 */
class Wnd_Order_Anonymous extends Wnd_Order {

	// 单个设备最多支持的匿名订单
	private static $max_anon_orders = 20;

	// 有效期（秒）
	private static $valid_period = 3600 * 24;

	/**
	 * 匿名订单处理
	 * 调用父类同名方法
	 *
	 * @since 0.9.32
	 */
	protected function generate_transaction_data(bool $is_completed) {
		$this->handle_anon_order_props();
		parent::generate_transaction_data($is_completed);
	}

	/**
	 * 构建匿名订单所需的订单属性
	 * - 将匿名订单 cookie 设置为订单 $this->transaction_slug
	 * - 设置匿名订单 cookie
	 */
	private function handle_anon_order_props() {
		$this->transaction_slug = $this->generate_anon_cookie();
		$this->set_anon_cookie($this->object_id, $this->transaction_slug);
	}

	/**
	 * 创建匿名支付随机码
	 */
	private function generate_anon_cookie(): string {
		return md5(uniqid($this->object_id));
	}

	/**
	 * 写入匿名支付订单 cookies
	 * - 之所以采用 LOGGED_IN_COOKIE 作为存储 cookie 的名称，在于兼容 WP 静态缓存
	 * - 缓存插件读取到 $_COOKIE[LOGGED_IN_COOKIE] 后，即认为当前请求属于登录账户，并不会去核查该 cookie 是否真实有效
	 * - 基于此，在缓存插件后台设置不缓存已登录用户，即可实现用户匿名订单支付后，禁止静态缓存
	 */
	private function set_anon_cookie(): bool{
		$cookies = static::get_anon_cookies();
		if (count($cookies) >= static::$max_anon_orders) {
			throw new Exception('Maximum ' . static::$max_anon_orders . ' anonymous orders per device');
		}

		$key           = static::generate_object_cookie_key($this->object_id);
		$cookies[$key] = ['value' => $this->transaction_slug, 'time' => time()];

		$cookies_string = json_encode($cookies);
		return setcookie(LOGGED_IN_COOKIE, $cookies_string, static::$valid_period + time(), '/');
	}

	/**
	 * 获取匿名支付订单 Cookies Json 合集，并转为数组
	 * @link https://developer.wordpress.org/reference/functions/stripslashes_deep/
	 */
	private static function get_anon_cookies(): array{
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
	 * 生成单个 object id 对应的 cookie key
	 */
	private static function generate_object_cookie_key(int $object_id): string {
		return 'id-' . $object_id;
	}

	/**
	 * 根据 object id 获取对应订单 Slug 值
	 */
	private static function get_anon_cookie(int $object_id): string{
		$cookies = static::get_anon_cookies();
		$key     = static::generate_object_cookie_key($object_id);
		return $cookies[$key]['value'] ?? '';
	}

	/**
	 * 查询匿名订单是否已完成支付
	 * @since 0.9.32
	 *
	 * @return bool
	 */
	public static function has_paid(int $user_id, int $object_id): bool{
		$anon_cookie = static::get_anon_cookie($object_id);
		if (!$anon_cookie) {
			return false;
		}

		$order = wnd_get_post_by_slug($anon_cookie, 'order', [static::$completed_status, static::$pending_status]);
		if (!$order) {
			return false;
		}

		/**
		 * 必须检测订单是否与 object id 是否匹配。否则用户前端支付任意订单后，即可修改 cookie name 篡改任意 object id 的支付权限
		 * @since 0.9.32
		 */
		if ($order->post_parent != $object_id) {
			return false;
		}

		if (time() - strtotime($order->post_date_gmt) < 3600 * 24) {
			return true;
		} else {
			return false;
		}
	}
}
