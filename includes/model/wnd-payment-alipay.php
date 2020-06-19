<?php
namespace Wnd\Model;

use Wnd\Component\Alipay\AlipayPagePayBuilder;
use Wnd\Component\Alipay\AlipayService;

/**
 *@since 2020.06.19
 *支付宝支付
 *
 *PC支付和wap支付中：product_code 、method 参数有所不同，详情查阅如下
 *@link https://opendocs.alipay.com/apis/api_1/alipay.trade.page.pay
 *@link https://opendocs.alipay.com/apis/api_1/alipay.trade.wap.pay
 *
 */
class Wnd_Payment_Alipay extends Wnd_Payment {

	/**
	 *发起支付
	 *
	 */
	protected function do_pay() {
		$aliPay = new AlipayPagePayBuilder();
		$aliPay->set_total_amount($this->get_total_amount());
		$aliPay->set_out_trade_no($this->get_out_trade_no());
		$aliPay->set_subject($this->get_subject());

		// 生成数据表单并提交
		echo $aliPay->doPay();
	}

	/**
	 *回调验签
	 */
	protected function check($params) {
		$aliPay = new AlipayService();
		$result = $aliPay->rsaCheck($params);
		if (true !== $result) {
			exit('error');
		}
	}

	/**
	 *同步回调通知
	 *
	 */
	protected function do_return() {
		/**
		 *验签
		 */
		$this->check($_GET);

		$this->complete(true);
		$this->return();
	}

	/**
	 *异步回调通知
	 *
	 */
	protected function do_notify() {
		/**
		 *验签
		 */
		$this->check($_POST);

		/**
		 *请在这里加上商户的业务逻辑程序代
		 *获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
		 *
		 *如果校验成功必须输出 'success'，页面源码不得包含其他及HTML字符
		 */
		if ('TRADE_FINISHED' == $_POST['trade_status']) {
			//判断该笔订单是否在商户网站中已经做过处理
			//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
			//请务必判断请求时的total_amount与通知时获取的total_fee为一致的
			//如果有做过处理，不执行商户的业务程序

			//注意：
			//退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知

			exit('success'); //由于是即时到账不可退款服务，因此直接返回成功
		}

		if ('TRADE_SUCCESS' == $_POST['trade_status']) {
			//判断该笔订单是否在商户网站中已经做过处理
			//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
			//请务必判断请求时的total_amount与通知时获取的total_fee为一致的
			//如果有做过处理，不执行商户的业务程序
			//注意：
			//付款完成后，支付宝系统发送该交易状态通知
			// app_id
			// $app_id = $_POST['app_id'];

			/**
			 *@since 2019.08.12 异步校验
			 */
			$this->complete(true);

			// 校验通过
			exit('success');
		}
	}
}
