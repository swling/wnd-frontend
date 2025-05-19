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
			'post_id' => $post_id,
			'skus'    => (object) $skus,
			'sign'    => Wnd_Request::sign(['sku', 'post_id']),
			'options' => static::get_options(),
		];

		return $app_data;
	}

	private static function get_options(): array {
		$settings = get_option('wnd_store_settings');
		if (isset($settings['sku_keys']) and is_array($settings['sku_keys'])) {
			return $settings['sku_keys'];
		}

		// 默认 SKU 选项
		return [
			[
				'name'  => '请前往 Dashboard 定义产品 SKU 属性',
				'attrs' => [
					__('颜色', 'wnd'),
					__('尺寸', 'wnd'),
				],
			],
		];
	}
}
