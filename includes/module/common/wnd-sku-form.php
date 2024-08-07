<?php
namespace Wnd\Module\Common;

use Exception;
use Wnd\Controller\Wnd_Request;
use Wnd\Model\Wnd_SKU;
use Wnd\Module\Wnd_Module_Html;

/**
 * 产品属性设置表单
 * @since 0.8.76
 */
class Wnd_SKU_Form extends Wnd_Module_Html {

	protected static function build(array $args = []): string {
		$post_id = $args['post_id'] ?? 0;
		if (!$post_id) {
			throw new Exception(__('ID无效', 'wnd'));
		}

		/**
		 * 根据配置定义默认空白 SKU 属性
		 */
		$sku_keys           = Wnd_SKU::get_sku_keys(get_post_type($post_id));
		$default_sku_detail = [];
		foreach (array_keys($sku_keys) as $key) {
			$default_sku_detail[$key] = '';
		}

		/**
		 *
		 * 现有属性格式参考
		 * 	$sku = [
		 * 		'sku_0' => ['name' => '套餐1', 'price' => '0.1', 'stock' => 10],
		 * 		'sku_1' => ['name' => '套餐2', 'price' => '0.2', 'stock' => 5],
		 * 	];
		 *
		 * 获取现有属性并追加一个空白属性
		 */
		$sku      = Wnd_SKU::get_object_sku($post_id);
		$sku_data = [];
		foreach ($sku as $key => $sku_detail) {
			$sku_data[] = array_merge($default_sku_detail, $sku_detail);
		}
		$sku_data[] = $default_sku_detail;

		$app_data = [
			'post_id'     => $post_id,
			'sku'         => $sku_data,
			'sku_keys'    => $sku_keys,
			'action'      => 'common/wnd_set_sku',
			'sign'        => Wnd_Request::sign(['sku', 'post_id']),
			'sign_key'    => Wnd_Request::$sign_name,
			'submit_text' => __('保存', 'wnd'),
		];

		/**
		 * 采用 vue 文件编写代码，并通过 php 读取文件文本作为字符串使用
		 * 主要目的是便于编辑，避免在 php 文件中混入大量 HTML 源码，难以维护
		 * 虽然的确基于 vue 构建，然而在这里，它并不是标准的 vue 文件，而是 HTML 文件
		 * 之所以使用 .vue 后缀是因为 .HTML 文件在文件夹中将以浏览器图标展示，非常丑陋，毫无科技感
		 * 仅此而已
		 */
		$html = '<script>var app_data = ' . json_encode($app_data, JSON_UNESCAPED_UNICODE) . ';</script>';
		$html .= file_get_contents(WND_PATH . '/includes/module-vue/common/sku-form.vue');
		return $html;
	}
}
