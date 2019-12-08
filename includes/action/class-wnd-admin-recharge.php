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
class Wnd_Admin_Recharge extends Wnd_Action_Ajax {

	public static function execute(): array{
		if (!is_super_admin()) {
			return array('status' => 0, 'msg' => '仅超级管理员可执行当前操作');
		}

		$user_field   = $_POST['user_field'];
		$total_amount = $_POST['total_amount'];
		$remarks      = $_POST['remarks'] ?: '人工充值';

		// 根据邮箱，手机，或用户名查询用户
		$user = wnd_get_user_by($user_field);
		if (!$user) {
			return array('status' => 0, 'msg' => '用户不存在');
		}

		if (!is_numeric($total_amount)) {
			return array('status' => 0, 'msg' => '请输入一个有效的充值金额');
		}

		// 写入充值记录
		try {
			$recharge = new Wnd_Recharge();
			$recharge->set_user_id($user->ID);
			$recharge->set_total_amount($total_amount);
			$recharge->set_subject($remarks);
			$recharge->create(true); // 直接写入余额
		} catch (Exception $e) {
			return array('status' => 0, 'msg' => $e->getMessage());
		}

		return array('status' => 1, 'msg' => $user->display_name . ' 充值：¥' . $total_amount);
	}
}
