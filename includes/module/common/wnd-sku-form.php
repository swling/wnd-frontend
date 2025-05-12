<?php

namespace Wnd\Module\Common;

use Exception;
use Wnd\Controller\Wnd_Request;
use Wnd\Model\Wnd_SKU;
use Wnd\Module\Wnd_Module_Vue;

/**
 * 产品属性设置表单
 * @since 0.8.76
 */
class Wnd_SKU_Form extends Wnd_Module_Vue {

	protected static function parse_data(array $args = []): array {
		$post_id = $args['post_id'] ?? 0;
		if (!$post_id) {
			throw new Exception(__('ID无效', 'wnd'));
		}

		$skus     = Wnd_SKU::get_object_sku($post_id);
		$app_data = [
			'post_id'  => $post_id,
			'skus'     => (object) $skus,
			'sign'     => Wnd_Request::sign(['sku', 'post_id']),
			'sku_keys' => Wnd_SKU::get_sku_keys(get_post_type($post_id)),
		];

		return $app_data;
	}
}
