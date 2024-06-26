<?php
namespace Wnd\Model;

use Wnd\Controller\Wnd_Request;
use Wnd\Model\Wnd_Term;
use WP_REST_Request;

abstract class Wnd_Post {

	/**
	 * 如果需要上传图片等，需要在提交文章之前，预先获取一篇文章
	 * 1、查询当前用户的草稿，如果存在获取最近一篇草稿用于编辑
	 * 2、若当前用户没有草稿，则查询其他用户超过一天未发布的自动草稿，并初始化标题、清除相关附件等
	 * 3、以上皆无结果，则创建一篇新文章
	 * 默认：获取当前用户，一天以前编辑过的文章（一天内更新过的文章表示正处于编辑状态）
	 *
	 * @since 2018.11.12
	 *
	 * @param  string 	$post_type  		类型
	 * @return int    		$post_id|0 		成功获取草稿返回ID，否则返回0
	 */
	public static function get_draft(string $post_type): int {
		$user_id = get_current_user_id();
		if (!$user_id) {
			return 0;
		}

		/**
		 * 写入及更新权限过滤
		 * 创建草稿时同步权限钩子：'wnd_can_insert_post'
		 * @see Wnd\Action\Wnd_Insert_Post
		 * @since 0.9.27
		 */
		$data            = ['_post_post_type' => $post_type, '_post_post_status' => 'auto-draft'];
		$can_insert_post = apply_filters('wnd_can_insert_post', ['status' => 1, 'msg' => ''], $data, 0);
		if (0 === $can_insert_post['status']) {
			return 0;
		}

		/**
		 * 写入post type检测
		 * attachment为附件类型，前端可能会允许编辑更新attachment post信息，但此种类型应该是上传文件后由WordPress自动创建
		 * @see media_handle_upload()
		 * @since 2019.02.19
		 * @since 2019.7.17
		 */
		if (!in_array($post_type, static::get_allowed_post_types()) or 'attachment' == $post_type) {
			return 0;
		}

		/**
		 * 查询当前用户否存在草稿，有则调用最近一篇草稿编辑
		 * @see get_posts()
		 * @see wp_update_post
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
		 * 当前用户没有草稿，查询其他用户超过指定时间未编辑的草稿
		 * 更新自动草稿时候，modified 不会变需要查询 post_date
		 * @see get_posts()
		 * @see wp_update_post
		 */
		$date_query = [
			[
				'column' => 'post_date',
				'before' => date('Y-m-d H:i', current_time('timestamp') - 86400),
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

			//清空之前的附件及 post_meta
			if ($post_id) {
				$attachments = get_children(['post_type' => 'attachment', 'post_parent' => $post_id]);
				foreach ($attachments as $attachment) {
					wp_delete_attachment($attachment->ID, true);
				}
				unset($attachment);

				foreach (array_keys(get_post_meta($post_id)) as $meta_key) {
					delete_post_meta($post_id, $meta_key);
				}
				unset($meta_key);
			}

			// 返回值
			return is_wp_error($post_id) ? 0 : $post_id;
		}

		/**
		 * 全站没有可用草稿，创建新文章用于编辑
		 * @see wp_insert_post()
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
	 * 批量设置文章meta
	 * @since 2019.12.22
	 *
	 * @param int   	$post_id
	 * @param array 	$meta_data    	wnd meta array
	 * @param array 	$wp_meta_data wp meta array
	 */
	public static function set_meta(int $post_id, array $meta_data, array $wp_meta_data) {
		if (!get_post($post_id)) {
			return false;
		}

		// 设置wnd post meta
		if ($meta_data) {
			wnd_update_post_meta_array($post_id, $meta_data);
		}

		// 设置原生 post meta
		if ($wp_meta_data) {
			foreach ($wp_meta_data as $key => $value) {
				// 空值，如设置了表单，但无数据的情况，过滤
				if ('' == $value) {
					// 此处不得使用 delete_post_meta() 因为它无法作用于 revision;
					delete_metadata('post', $post_id, $key);
				} else {
					// 此处不得使用 update_post_meta() 因为它无法给 revision 添加字段;
					update_metadata('post', $post_id, $key, $value);
				}

			}
			unset($key, $value);
		}
	}

	/**
	 * 批量设置文章terms
	 * @since 2019.12.22
	 *
	 * @param int   	$post_id
	 * @param array 	$terms_data 	terms_array
	 */
	public static function set_terms(int $post_id, array $terms_data) {
		if (!get_post($post_id)) {
			return false;
		}

		//  设置 term
		if ($terms_data) {
			foreach ($terms_data as $taxonomy => $term) {
				wp_set_post_terms($post_id, $term, $taxonomy, false);
			}
			unset($taxonomy, $term);
		}
	}

	/**
	 * 从请求数据中提取 meta 及 terms 数据，并设置到对应 post
	 * @since 0.9.52
	 */
	public static function set_meta_and_terms(int $post_id, array $data) {
		$wp_rest_request = new WP_REST_Request('POST');
		foreach ($data as $key => $value) {
			$wp_rest_request->set_param($key, $value);
		}
		unset($key, $value);

		// 提取 meta 及 terms
		$wnd_request  = new Wnd_Request($wp_rest_request, false, false);
		$meta_data    = $wnd_request->get_post_meta_data();
		$wp_meta_data = $wnd_request->get_wp_post_meta_data();
		$terms_data   = $wnd_request->get_terms_data();

		// 设置 Meta 及 terms
		Wnd_Post::set_meta($post_id, $meta_data, $wp_meta_data);
		Wnd_Post::set_terms($post_id, $terms_data);
	}

	/**
	 * 标题去重
	 * @since 初始化
	 *
	 * @param  string      	$title
	 * @param  int         	$exclude_id
	 * @param  string      	$post_type
	 * @return int|false
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
	 * @since 2019.02.17 根据post name 获取post
	 *
	 * @param  string        $post_name
	 * @param  string        $post_type
	 * @param  string|array  $post_status
	 * @return object|null
	 */
	public static function get_post_by_slug($post_name, $post_type = 'post', $post_status = 'publish') {
		global $wpdb;
		$post_name = urlencode($post_name);

		if (is_array($post_status)) {
			return $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM $wpdb->posts WHERE post_name = %s AND post_type = %s AND post_status in ('" . implode("','", $post_status) . "') LIMIT 1",
					$post_name,
					$post_type
				)
			);
		}

		if ('any' == $post_status) {
			return $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM $wpdb->posts WHERE post_name = %s AND post_type = %s LIMIT 1",
					$post_name,
					$post_type
				)
			);
		}

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->posts WHERE post_name = %s AND post_type = %s AND post_status = %s LIMIT 1",
				$post_name,
				$post_type,
				$post_status
			)
		);
	}

	/**
	 * 当前用户可以写入或管理的文章类型
	 * @since 2019.02.19
	 *
	 * @return array : post type name数组
	 */
	public static function get_allowed_post_types(): array {
		$post_types   = get_post_types(['public' => true], 'names', 'and');
		$post_types[] = 'revision';
		// 排除页面/站内信
		if (!is_super_admin()) {
			unset($post_types['page']);
		}
		return apply_filters('wnd_allowed_post_types', $post_types);
	}

	/**
	 * 获取revision ID
	 * 普通用户已公开发布的信息，如再次修改，将创建一个child post，并设置post meta。此revision不同于wp官方revision。
	 * @since 2020.05.20
	 */
	public static function get_revision_id($post_id): int {
		if (!$post_id) {
			return 0;
		}

		$args = [
			'order'       => 'DESC',
			'orderby'     => 'date ID',
			'post_parent' => $post_id,
			'post_type'   => 'revision',
			'post_status' => 'any',
		];

		$revisions = get_posts($args);
		if (!$revisions) {
			return 0;
		}

		return $revisions[0]->ID;
	}

	/**
	 * 当前post 是否为自定义 revision
	 * 此revision不同于wp官方revision。
	 * @since 2020.05.20
	 */
	public static function is_revision($post_id): bool {
		$revision = get_post($post_id);
		if (!$revision) {
			return false;
		}

		return 'revision' == $revision->post_type;
	}

	/**
	 * 将当前post更改为指定 revision
	 * 该revision不同于WordPress原生revision
	 *
	 * 将恢复 post 及 post_meta、terms
	 *
	 * @since 2020.05.20
	 *
	 * @param int    $revision_id 将要恢复的版本ID
	 * @param string $post_stauts 新的状态
	 */
	public static function restore_post_revision($revision_id, $post_status) {
		$revision = get_post($revision_id, ARRAY_A);
		if (!$revision) {
			return $revision;
		}

		// Allow these to be versioned.
		$fields = [
			'post_title',
			'post_content',
			'post_excerpt',
		];
		$update = [];
		foreach (array_intersect(array_keys($revision), $fields) as $field) {
			$update[$field] = $revision[$field];
		}

		$update['ID']          = $revision['post_parent'];
		$update['post_status'] = $post_status;
		$update                = wp_slash($update); // Since data is from DB.
		$post_id               = wp_update_post($update);
		if (!$post_id || is_wp_error($post_id)) {
			return $post_id;
		}

		// restore post meta
		foreach (get_post_meta($revision_id) as $key => $value) {
			if ('views' == $key) {
				continue;
			}

			update_post_meta($revision['post_parent'], $key, maybe_unserialize($value[0]));
		}
		unset($key, $value);

		// restore terms
		$post_type = get_post_type($revision['post_parent']);
		foreach (get_object_taxonomies($post_type, 'names') as $taxonomy) {
			$terms = Wnd_Term::get_post_terms($revision_id, $taxonomy);
			if ($terms) {
				wp_set_post_terms($revision['post_parent'], $terms, $taxonomy);
			}
		}unset($taxonomy);

		// 永久删除revision（此处必须手动删除 term relationships）
		wp_delete_object_term_relationships($revision_id, get_object_taxonomies($post_type));
		wp_delete_post($revision_id, true);

		return $post_id;
	}
}
