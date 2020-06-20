<?php
namespace Wnd\Model;

use Exception;
use Wnd\Component\Alipay\AlipayRefunder;

/**
 *@since 2020.06.09
 *支付宝退款
 *@link https://opendocs.alipay.com/apis/api_1/alipay.trade.refund
 */
class Wnd_Refunder_Alipay extends Wnd_Refunder {

	/**
	 *需要根据平台退款响应，设定退款状态，及平台响应数据包
	 *
	 *$this->out_trade_no
	 *$this->out_request_no
	 *$this->refund_amount
	 *$this->total_amount
	 */
	protected function do_refund() {
		try {
			/**
			 * - 部分退款：以退款次数作为标识
			 * - 获取支付宝响应
			 */
			$alipay = new AlipayRefunder();
			$alipay->set_out_trade_no($this->out_trade_no);
			if ($this->refund_amount != $this->total_amount) {
				$alipay->set_out_request_no($this->out_request_no);
			}
			$alipay->set_refund_amount($this->refund_amount);

			$response       = $alipay->do_refund();
			$this->response = $response['alipay_trade_refund_response'];

			// 判断退款结果
			$code        = $this->response['code'];
			$fund_change = $this->response['fund_change'] ?? '';
			if (10000 != $code or 'N' == $fund_change) {
				throw new Exception($this->response['sub_msg'] ?? __('退款失败', 'wnd'));
			}
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
	}
}
