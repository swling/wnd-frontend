<?php

namespace Wnd\Module\Admin;

use Wnd\Module\Wnd_Module_Vue;

/**
 * 产品属性设置表单
 * @since 0.8.76
 */
class Wnd_SKU_Keys_Editor extends Wnd_Module_Vue {

	protected static function parse_data(array $args = []): array {
		$sku_keys = wnd_get_option('wnd_store_settings', 'sku_keys');
		$app_data = [
			'sku_keys' => (array) ($sku_keys ?: []),
		];

		return $app_data;
	}
}
