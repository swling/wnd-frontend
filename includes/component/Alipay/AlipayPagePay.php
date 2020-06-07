<?php
namespace Wnd\Component\Alipay;

use Exception;
use Wnd\Component\Alipay\AlipayPagePayBuilder;
use Wnd\Model\Wnd_Payment;

/**
 *网页支付
 *@since 2019.03.02 轻量化改造，新增wap支付
 */
class AlipayPagePay {

	public static function pay() {
		/**
		 *@since 2019.08.12 面向对象重构
		 *
		 *创建站内支付信息
		 */
		$post_id      = $_REQUEST['post_id'] ?? 0;
		$total_amount = $_REQUEST['total_amount'] ?? 0;
		try {
			$payment = new Wnd_Payment();
			$payment->set_object_id($post_id);
			$payment->set_total_amount($total_amount);
			$payment->create();
		} catch (Exception $e) {
			exit($e->getMessage());
		}

		/**
		 *@since 2019.03.03
		 *配置支付宝API
		 *
		 */
		$aliPay = new AlipayPagePayBuilder();
		$aliPay->set_total_amount($payment->get_total_amount());
		$aliPay->set_out_trade_no($payment->get_out_trade_no());
		$aliPay->set_subject($payment->get_subject());

		// 生成数据表单并提交
		echo $aliPay->doPay();
	}
}
