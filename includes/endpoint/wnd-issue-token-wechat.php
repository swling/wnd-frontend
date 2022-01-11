<?php
namespace Wnd\Endpoint;

use Exception;
use Wnd\Endpoint\Wnd_Issue_Token_Abstract;

/**
 * 用于微信小程序 code 获取 openid，并在本站系统注册或登录，返回 token
 * @link https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/login/auth.code2Session.html
 */
class Wnd_Issue_Token_WeChat extends Wnd_Issue_Token_Abstract {

	protected function get_app_openid(): string{
		$config         = json_decode(wnd_get_config('wechat_app'));
		$code           = $this->data['code'] ?? '';
		$app_id         = $this->data['app_id'] ?? (getallheaders()['Wx-App-Id'] ?? '');
		$this->app_type = static::get_app_type($app_id);

		// 读取对应 App id 的密匙
		$secret = $config->$app_id ?? '';
		if (!$secret) {
			throw new Exception('当前应用 app_id 尚未在后台配置密匙');
		}

		$url     = "https://api.weixin.qq.com/sns/jscode2session?appid={$app_id}&secret={$secret}&js_code={$code}&grant_type=authorization_code";
		$request = wp_remote_get($url);
		if (is_wp_error($request)) {
			throw new Exception('获取 openid 网络请求失败');
		}

		$result = json_decode($request['body'], true);
		$openid = $result['openid'] ?? '';
		if (!$openid) {
			throw new Exception('解析 openid 失败');
		}

		return $openid;
	}

	/**
	 * 微信应用标识
	 * - 同一个站点可能对应多个微信应用，且应用之间的 openid 并不通用。因此不能设置为简单的 mp 或 wechat 字符
	 */
	private static function get_app_type(string $app_id): string {
		return 'wx_' . $app_id;
	}

	/**
	 * 获取当前用户在指定微信应用中的 openid
	 */
	public static function get_current_user_openid(string $app_id): string{
		$app_type = static::get_app_type($app_id);
		$user_id  = get_current_user_id();
		return wnd_get_user_openid($user_id, $app_type) ?: '';
	}
}
