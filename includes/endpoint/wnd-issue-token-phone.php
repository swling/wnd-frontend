<?php
namespace Wnd\Endpoint;

use Exception;
use Wnd\Endpoint\Wnd_Issue_Token_Abstract;

/**
 * 基于手机号注册或登录并签发 Token
 * @since 0.9.58
 */
class Wnd_Issue_Token_Phone extends Wnd_Issue_Token_Abstract {

	protected $app_type = 'phone';

	/**
	 * 获取手机号
	 * - 应根据特定的安全方式，验证后获取手机号
	 */
	protected function get_app_openid(): string{
		$app = $this->data['app'] ?? '';
		switch ($app) {
			case 'wechat':
				return $this->get_wechat_phone_number();
				break;

			default:
				throw new Exception('The method to get the phone is not set. App:' . $app);
				break;
		}
	}

	/**
	 * 微信小程序 code 获取用户手机
	 * @link https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/phonenumber/phonenumber.getPhoneNumber.html
	 */
	private function get_wechat_phone_number(): string{
		$code         = $this->data['code'] ?? '';
		$access_token = $this->get_wechat_access_token();
		$url          = "https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token={$access_token}";
		$request      = wp_remote_post($url, [
			'body' => json_encode(['code' => $code]),
		]);
		if (is_wp_error($request)) {
			throw new Exception('获取手机号码网络请求失败');
		}

		$result = json_decode($request['body'], true);
		if (0 !== $result['errcode']) {
			throw new Exception($result['errmsg']);
		}

		$phone = $result['phone_info']['phoneNumber'] ?? '';
		if (!$phone) {
			throw new Exception('解析手机号码失败');
		}

		return $phone;
	}

	/**
	 * @link https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/access-token/auth.getAccessToken.html
	 */
	private function get_wechat_access_token(): string{
		$config = json_decode(wnd_get_config('wechat_app'));
		$app_id = $this->data['app_id'] ?? (getallheaders()['Wx-App-Id'] ?? '');

		// 读取对应 App id 的密匙
		$secret = $config->$app_id ?? '';
		if (!$secret) {
			throw new Exception('当前应用 app_id 尚未在后台配置密匙');
		}

		$url     = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$app_id}&secret={$secret}";
		$request = wp_remote_get($url);
		if (is_wp_error($request)) {
			throw new Exception('获取 access_token 网络请求失败');
		}

		$result       = json_decode($request['body'], true);
		$access_token = $result['access_token'] ?? '';
		if (!$access_token) {
			throw new Exception('解析 access_token 失败');
		}

		return $access_token;
	}
}
