<?php
namespace Wnd\Model;

use Exception;

/**
 *@since 2020.06.09
 *退款
 */
abstract class Wnd_Refunder {
	// 支付纪录ID
	protected $payment_id;

	// 支付订单ID
	protected $out_trade_no;

	// 统一订单，部分退款，或分批退款时，每次请求编号
	protected $out_request_no;

	// 订单总金额
	protected $total_amount;

	// 退款金额
	protected $refund_amount;

	// 支付平台响应数据：支付失败时用于查询信息及调试
	protected $response = [];

	// 支付订单WP Post
	protected static $post;

	public function __construct($payment_id) {
		if (!wnd_is_manager()) {
			throw new Exception(__('权限不足', 'wnd'));
		}

		$this->payment_id = $payment_id;

		// 获取订单的支付信息
		$payment = new Wnd_Payment;
		$payment->set_ID($this->payment_id);
		$this->total_amount = $payment->get_total_amount();
		$this->out_trade_no = $payment->get_out_trade_no();

		// 部分退款：以退款次数作为标识
		$refund_count         = wnd_get_post_meta($this->payment_id, 'refund_count') ?: 0;
		$this->out_request_no = $refund_count;
	}

	/**
	 *根据payment_id读取支付平台信息，并自动选择子类处理当前业务
	 */
	public static function get_instance($payment_id): Wnd_Refunder {
		static::$post = $payment_id ? get_post($payment_id) : false;
		if (!static::$post) {
			throw new Exception(__('ID无效', 'wnd'));
		}

		// 判断是否为在线交易，此处判断不涉及资金安全，订单号提交至支付平台后，支付平台会校验订单号是否存在
		$payment_method = static::$post->post_excerpt;
		if (!$payment_method) {
			throw new Exception(__('本笔业务为站内交易，不支持在线退款', 'wnd'));
		}

		$class_name = __NAMESPACE__ . '\\' . 'Wnd_Refunder_' . $payment_method;
		if (class_exists($class_name)) {
			return new $class_name($payment_id);
		} else {
			throw new Exception(__('未定义支付方式：', 'wnd') . $payment_method);
		}
	}

	/**
	 *设置退款金额
	 *如未设置退款金额，则全额退款
	 */
	public function set_refund_amount($refund_amount) {
		$this->refund_amount = $refund_amount ?: $this->total_amount;
	}

	/**
	 *退款并纪录
	 */
	public function refund() {
		$this->do_refund();

		$this->add_refund_records();

		// 关闭支付订单并设置标题
		$post_arr = [
			'ID'          => $this->payment_id,
			'post_status' => 'close',
			'post_title'  => static::$post->post_title . __('*已退款*', 'wnd'),
		];
		$ID = wp_update_post($post_arr);
		if (!$ID or is_wp_error($ID)) {
			throw new Exception(__('数据更新失败', 'wnd'));
		}
	}

	/**
	 *抽象方法
	 *
	 *子类需定义并实现如下功能：
	 * - 执行退款
	 * - 设定平台响应数组数据 $this->response（平台通常响应为json格式，需转为数组）
	 */
	abstract protected function do_refund();

	/**
	 *获取支付平台响应数据
	 */
	public function get_response(): array{
		return $this->response;
	}

	/**
	 *退款完成
	 *记录退款次数
	 *
	 *记录操作记录
	 */
	protected function add_refund_records() {
		wnd_inc_wnd_post_meta($this->payment_id, 'refund_count', 1);

		$refund_records   = wnd_get_post_meta($this->payment_id, 'refund_records');
		$refund_records   = is_array($refund_records) ? $refund_records : [];
		$refund_records[] = [
			'user_id'       => get_current_user_id(),
			'refund_amount' => $this->refund_amount,
			'time'          => time(),
		];
		wnd_update_post_meta($this->payment_id, 'refund_records', $refund_records);
	}
}
