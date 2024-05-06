<?php
namespace Wnd\Action\Admin;

use Exception;
use Wnd\Action\Wnd_Action_Admin;
use Wnd\Model\Wnd_Recharge;

/**
 * 管理员ajax手动新增用户金额
 * @since 2019.02.22
 */
class Wnd_Admin_Recharge extends Wnd_Action_Admin {

	private $total_amount;
	private $remarks;
	private $target_user;

	protected function execute(): array {
		$recharge = new Wnd_Recharge();
		$recharge->set_user_id($this->target_user->ID);
		$recharge->set_total_amount($this->total_amount);
		$recharge->set_payment_gateway('internal');
		$recharge->set_subject($this->remarks);
		$recharge->set_props($this->data);
		$recharge->create(true); // 直接写入余额

		return ['status' => 1, 'msg' => $this->target_user->display_name . '&nbsp;' . __('充值：¥', 'wnd') . $this->total_amount];
	}

	protected function parse_data() {
		$this->target_user  = wnd_get_user_by($this->data['user_field']);
		$this->total_amount = (float) $this->data['total_amount'];
		$this->remarks      = $this->data['remarks'] ?: __('人工充值', 'wnd');
	}

	protected function check() {
		if (!$this->target_user) {
			throw new Exception(__('用户不存在', 'wnd'));
		}
	}
}
