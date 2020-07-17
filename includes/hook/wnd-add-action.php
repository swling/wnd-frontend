<?php
namespace Wnd\Hook;

use Wnd\Action\Wnd_Verify_Pay;
use Wnd\Utility\Wnd_Singleton_Trait;
use Wnd\View\Wnd_Form_Option;

/**
 *Wnd Action
 */
class Wnd_Add_Action {

	use Wnd_Singleton_Trait;

	private function __construct() {
		add_action('wnd_upload_file', [__CLASS__, 'action_on_upload_file'], 10, 3);
		add_action('wnd_upload_gallery', [__CLASS__, 'action_on_upload_gallery'], 10, 2);
		add_action('wnd_delete_file', [__CLASS__, 'action_on_delete_file'], 10, 3);
		add_action('wnd_do_action', [__CLASS__, 'action_on_do_action'], 10);
	}

	/**
	 *ajax上传文件时，根据 meta_key 做后续处理
	 *@since 2018
	 */
	public static function action_on_upload_file($attachment_id, $post_parent, $meta_key) {
		if (!$meta_key) {
			return;
		}

		// 存储在 Wnd option中 : _option_{$option_name}_{$option_key}
		if (0 === stripos($meta_key, '_option_')) {
			$old_option = Wnd_Form_Option::get_option_value_by_form_name($meta_key);
			if ($old_option) {
				wp_delete_attachment($old_option, true);
			}

			Wnd_Form_Option::update_option_by_form_name($meta_key, $attachment_id);
			return;
		}

		// WordPress原生缩略图
		if ($meta_key == '_wpthumbnail_id') {
			$old_meta = get_post_meta($post_parent, '_thumbnail_id', true);
			if ($old_meta) {
				wp_delete_attachment($old_meta, true);
			}

			set_post_thumbnail($post_parent, $attachment_id);
			return;
		}

		// 储存在文章字段
		if ($post_parent) {
			$old_meta = wnd_get_post_meta($post_parent, $meta_key);
			if ($old_meta) {
				wp_delete_attachment($old_meta, true);
			}
			wnd_update_post_meta($post_parent, $meta_key, $attachment_id);

			//储存在用户字段
		} else {
			$user_id       = get_current_user_id();
			$old_user_meta = wnd_get_user_meta($user_id, $meta_key);
			if ($old_user_meta) {
				wp_delete_attachment($old_user_meta, true);
			}
			wnd_update_user_meta($user_id, $meta_key, $attachment_id);
		}
	}

	/**
	 *@since 2019.05.05 相册
	 *do_action('wnd_upload_gallery', $return_array, $post_parent);
	 **/
	public static function action_on_upload_gallery($image_array, $post_parent) {
		if (empty($image_array)) {
			return;
		}

		$images = [];
		foreach ($image_array as $image_info) {
			// 上传失败的图片跳出
			if ($image_info['status'] === 0) {
				continue;
			}

			// 将 img+附件id 作为键名（整型直接做数组键名会存在有效范围，超过整型范围后会出现负数，0等错乱）
			$images['img' . $image_info['data']['id']] = $image_info['data']['id'];
		}
		unset($image_array, $image_info);

		$old_images = wnd_get_post_meta($post_parent, 'gallery');
		$old_images = is_array($old_images) ? $old_images : [];

		// 合并数组，注意新旧数据顺序 array_merge($images, $old_images) 表示将旧数据合并到新数据，因而新上传的在顶部，反之在尾部
		$new_images = array_merge($images, $old_images);
		wnd_update_post_meta($post_parent, 'gallery', $new_images);
	}

	/**
	 *ajax删除附件时
	 *@since 2018
	 */
	public static function action_on_delete_file($attachment_id, $post_parent, $meta_key) {
		if (!$meta_key) {
			return;
		}

		/**
		 *@since 2019.05.06 相册编辑
		 */
		if ($meta_key == 'gallery') {
			// 从相册数组中删除当前图片
			$user_id = get_current_user_id();
			$images  = $post_parent ? wnd_get_post_meta($post_parent, 'gallery') : wnd_get_user_meta($user_id, 'gallery');
			$images  = is_array($images) ? $images : [];
			unset($images['img' . $attachment_id]);

			if ($post_parent) {
				wnd_update_post_meta($post_parent, 'gallery', $images);
			} else {
				wnd_update_user_meta($user_id, 'gallery', $images);
			}

			return;
		}

		// 删除在 option
		if (0 === stripos($meta_key, '_option_')) {
			Wnd_Form_Option::delete_option_by_form_name($meta_key);
		}

		// 删除文章字段
		if ($post_parent) {
			wnd_delete_post_meta($post_parent, $meta_key);
			//删除用户字段
		} else {
			wnd_delete_user_meta(get_current_user_id(), $meta_key);
		}
	}

	/**
	 *do action
	 *在没有任何html输出的WordPress环境中执行的相关操作
	 *@since 2018.9.25
	 */
	public static function action_on_do_action() {
		$action = $_GET['action'] ?? '';
		$module = $_GET['module'] ?? '';

		// Action
		if ($action) {
			//@since 2019.03.04 刷新所有缓存（主要用于刷新对象缓存，静态缓存通常通过缓存插件本身删除）
			if ('wp_cache_flush' == $action) {
				wp_cache_flush();
				return;
			}

			//@since 2019.05.12 默认：校验nonce后执行action对应的控制类
			if (($_GET['_wpnonce'] ?? false) and wp_verify_nonce($_GET['_wpnonce'], $action)) {
				$class = \Wnd\Controller\Wnd_API::parse_class($action, 'Action');
				return is_callable([$class, 'execute']) ? $class::execute() : __('未定义的Action', 'wnd') . $class;
			} else {
				return __('Nonce校验失败', 'wnd');
			}
		}

		// Module
		if ($module) {
			$class = \Wnd\Controller\Wnd_API::parse_class($module, 'Module');
			$param = $_GET['param'] ?? '';
			return is_callable([$class, 'build']) ? $class::build($param) : __('未定义的Module', 'wnd') . $class;
		}

		// 支付异步通知
		if (!empty($_GET) or !empty($_POST)) {
			Wnd_Verify_Pay::execute();
		}
	}
}
