<?php
namespace Wnd\Getway\Login;

use Exception;
use Wnd\Getway\Wnd_Login_Social;

/**
 * Google登录
 * 注意，上述链接属于YouTube产品api，但可参考其互获取code及token的流程
 * @link https://developers.google.com/youtube/v3/live/guides/auth/server-side-web-apps#OAuth2_Revoking_a_Token
 * @since 2020.04.11
 */
class Google extends Wnd_Login_Social {

	protected $domain = 'google';

	public function __construct() {
		parent::__construct();

		$this->app_id  = wnd_get_config('google_appid');
		$this->app_key = wnd_get_config('google_appkey');
	}

	/**
	 * 创建授权地址
	 * @link https://developers.google.com/identity/protocols/oauth2/scopes#google-sign-in
	 */
	public function build_oauth_url() {
		if (!$this->app_id) {
			throw new Exception('未配置APP ID');
		}
		if (!$this->redirect_url) {
			throw new Exception('未配置回调网址：redirect_url');
		}

		$query = http_build_query(
			[
				'client_id'       => $this->app_id,
				'state'           => static::build_state('Google'),
				'response_type'   => 'code',
				'redirect_uri'    => $this->redirect_url,
				'access_type'     => 'offline',
				'approval_prompt' => 'auto',
				'scope'           => 'email profile',
			]
		);

		return 'https://accounts.google.com/o/oauth2/auth?' . $query;
	}

	/**
	 * 根据授权码请求token
	 */
	protected function get_token() {
		if (!$this->app_key) {
			throw new Exception('未配置APP Key');
		}

		if (!isset($_GET['state'])) {
			throw new Exception('state is empty');
		}

		if (!isset($_GET['code'])) {
			throw new Exception('code is empty');
		}

		// 校验自定义state nonce
		static::check_state_nonce($_GET['state']);

		//用户允许授权后，将会重定向到redirect_uri的网址上，并且带上code参数
		$data = [
			'code'          => $_GET['code'],
			'client_id'     => $this->app_id,
			'client_secret' => $this->app_key,
			'redirect_uri'  => $this->redirect_url,
			'grant_type'    => 'authorization_code',
		];

		$token_url = 'https://accounts.google.com/o/oauth2/token';

		//获取响应报文
		$response = wp_remote_post($token_url, ['body' => $data]);
		if (is_wp_error($response)) {
			throw new Exception($response->get_error_message());
		}

		//解析报文，获取token
		$body        = json_decode($response['body'], true);
		$this->token = $body['access_token'] ?? false;
		if (!$this->token) {
			throw new Exception($body['access_token']);
		}
	}

	/**
	 * 根据token 和 open id获取用户信息
	 *
	 */
	protected function get_user_info() {
		$url       = 'https://www.googleapis.com/oauth2/v3/userinfo?access_token=' . $this->token;
		$user_info = wp_remote_get($url);
		$user_info = $user_info['body'];
		$user_info = json_decode($user_info, true);

		$this->display_name = $user_info['name'];
		$this->avatar_url   = $user_info['picture'];
		$this->open_id      = $user_info['sub'];
		$this->email        = $user_info['email'];
	}
}
