<?php
namespace Wnd\Module;

use Exception;
use Wnd\Module\Wnd_Module_Form;
use Wnd\Permission\Wnd_PPC;

/**
 * 内容编辑表抽象类：权限检测
 * @since 0.9.36
 */
abstract class Wnd_Post_Form extends Wnd_Module_Form {

	public static $post_type;

	protected static function check($args) {
		$post_id = $args['post_id'] ?? 0;
		if (!static::$post_type) {
			throw new Exception(__('Post Type 无效', 'wnd') . '@' . __CLASS__);
		}

		// 更新权限检测
		if ($post_id) {
			$edit_post = $post_id ? get_post($post_id) : false;
			if (!$edit_post) {
				throw new Exception(static::build_error_notification(__('ID 无效', 'wnd') . '@' . __CLASS__, true));
			}

			$ppc = Wnd_PPC::get_instance($edit_post->post_type);
			$ppc->set_post_id($post_id);
			$ppc->check_update();

			// 发布权限检测
		} else {
			$ppc = Wnd_PPC::get_instance(static::$post_type);
			$ppc->check_create();
		}
	}
}
