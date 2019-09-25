<?php
/**
 *@since 2019.09.25
 *短信
 */
use Aliyun\DySDKLite\SignatureHelper;

require WND_PATH . 'components/aliyun-sms/SignatureHelper.php'; //阿里云短信

class Wnd_Sms_Ali extends Wnd_sms {
	/**
	 * 发送短信
	 */
	public function send() {
		$params                 = array();
		$params['PhoneNumbers'] = $this->phone;
		$params['SignName']     = $this->sign_name;
		$params['TemplateCode'] = $this->template;

		// fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
		$params['TemplateParam'] = Array(
			'code' => $this->code,
		);

		// *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
		if (!empty($params['TemplateParam']) and is_array($params['TemplateParam'])) {
			$params['TemplateParam'] = json_encode($params['TemplateParam'], JSON_UNESCAPED_UNICODE);
		}

		// 初始化SignatureHelper实例用于设置参数，签名以及发送请求
		$helper = new SignatureHelper();

		// 此处可能会抛出异常，注意catch
		$request = $helper->request(
			$this->app_id,
			$this->app_key,
			'dysmsapi.aliyuncs.com',
			array_merge($params, array(
				'RegionId' => 'cn-hangzhou',
				'Action'   => 'SendSms',
				'Version'  => '2017-05-25',
			))
			// fixme 选填: 启用https
			, true
		);

		// 返回结果
		if (!$request) {
			throw new Exception('短信请求发送失败！');

		} elseif ($request->Code != 'OK') {

			throw new Exception('错误：' . $request->Code);
		} else {
			return true;
		}
	}
}
