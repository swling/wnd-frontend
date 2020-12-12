<?php
namespace Wnd\Module;

use Wnd\Model\Wnd_SKU;
use Wnd\View\Wnd_Form_WP;

/**
 *@since 0.8.76
 *
 *产品属性设置表单
 */
class Wnd_SKU_Form extends Wnd_Module {

	protected static function build($args = []): string{
		$post_id = $args['post_id'] ?? 0;
		if (!$post_id) {
			return static::build_error_message(__('ID无效', 'wnd'));
		}

		/**
		 *根据配置定义默认空白 SKU 属性
		 */
		$sku_keys           = Wnd_SKU::get_sku_keys(get_post_type($post_id));
		$default_sku_detail = [];
		foreach (array_keys($sku_keys) as $key) {
			$default_sku_detail[$key] = '';
		}

		/**
		 *
		 *现有属性格式参考
		 *	$sku = [
		 *		'sku_0' => ['name' => '套餐1', 'price' => '0.1', 'stock' => 10],
		 *		'sku_1' => ['name' => '套餐2', 'price' => '0.2', 'stock' => 5],
		 *	];
		 *
		 * 获取现有属性并追加一个空白属性
		 */
		$sku   = Wnd_SKU::get_object_sku($post_id);
		$sku[] = $default_sku_detail;

		/**
		 *构建表单
		 */
		$form = new Wnd_Form_WP();
		foreach ($sku as $sku_detail) {
			// 将现有单个 SKU 信息与默认单个 SKU 信息合并，以确保属性字段完整性及后续新增字段呈现
			$sku_detail = array_merge($default_sku_detail, $sku_detail);

			// 单个 SKU 容器
			$form->add_html('<div class="field"><div class="box">');

			// 构造 SKU 详情字段
			$form->add_html('<div class="columns is-multiline">');
			foreach ($sku_detail as $sku_detail_key => $sku_detail_value) {
				$label = $sku_keys[$sku_detail_key] ?? $sku_detail_key;
				$form->add_html('<div class="column is-4">');
				$form->add_text(
					[
						'label'       => $label,
						'name'        => '_' . Wnd_SKU::$sku_key . '_' . $sku_detail_key . '[]',
						'value'       => $sku_detail_value,
						'placeholder' => $label,
						'class'       => 'is-small',
					]
				);
				$form->add_html('</div>');
			}unset($sku_detail_key, $sku_detail_value);
			$form->add_html('</div>');

			// 按钮设置：现有数据设置移除按钮，空白数据设置新增按钮
			if (array_filter($sku_detail)) {
				$form->add_html('<div class="has-text-centered"><button type="button" class="button remove-row is-small" title="Remove">-</button></div>');
			} else {
				$form->add_html('<div class="has-text-centered"><button type="button" class="button add-row is-small" title="Add">+</button></div>');
			}

			// 容器闭合
			$form->add_html('</div></div>');
		}unset($sku_detail);

		$form->add_hidden('post_id', $post_id);
		$form->set_action('wnd_set_sku');
		$form->set_submit_button(__('保存 SKU', 'wnd'));

		$form->build();
		return $form->html;
	}
}
