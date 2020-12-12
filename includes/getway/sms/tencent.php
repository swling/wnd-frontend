<?php
namespace Wnd\Getway\Sms;

use Wnd\Component\Qcloud\Sms\SmsSender;
use Wnd\Utility\Wnd_Sms;

/**
 *@since 2019.09.25
 *短信
 */
class Tencent extends Wnd_Sms {

	/**
	 *@since 2019.02.11 发送短信
	 *@param $phone     string 手机号
	 *@param $code      string 验证码
	 *@param $phone     string 短信模板ID
	 */
	public function send() {
		$secret_id  = wnd_get_config('tencent_secretid');
		$secret_key = wnd_get_config('tencent_secretkey');
		$app_id     = wnd_get_config('sms_appid');

		/**
		 *模板参数:
		 *
		 *模板实例：
		 *验证码：{1}，{2}分钟内有效！（如非本人操作，请忽略本短信）
		 *
		 *$params = [$this->code, '10']实际发送：
		 *验证码：XXXX，10分钟内有效！（如非本人操作，请忽略本短信）
		 *即数组具体的元素，与信息模板中的变量一一对应
		 *
		 */
		$params = ($this->code and $this->valid_time) ? [$this->code, $this->valid_time] : [];

		// 指定模板ID单发短信
		$ssender = new SmsSender($secret_id, $secret_key, $app_id);
		$ssender->sendWithParam('86', [$this->phone], $this->template, $params, $this->sign_name);
	}
}
