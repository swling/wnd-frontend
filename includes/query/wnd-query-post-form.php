<?php
namespace Wnd\Query;

use Exception;

/**
 * 插件管理菜单
 * @since 0.9.11
 */
class Wnd_Query_Post_Form extends Wnd_Query {

	protected static function query(array $args = []): array {
		$defaults = [
			'post_id'   => 0,
			'post_type' => 'post',
		];
		$args = wp_parse_args($args, $defaults);

		$post_id   = $args['post_id'];
		$edit_post = $post_id ? get_post($post_id) : null;
		$post_type = $edit_post ? $edit_post->post_type : ($args['type'] ?? $args['post_type']);

		$module   = static::get_post_form_module($post_type, $edit_post);
		$instance = new $module();
		return $instance->get_structure();
	}

	/**
	 * 根据 Post Type 查找对应表单模块
	 * @since 0.9.39
	 */
	private static function get_post_form_module(string $post_type, $edit_post = null): string {
		// 主题定义的表单优先，其次为插件表单
		if (wnd_is_revision($edit_post)) {
			$post_type = get_post_type($edit_post->post_parent);
		}

		// $namespace = 'Wndt';
		$module = 'Wndt_Post_Form_' . $post_type;
		$class  = 'Wndt\\Module\\Post\\' . $module;
		if (!class_exists($class)) {
			// $namespace = 'Wndt';
			$module = 'Wnd_Post_Form_' . $post_type;
			$class  = 'Wnd\\Module\\Post\\' . $module;
		}

		if (!class_exists($class)) {
			throw new Exception(__('未定义表单', 'wnd') . ' : ' . $class);
		}

		return $class;
	}

}
