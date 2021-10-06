<?php
namespace Wnd\Component\Payment\WeChat;

use Wnd\Component\Payment\PaymentBuilder;
use Wnd\Component\Requests\Requests;

/**
 * 微信支付 V3
 * @link https://pay.weixin.qq.com/wiki/doc/apiv3/apis/index.shtml
 */
abstract class PayBuilder implements PaymentBuilder {
	protected $mchID;
	protected $appID;

	protected $totalFee;
	protected $outTradeNo;
	protected $subject;
	protected $notifyUrl;

	protected $gateWay    = '';
	protected $method     = 'POST';
	protected $ReqHeaders = [];
	protected $ReqBody    = '';

	public function __construct(string $mchID, string $appID, string $serialNumber, string $privateKey) {
		$this->mchID     = $mchID;
		$this->appID     = $appID;
		$this->signature = new Signature($mchID, $serialNumber, $privateKey);
	}

	public function setTotalAmount(float $totalFee) {
		$this->totalFee = floatval($totalFee);
	}

	public function setOutTradeNo(string $outTradeNo) {
		$this->outTradeNo = $outTradeNo;
	}

	public function setSubject(string $subject) {
		$this->subject = $subject;
	}

	public function setNotifyUrl(string $notifyUrl) {
		$this->notifyUrl = $notifyUrl;
	}

	/**
	 * 发起客户端支付请求
	 *
	 * @return string 返回构造支付请求的Html，如自动提交的表单或支付二维码
	 */
	abstract public function buildInterface(): string;

	/**
	 * 签名并构造完整的请求参数
	 * @return string
	 */
	public function generateParams() {
		$reqParams = [
			'appid'        => $this->appID, //公众号或移动应用appID
			'mchid'        => $this->mchID, //商户号
			'description'  => $this->subject, //商品描述
			'attach'       => 'pay', //附加数据，在查询API和支付通知中原样返回，可作为自定义参数使用
			'notify_url'   => $this->notifyUrl, //通知URL必须为直接可访问的URL，不允许携带查询串。
			'out_trade_no' => $this->outTradeNo, //商户系统内部订单号，只能是数字、大小写字母_-*且在同一个商户号下唯一，详见【商户订单号】。特殊规则：最小字符长度为6
			'amount'       => [
				'total'    => floatval($this->totalFee) * 100, //订单总金额，单位为分
				'currency' => 'CNY', //CNY：人民币，境内商户号仅支持人民币
			],
			'scene_info'   => [ //支付场景描述
				'payer_client_ip' => '127.0.0.1', //调用微信支付API的机器IP
			],
		];

		$this->ReqHeaders = [
			'Authorization' => $this->signature->getAuthStr($this->gateWay, $this->method, $reqParams),
			'Content-Type'  => 'application/json',
			'Accept'        => 'application/json',
			'User-Agent'    => $_SERVER['HTTP_USER_AGENT'],
		];

		$this->ReqBody = json_encode($reqParams);
	}

	/**
	 * 拆分为独立方法，以便某些情况子类可重写覆盖
	 */
	protected function excuteRequest(): array{
		$request = new Requests;
		return $request->request(
			$this->gateWay,
			[
				'method'  => $this->method,
				'body'    => $this->ReqBody,
				'headers' => $this->ReqHeaders,
				'timeout' => 10,
			]
		);
	}
}
