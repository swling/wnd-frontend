<?php
/**
 *
 *@since 2019/05/04 腾讯云短信发送
 */

if (!defined('ABSPATH')) {
	exit;
}

require WND_PATH . 'components/tencent-sms/src/index.php'; //腾讯短信

use Qcloud\Sms\SmsSingleSender;
// use Qcloud\Sms\SmsMultiSender;
// use Qcloud\Sms\SmsStatusPuller;
// use Qcloud\Sms\SmsMobileStatusPuller;

/**
 *@since 2019.02.11 发送短信
 *@param $phone     string 手机号
 *@param $code      string 验证码
 *@param $phone     string 短信模板ID
 */
function wnd_send_sms($phone, $code, $template) {

    // 短信应用SDK AppID
	$appid = wnd_get_option('wnd','wnd_sms_appid');

    // 短信应用SDK AppKey
	$appkey = wnd_get_option('wnd','wnd_sms_appkey');

    // 签名
	$sign = wnd_get_option('wnd','wnd_sms_sign');

    // 模板参数
	$params = [$code, '10']; // 数组具体的元素个数及顺序和信息模板中须保持一致

    // 指定模板ID单发短信
	try {
		$ssender = new SmsSingleSender($appid, $appkey);
		$result = $ssender->sendWithParam('86', $phone, $template, $params, $sign, '', '');
		$rsp = json_decode($result);
		return $rsp;
	} catch (\Exception $e) {
		return false;
	}
}
