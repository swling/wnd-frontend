<?php
namespace Wnd\Getway\Sms;

use Wnd\Getway\Wnd_Cloud_Client;
use Wnd\Getway\Wnd_Sms;

/**
 * 短信
 * 参数配置
 * - 公共参数 @link https://help.aliyun.com/document_detail/101341.html
 * - 发送短信 @link https://help.aliyun.com/document_detail/101414.html
 *
 * @since 2019.09.25
 */
class Aliyun extends Wnd_Sms {
	/**
	 * 发送短信
	 */
	public function send() {
		$params                 = [];
		$params['PhoneNumbers'] = $this->phone;
		$params['SignName']     = $this->sign_name;
		$params['TemplateCode'] = $this->template;

		// 阿里云短信模板仅支持一个变量
		if ($this->code) {
			$params['TemplateParam'] = json_encode(['code' => $this->code], JSON_UNESCAPED_UNICODE);
		}

		$client  = Wnd_Cloud_Client::get_instance('Aliyun');
		$request = $client->request(
			'https://dysmsapi.aliyuncs.com',
			[
				'body' => array_merge($params, [
					'RegionId' => 'cn-hangzhou',
					'Action'   => 'SendSms',
					'Version'  => '2017-05-25',
				]),
			]
		);
	}
}
