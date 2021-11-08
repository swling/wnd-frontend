<?php
use Wnd\Model\Wnd_Post;
use Wnd\Model\Wnd_Sticky;

/**
 * 当前用户可以写入或管理的文章类型
 * @since 2019.02.19
 *
 * @return array : post type name数组
 */
function wnd_get_allowed_post_types() {
	return Wnd_Post::get_allowed_post_types();
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
function wnd_is_title_duplicated($title, $exclude_id = 0, $post_type = 'post') {
	return Wnd_Post::is_title_duplicated($title, $exclude_id, $post_type);
}

/**
 * @since 2019.02.17 根据post name 获取post
 *
 * @param  string        $post_name
 * @param  string        $post_type
 * @param  string        $post_status
 * @return object|null
 */
function wnd_get_post_by_slug($post_name, $post_type = 'post', $post_status = 'publish') {
	return Wnd_Post::get_post_by_slug($post_name, $post_type, $post_status);
}

/**
 * 精选置顶文章
 * 精选post id存储方式：
 * option：二维数组 wnd_sticky_posts[$post_type]['post'.$post_id]
 * @since 2019.06.11
 *
 * @param $post_id
 */
function wnd_stick_post($post_id) {
	return Wnd_Sticky::stick_post($post_id);
}

/**
 * 取消精选置顶文章
 * @since 2019.06.11
 *
 * @param $post_id
 */
function wnd_unstick_post($post_id) {
	return Wnd_Sticky::unstick_post($post_id);
}

/**
 * 获取精选置顶文章
 * @since 2019.06.11
 *
 * @param  	$post_type	文章类型
 * @param  	$number                   	文章数量
 * @return 	array                     	文章id数组
 */
function wnd_get_sticky_posts($post_type, $number = -1) {
	return Wnd_Sticky::get_sticky_posts($post_type, $number);
}

/**
 * 判断当前post是否为自定义revision
 * @since 2020.05.20
 */
function wnd_is_revision($post_id): bool {
	return Wnd_post::is_revision($post_id);
}

/**
 * 获取revision ID
 * 普通用户已公开发布的信息，如再次修改，将创建一个child post，并设置post meta。此revision不同于wp官方revision。
 * @since 2020.05.20
 */
function wnd_get_revision_id($post_id): int {
	return Wnd_post::get_revision_id($post_id);
}

/**
 * 获取付费内容的免费摘要文本
 * @since 0.9.52
 */
function wnd_get_free_content(WP_Post $post): string {
	if (!wnd_is_paid_post($post->ID)) {
		return $post->post_content;
	}

	$content = wnd_explode_post_by_more($post->post_content);
	return $content[0];
}

/**
 * 获取付费内容的付费文本
 * @since 0.9.52
 */
function wnd_get_paid_content(WP_Post $post): string {
	if (!wnd_is_paid_post($post->ID)) {
		return '';
	}

	$content = wnd_explode_post_by_more($post->post_content);
	return $content[1] ?? '';
}

/**
 * 根据当前 user 及 post 获取“安全”的 post
 * @since 0.9.52
 */
function wnd_filter_post(WP_Post $post): WP_Post{
	$user_id                = get_current_user_id();
	$is_current_post_author = ($user_id and $user_id = $post->post_author);
	if (!$is_current_post_author and wnd_is_paid_post($post->ID) and !wnd_user_has_paid($user_id, $post->ID)) {
		$post->post_content = wnd_get_free_content($post);
	}

	return $post;
}
