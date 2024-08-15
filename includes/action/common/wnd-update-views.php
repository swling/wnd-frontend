<?php
namespace Wnd\Action\Common;

use Exception;
use Wnd\Action\Wnd_Action;
use Wnd\WPDB\Wnd_Analysis_DB;

/**
 * 更新 Post Views
 *
 * @since 0.9.20
 */
class Wnd_Update_Views extends Wnd_Action {

	protected $verify_sign = false;

	private $post_id;

	protected function execute(): array {
		$handler = Wnd_Analysis_DB::get_instance();
		$handler->update_post_views($this->post_id);

		// 捕获挂载函数中可能抛出的异常信息
		try {
			do_action('wnd_update_views', $this->post_id);
		} catch (Exception $e) {
			$error = 'Hook Error: ' . $e->getMessage();
		} finally {
			return ['status' => 1, 'msg' => 'success', 'data' => $error ?? ''];
		}
	}

	protected function parse_data() {
		$this->post_id = (int) ($this->data['post_id'] ?? 0);
	}

	protected function check() {
		if (!$this->post_id) {
			throw new Exception(__('ID无效', 'wnd'));
		}

		if (wnd_is_revision($this->post_id)) {
			throw new Exception('revision');
		}
	}

}
