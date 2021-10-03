<?php
namespace Wnd\Action;

use Exception;
use Wnd\Model\Wnd_SKU;

/**
 * 设置产品属性
 * @since 0.8.76
 */
class Wnd_Set_SKU extends Wnd_Action_User {

	private $post_id;
	private $post;

	public function execute(): array{
		$sku_data = Wnd_SKU::parse_sku_data($this->data, $this->post->post_type);
		Wnd_SKU::set_object_sku($this->post_id, $sku_data);

		return ['status' => 1, 'msg' => __('设置成功', 'wnd')];
	}

	protected function check() {
		$this->post_id = $this->data['post_id'] ?? 0;
		$this->post    = get_post($this->post_id);

		if (wnd_get_post_price($this->post_id)) {
			throw new Exception(__('当前商品已设置固定价格', 'wnd'));
		}

		if (!$this->post_id or !$this->post) {
			throw new Exception(__('ID 无效', 'wnd'));
		}

		if (!current_user_can('edit_post', $this->post_id)) {
			throw new Exception(__('权限错误', 'wnd'));
		}
	}
}
