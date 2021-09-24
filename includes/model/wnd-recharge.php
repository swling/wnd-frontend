<?php
namespace Wnd\Model;

use Exception;

/**
 * 支付模块
 * @since 2019.08.11
 */
class Wnd_Recharge extends Wnd_Transaction {

	protected $transaction_type = 'recharge';

	/**
	 * 此方法用于补充、修改、核查外部通过方法设定的交易数据，组成最终写入数据库的数据。完整的交易记录构造如下所示：
	 *
	 * $post_arr = [
	 *     'ID'           => $this->transaction_id,
	 *     'post_type'    => $this->transaction_type,
	 *     'post_author'  => $this->user_id,
	 *     'post_parent'  => $this->object_id,
	 *     'post_content' => $this->total_amount,
	 *     'post_excerpt' => $this->payment_gateway,
	 *     'post_status'  => $this->status,
	 *     'post_title'   => $this->subject,
	 *     'post_name'    => $this->transaction_slug ?: uniqid(),
	 * ];
	 *
	 * @since 0.9.32
	 */
	protected function generate_transaction_data() {
		if (!$this->user_id) {
			throw new Exception(__('请登录', 'wnd'));
		}
		if (!$this->total_amount) {
			throw new Exception(__('获取金额失败', 'wnd'));
		}

		// 定义变量
		$this->subject = $this->subject ?: (($this->object_id ? __('佣金：¥', 'wnd') : __('充值：¥', 'wnd')) . $this->total_amount);

		/**
		 * @since 2019.03.31 查询符合当前条件，但尚未完成的付款订单
		 */
		$this->transaction_id = $this->get_reusable_transaction_id();
	}

	/**
	 * 完成充值
	 *
	 * 在线充值：直接新增用户余额
	 *
	 * 当充值包含关联object_id，表示收入来自站内佣金收入：更新用户佣金及产品总佣金统计
	 * @param object 	$this->transaction			required 	订单记录Post
	 */
	protected function complete_transaction(): int{
		// 在线订单校验时，由支付平台发起请求，并指定订单ID，需根据订单ID设置对应变量
		$ID           = $this->get_transaction_id();
		$user_id      = $this->get_user_id();
		$total_amount = $this->get_total_amount();
		$object_id    = $this->get_object_id();

		// 当充值包含关联object_id，表示收入来自站内佣金收入：更新用户佣金及产品总佣金统计
		if ($object_id) {
			wnd_inc_user_commission($user_id, $total_amount);
			wnd_inc_post_total_commission($object_id, $total_amount);

			// 在线余额充值
		} else {
			wnd_inc_user_money($user_id, $total_amount, true);
		}

		return $ID;
	}

	/**
	 * 用户充值金额选项
	 * @since 0.8.62
	 */
	public static function get_recharge_amount_options(): array{
		$defaults = ['0.01' => '0.01', '10.00' => '10.00', '50.00' => '50.00', '100.00' => '100.00', '500.00' => '500.00'];
		return apply_filters('wnd_recharge_amount_options', $defaults);
	}
}
