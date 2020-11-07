<?php
namespace Wnd\Model;

use Exception;
use Wnd\Model\Wnd_Recharge;
use Wnd\Model\Wnd_Transaction;

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

	// 订单关联的产品 ID
	protected $object_id;

	// 交易类型：order / recharge
	protected $transaction_type;

	// 订单创建用户
	protected $user_id;

	// 订单支付接口
	protected static $payment_gateway;

	/**
	 *构造函数
	 *读取订单基本信息
	 */
	public function __construct($payment_id) {
		if (!wnd_is_manager()) {
			throw new Exception(__('权限不足', 'wnd'));
		}

		$this->payment_id = $payment_id;

		// 获取订单的支付信息
		$payment = Wnd_Payment::get_instance(static::$payment_gateway);
		$payment->set_transaction_id($this->payment_id);
		$this->total_amount     = $payment->get_total_amount();
		$this->out_trade_no     = $payment->get_out_trade_no();
		$this->object_id        = $payment->get_object_id();
		$this->transaction_type = $payment->get_type();
		$this->user_id          = $payment->get_user_id();

		// 分批次退款时需要设置子订单编号（支付宝）
		$refund_count         = wnd_get_post_meta($this->payment_id, 'refund_count') ?: 0;
		$this->out_request_no = $refund_count + 1;
	}

	/**
	 *根据payment_id读取支付平台信息，并自动选择子类处理当前业务
	 */
	public static function get_instance($payment_id): Wnd_Refunder {
		// 订单支付方式
		static::$payment_gateway = Wnd_Payment_Getway::get_payment_gateway($payment_id) ?: 'Internal';

		/**
		 *根据交易类型选择退款方式
		 *在线支付交易：@see Wnd_Payment->verify($payment_gateway)
		 *
		 *站内交易为缺省状态，设置为：'Internal' 对应站内退款方法
		 */
		$class_name = __NAMESPACE__ . '\\' . 'Wnd_Refunder_' . static::$payment_gateway;
		if (class_exists($class_name)) {
			return new $class_name($payment_id);
		} else {
			throw new Exception(__('未定义支付方式：', 'wnd') . static::$payment_gateway);
		}
	}

	/**
	 *设置退款金额
	 *如未设置退款金额，则全额退款
	 */
	public function set_refund_amount(float $refund_amount) {
		$this->refund_amount = $refund_amount ?: $this->total_amount;
	}

	/**
	 *退款并纪录
	 */
	public function refund() {
		// 订单已全额退款
		if (!$this->total_amount) {
			throw new Exception(__('订单无可退余额', 'wnd'));
		}

		// 退款金额不合法
		$balance = number_format($this->total_amount - $this->refund_amount, 2, '.', '');
		if ($balance < 0) {
			throw new Exception(__('退款金额不得大于订单总额', 'wnd'));
		}

		$this->do_refund();

		$this->add_refund_records();

		$this->deduction();

		// 关闭支付订单，扣除订单余额，设置标题备注
		$post_arr = [
			'ID'           => $this->payment_id,
			'post_status'  => Wnd_Transaction::$refunded_status,
			'post_content' => $balance,
		];
		$ID = wp_update_post($post_arr);
		if (!$ID or is_wp_error($ID)) {
			throw new Exception(__('更新订单失败', 'wnd'));
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

	/**
	 *充值订单：扣除用户余额，扣除对应充值统计（站内佣金暂不支持退款）
	 *
	 *产品订单：扣除总销售额，扣除作者佣金
	 */
	protected function deduction() {
		/**
		 *充值退款
		 *
		 * - 站内佣金不支持退款
		 * - 扣除账户余额
		 */
		if ('recharge' == $this->transaction_type) {
			if ($this->object_id) {
				throw new Exception(__('当前交易不支持退款', 'wnd'));
			}

			return wnd_inc_user_money($this->user_id, $this->refund_amount * -1, true);
		}

		/**
		 *订单退款
		 *
		 * - 扣除销售额
		 * - 扣除作者佣金
		 */
		wnd_inc_post_total_sales($this->object_id, $this->refund_amount * -1);

		$commission = wnd_get_order_commission($this->payment_id);
		if (!$commission) {
			return;
		}

		$object   = get_post($this->object_id);
		$recharge = new Wnd_Recharge();
		$recharge->set_object_id($object->ID);
		$recharge->set_user_id($object->post_author);
		$recharge->set_total_amount($commission * -1);
		$recharge->create(true);
	}
}
