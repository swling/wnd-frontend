<?php
namespace Wnd\Utility;

use Exception;

/**
 * 封装微信常用 API
 * @since 0.9.58.2
 */
class Wnd_Wechat {

	/**
	 * 获取稳定版接口调用凭据 access_token
	 * @link https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/mp-access-token/getStableAccessToken.html
	 */
	public static function get_access_token(string $app_id, string $secret): string {
		// 获取缓存
		$cache_key    = md5($app_id . $secret);
		$cache_group  = 'wechat_token';
		$access_token = wp_cache_get($cache_key, $cache_group);
		if ($access_token) {
			return $access_token;
		}

		$url     = 'https://api.weixin.qq.com/cgi-bin/stable_token';
		$request = wp_remote_post($url,
			[
				'body' => json_encode(['grant_type' => 'client_credential', 'appid' => $app_id, 'secret' => $secret], JSON_UNESCAPED_UNICODE),
			]
		);
		if (is_wp_error($request)) {
			throw new Exception('获取 access_token 网络请求失败');
		}

		$result       = json_decode($request['body'], true);
		$access_token = $result['access_token'] ?? '';
		if (!$access_token) {
			throw new Exception('解析 access_token 失败：' . ($result['errmsg'] ?? ''));
		}

		// 设置缓存
		wp_cache_set($cache_key, $access_token, $cache_group, $result['expires_in']);

		return $access_token;
	}

	/**
	 * 获取微信 openid
	 *
	 * @link https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/login/auth.code2Session.html
	 */
	public static function get_openid(string $app_id, string $secret, string $js_code): string {
		$url     = "https://api.weixin.qq.com/sns/jscode2session?appid={$app_id}&secret={$secret}&js_code={$js_code}&grant_type=authorization_code";
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
	 * 微信小程序 code 获取用户手机
	 *
	 * @link https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/phonenumber/phonenumber.getPhoneNumber.html
	 */
	public static function get_phone_number(string $app_id, string $secret, string $js_code): string {
		$access_token = static::get_access_token($app_id, $secret);
		$url          = "https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token={$access_token}";
		$request      = wp_remote_post($url, [
			'body' => json_encode(['code' => $js_code]),
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
	 * 获取当前用户在指定微信应用中的 openid
	 */
	public static function get_current_user_openid(string $app_id): string {
		$app_type = static::build_app_type($app_id);
		$user_id  = get_current_user_id();
		return wnd_get_user_openid($user_id, $app_type) ?: '';
	}

	/**
	 * 微信应用标识
	 * - 同一个站点可能对应多个微信应用，且应用之间的 openid 并不通用。因此不能设置为简单的 mp 或 wechat 字符
	 */
	public static function build_app_type(string $app_id): string {
		return 'wx_' . $app_id;
	}

}
