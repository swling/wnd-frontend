<?php

namespace Wnd\Module\Common;

use Wnd\Controller\Wnd_Request;
use Wnd\Getway\Wnd_Payment_Getway;
use Wnd\Module\Wnd_Module_Vue;

/**
 * 在线支付订单表单
 * 匿名支付订单默认启用人机验证
 * @since 2020.06.30
 */
class Wnd_User_Recharge_Form extends Wnd_Module_Vue {

	/**
	 * 根据参数读取本次订单对应产品信息
	 * 构造：产品ID，SKU ID，数量，总金额，订单标题，SKU 提示信息
	 * @since 0.8.76
	 */
	protected static function parse_data(array $args): array {
		$gateway_options = Wnd_Payment_Getway::get_gateway_options();
		$default_gateway = Wnd_Payment_Getway::get_default_gateway();

		if (!is_user_logged_in()) {
			$balance = wnd_get_anon_user_balance();
			$msg     = $balance ? (__('<b>注意：</b>新充值将覆盖当前余额：', 'wnd') . $balance) : __('您当前尚未登录，匿名订单仅24小时有效，请悉知！', 'wnd');
		}

		$payments = [];
		foreach ($gateway_options as $label => $gateway) {
			$payments[] = [
				'label' => $label,
				'value' => $gateway,
				'icon'  => '',
			];
		}

		$sign = Wnd_Request::sign(['total_amount', 'payment_gateway']);

		/**
		 * 构造：产品ID，SKU ID，数量，总金额，订单标题，SKU 提示信息
		 */
		return compact('payments', 'default_gateway', 'msg', 'sign');
	}
}
