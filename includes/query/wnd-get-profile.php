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

	protected static function query($args = []): array{
		$user_id    = get_current_user_id();
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
		unset($user->data->user_pass);
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

}
