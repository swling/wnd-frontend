<?php

use Exception;
use Wnd\Utility\Wnd_Sms;

/**
 *发送短信
 *
 */
try {
	$sms = Wnd_Sms::get_instance();

	// 设置手机号（必须）
	$sms->set_phone($phone);

	// 设置短信模板（必须）
	$sms->set_template($template);

	// 设置验证码及有效时间（可选，与模板中的对应的变量匹配）
	$sms->set_code($code);
	$sms->set_valid_time($valid_time);

	$sms->send();
} catch (Exception $e) {
	throw new Exception($e->getMessage());
}

/**
 *验证码默认有效时间均为600秒 @see Wnd\Model\Wnd_Auth_Phone
 *
 *1、腾讯验证码短信模板参考：
 *验证码：{1}，{2}分钟内有效！（如非本人操作，请忽略本短信）
 *
 *2、阿里验证码短信模板参考：
 *验证码：${code}，${valid_time}分钟内有效！（如非本人操作，请忽略本短信）
 *
 *注：文字信息可根据情况自行修改，变量顺序及名称请保持一致
 */
