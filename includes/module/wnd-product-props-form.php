<?php
namespace Wnd\Module;

use Wnd\Model\Wnd_Product;
use Wnd\View\Wnd_Form_WP;

/**
 *@since 0.8.76
 *
 *产品属性设置表单
 */
class Wnd_Product_Props_Form extends Wnd_Module {

	protected static function build($args = []): string{
		$post_id = $args['post_id'] ?? 0;
		if (!$post_id) {
			return static::build_error_message(__('ID无效', 'wnd'));
		}

		/**
		 *构建表单
		 *
		 */
		$form = new Wnd_Form_WP();

		// 现有
		$props = Wnd_Product::get_object_props($post_id);

		// 所有产品字段
		foreach (Wnd_Product::get_props_keys() as $key => $name) {
			/**
			 * SKU 为二维数组，需要额外处理
			 */
			if (Wnd_Product::$sku_key == $key) {
				$sku_data = $props[Wnd_Product::$sku_key] ?? [];
				$sku_data = is_array($sku_data) ? $sku_data : [];
				static::add_sku_input($sku_data, $form);
				continue;
			}

			/**
			 * 其他常规属性
			 * - 现有数据
			 */
			if (isset($props[$key]) and is_array($props[$key])) {
				for ($i = 0; $i < count($props[$key]); $i++) {
					$form->add_text(
						[
							'label'       => (0 == $i) ? $name : '',
							'name'        => $key . '[]',
							'value'       => $props[$key][$i],
							'placeholder' => $name,
							'class'       => 'is-small',
							'addon_right' => '<button type="button" class="button remove-row is-small" title="Remove">-</button>',
						]
					);
				}
			}

			// 新增
			$form->add_text(
				[
					'label'       => isset($props[$key]) ? '' : $name,
					'name'        => $key . '[]',
					'value'       => '',
					'placeholder' => $name,
					'class'       => 'is-small',
					'addon_right' => '<button type="button" class="button add-row is-small" title="Add">+</button>',
				]
			);
		}

		$form->add_hidden('post_id', $post_id);
		$form->set_action('wnd_set_product_props');
		$form->set_submit_button(__('保存 SKU', 'wnd'));

		// 合并常规字段与 SKU 字段
		// $form->set_input_values(array_merge($sku_input_values ?? [], $form->get_input_values()));
		$form->build();

		return $form->html;

	}

	/**
	 *构建 SKU 字段表单数组数据
	 */
	protected static function add_sku_input(array $sku_data, Wnd_Form_WP $form) {
		/**
		 *读取现有 SKU 数据。数据格式参考：
		 *
		 *	$sku = [
		 *		'sku_0' => ['title' => '套餐1', 'price' => '0.1', 'stock' => 10],
		 *		'sku_1' => ['title' => '套餐2', 'price' => '0.2', 'stock' => 5],
		 *	];
		 */
		foreach ($sku_data as $sku) {
			$form->add_number(
				[
					'addon_left'  => static::build_sku_addon_left($sku),
					'name'        => 'sku_price[]',
					'value'       => $sku['price'],
					'placeholder' => __('SKU 价格', 'wnd'),
					'step'        => 0.01,
					'min'         => '0',
					'class'       => 'is-small',
					'addon_right' => '<button type="button" class="button remove-row is-small" title="Remove">-</button>',
				]
			);
		}unset($sku);

		/**
		 *构建表单
		 *
		 */
		$form->add_number(
			[
				'addon_left'  => static::build_sku_addon_left(),
				'name'        => 'sku_price[]',
				'placeholder' => __('SKU 价格', 'wnd'),
				'step'        => 0.01,
				'min'         => '0',
				'class'       => 'is-small',
				'addon_right' => '<button type="button" class="button add-row is-small" title="Add">+</button>',
			]
		);

		/**
		 * - Addon 中将添加 input 字段，需要新增字段名，以生成表单签名，两者需保持一致。
		 */
		$form->add_input_name('sku_title');
		$form->add_input_name('sku_stock');
	}

	/**
	 *构建 SKU 附加字段
	 */
	protected static function build_sku_addon_left(array $sku = []): string{
		$title = $sku['title'] ?? '';
		$stock = $sku['stock'] ?? '';

		$addon = '<div class="field is-horizontal"><div class="field-body">';
		$addon .= '<input class="input is-small" name="sku_title[]" value="' . $title . '"  type="text" placeholder="' . __('SKU 标题', 'wnd') . '">';
		$addon .= '<input class="input is-small" name="sku_stock[]" value="' . $stock . '" type="number" step="1" min="0" placeholder="' . __('SKU 库存', 'wnd') . '">';
		$addon .= '</div></div>';

		return $addon;
	}
}
