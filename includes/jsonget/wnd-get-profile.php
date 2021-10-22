<?php
namespace Wnd\JsonGet;

use Exception;

/**
 * 获取 User Profile Json
 * @since 0.9.39
 *
 * @param int $user_id User ID
 */
class Wnd_Get_Profile extends Wnd_JsonGet {

	protected static function query($args = []): array{
		$user_id    = get_current_user_id();
		$avatar_url = wnd_get_config('default_avatar_url') ?: WND_URL . 'static/images/avatar.jpg';
		if (!$user_id) {
			return [
				'avatar_url'   => $avatar_url,
				'display_name' => __('匿名用户', 'wnd'),
			];
		}

		$user = get_userdata($user_id);
		if (!$user) {
			throw new Exception(__('User ID 无效', 'wnd'));
		}

		// 定义用户 profile 数组
		unset($user->data->user_pass);
		$user_profile = (array) $user->data;

		// 头像
		if (wnd_get_user_meta($user_id, 'avatar')) {
			$avatar_id  = wnd_get_user_meta($user_id, 'avatar');
			$avatar_url = wp_get_attachment_url($avatar_id) ?: $avatar_url;

			/**
			 * 统一按阿里云oss裁剪缩略图
			 * @since 2019.07.23
			 */
			$avatar_url = wnd_get_thumbnail_url($avatar_url, 200, 200);
		} elseif (wnd_get_user_meta($user_id, 'avatar_url')) {
			$avatar_url = wnd_get_user_meta($user_id, 'avatar_url') ?: $avatar_url;
		}

		$user_profile['avatar_url']  = $avatar_url;
		$user_profile['description'] = get_user_meta($user_id, 'description', true);

		return (array) $user_profile;
	}
}
