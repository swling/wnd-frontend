<?php
namespace Wnd\Controller;

use \Exception;

/**
 *@since 2019.09.27
 *QQ登录
 */
class Wnd_Social_Login_QQ extends Wnd_Social_Login {

	/**
	 *创建授权地址
	 */
	public function build_oauth_url($return_url) {
		if (!$this->app_id) {
			throw new Exception('未配置APP ID');
		}

		$query = http_build_query(
			array(
				'client_id'     => $this->app_id,
				'state'         => wnd_create_nonce('qq_login'),
				'response_type' => 'code',
				'redirect_uri'  => $return_url ?: home_url(),
			)
		);

		return 'https://graph.qq.com/oauth2.0/authorize?' . $query;
	}

	/**
	 *根据授权码请求token
	 */
	protected function get_token() {
		if (!isset($_GET['state']) or !isset($_GET['code'])) {
			return;
		}

		$code  = $_GET['code'];
		$nonce = $_GET['state'];
		if (!wnd_verify_nonce($nonce, 'qq_login')) {
			throw new Exception('验证失败，请返回页面并刷新重试');
		}

		$token_url = 'https://graph.qq.com/oauth2.0/token?client_id=' . $this->app_id . '&client_secret=' . wnd_get_option('wndt', 'wndt_qq_appkey') . '&grant_type=authorization_code&redirect_uri=' . urlencode(home_url('ucenter?type=qq')) . '&code=' . $code;

		//获取响应报文
		$response = wp_remote_get($token_url);
		if (is_wp_error($response)) {
			throw new Exception($response->get_error_message());
		}

		//解析报文，获取token
		$response = $response['body'];
		$params   = array();
		parse_str($response, $params);
		$this->token = $params['access_token'] ?? false;
		if (!$this->token) {
			throw new Exception('获取token失败');
		}
	}

	/**
	 *根据token 获取用户QQ open id
	 */
	protected function get_open_id() {
		if (!$this->token) {
			throw new Exception('获取token失败');
		}

		$graph_url = 'https://graph.qq.com/oauth2.0/me?access_token=' . $this->token;
		$str       = wp_remote_get($graph_url);
		$str       = $str['body'];
		if (strpos($str, 'callback') !== false) {
			$lpos = strpos($str, '(');
			$rpos = strrpos($str, ')');
			$str  = substr($str, $lpos + 1, $rpos - $lpos - 1);
		}
		$user = json_decode($str, true);
		if (isset($user->error)) {
			echo '<h3>错误代码:</h3>' . $user->error;
			echo '<h3>信息  :</h3>' . $user->error_description;
			exit();
		}
		$this->open_id = $user['openid'];
		if (!$this->open_id) {
			throw new Exception('获取用户open id失败');
		}
	}

	/**
	 *根据token 和 open id获取用户信息
	 */
	protected function get_user_info() {
		if (!$this->token or !$this->open_id) {
			throw new Exception('Token 或 open ID为空');
		}

		$get_user_info = 'https://graph.qq.com/user/get_user_info?' . 'access_token=' . $this->token . '&oauth_consumer_key=' . $this->app_id . '&openid=' . $this->open_id . '&format=json';
		$user_info     = wp_remote_get($get_user_info);
		$user_info     = $user_info['body'];
		$user_info     = json_decode($user_info, true);

		//2.4 组成用户数据
		$this->display_name = $user_info['nickname'];
		$avatar_url         = $user_info['figureurl_qq_2'] ?? $user_info['figureurl_qq_1'];
		$this->avatar_url   = str_replace('http://', 'https://', $avatar_url);
	}
}