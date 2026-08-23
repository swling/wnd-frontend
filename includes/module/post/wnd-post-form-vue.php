<?php

namespace Wnd\Module\Post;

use Exception;
use Wnd\Module\Wnd_Module_Vue;

/**
 * 根据 post id 或 type 自行选择对应 vue 表单
 * @since 0.9.0
 */
class Wnd_Post_Form_Vue extends Wnd_Module_Vue {

	/**
	 * 权限核查请复写本方法
	 */
	protected static function check($args) {
		$defaults = [
			'post_id'   => 0,
			'post_type' => 'post',
		];
		$args = wp_parse_args($args, $defaults);

		$post_id = $args['post_id'];
		if ($post_id and !current_user_can('edit_post', $post_id)) {
			throw new Exception('Permission Denied');
		}
	}

	protected static function get_file_path($args = []): string {
		$defaults = [
			'post_id'   => 0,
			'post_type' => 'post',
		];
		$args = wp_parse_args($args, $defaults);

		$post_id   = $args['post_id'];
		$edit_post = $post_id ? get_post($post_id) : null;
		$post_type = $edit_post ? $edit_post->post_type : ($args['type'] ?? $args['post_type']);

		// 主题定义的表单优先，其次为插件表单
		if (wnd_is_revision($edit_post)) {
			$post_type = get_post_type($edit_post->post_parent);
		}

		return '/includes/module-vue/post/post-form-' . $post_type . '.vue';
	}
}
