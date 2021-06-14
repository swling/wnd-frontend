<?php
namespace Wnd\Getway\Sms;

use Exception;
use Wnd\Getway\Wnd_Cloud_Client;
use Wnd\Getway\Wnd_Sms;

/**
 * 短信
 * @since 2019.09.25
 */
class Qcloud extends Wnd_Sms {

	/**
	 * @since 2019.02.11 发送短信
	 *
	 * @param $phone string 手机号
	 * @param $code  string 验证码
	 * @param $phone string 短信模板ID
	 */
	public function send() {
		/**
		 * 模板参数:
		 *
		 * 模板实例：
		 * 验证码：{1}，{2}分钟内有效！（如非本人操作，请忽略本短信）
		 *
		 * $params = [$this->code, '10']实际发送：
		 * 验证码：XXXX，10分钟内有效！（如非本人操作，请忽略本短信）
		 * 即数组具体的元素，与信息模板中的变量一一对应
		 *
		 */
		$params = ($this->code and $this->valid_time) ? [(string) $this->code, (string) $this->valid_time] : [];

		// 指定模板ID单发短信
		static::sendWithParam('86', [$this->phone], $this->template, $params, $this->sign_name);
	}

	/**
	 * 发送含参模板短信
	 * 使用签名方法 v1
	 * @link https://cloud.tencent.com/document/product/382/38778
	 */
	protected static function sendWithParam($nation_code, $phone_numbers, $templ_id, $templ_params, $sign_name) {
		$url    = 'https://sms.tencentcloudapi.com';
		$params = [
			'TemplateID'  => $templ_id,
			'SmsSdkAppid' => wnd_get_config('sms_appid'),
			'Sign'        => $sign_name,
		];

		// 手机号码参数
		foreach ($phone_numbers as $phone_number) {
			$params['PhoneNumberSet'][] = '+' . $nation_code . $phone_number;
		}

		// 模板传参
		$params['TemplateParamSet'] = $templ_params;

		$args = [
			'headers' => [
				'X-TC-Action'  => 'SendSms',
				'X-TC-Version' => '2019-07-11',
			],
			'body'    => json_encode($params),
		];

		// 发起请求
		$action  = Wnd_Cloud_Client::get_instance('Qcloud');
		$request = $action->request($url, $args);

		// 核查响应
		if ($request['Response']['Error'] ?? false) {
			throw new Exception($request['Response']['Error']['Code'] . ':' . $request['Response']['Error']['Message']);
		}

		if ($request['Response']['SendStatusSet'][0]['Code'] != 'Ok') {
			throw new Exception($request['Response']['SendStatusSet'][0]['Message']);
		}
	}
}
