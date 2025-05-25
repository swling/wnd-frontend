<?php

namespace Wnd\Utility;

use Wnd\Model\Wnd_Recharge;
use Wnd\WPDB\Wnd_User_DB;

/**
 * 推广注册返佣
 *
 * @since 0.9.70
 */
class Wnd_Affiliate {

	// 通过推广注册
	public static function handle_aff_reg() {
		// 核查推广 id、佣金、当前用户登录状态
		$aff_id     = wnd_get_aff_cookie();
		$commission = floatval(wnd_get_config('reg_commission'));
		if (!$aff_id or $commission <= 0) {
			return;
		}
		$user_id = get_current_user_id();
		if (!$user_id) {
			return;
		}

		// 仅首次登陆
		if (!Wnd_User_DB::is_first_login($user_id)) {
			return;
		}

		// 自己的推广链接
		if ($user_id == $aff_id) {
			return;
		}

		// 已经写入
		if (wnd_get_user_meta($user_id, WND_AFF_KEY)) {
			return;
		}

		// 推广注册成功，给推广 id 对应的用户返佣
		$recharge = new Wnd_Recharge();
		$recharge->set_user_id($aff_id);
		$recharge->set_total_amount($commission);
		$recharge->set_payment_gateway('internal');
		$recharge->set_subject('Affiliate : ' . $user_id);
		$recharge->create(true); // 直接写入余额

		// 将推广 id 写入用户 meta 备查备用
		wnd_update_user_meta($user_id, WND_AFF_KEY, $aff_id);
	}

	/**
	 * 新用户注册
	 * 赠送金额
	 *
	 */
	public static function reg_success(int $user_id) {
		// 钩子本身应该挂载在注册完成时，理应只在注册时执行一次，但保险起见再次权限检查
		if (!Wnd_User_DB::is_first_login($user_id)) {
			return;
		}

		// 新用户注册奖励
		$reg_bonus = floatval(wnd_get_config('reg_bonus'));
		if ($reg_bonus <= 0) {
			return;
		}

		// 邀请码初次登陆，赠送金额
		$recharge = new Wnd_Recharge();
		$recharge->set_user_id($user_id);
		$recharge->set_total_amount($reg_bonus);
		$recharge->set_payment_gateway('internal');
		$recharge->set_subject('注册赠送');
		$recharge->create(true); // 直接写入余额
	}

}
