<?php
namespace Wnd\Getway\Payment;

use Exception;
use Wnd\Model\Wnd_Payment;

/**
 *@since 0.9.29
 *PayPal 支付
 * - paypal 支付流程显著区别于支付宝及微信支付
 * - 由于流程上的区别，本网关重写了 $this->verify_transaction 方法
 * - @link https://developer.paypal.com/docs/api/overview/
 *
 * 参考链接
 * - @link https://cloud.tencent.com/developer/article/1693706 （部分代码有误
 * - @link https://developer.paypal.com/docs/api/orders/v2/#orders）
 *
 *【注意事项】
 * @date 2021.05.15 PayPal 尚未完成货币转换，目前仅完成支付接口引入，并统一按美元结算
 */
class PayPal extends Wnd_Payment {

	private $client_id;
	private $client_secret;
	private $capture_token;
	private $api_base;
	private $currency_code;

	/**
	 *继承父类构造，及 PayPal 基础环境构造
	 */
	public function __construct($payment_gateway) {
		parent::__construct($payment_gateway);
		$this->api_base      = wnd_get_config('payment_sandbox') ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
		$this->client_id     = wnd_get_config('paypal_clientid');
		$this->client_secret = wnd_get_config('paypal_secret');
		$this->currency_code = 'USD';
	}

	/**
	 *PayPal 货币代码如：'USD'，'CNY'
	 */
	public function set_currency_code(string $currency_code) {
		$this->currency_code = $currency_code;
	}

	/**
	 *发起支付
	 *
	 */
	public function build_interface(): string {
		return '<h1 class="title">……' . __('请稍后', 'wnd') . '……</h1><script>window.location.href = "' . $this->create_order() . '";</script>';
	}

	/**
	 *PayPal 返回的是订单 Token，需通过 token 完成 capture order，并从PayPal 响应数据中解析出 reference_id 即为 out_trade_no
	 */
	public function set_capture_token(string $capture_token) {
		$this->capture_token = $capture_token;
	}

	/**
	 *验证支付 paypal 使用 $this->capture_order 替代本方法
	 *
	 *@return bool
	 */
	protected function verify_transaction(): bool{
		$this->capture_order();
		return true;
	}

	/**
	 *同步回调通知（paypal 使用 $this->capture_order 替代本方法，本方法不会被调用，但抽象方法必须完成故此直接返回 false）
	 *
	 */
	protected function check_return(): bool {
		return false;
	}

	/**
	 *异步回调通知（paypal 使用 $this->capture_order 替代本方法，本方法不会被调用，但抽象方法必须完成故此直接返回 false）
	 *
	 */
	protected function check_notify(): bool {
		return false;
	}

	/**
	 * 获取access token
	 * @link https://developer.paypal.com/docs/api/get-an-access-token-curl/
	 * 		Token 具有一定时效性，出于性能考虑，避免重复请求，故缓存至瞬态 "set_transient"
	 * 		当开启对象缓存后，瞬态将存储在对象缓存中，否则存储在 option
	 */
	private function get_access_token(): string{
		// 瞬态缓存
		$access_token = get_transient('paypal_access_token');
		if ($access_token) {
			return $access_token;
		}

		// API 请求
		$url = $this->api_base . '/v1/oauth2/token';
		$ch  = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		// curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER,
			[
				'Content-Type: application/json',
				'Accept-Language: en_US',
			]
		);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERPWD, $this->client_id . ':' . $this->client_secret);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
		$result = curl_exec($ch);
		curl_close($ch);
		// echo $result;

		$result       = json_decode($result);
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

	/**
	 *创建订单
	 *@link https://developer.paypal.com/docs/api/orders/v2/#orders_create
	 */
	private function create_order(): string{
		$url          = $this->api_base . '/v2/checkout/orders';
		$access_token = $this->get_access_token();
		$postfilds    = [
			'intent'              => 'CAPTURE',
			'purchase_units'      => [
				[
					'reference_id' => $this->get_out_trade_no(),
					'amount'       => [
						'value'         => $this->get_total_amount(),
						'currency_code' => $this->currency_code,
					],
					'description'  => $this->get_subject(),
				],
			],
			'application_context' => [
				'cancel_url' => home_url(),
				'return_url' => wnd_get_endpoint_url('wnd_verify_paypal'),
			],
		];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		// curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Authorization: Bearer ' . $access_token,
			'Accept: application/json',
			'Content-Type: application/json',
		));
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postfilds));
		$result = curl_exec($ch);
		curl_close($ch);

		$result = json_decode($result);
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
	 *捕获订单：需要考虑重复捕获的判断
	 *@link https://developer.paypal.com/docs/api/orders/v2/#orders_capture
	 */
	private function capture_order() {
		$url          = $this->api_base . '/v2/checkout/orders/' . $this->capture_token . '/capture';
		$access_token = $this->get_access_token();
		$ch           = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		// curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER,
			[
				'Authorization: Bearer ' . $access_token,
				'Content-Type: application/json',
			]
		);
		// curl_setopt($ch, CURLOPT_POSTFIELDS, $postfilds);
		$result = curl_exec($ch);
		curl_close($ch);

		// 状态不为 COMPLETED 且原因不是“已支付”：抛出异常
		$result = json_decode($result);
		$status = $result->status ?? '';
		$issue  = $result->details[0]->issue ?? '';
		if ('COMPLETED' !== $status and 'ORDER_ALREADY_CAPTURED' != $issue) {
			throw new Exception('Capture order failed');
		}

		// 订单扣款完成根据响应，设置 $out_trade_no
		$out_trade_no = $result->purchase_units[0]->reference_id ?? 0;
		$this->set_out_trade_no($out_trade_no);

		// 核查订单金额
		$amount = $result->purchase_units[0]->payments->captures[0]->amount;
		if ($amount->value != $this->get_total_amount()) {
			throw new Exception('Amount does not match ');
		}

		return $result;
	}
}
