<?php
namespace Wnd\Controller;

/**
 *@since 2019.02.12 文件校验下载
 *@param $_REQUEST['post_id']
 */
class Wnd_Paid_Download extends Wnd_Controller {

	public static function execute() {
		$post_id = (int) $_REQUEST['post_id'];
		$price   = get_post_meta($post_id, 'price', 1);
		$file_id = wnd_get_post_meta($post_id, 'file') ?: get_post_meta($post_id, 'file');

		$file = get_attached_file($file_id, $unfiltered = false);
		if (!$file) {
			wp_die('获取文件失败！', get_option('blogname'));
		}

		/**
		 *@since 2019.02.12
		 *此处必须再次校验用户下载权限。
		 *否则用户下载一次后即可获得ajax_nonce，从而在24小时内可以通过ajax校验
		 *此期间通过修改 post_id 可参数下载其他为经过权限校验的文件
		 *校验方式：
		 *1、通过生成特定nonce，$action必须包含 $post_id 或$file_id以确保文件唯一性，且改nonce不得通过其他任何获得
		 *（wp_nonce已包含了当前用户数据）
		 *2、再次完整验证用户权限
		 *（安全性更高，但重复验证稍显繁琐）
		 */

		/**
		 *@since 2019.02.12 nonce验证
		 */
		// $action  = $post_id.'_paid_download_key';
		// if(wnd_verify_nonce( $_REQUEST['_download_key'], $action )){
		// 	return wnd_download_file($file, $post_id);
		// }

		/**
		 *@since 2019.02.12 重复权限验证
		 */
		$user_id = get_current_user_id();
		//1、免费，或者已付费
		if (!$price or wnd_user_has_paid($user_id, $post_id)) {
			wnd_inc_wnd_post_meta($post_id, 'download_count', 1);
			return wnd_download_file($file, $post_id);
		}

		//2、 作者直接下载
		if (get_post_field('post_author', $post_id) == get_current_user_id()) {
			return wnd_download_file($file, $post_id);
		}

		// 校验失败
		wp_die('下载权限校验失败！', get_option('blogname'));
	}
}
