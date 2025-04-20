<?php

namespace Wnd\Module\Post;

use Exception;
use Wnd\Module\Wnd_Module_Html;

/**
 * 获取订单详情（未完善信息）
 * @since 0.9.0
 */
class Wnd_Post_Form_Vue extends Wnd_Module_Html {

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

	protected static function build(array $args = []): string {
		$defaults = [
			'post_id'   => 0,
			'post_type' => 'post',
		];
		$args = wp_parse_args($args, $defaults);

		$post_id   = $args['post_id'];
		$edit_post = $post_id ? get_post($post_id) : null;
		$post_type = $edit_post ? $edit_post->post_type : ($args['type'] ?? $args['post_type']);

		return static::get_post_form($post_type, $edit_post);
	}

	/**
	 * 根据 Post Type 查找对应表单模块
	 * @since 0.9.39
	 */
	private static function get_post_form(string $post_type, $edit_post = null): string {
		// 主题定义的表单优先，其次为插件表单
		if (wnd_is_revision($edit_post)) {
			$post_type = get_post_type($edit_post->post_parent);
		}

		WND_PATH . DIRECTORY_SEPARATOR . 'includes';
		get_template_directory() . DIRECTORY_SEPARATOR . 'includes';

		$file_path = '/includes/module-vue/post/post-form-' . $post_type . '.vue';
		$file      = get_template_directory() . $file_path;
		if (file_exists($file)) {
			return file_get_contents($file);
		} elseif (file_exists(WND_PATH . $file_path)) {
			return file_get_contents(WND_PATH . $file_path);
		} else {
			throw new Exception(__('未定义表单', 'wnd') . ' : ' . $post_type);
			return file_get_contents(WND_PATH . '/includes/module-vue/post-form-post.vue');
		}
	}
}
