<?php
namespace Wnd\JsonGet;

use Exception;

/**
 *@since 2020.07.21
 *获取Post Json
 *
 *@param int $post_id  Post ID
 */
class Wnd_Get_Post extends Wnd_JsonGet {

	protected static function query($args): array{
		$post_id = (int) ($args['post_id'] ?? 0);
		if (!$post_id) {
			throw new Exception(__('ID 无效', 'wnd'));
		}

		$post = get_post($post_id, ARRAY_A);

		/**
		 *非公开post仅返回基本状态
		 */
		if ('publish' != $post['post_status'] and !current_user_can('edit_post', $post_id)) {
			return [
				'post_status' => $post['post_status'],
			];
		}

		return $post;
	}
}
