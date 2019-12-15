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
		$query_array = [
			'post_status'    => 'auto-draft',
			'post_type'      => $post_type,
			'author'         => $user_id,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'cache_results'  => false,
			'posts_per_page' => 1,
		];
		$draft_post_array = get_posts($query_array);

		// 有草稿：返回第一篇草稿ID
		if ($draft_post_array) {
			$post_id = $draft_post_array[0]->ID;

			// 更新草稿状态
			$post_id = wp_update_post(
				[
					'ID'          => $post_id,
					'post_status' => 'auto-draft',
					'post_title'  => 'Auto-draft',
					'post_author' => $user_id,
				]
			);

			return is_wp_error($post_id) ? 0 : $post_id;
		}

		/**
		 *当前用户没有草稿，查询其他用户超过指定时间未编辑的草稿
		 *更新自动草稿时候，modified 不会变需要查询 post_date
		 *@see get_posts()
		 *@see wp_update_post
		 */
		$date_query = [
			[
				'column' => 'post_date',
				'before' => date('Y-m-d H:i', time() - 86400),
			],
		];
		$query_array = array_merge($query_array, ['date_query' => $date_query]);
		unset($query_array['author']);
		$draft_post_array = get_posts($query_array);

		// 有符合条件的其他用户创建的草稿
		if ($draft_post_array) {
			$post_id = $draft_post_array[0]->ID;

			// 更新草稿状态
			$post_id = wp_update_post(
				[
					'ID'          => $post_id,
					'post_status' => 'auto-draft',
					'post_title'  => 'Auto-draft',
					'post_author' => $user_id,
				]
			);

			//清空之前的附件
			if ($post_id) {
				$attachments = get_children(['post_type' => 'attachment', 'post_parent' => $post_id]);
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
			[
				'post_title'  => 'Auto-draft',
				'post_name'   => uniqid(),
				'post_type'   => $post_type,
				'post_author' => $user_id,
				'post_status' => 'auto-draft',
			]
		);
		return is_wp_error($post_id) ? 0 : $post_id;
	}

	/**
	 *@since 初始化
	 *批量设置文章 meta 及 term
	 *@param int 	$post_id
	 *@param array 	$meta_array 	wnd meta array
	 *@param array 	$wp_meta_array  wp meta array
	 *@param array 	$term_array  	term_array
	 */
	public static function update_meta_and_term($post_id, $meta_array, $wp_meta_array, $term_array) {
		if (!get_post($post_id)) {
			return false;
		}

		// 设置wnd post meta
		if (!empty($meta_array) and is_array($meta_array)) {
			wnd_update_post_meta_array($post_id, $meta_array);
		}

		// 设置原生 post meta
		if (!empty($wp_meta_array) and is_array($wp_meta_array)) {
			foreach ($wp_meta_array as $key => $value) {
				// 空值，如设置了表单，但无数据的情况，过滤
				if ('' == $value) {
					delete_post_meta($post_id, $key);
				} else {
					update_post_meta($post_id, $key, $value);
				}

			}
			unset($key, $value);
		}

		//  设置 term
		if (!empty($term_array) and is_array($term_array)) {
			foreach ($term_array as $taxonomy => $term) {
				if ($term != '-1') {
					//排除下拉菜单为选择的默认值
					wp_set_post_terms($post_id, $term, $taxonomy, false);
				}
			}
			unset($taxonomy, $term);
		}
	}

	/**
	 *@since 初始化
	 *标题去重
	 *
	 *@param string 	$title
	 *@param int 	$exclude_id
	 *@param string 	$post_type
	 *
	 *@return int|false
	 */
	public static function is_title_duplicated($title, $exclude_id = 0, $post_type = 'post') {
		if (empty($title)) {
			return false;
		}

		global $wpdb;
		$results = $wpdb->get_var($wpdb->prepare(
			"SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = %s AND  ID != %d  limit 1",
			$title,
			$post_type,
			$exclude_id
		));

		return $results ?: false;
	}

	/**
	 *@since 2019.02.17 根据post name 获取post
	 *
	 *@param string $post_name
	 *@param string $post_type
	 *@param string $post_status
	 *
	 *@return object|null
	 */
	public static function get_post_by_slug($post_name, $post_type = 'post', $post_status = 'publish') {
		global $wpdb;
		$post_name = urlencode($post_name);

		if ('any' == $post_status) {
			$post = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $wpdb->posts WHERE post_name = %s AND post_type = %s LIMIT 1",
					$post_name,
					$post_type
				)
			);
		} else {
			$post = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM $wpdb->posts WHERE post_name = %s AND post_type = %s AND post_status = %s LIMIT 1",
					$post_name,
					$post_type,
					$post_status
				)
			);
		}

		if ($post) {
			return $post[0];
		}
		return false;
	}

	/**
	 *@since 2019.02.19
	 *当前用户可以写入或管理的文章类型
	 *@return array : post type name数组
	 */
	public static function get_allowed_post_types() {
		$post_types = get_post_types(['public' => true], 'names', 'and');
		// 排除页面/站内信
		unset($post_types['page'], $post_types['mail']);
		return apply_filters('wnd_allowed_post_types', $post_types);
	}
}
