<?php
namespace Wnd\Model;

/**
 * 匿名订单模块
 * @since 0.9.32
 */
class Wnd_Order_Anonymous extends Wnd_Order {

	// 定义匿名支付cookie名称
	private static $anon_cookie_name_prefix = 'anon_order';

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
	 * 构建匿名订单所需的订单属性：$this->transaction_slug
	 * - 设置匿名订单 cookie
	 * - 将匿名订单 cookie 设置为订单 post name
	 */
	private function handle_anon_order_props() {
		$anon_cookie            = $this->generate_anon_cookie();
		$this->transaction_slug = $anon_cookie;
		setcookie(static::get_anon_cookie_name($this->object_id), $anon_cookie, time() + 3600 * 24, '/');
	}

	/**
	 * 创建匿名支付随机码
	 */
	private function generate_anon_cookie() {
		return md5(uniqid($this->object_id));
	}

	/**
	 * 匿名支付订单cookie name
	 */
	public static function get_anon_cookie_name(int $object_id) {
		return static::$anon_cookie_name_prefix . '_' . $object_id;
	}

	/**
	 * 匿名支付订单查询
	 * @since 0.9.32
	 *
	 * @return bool
	 */
	public static function has_paid(int $user_id, int $object_id): bool{
		$cookie_name = static::get_anon_cookie_name($object_id);
		$anon_cookie = $_COOKIE[$cookie_name] ?? '';
		if (!$anon_cookie) {
			return false;
		}

		$order = wnd_get_post_by_slug($anon_cookie, 'order', [static::$completed_status, static::$pending_status]);
		if (!$order) {
			return false;
		}

		if (time() - strtotime($order->post_date_gmt) < 3600 * 24) {
			return true;
		} else {
			return false;
		}
	}
}
