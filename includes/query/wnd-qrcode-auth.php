<?php
namespace Wnd\Query;

use Exception;
use Wnd\Query\Wnd_Query;
use Wnd\Utility\Wnd_Qrcode_Login;

/**
 * 查询参二维码注册登录用户信息
 * @since 2023.06.12
 */
class Wnd_Qrcode_Auth extends Wnd_Query {

	protected static function query($args = []): array{
		$scene = $args['scene'] ?? '';

		$user_id = Wnd_Qrcode_Login::query($scene);
		if (!$user_id) {
			throw new Exception('暂无用户信息');
		}

		// 登录成功
		wp_set_current_user($user_id);
		wp_set_auth_cookie($user_id);

		// 删除绑定
		Wnd_Qrcode_Login::delete($scene);

		return ['user' => $user_id];
	}

}
