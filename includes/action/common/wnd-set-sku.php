<?php
namespace Wnd\Action\Common;

use Exception;
use Wnd\Action\Wnd_Action_User;
use Wnd\Model\Wnd_SKU;

/**
 * 设置产品属性
 * @since 0.8.76
 */
class Wnd_Set_SKU extends Wnd_Action_User {

	private $post_id;

	protected function execute(): array{
		Wnd_SKU::set_object_sku($this->post_id, $this->data);

		return ['status' => 1, 'msg' => __('设置成功', 'wnd')];
	}

	protected function parse_data() {
		$this->post_id = $this->data['post_id'] ?? 0;
	}

	protected function check() {
		if (wnd_get_post_price($this->post_id)) {
			throw new Exception(__('当前商品已设置固定价格', 'wnd'));
		}

		if (!$this->post_id or !get_post($this->post_id)) {
			throw new Exception(__('ID 无效', 'wnd'));
		}

		if (!current_user_can('edit_post', $this->post_id)) {
			throw new Exception(__('权限错误', 'wnd'));
		}
	}
}
