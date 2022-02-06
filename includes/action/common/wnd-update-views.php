<?php
namespace Wnd\Action\Common;

use Exception;
use Wnd\Action\Wnd_Action;

/**
 * 更新 Post Views
 * @since 0.9.20
 */
class Wnd_Update_Views extends Wnd_Action {

	protected $verify_sign = false;

	private $post_id;

	protected function execute(): array{
		// 更新字段信息
		if (wnd_inc_post_meta($this->post_id, 'views', 1)) {
			do_action('wnd_update_views', $this->post_id);
			return ['status' => 1, 'msg' => time()];
		}

		//字段写入失败，清除对象缓存
		wp_cache_delete($this->post_id, 'post_meta');
		return ['status' => 0, 'msg' => time()];
	}

	protected function parse_data() {
		$this->post_id = (int) ($this->data['post_id'] ?? 0);
	}

	protected function check() {
		if (!$this->post_id) {
			throw new Exception(__('ID无效', 'wnd'));
		}
	}
}
