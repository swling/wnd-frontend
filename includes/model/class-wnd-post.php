<?php
namespace Wnd\Model;

class Wnd_Post {

	/**
	 *如果需要上传图片等，需要在提交文章之前，预先获取一篇文章
	 *1、查询当前用户的草稿，如果存在获取最近一篇草稿用于编辑
	 *2、若当前用户没有草稿，则查询其他用户超过一天未发布的自动草稿，并初始化标题、清除相关附件等
	 *3、以上皆无结果，则创建一篇新文章
	 *@since 2018.11.12
	 *默认：获取当前用户，一天以前编辑过的文章（一天内更新过的文章表示正处于编辑状态）
	 *
	 *@param string 	$post_type 		类型
	 *
	 *@return int 		$post_id|0 		成功获取草稿返回ID，否则返回0
	 *
	 */
	public static function get_draft($post_type) {
		$user_id = get_current_user_id();
		if (!$user_id) {
			return 0;
		}

		/**
		 *@since 2019.02.19
		 * 写入post type检测
		 *@since 2019.7.17
		 *attachment为附件类型，前端可能会允许编辑更新attachment post信息，但此种类型应该是上传文件后由WordPress自动创建
		 *@see media_handle_upload()
		 */
		if (!in_array($post_type, wnd_get_allowed_post_types()) or $post_type == 'attachment') {
			return 0;
		}

		/**
		 *查询当前用户否存在草稿，有则调用最近一篇草稿编辑
		 *@see get_posts()
		 *@see wp_update_post
		 */
		$query_array = array(
			'post_status'    => 'auto-draft',
			'post_type'      => $post_type,
			'author'         => $user_id,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'cache_results'  => false,
			'posts_per_page' => 1,
		);
		$draft_post_array = get_posts($query_array);

		// 有草稿：返回第一篇草稿ID
		if ($draft_post_array) {
			$post_id = $draft_post_array[0]->ID;

			// 更新草稿状态
			$post_id = wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => 'auto-draft',
					'post_title'  => 'Auto-draft',
					'post_author' => $user_id,
				)
			);

			return is_wp_error($post_id) ? 0 : $post_id;
		}

		/**
		 *当前用户没有草稿，查询其他用户超过指定时间未编辑的草稿
		 *更新自动草稿时候，modified 不会变需要查询 post_date
		 *@see get_posts()
		 *@see wp_update_post
		 */
		$date_query = array(
			array(
				'column' => 'post_date',
				'before' => date('Y-m-d H:i', time() - 86400),
			),
		);
		$query_array = array_merge($query_array, array('date_query' => $date_query));
		unset($query_array['author']);
		$draft_post_array = get_posts($query_array);

		// 有符合条件的其他用户创建的草稿
		if ($draft_post_array) {
			$post_id = $draft_post_array[0]->ID;

			// 更新草稿状态
			$post_id = wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => 'auto-draft',
					'post_title'  => 'Auto-draft',
					'post_author' => $user_id,
				)
			);

			//清空之前的附件
			if ($post_id) {
				$attachments = get_children(array('post_type' => 'attachment', 'post_parent' => $post_id));
				foreach ($attachments as $attachment) {
					wp_delete_attachment($attachment->ID, true);
				}
				unset($attachment);
			}

			// 返回值
			return is_wp_error($post_id) ? 0 : $post_id;
		}

		/**
		 *全站没有可用草稿，创建新文章用于编辑
		 *@see wp_insert_post()
		 */
		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Auto-draft',
				'post_name'   => uniqid(),
				'post_type'   => $post_type,
				'post_author' => $user_id,
				'post_status' => 'auto-draft',
			)
		);
		return is_wp_error($post_id) ? 0 : $post_id;
	}

}
