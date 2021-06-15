<?php
namespace Wnd\Getway\Payment;

use Wnd\Component\Payment\Alipay\AlipayPagePay;
use Wnd\Component\Payment\Alipay\AlipayService;
use Wnd\Model\Wnd_Payment;

/**
 *@since 2020.06.19
 *支付宝支付
 *
 *PC支付和wap支付中：product_code 、method 参数有所不同，详情查阅如下
 *@link https://opendocs.alipay.com/apis/api_1/alipay.trade.page.pay
 *@link https://opendocs.alipay.com/apis/api_1/alipay.trade.wap.pay
 *
 *问题排查：
 * - @link https://opendocs.alipay.com/open/common/fr9vsk
 * - @link https://opensupport.alipay.com/support/tools/cloudparse
 */
class Alipay extends Wnd_Payment {
	/**
	 *发起支付
	 *
	 */
	public function build_interface(): string{
		$aliPay = new AlipayPagePay();
		$aliPay->setTotalAmount($this->get_total_amount());
		$aliPay->setOutTradeNo($this->get_out_trade_no());
		$aliPay->setSubject($this->get_subject());

		// 生成表单
		return $aliPay->build();
	}

	/**
	 *回调验签
	 *
	 */
	protected function check($params): bool{
		$aliPay = new AlipayService();
		return $aliPay->rsaCheck($params);
	}

	/**
	 *同步回调通知
	 *
	 */
	protected function check_return(): bool{
		/**
		 *验签
		 */
		$check = $this->check($_GET);
		if (true !== $check) {
			echo ('fail');
		}

		return $check;
	}

	/**
	 *异步回调通知
	 *
	 *@link https://opendocs.alipay.com/open/203/105286/#%E6%9C%8D%E5%8A%A1%E5%99%A8%E5%BC%82%E6%AD%A5%E9%80%9A%E7%9F%A5%E9%A1%B5%E9%9D%A2%E7%89%B9%E6%80%A7
	 */
	protected function check_notify(): bool {
		/**
		 *验签
		 */
		if (true !== $this->check($_POST)) {
			echo ('fail');
			return false;
		}

		/**
		 *请在这里加上商户的业务逻辑程序代
		 *获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
		 *
		 *如果校验成功必须输出 'success'，页面源码不得包含其他及HTML字符
		 *
		 *# 状态TRADE_SUCCESS：
		 * - 通知触发条件是商户签约的产品支持退款功能的前提下，买家付款成功
		 *
		 *# 交易状态TRADE_FINISHED：
		 * - 通知触发条件是商户签约的产品不支持退款功能的前提下，买家付款成功；或者，商户签约的产品支持退款功能的前提下，交易已经成功并且已经超过可退款期限。
		 *
		 *业务处理：
		 * - 判断该笔订单是否在商户网站中已经做过处理
		 * - 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
		 * - 请务必判断请求时的total_amount与通知时获取的total_fee为一致的
		 * - 如果有做过处理，不执行商户的业务程序
		 */
		if ('TRADE_FINISHED' == $_POST['trade_status']) {
			echo ('success');
			return true;
		}

		if ('TRADE_SUCCESS' == $_POST['trade_status']) {
			echo ('success');
			return true;
		}

		/**
		 *交易关闭
		 *
		 */
		if ('TRADE_CLOSED' == $_POST['trade_status']) {
			echo ('success');
			return false;
		}

		/**
		 *其他未知
		 */
		return false;
	}
}
