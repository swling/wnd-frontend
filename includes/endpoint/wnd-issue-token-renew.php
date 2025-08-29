<?php
namespace Wnd\Endpoint;

use Wnd\Endpoint\Wnd_Endpoint;
use Wnd\Utility\Wnd_JWT_handler;

/**
 * ## 接口：续期JWT令牌
 * @since 0.9.91
 */
class Wnd_Issue_Token_Renew extends Wnd_Endpoint {

	public $period      = 5;
	public $max_actions = 1;

	protected $content_type = 'json';

	private $user_id = 0;

	final protected function do() {
		$jwt   = Wnd_JWT_Handler::get_instance();
		$token = $jwt->generate_token($this->user_id);
		$exp   = $jwt->parse_token($token)['exp'] ?? 0;

		echo json_encode(['status' => 1, 'token' => $token, 'exp' => $exp, 'user_id' => $this->user_id]);
	}

	protected function check() {
		$this->user_id = get_current_user_id();
		if (!$this->user_id) {
			http_response_code(401);
			echo json_encode(['status' => 0, 'msg' => 'invalid user']);
			exit;
		}
	}

}
