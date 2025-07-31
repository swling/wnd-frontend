<?php
namespace Wnd\Query;

use Exception;

/**
 * 获取 current user Profile Json
 * @since 0.9.39
 *
 * @param int $user_id User ID
 */
class Wnd_Get_Profile extends Wnd_Query {

	protected static function query($args = []): array {
		$user_id    = static::get_user_id($args);
		$avatar_url = wnd_get_avatar_url($user_id);
		if (!$user_id) {
			$user_profile = [
				'avatar_url'   => $avatar_url,
				'display_name' => __('匿名用户', 'wnd'),
				'balance'      => wnd_get_anon_user_balance(),
			];

			/**
			 * @since 0.9.57.9
			 * 过滤用户 profile 数据
			 */
			return apply_filters('wnd_get_profile', $user_profile, $user_id);
		}

		$user = get_userdata($user_id);
		if (!$user) {
			throw new Exception(__('User ID 无效', 'wnd'));
		}

		// 定义用户 profile 数组
		unset($user->data->user_pass, $user->data->user_activation_key, $user->data->user_status);
		$user_profile                = (array) $user->data;
		$user_profile['avatar_url']  = $avatar_url;
		$user_profile['description'] = get_user_meta($user_id, 'description', true);
		$user_profile['balance']     = wnd_get_user_balance($user_id);
		$user_profile['phone']       = wnd_get_user_phone($user_id);

		/**
		 * @since 0.9.57.9
		 * 过滤用户 profile 数据
		 */
		return apply_filters('wnd_get_profile', $user_profile, $user_id);
	}

	private static function get_user_id(array $args): int {
		// 如果不是管理员，则返回当前用户 ID
		if (!wnd_is_manager()) {
			return get_current_user_id();
		}

		// 如果是管理员，则返回 args 中的 user_id 或当前用户 ID
		if (!isset($args['user_id'])) {
			return get_current_user_id();
		}

		return (int) $args['user_id'];
	}
}
