<?php
namespace Wnd\Model;

use Exception;
use Wnd\WPDB\Wnd_User_DB;

/**
 * 管理员手动充值
 * - 用于极少数特殊情况，修正用户余额
 * - 管理员手动充值不会计入财务统计
 *
 * @since 0.9.72
 */
class Wnd_Recharge_Admin extends Wnd_Transaction {

	protected $transaction_type = 'recharge';

	protected function generate_transaction_data() {
		if (!$this->total_amount) {
			throw new Exception(__('获取金额失败', 'wnd'));
		}

		if ('internal' != $this->payment_gateway) {
			throw new Exception('internal payment gateway Only');
		}

		// 定义变量
		$this->subject = $this->subject ?: ('Admin : ' . $this->total_amount);

		// 记录充值订单 IP
		$this->props['ip'] = wnd_get_user_ip();
	}

	/**
	 * 完成充值
	 *
	 * 管理员充值：使用 Wnd_User_DB::inc() 直接写入数据库以绕开财务统计
	 */
	final protected function complete_transaction(): int {
		// 在线订单校验时，由支付平台发起请求，并指定订单ID，需根据订单ID设置对应变量
		$ID           = $this->get_transaction_id();
		$user_id      = $this->get_user_id();
		$total_amount = $this->get_total_amount();

		$instance = Wnd_User_DB::get_instance();
		$action   = $instance->inc(['user_id' => $user_id], 'balance', $total_amount);
		if (!$action) {
			throw new Exception('【支付错误】: 写入充值失败 user_id : ' . $user_id . ' - 金额 : ' . $total_amount);
		}

		return $ID;
	}

}
