<?php
namespace Wnd\Action;

use Exception;
use Wnd\Model\Wnd_Recharge;

/**
 *@since 2019.02.22
 *管理员ajax手动新增用户金额
 *@param $_POST['user_field']
 *@param $_POST['total_amount']
 *@param $_POST['remarks']
 */
class Wnd_Admin_Recharge extends Wnd_Action_Ajax_Admin {

	public function execute(): array{
		$user_field   = $this->data['user_field'];
		$total_amount = (float) $this->data['total_amount'];
		$remarks      = $this->data['remarks'] ?: __('人工充值', 'wnd');

		// 根据邮箱，手机，或用户名查询用户
		$user = wnd_get_user_by($user_field);
		if (!$user) {
			throw new Exception(__('用户不存在', 'wnd'));
		}

		// 写入充值记录
		$recharge = new Wnd_Recharge();
		$recharge->set_user_id($user->ID);
		$recharge->set_total_amount($total_amount);
		$recharge->set_subject($remarks);
		$recharge->create(true); // 直接写入余额

		return ['status' => 1, 'msg' => $user->display_name . '&nbsp;' . __('充值：¥', 'wnd') . $total_amount];
	}
}
