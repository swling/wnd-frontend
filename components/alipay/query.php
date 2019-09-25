<?php
/**
 *@since 2019.03.02
 *根据商户订单号或者支付宝交易号查询支付状态
 *@link https://docs.open.alipay.com/api_1/alipay.trade.query
 */
// require "../../../../../wp-load.php";
/*** 请填写以下配置信息 ***/

$out_trade_no = $_REQUEST['out_trade_no'] ?? ''; //要查询的商户订单号。注：商户订单号与支付宝交易号不能同时为空
$trade_no = $_REQUEST['trade_no'] ?? ''; //要查询的支付宝交易号。注：商户订单号与支付宝交易号不能同时为空
if (!$out_trade_no and !$trade_no) {

	echo '商户订单号和支付宝交易号均为空！';
	return;
}

/*** 配置结束 ***/

/**
 *@since 2019.03.02
 *构建查询参数，并输出查询结果
 */
require dirname(__FILE__) . '/config.php';
$aliPay = new AlipayService();
$aliPay->setAppid($config['app_id']);
$aliPay->setRsaPrivateKey($config['merchant_private_key']);
$aliPay->setOutTradeNo($out_trade_no);
$aliPay->setTradeNo($trade_no);

$result = $aliPay->doQuery();
if ($result['alipay_trade_query_response']['code'] != '10000') {
	echo $result['alipay_trade_query_response']['msg'] . '：' . $result['alipay_trade_query_response']['sub_code'] . ' ' . $result['alipay_trade_query_response']['sub_msg'];
} else {
	echo $result['alipay_trade_query_response']['trade_status'];
}

/**
 *@since 2019.03.02 封装查询类
 *
 */
class AlipayService {
	protected $appId;
	protected $charset;
	//私钥值
	protected $rsaPrivateKey;
	protected $out_trade_no;
	protected $trade_no;

	public function __construct() {
		$this->charset = 'utf-8';
	}

	public function setAppid($appid) {
		$this->appId = $appid;
	}

	public function setRsaPrivateKey($saPrivateKey) {
		$this->rsaPrivateKey = $saPrivateKey;
	}

	public function setOutTradeNo($out_trade_no) {
		$this->outTradeNo = $out_trade_no;
	}

	public function setTradeNo($trade_no) {
		$this->tradeNo = $trade_no;
	}

	/**
	 * 发起查询
	 * @return array
	 */
	public function doQuery() {
		//请求参数
		$requestConfigs = array(
			'out_trade_no' => $this->outTradeNo,
			'trade_no' => $this->tradeNo,
		);
		$commonConfigs = array(
			//公共参数
			'app_id' => $this->appId,
			'method' => 'alipay.trade.query', //接口名称
			'format' => 'JSON',
			// 'return_url' => $this->returnUrl,
			'charset' => $this->charset,
			'sign_type' => 'RSA2',
			'timestamp' => date('Y-m-d H:i:s'),
			'version' => '1.0',
			// 'notify_url' => $this->notifyUrl,
			'biz_content' => json_encode($requestConfigs),
		);
		$commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
		$result = $this->curlPost('https://openapi.alipay.com/gateway.do', $commonConfigs);
		return json_decode($result, true);
	}

	public function generateSign($params, $signType = "RSA") {
		return $this->sign($this->getSignContent($params), $signType);
	}

	protected function sign($data, $signType = "RSA") {
		$priKey = $this->rsaPrivateKey;
		$res = "-----BEGIN RSA PRIVATE KEY-----\n" .
		wordwrap($priKey, 64, "\n", true) .
			"\n-----END RSA PRIVATE KEY-----";
		($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');
		if ("RSA2" == $signType) {
			openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256); //OPENSSL_ALGO_SHA256是php5.4.8以上版本才支持
		} else {
			openssl_sign($data, $sign, $res);
		}
		$sign = base64_encode($sign);
		return $sign;
	}

	/**
	 * 校验$value是否非空
	 *  if not set ,return true;
	 *    if is null , return true;
	 **/
	protected function checkEmpty($value) {
		if (!isset($value)) {
			return true;
		}

		if ($value === null) {
			return true;
		}

		if (trim($value) === "") {
			return true;
		}

		return false;
	}

	public function getSignContent($params) {
		ksort($params);
		$stringToBeSigned = "";
		$i = 0;
		foreach ($params as $k => $v) {
			if (false === $this->checkEmpty($v) and "@" != substr($v, 0, 1)) {
				// 转换成目标字符集
				$v = $this->characet($v, $this->charset);
				if ($i == 0) {
					$stringToBeSigned .= "$k" . "=" . "$v";
				} else {
					$stringToBeSigned .= "&" . "$k" . "=" . "$v";
				}
				$i++;
			}
		}

		unset($k, $v);
		return $stringToBeSigned;
	}

	/**
	 * 转换字符集编码
	 * @param $data
	 * @param $targetCharset
	 * @return string
	 */
	function characet($data, $targetCharset) {
		if (!empty($data)) {
			$fileType = $this->charset;
			if (strcasecmp($fileType, $targetCharset) != 0) {
				$data = mb_convert_encoding($data, $targetCharset, $fileType);
			}
		}
		return $data;
	}

	public function curlPost($url = '', $postData = '', $options = array()) {
		if (is_array($postData)) {
			$postData = http_build_query($postData);
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('content-type: application/x-www-form-urlencoded;charset=' . $this->charset));
		if (!empty($options)) {
			curl_setopt_array($ch, $options);
		}
		//https请求 不验证证书和host
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
}