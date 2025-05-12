<?php

namespace Wnd\Module\Common;

use Wnd\Model\Wnd_SKU;
use Wnd\Module\Wnd_Module_Vue;

/**
 * 商品购买表单
 * @since 0.8.73
 */
class Wnd_Order_Form extends Wnd_Module_Vue {

	protected static function parse_data(array $args): array {
		$post_id  = $args['post_id'] ?? 0;
		$defaults = [
			'post_id'    => $post_id,
			'is_virtual' => '1',
			'skus'       => Wnd_SKU::get_object_sku($post_id),
		];
		return array_merge($defaults, $args);
	}
}
