<?php
namespace Wnd\Action;

use Exception;
use Wnd\Model\Wnd_Product;

/**
 *@since 0.8.76
 *
 *设置产品属性
 */
class Wnd_Set_Product_Props extends Wnd_Action_Ajax {

	public function execute(int $post_id = 0): array{
		if (!$post_id) {
			$post_id = $this->data['post_id'] ?? 0;
		}

		if (!$post_id) {
			throw new Exception(__('ID 无效', 'wnd'));
		}

		Wnd_Product::set_object_props($post_id, $this->data);

		return ['status' => 1, 'msg' => __('设置成功', 'wnd')];
	}
}
