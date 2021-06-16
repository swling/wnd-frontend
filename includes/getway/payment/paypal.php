<?php
namespace Wnd\Getway\Payment;

use Exception;
use Wnd\Getway\Wnd_Payment;
use Wnd\Model\Wnd_Transaction;

/**
 * PayPal 支付
 * - paypal 支付流程显著区别于支付宝及微信支付
 * - 由于流程上的区别，本网关重写了 $this->verify_Payment 方法
 * - @link https://developer.paypal.com/docs/api/overview/
 * 参考链接
 * - @link https://cloud.tencent.com/developer/article/1693706 （部分代码有误)
 * - @link https://developer.paypal.com/docs/api/orders/v2/#orders
 * - @link https://github.com/paypal/Checkout-PHP-SDK
 *
 * ### 注意事项及待解决问题
 * @date 2021.05.15 PayPal 尚未完成货币转换，目前仅完成支付接口引入，并统一按美元结算
 * @date 2021.06.16
 * - 尚未测试异步通知（需在PayPal后台添加 webhooks）
 * - 不同于国内支付平台，PayPal 对同一个站内订单号可重复创建，因此可能存在对同一个站内订单重复付款的情况
 *
 * @since 0.9.29
 */
class PayPal extends Wnd_Payment {

	private $currency_code = 'USD';

	public static function get_config() {
		return [
			'api_base'      => wnd_get_config('payment_sandbox') ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com',
			'client_id'     => wnd_get_config('paypal_clientid'),
			'client_secret' => wnd_get_config('paypal_secret'),
			'currency_code' => 'USD',
		];
	}

	/**
	 * PayPal 货币代码如：'USD'，'CNY'
	 */
	public function set_currency_code(string $currency_code) {
		$this->currency_code = $currency_code;
	}

	/**
	 * 发起支付
	 *
	 */
	public function build_interface(): string {
		return '<h1 class="title">……' . __('请稍后', 'wnd') . '……</h1><script>window.location.href = "' . $this->create_order() . '";</script>';
	}

	/**
	 * 根据交易订单解析站内交易ID，并查询记录
	 */
	public static function parse_transaction(): Wnd_Transaction{
		$capture_token = $_REQUEST['token'] ?? '';
		return static::capture_order($capture_token);
	}

	/**
	 * Paypal 使用 $this->capture_order 替代本方法
	 *
	 */
	public function verify_payment() {
		return false;
	}

	/**
	 * 创建订单
	 * @link https://developer.paypal.com/docs/api/orders/v2/#orders_create
	 */
	private function create_order(): string{
		$url          = static::get_config()['api_base'] . '/v2/checkout/orders';
		$access_token = $this->get_access_token();
		$postfilds    = [
			'intent'              => 'CAPTURE',
			'purchase_units'      => [
				[
					'reference_id' => $this->out_trade_no,
					'amount'       => [
						'value'         => $this->total_amount,
						'currency_code' => $this->currency_code,
					],
					'description'  => $this->subject,
				],
			],
			'application_context' => [
				'cancel_url' => home_url(),
				'return_url' => wnd_get_endpoint_url('wnd_verify_paypal'),
			],
		];
		$request = wp_remote_request($url,
			[
				'method'  => 'POST',
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $access_token,
				],
				'body'    => json_encode($postfilds),
			]
		);
		if (is_wp_error($request)) {
			throw new Exception($request->get_error_message());
		}

		$result = json_decode($request['body']);
		if ('CREATED' !== $result->status) {
			throw new Exception('Crete order failed');
		}

		$approve_link = '';
		foreach ($result->links as $link) {
			if ('approve' == $link->rel) {
				$approve_link = $link->href;
				break;
			}
		};

		return $approve_link;
	}

	/**
	 * 捕获订单：需要考虑重复捕获的判断
	 * @link https://developer.paypal.com/docs/api/orders/v2/#orders_capture
	 */
	private static function capture_order(string $capture_token): Wnd_Transaction{
		$url          = static::get_config()['api_base'] . '/v2/checkout/orders/' . $capture_token . '/capture';
		$access_token = static::get_access_token();
		$request      = wp_remote_request($url,
			[
				'method'  => 'POST',
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $access_token,
				],
				'body'    => '',
			]
		);
		if (is_wp_error($request)) {
			throw new Exception($request->get_error_message());
		}

		// 状态不为 COMPLETED 且原因不是“已支付”：抛出异常
		$result = json_decode($request['body']);
		$status = $result->status ?? '';
		$issue  = $result->details[0]->issue ?? '';
		if ('COMPLETED' !== $status and 'ORDER_ALREADY_CAPTURED' != $issue) {
			throw new Exception('Capture order failed');
		}

		// 订单扣款完成，根据响应查询站内交易记录实例
		$out_trade_no   = $result->purchase_units[0]->reference_id ?? 0;
		$transaction_id = static::parse_out_trade_no($out_trade_no);
		$transaction    = Wnd_Transaction::get_instance('', $transaction_id);

		// 核查订单金额
		$amount = $result->purchase_units[0]->payments->captures[0]->amount;
		if ($amount->value != $transaction->get_total_amount()) {
			throw new Exception('Amount does not match ');
		}

		return $transaction;
	}

	/**
	 * 获取access token
	 * 		Token 具有一定时效性，出于性能考虑，避免重复请求，故缓存至瞬态 "set_transient"
	 * 		当开启对象缓存后，瞬态将存储在对象缓存中，否则存储在 option
	 * @link https://developer.paypal.com/docs/api/get-an-access-token-curl/
	 */
	private static function get_access_token(): string{
		// 瞬态缓存
		$access_token = get_transient('paypal_access_token');
		if ($access_token) {
			return $access_token;
		}

		$config = static::get_config();

		// API 请求
		$url     = $config['api_base'] . '/v1/oauth2/token';
		$request = wp_remote_request($url,
			[
				'method'  => 'POST',
				'headers' => [
					'Content-Type'  => 'application/x-www-form-urlencoded',
					'Authorization' => 'Basic ' . base64_encode($config['client_id'] . ':' . $config['client_secret']),
				],
				'body'    => ['grant_type' => 'client_credentials'],
			]
		);
		if (is_wp_error($request)) {
			throw new Exception($request->get_error_message());
		}
		$result       = json_decode($request['body']);
		$access_token = $result->access_token ?? '';
		if (!$access_token) {
			throw new Exception('Get access token failed');
		}

		// 设置瞬态缓存
		if ($result->expires_in > 600) {
			set_transient('paypal_access_token', $access_token, $result->expires_in - 60);
		}

		return $access_token;
	}
}
