<?php
namespace Wnd\Endpoint;

use Exception;
use Wnd\Getway\Wnd_Payment;
use Wnd\Model\Wnd_Transaction;

/**
 * 支付同步回调
 *
 * 注意事项：
 * - 同步回调不可以作为最终的支付结果确认依据，必须以异步通知为准。
 * - 同步回调中不得更新站内订单状态，以免出现用户未支付成功但站内订单已更新的情况。
 *
 * @since 0.9.91
 */
abstract class Wnd_Payment_Return extends Wnd_Endpoint {

	// 响应类型
	protected $content_type = 'txt';

	/**
	 * 响应操作
	 */
	protected function do() {
		/**
		 * 同步回调仅解析订单信息并跳转，不得更新订单状态
		 */
		try {
			$transaction = $this->get_transaction();
			$payment     = Wnd_Payment::get_instance($transaction);
			$payment->return();
		} catch (Exception $e) {
			wnd_error_payment_log('【支付同步回调错误】: ' . $e->getMessage() . ' 序列化后的数据 : ' . json_encode($_POST, JSON_UNESCAPED_UNICODE));
			http_response_code(500);
			exit($e->getMessage());
		}
	}

	/**
	 * 根据交易订单解析站内交易ID，并查询记录
	 */
	public function get_transaction(): Wnd_Transaction {
		$out_trade_no   = $this->parse_transaction_id();
		$transaction_id = Wnd_Payment::parse_out_trade_no($out_trade_no);
		$transaction    = Wnd_Transaction::get_instance('', $transaction_id);
		return $transaction;
	}

	/**
	 * 解析返回站内交易订单对象实例化
	 */
	abstract protected function parse_transaction_id(): string;

}
