<?php
namespace Wnd\Endpoint;

/**
 *@since 0.9.20
 *更新 Post Views
 *
 *之所以通过本节点而非 Action 更新，在于 Action 需要进行 nonce 校验不便于前端实现
 */
class Wnd_Update_Views extends Wnd_Endpoint {

	protected $content_type = 'json';

	protected function do() {
		$post_id = (int) ($this->data['post_id'] ?? 0);
		if (!$post_id) {
			wp_send_json(['status' => 0, 'msg' => __('ID无效', 'wnd')]);
		}

		// 更新字段信息
		if (wnd_inc_post_meta($post_id, 'views', 1)) {
			do_action('wnd_update_views', $post_id);
			wp_send_json(['status' => 1, 'msg' => time()]);

			//字段写入失败，清除对象缓存
		} else {
			wp_cache_delete($post_id, 'post_meta');
			wp_send_json(['status' => 0, 'msg' => time()]);
		}
	}
}
