<?php
namespace Wnd\Endpoint;

use Wnd\Model\Wnd_Order_Product;

/**
 *@since 2019.02.12 文件校验下载
 */
class Wnd_Paid_Download extends Wnd_Endpoint_Action {

	protected function do() {
		/**
		 *@since 0.8.76
		 *新增 SKU ID
		 */
		$sku_id = $_REQUEST[Wnd_Order_Product::$sku_id_key] ?? '';

		$post_id = (int) $_REQUEST['post_id'];
		$user_id = get_current_user_id();
		$price   = wnd_get_post_price($post_id, $sku_id);
		$file    = wnd_get_paid_file($post_id);
		if (!$file) {
			wp_die(__('获取文件失败', 'wnd'), get_option('blogname'));
		}

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
		wp_die(__('权限错误', 'wnd'), get_option('blogname'));
	}
}
