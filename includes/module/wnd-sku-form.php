<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Form_WP;

/**
 *@since 0.8.76
 *
 *SKU 表单
 */
class Wnd_SKU_Form extends Wnd_Module {

	protected static function build($args = []): string{
		$post_id = $args['post_id'] ?? 0;
		if (!$post_id) {
			return static::build_error_message(__('ID无效', 'wnd'));
		}

		/**
		 *读取现有 SKU 数据。数据格式参考：
		 *
		 *	$sku = [
		 *		'sku_0' => ['title' => '套餐1', 'price' => '0.1', 'stock' => 10],
		 *		'sku_1' => ['title' => '套餐2', 'price' => '0.2', 'stock' => 5],
		 *	];
		 */
		$sku        = wnd_get_post_meta($post_id, 'sku') ?: [];
		$form_input = '';
		foreach ($sku as $sku_detail) {
			$form_input .= '
			<div class="field has-addons">
				<div class="control">
					<div class="field is-horizontal">
						<div class="field-body">
							<input class="input is-small" name="sku_title[]" value="' . $sku_detail['title'] . '" type="text" placeholder="SKU 标题">
							<input class="input is-small" name="sku_stock[]" value="' . $sku_detail['stock'] . '" type="number" step="1" min="0" placeholder="SKU 库存">
						</div>
					</div>
				</div>
				<div class="control is-expanded">
					<input class="input is-small" name="sku_price[]" value="' . $sku_detail['price'] . '" placeholder="SKU 价格" min="0" step="0.01" type="number">
				</div>
				<div class="control">
					<button type="button" class="button remove-row is-small" title="Remove">-</button>
				</div>
			</div>';
		}unset($sku_detail);

		/**
		 *构建表单
		 *
		 */
		$form = new Wnd_Form_WP();

		// 现有数据
		$form->add_html($form_input);

		// 新建表单
		$addon_left = '<div class="field is-horizontal"><div class="field-body">';
		$addon_left .= '<input class="input is-small" name="sku_title[]" type="text" placeholder="' . __('SKU 标题', 'wnd') . '">';
		$addon_left .= '<input class="input is-small" name="sku_stock[]" type="number" step="1" min="0" placeholder="' . __('SKU 库存', 'wnd') . '">';
		$addon_left .= '</div></div>';

		$form->add_input_name('sku_title');
		$form->add_input_name('sku_stock');
		$form->add_number(
			[
				'addon_left'  => $addon_left,
				'name'        => 'sku_price[]',
				'placeholder' => __('SKU 价格', 'wnd'),
				'step'        => 0.01,
				'min'         => '0',
				'class'       => 'is-small',
				'addon_right' => '<button type="button" class="button add-row is-small" title="Add">+</button>',
			]
		);

		$form->add_hidden('post_id', $post_id);
		$form->set_action('wnd_set_sku');
		$form->set_submit_button(__('保存 SKU', 'wnd'));
		$form->build();

		return $form->html;
	}
}
