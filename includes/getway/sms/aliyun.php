<?php
namespace Wnd\Getway\Sms;

use Exception;
use Wnd\Component\Aliyun\Sms\SignatureHelper;
use Wnd\Utility\Wnd_Sms;

/**
 *@since 2019.09.25
 *短信
 *
 *参数配置
 * - 公共参数 @link https://help.aliyun.com/document_detail/101341.html
 * - 发送短信 @link https://help.aliyun.com/document_detail/101414.html
 */
class Aliyun extends Wnd_Sms {
	/**
	 * 发送短信
	 */
	public function send() {
		$secret_id  = wnd_get_config('aliyun_secretid');
		$secret_key = wnd_get_config('aliyun_secretkey');

		$params                 = [];
		$params['PhoneNumbers'] = $this->phone;
		$params['SignName']     = $this->sign_name;
		$params['TemplateCode'] = $this->template;

		// fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
		$params['TemplateParam'] = ($this->code and $this->valid_time) ? ['code' => $this->code, 'valid_time' => $this->valid_time] : [];

		// *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
		if (!empty($params['TemplateParam']) and is_array($params['TemplateParam'])) {
			$params['TemplateParam'] = json_encode($params['TemplateParam'], JSON_UNESCAPED_UNICODE);
		}

		// 初始化SignatureHelper实例用于设置参数，签名以及发送请求
		$helper = new SignatureHelper();

		// 此处可能会抛出异常，注意catch
		$request = $helper->request(
			$secret_id,
			$secret_key,
			'dysmsapi.aliyuncs.com',
			array_merge($params, [
				'RegionId' => 'cn-hangzhou',
				'Action'   => 'SendSms',
				'Version'  => '2017-05-25',
			])
			// fixme 选填: 启用https
			, true
		);

		// 返回结果
		if (!$request) {
			throw new Exception(__('短信请求发送失败', 'wnd'));

		} elseif ($request->Code != 'OK') {
			throw new Exception(__('系统错误：', 'wnd') . $request->Code);

		} else {
			return true;
		}
	}
}