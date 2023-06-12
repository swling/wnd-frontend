<?php
namespace Wnd\Utility;

use Exception;

/**
 * Qrcode 扫描授权登录绑定及查询
 *
 * @since 0.9.59.12
 */
class Wnd_Qrcode_Login {

	private static $cache_group = 'qrcode_login';

	public static function bind($scene, int $user_id) {
		if (strlen($scene) < 13) {
			throw new Exception('scene 长度不足 13 位，请重新获取二维码');
		}

		if (static::query($scene)) {
			throw new Exception('系统出错，请重新获取二维码');
		}

		wp_cache_set($scene, $user_id, static::$cache_group, 600);
	}

	public static function query($scene): int {
		return wp_cache_get($scene, static::$cache_group) ?: 0;
	}

	public static function delete($scene) {
		wp_cache_delete($scene, static::$cache_group);
	}

}
