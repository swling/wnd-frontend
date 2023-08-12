<?php
namespace Wnd\Getway\Payment;

use Exception;
use Wnd\Component\Payment\WeChat\Native;
use Wnd\Component\Payment\WeChat\Verify;
use Wnd\Getway\Wnd_Payment;
use Wnd\Model\Wnd_Transaction;

/**
 * 微信支付
 * @since 0.9.38
 */
class WeChat_Native extends Wnd_Payment {

	protected static function get_config(): array{
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
	public function build_interface(): string {
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
		$payment_id        = $this->transaction->get_transaction_id();
		$qr_code           = $pay->buildInterface();
		$payment_interface = '<div id="wechat-qrcode" style="height:250px;"></div><script>wnd_qrcode("#wechat-qrcode", "' . $qr_code . '", 250)</script>';
		$title             = '微信支付';

		return static::build_payment_interface($payment_id, $payment_interface, $title);
	}

	/**
	 * 根据交易订单解析站内交易ID，并查询记录
	 * - 微信支付V3采用了加密数据，获得订单信息之前，需要解密报文
	 * - 因而在解密订单信息的同时，也完成了验签
	 * - 故此本类中的 verify_payment() 方法无需额外处理
	 */
	public static function verify_payment(): Wnd_Transaction {
		$result = static::handle_verify();

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
			static::handle_verify_failed('【微信支付】金额不匹配');
		}

		return $transaction;
	}

	/**
	 * 处理验签
	 */
	private static function handle_verify(): array{
		try {
			return static::verify();
		} catch (Exception $e) {
			static::handle_verify_failed($e->getMessage());
		}
	}

	/**
	 * 处理验签失败
	 * - 若验签失败，需要设定 HTTP 状态码
	 * - 若状态码为 200 无论响应何种数据，微信支付均认为验签完成，将不再发送回调消息
	 *
	 * @see https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_1_5.shtml
	 */
	private static function handle_verify_failed(string $message) {
		$msg = json_encode(['code' => 'FAIL', 'message' => $message], JSON_UNESCAPED_UNICODE);
		throw new Exception($msg);
	}

	/**
	 * 验签并解密报文
	 */
	private static function verify(): array{
		extract(static::get_config());
		$verify = new verify($mchid, $apikey, $apicert_sn, $private_key);

		// 缓存平台证书
		$transient_key       = 'wnd_wechat_certificates';
		$wechat_certificates = get_transient($transient_key);
		if (!$wechat_certificates) {
			$wechat_certificates = $verify->getRemoteWechatCertificates();
			set_transient($transient_key, $wechat_certificates, 3600 * 12);
		}
		$verify->setWechatCertificates($wechat_certificates);

		// 验签
		if (!$verify->validate()) {
			throw new Exception('验签失败');
		}

		// 解密微信支付报文
		$result = $verify->notify();
		if (!$result) {
			throw new Exception('解密报文失败');
		}

		// 交易状态检测
		if ('SUCCESS' != $result['trade_state']) {
			throw new Exception('验签成功，但支付状态异常' . $result['trade_state']);
		}

		return $result;
	}

}
