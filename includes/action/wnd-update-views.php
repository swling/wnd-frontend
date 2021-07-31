<?php
namespace Wnd\Action;

/**
 * 更新 Post Views
 * @since 0.9.20
 */
class Wnd_Update_Views extends Wnd_Action {

	protected $verify_sign = false;

	public function execute(): array{
		$post_id = (int) ($this->data['post_id'] ?? 0);
		if (!$post_id) {
			return ['status' => 0, 'msg' => __('ID无效', 'wnd')];
		}

		// 更新字段信息
		if (wnd_inc_post_meta($post_id, 'views', 1)) {
			do_action('wnd_update_views', $post_id);
			return ['status' => 1, 'msg' => time()];
		}

		//字段写入失败，清除对象缓存
		wp_cache_delete($post_id, 'post_meta');
		return ['status' => 0, 'msg' => time()];
	}

}
