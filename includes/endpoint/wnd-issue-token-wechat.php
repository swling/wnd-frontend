<?php
namespace Wnd\Endpoint;

use Exception;
use Wnd\Endpoint\Wnd_Issue_Token_Abstract;

/**
 * 微信小程序注册或登录
 * - 微信 openid 注册或登录
 * - 微信手机注册或登录
 */
class Wnd_Issue_Token_WeChat extends Wnd_Issue_Token_Abstract {

	private $app_id;

	private $secret;

	private $openid_code;

	private $phone_code;

	protected function check() {
		$config         = json_decode(wnd_get_config('wechat_app'));
		$app_id         = $this->data['app_id'] ?? (getallheaders()['Wx-App-Id'] ?? '');
		$this->app_id   = $app_id;
		$this->secret   = $config->$app_id ?? '';
		$this->app_type = static::get_app_type($this->app_id);

		$this->openid_code = $this->data['code'] ?? '';
		$this->phone_code  = $this->data['phone_code'] ?? '';

		if (!$this->secret) {
			throw new Exception('当前应用 app_id 尚未在后台配置密匙');
		}
	}

	/**
	 * 微信注册或登录（支持仅微信 openid 或微信手机）
	 */
	protected function register_or_login(): int {
		// 仅微信 openid
		if (!$this->phone_code) {
			return parent::register_or_login();
		}

		/**
		 * 微信手机
		 * - 手机绑定占用检测
		 * - 当前手机已注册（PC端或其他App、小程序），登录并绑定当前微信 openid
		 * - 当前手机未注册，注册用户并同时绑定 openid 和手机
		 */
		$phone           = $this->get_wechat_phone_number();
		$exists_user     = wnd_get_user_by($phone);
		$current_user_id = get_current_user_id();
		if ($current_user_id and $exists_user and $current_user_id != $exists_user->ID) {
			throw new Exception($phone . ' 已被其他账号占用');
		}

		if ($exists_user) {
			$openid = $this->get_app_openid();
			wnd_update_user_openid($exists_user->ID, $this->app_type, $openid);
			return $exists_user->ID;
		} else {
			$user_id = parent::register_or_login();
			wnd_update_user_phone($user_id, $phone);
			return $user_id;
		}
	}

	/**
	 * 获取微信 openid
	 *
	 * @link https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/login/auth.code2Session.html
	 */
	protected function get_app_openid(): string{
		$url     = "https://api.weixin.qq.com/sns/jscode2session?appid={$this->app_id}&secret={$this->secret}&js_code={$this->openid_code}&grant_type=authorization_code";
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
	private function get_wechat_phone_number(): string{
		$access_token = $this->get_wechat_access_token();
		$url          = "https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token={$access_token}";
		$request      = wp_remote_post($url, [
			'body' => json_encode(['code' => $this->phone_code]),
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
	 * 获取 access_token
	 * @link https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/access-token/auth.getAccessToken.html
	 */
	private function get_wechat_access_token(): string{
		// 获取缓存
		$cache_key    = md5($this->app_id . $this->secret);
		$cache_group  = 'wechat_token';
		$access_token = wp_cache_get($cache_key, $cache_group);
		if ($access_token) {
			return $access_token;
		}

		$url     = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->app_id}&secret={$this->secret}";
		$request = wp_remote_get($url);
		if (is_wp_error($request)) {
			throw new Exception('获取 access_token 网络请求失败');
		}

		$result       = json_decode($request['body'], true);
		$access_token = $result['access_token'] ?? '';
		if (!$access_token) {
			throw new Exception('解析 access_token 失败');
		}

		// 设置缓存
		wp_cache_set($cache_key, $access_token, $cache_group, $result['expires_in']);

		return $access_token;
	}

	/**
	 * 获取当前用户在指定微信应用中的 openid
	 */
	public static function get_current_user_openid(string $app_id): string{
		$app_type = static::get_app_type($app_id);
		$user_id  = get_current_user_id();
		return wnd_get_user_openid($user_id, $app_type) ?: '';
	}

	/**
	 * 微信应用标识
	 * - 同一个站点可能对应多个微信应用，且应用之间的 openid 并不通用。因此不能设置为简单的 mp 或 wechat 字符
	 */
	private static function get_app_type(string $app_id): string {
		return 'wx_' . $app_id;
	}
}
