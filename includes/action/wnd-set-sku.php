<?php
namespace Wnd\Action;

use Exception;

/**
 *@since 0.8.76
 *
 *设置 产品 SKU
 */
class Wnd_Set_SKU extends Wnd_Action_Ajax {

	public function execute(int $post_id = 0): array{
		if (!$post_id) {
			$post_id = $this->data['post_id'] ?? 0;
		}

		if (!$post_id) {
			throw new Exception(__('ID 无效', 'wnd'));
		}

		/**
		 *依序遍历提取 sku_stock, stock_price, sku_title 并组合成新的数组，数据格式如下：
		 *
		 *	$sku = [
		 *		'sku_0' => ['title' => '套餐1', 'price' => '0.1', 'stock' => 10],
		 *		'sku_1' => ['title' => '套餐2', 'price' => '0.2', 'stock' => 5],
		 *	];
		 */
		$sku = [];
		for ($i = 0; $i < count($this->data['sku_stock']); $i++) {
			$sku['sku_' . $i]['stock'] = $this->data['sku_stock'][$i];
		}

		for ($i = 0; $i < count($this->data['sku_price']); $i++) {
			$sku['sku_' . $i]['price'] = $this->data['sku_price'][$i];
		}

		for ($i = 0; $i < count($this->data['sku_title']); $i++) {
			// SKU 标题为必选，若未设置，则删除本条信息
			if (!$this->data['sku_title'][$i]) {
				unset($sku['sku_' . $i]);
			} else {
				$sku['sku_' . $i]['title'] = $this->data['sku_title'][$i];
			}
		}

		wnd_update_post_meta($post_id, 'sku', array_filter($sku));

		return ['status' => 1, 'msg' => __('设置成功', 'wnd')];
	}
}
