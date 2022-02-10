<?php
namespace Wnd\JsonGet;

use Exception;

/**
 * 获取Post Json
 * @since 2020.07.21
 *
 * @param int $post_id Post ID
 */
class Wnd_Get_Post extends Wnd_JsonGet {

	protected static function query($args = []): array{
		$post_id = (int) ($args['post_id'] ?? 0);
		$post    = get_post($post_id);
		if (!$post) {
			throw new Exception('Invalid Post ID');
		}

		// 处理付费内容
		$post = wnd_filter_post($post);

		/**
		 * 非公开post仅返回基本状态
		 */
		if ('publish' != $post->post_status and !current_user_can('edit_post', $post_id)) {
			return [
				'post_status' => $post->post_status,
			];
		}

		return (array) $post;
	}
}
