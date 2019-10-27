<?php
namespace Wnd\Model;

class Wnd_Sticky {

	/**
	 *@since 2019.06.11
	 *精选置顶文章
	 *精选post id存储方式：
	 *option：二维数组 wnd_sticky_posts[$post_type]['post'.$post_id]
	 *@param $post_id
	 **/
	public static function stick_post($post_id) {
		$post_type = get_post_type($post_id);
		if (!$post_type) {
			return;
		}

		$max              = wnd_get_option('wnd', 'wnd_max_stick_posts') ?: 10;
		$old_sticky_posts = wnd_get_option('wnd_sticky_posts', $post_type);
		$old_sticky_posts = is_array($old_sticky_posts) ? $old_sticky_posts : array();

		// 创建以post+id作为键名，id作为键值的数组，并合并入数组（注意顺序）
		$sticky_post      = array('post' . $post_id => $post_id);
		$new_sticky_posts = array_merge($sticky_post, $old_sticky_posts);

		// 仅保留指定个数元素（按最新）
		$new_sticky_posts = array_slice($new_sticky_posts, 0, $max);

		return wnd_update_option('wnd_sticky_posts', $post_type, $new_sticky_posts);
	}

	/**
	 *@since 2019.06.11
	 *取消精选置顶文章
	 *@param $post_id
	 **/
	public static function unstick_post($post_id) {
		$post_type = get_post_type($post_id);
		if (!$post_type) {
			return;
		}

		$sticky_posts = wnd_get_option('wnd_sticky_posts', $post_type);
		$sticky_posts = is_array($sticky_posts) ? $sticky_posts : array();

		// 移除指定post id
		unset($sticky_posts['post' . $post_id]);

		return wnd_update_option('wnd_sticky_posts', $post_type, $sticky_posts);
	}

	/**
	 *@since 2019.06.11
	 *获取精选置顶文章
	 *@param 	$post_type 		文章类型
	 *@return 	array or false 	文章id数组
	 **/
	public static function get_sticky_posts($post_type): array{
		$sticky_posts = wnd_get_option('wnd_sticky_posts', $post_type);
		$sticky_posts = is_array($sticky_posts) ? $sticky_posts : array();

		// 检测post是否有效
		foreach ($sticky_posts as $key => $sticky_post) {
			if (!get_post($sticky_post)) {
				unset($sticky_posts[$key]);
			}
		}
		unset($key, $sticky_post);
		wnd_update_option('wnd_sticky_posts', $post_type, $sticky_posts);

		return $sticky_posts;
	}
}
