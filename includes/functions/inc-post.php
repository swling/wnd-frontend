<?php
use Wnd\Model\Wnd_Post;
use Wnd\Model\Wnd_Post_Sticky;

/**
 *@since 2019.02.19
 *当前用户可以写入或管理的文章类型
 *@return array : post type name数组
 */
function wnd_get_allowed_post_types() {
	return Wnd_Post::get_allowed_post_types();
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
function wnd_is_title_duplicated($title, $exclude_id = 0, $post_type = 'post') {
	return Wnd_Post::is_title_duplicated($title, $exclude_id, $post_type);
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
function wnd_get_post_by_slug($post_name, $post_type = 'post', $post_status = 'publish') {
	return Wnd_Post::get_post_by_slug($post_name, $post_type, $post_status);
}

/**
 *@since 2019.06.11
 *精选置顶文章
 *精选post id存储方式：
 *option：二维数组 wnd_sticky_posts[$post_type]['post'.$post_id]
 *@param $post_id
 **/
function wnd_stick_post($post_id) {
	return Wnd_Post_Sticky::stick_post($post_id);
}

/**
 *@since 2019.06.11
 *取消精选置顶文章
 *@param $post_id
 **/
function wnd_unstick_post($post_id) {
	return Wnd_Post_Sticky::unstick_post($post_id);
}

/**
 *@since 2019.06.11
 *获取精选置顶文章
 *@param 	$post_type 		文章类型
 *@return 	array or false 	文章id数组
 **/
function wnd_get_sticky_posts($post_type) {
	return Wnd_Post_Sticky::get_sticky_posts($post_type);
}
