<?php

namespace Wnd\Component\Qcloud\Sms;

use Exception;
use Wnd\Component\Qcloud\SignatureTrait;

/**
 * 单发短信类
 *
 */
class SmsSender {

	// 短信 APP ID
	protected $app_id;

	// 引入 API 签名及请求 Trait
	use SignatureTrait;

	/**
	 *构造
	 */
	public function __construct($secret_id, $secret_key, $app_id) {
		$this->endpoint   = 'sms.tencentcloudapi.com';
		$this->secret_id  = $secret_id;
		$this->secret_key = $secret_key;
		$this->app_id     = $app_id;
	}

	/**
	 *发送含参模板短信
	 */
	public function sendWithParam($nation_code, $phone_numbers, $templ_id, $params, $sign) {
		$this->params = [
			// 公共参数
			'Action'      => 'SendSms',
			'Timestamp'   => time(),
			'Nonce'       => wnd_random_code(6, true),
			'Version'     => '2019-07-11',
			'SecretId'    => $this->secret_id,

			// 短信参数
			'TemplateID'  => $templ_id,
			'SmsSdkAppid' => $this->app_id,
			'Sign'        => $sign,
		];

		// 手机号码参数
		foreach ($phone_numbers as $key => $phone_number) {
			$this->params['PhoneNumberSet.' . $key] = '+' . $nation_code . $phone_number;
		}

		// 模板传参
		foreach ($params as $key => $param) {
			$this->params['TemplateParamSet.' . $key] = $param;
		}

		// 发起请求
		$request = $this->request();

		// 核查响应
		if ($request['Response']['Error'] ?? false) {
			throw new Exception($request['Response']['Error']['Code'] . ':' . $request['Response']['Error']['Message']);
		}

		if ($request['Response']['SendStatusSet'][0]['Code'] != 'Ok') {
			throw new Exception($request['Response']['SendStatusSet'][0]['Message']);
		}
	}
}
