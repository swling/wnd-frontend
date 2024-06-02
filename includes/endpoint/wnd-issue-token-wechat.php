<?php
namespace Wnd\Endpoint;

use Exception;
use Wnd\Endpoint\Wnd_Issue_Token_Abstract;
use Wnd\Utility\Wnd_Wechat;

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
		$this->app_type = Wnd_Wechat::build_app_type($this->app_id);

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
		$this->display_name = 'wx_' . uniqid();

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
		$phone           = Wnd_Wechat::get_phone_number($this->app_id, $this->secret, $this->phone_code);
		$exists_user     = wnd_get_user_by($phone);
		$current_user_id = get_current_user_id();
		if ($current_user_id and $exists_user and $current_user_id != $exists_user->ID) {
			throw new Exception($phone . ' 已被其他账号占用');
		}

		if ($exists_user) {
			wnd_update_user_openid($exists_user->ID, $this->app_type, $this->get_openid());
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
	protected function get_app_openid(): string {
		return Wnd_Wechat::get_openid($this->app_id, $this->secret, $this->openid_code);
	}
}
