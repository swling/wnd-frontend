<?php
namespace Wnd\Utility;

use Exception;

/**
 * Qrcode 扫描授权登录绑定及查询
 *
 * @since 0.9.59.12
 * 业务逻辑（以微信为例）：
 * - web 前端生成一个随机【参数】(请在主题中自行实现)，并生微信带参二维码 @see Endpoint\Wnd_MP_QRCode
 * - 扫码打开小程序指定页面，用户授权后，携带微信授权 code（获取openid）及随机【参数】发送至 Endpoint\Wnd_Issue_Token_Abstract
 * - Endpoint\Wnd_Issue_Token_Abstract 注册或登录后，将接收到【参数】与 user id 绑定
 *- 前端轮询【参数】Query\Wnd_Qrcode_Auth 若匹配，则设定对用用户登录状态
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
