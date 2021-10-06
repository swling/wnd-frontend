<?php
namespace Wnd\Getway\Payment;

use Wnd\Component\Payment\WeChat\Native;
use Wnd\Component\Payment\WeChat\Verify;
use Wnd\Getway\Wnd_Payment;
use Wnd\Model\Wnd_Transaction;

/**
 * 微信支付
 * @since 0.9.38
 */
class WeChat_Native extends Wnd_Payment {

	private static function get_config(): array{
		return [
			'mchid'       => wnd_get_config('wechat_mchid'),
			'appid'       => wnd_get_config('wechat_appid'),
			'apikey'      => wnd_get_config('wechat_apikey'),
			'private_key' => "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap(wnd_get_config('wechat_private_key'), 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----",
			'apicert_sn'  => wnd_get_config('wechat_apicert_sn'),
		];
	}

	/**
	 * 发起支付
	 *
	 */
	public function build_interface(): string{
		extract(static::get_config());

		$pay = new Native($mchid, $appid, $apicert_sn, $private_key);

		$pay->setTotalAmount($this->total_amount);
		$pay->setOutTradeNo($this->out_trade_no);
		$pay->setSubject($this->subject);
		$pay->setNotifyUrl(wnd_get_endpoint_url('wnd_verify_wechat'));
		$pay->generateParams();

		/**
		 * 获取响应提取支付链接信息，生成二维码
		 * Ajax定期查询订单是否已经完成支付，以便下一步操作
		 */
		return $pay->buildInterface() . static::build_ajax_check_script($this->transaction->get_transaction_id());
	}

	/**
	 * 根据交易订单解析站内交易ID，并查询记录
	 * - 微信支付V3采用了加密数据，获得订单信息之前，需要解密报文
	 * - 因而在解密订单信息的同时，也完成了验签
	 * - 故此本类中的 verify_payment() 方法无需额外处理
	 */
	public static function parse_transaction(): Wnd_Transaction{
		extract(static::get_config());
		$verify = new verify($mchid, $apikey, $apicert_sn, $private_key);

		// 验签
		if (!$verify->validate()) {
			wnd_error_payment_log('【微信支付】验签失败');
			http_response_code(401);
			echo json_encode([
				'code'    => 'ERROR',
				'message' => '验签失败',
			]);
			exit;
		}

		// 解密微信支付报文
		$result = $verify->notify();
		if (!$result) {
			wnd_error_payment_log('【微信支付】解密报文失败');
			http_response_code(401);
			echo json_encode([
				'code'    => 'ERROR',
				'message' => '解密报文失败',
			]);
			exit;
		}

		// 交易状态检测
		if ('SUCCESS' != $result['trade_state']) {
			wnd_error_payment_log('【微信支付】校验成功，但支付状态异常' . $result['trade_state']);
			echo json_encode([
				'code'    => 'SUCCESS',
				'message' => '',
			]);
			exit;
		}

		/**
		 * 支付成功，完成你的逻辑
		 * 例如连接数据库，获取付款金额$result['amount']['total']，获取订单号$result['out_trade_no']修改数据库中的订单状态等;
		 * 订单总金额，单位为分：$result['amount']['total']
		 * 用户支付金额，单位为分：$result['amount']['payer_total']
		 * 商户订单号：$result['out_trade_no']
		 * 微信支付订单号：$result['transaction_id']
		 * 银行类型：$result['bank_type']
		 * 支付完成时间：$result['success_time'] 格式为YYYY-MM-DDTHH:mm:ss+TIMEZONE
		 * 用户标识：$result['payer']['openid']
		 * 交易状态：$result['trade_state']
		 *
		 * @see https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_1_5.shtml
		 */
		$out_trade_no   = $result['out_trade_no'] ?? '';
		$total_amount   = $result['amount']['total'] ?? 0.00;
		$transaction_id = static::parse_out_trade_no($out_trade_no);
		$transaction    = Wnd_Transaction::get_instance('', $transaction_id);

		if ($total_amount != $transaction->get_total_amount() * 100) {
			wnd_error_payment_log('【微信支付】金额不匹配');
			http_response_code(401);
			echo json_encode([
				'code'    => 'ERROR',
				'message' => 'pay error',
			]);
			exit;
		}

		echo json_encode([
			'code'    => 'SUCCESS',
			'message' => '支付成功',
		]);

		return $transaction;
	}

	/**
	 * 验证支付
	 */
	public function verify_payment() {
		return true;
	}
}
