<?php
namespace Wnd\Module;

use Wnd\Model\Wnd_Product;
use Wnd\View\Wnd_Form_WP;

/**
 *@since 0.8.73
 *
 *商品购买表单
 */
class Wnd_Order_Form extends Wnd_Module {

	protected static function build($args = []): string{
		$defaults = [
			'post_id'          => 0,
			'ajax'             => true,
			'buy_text'         => __('立即购买'),
			'add_cart_text'    => __('立即购买'),
			'support_quantity' => true,
		];
		$args = wp_parse_args($args, $defaults);
		extract($args);

		$post = get_post($post_id);
		if (!$post) {
			return __('ID 无效', 'wnd');
		}

		/**
		 * 遍历产品属性，并生成表单字段
		 *
		 *	$sku = [
		 *		'sku_0' => ['name' => '套餐1', 'price' => '0.1', 'stock' => 10],
		 *		'sku_1' => ['name' => '套餐2', 'price' => '0.2', 'stock' => 5],
		 *	];
		 */
		$sku     = Wnd_Product::get_object_sku($post_id);
		$options = [];
		foreach ($sku as $sku_id => $sku_detail) {
			$price              = (float) ($sku_detail['price'] ?? 0);
			$sku_name           = $sku_detail['name'] . ': ¥ ' . number_format($price, 2, '.', '');
			$options[$sku_name] = $sku_id;
		}unset($key, $value);

		// 构建表单
		$form = new Wnd_Form_WP($ajax);
		$form->add_hidden('module', 'wnd_payment_form');
		$form->add_hidden('post_id', $post_id);
		$form->set_action(get_permalink(wnd_get_config('ucenter_page')), 'GET');
		$form->add_radio(
			[
				'label'    => '',
				'name'     => Wnd_Product::$sku_key,
				'options'  => $options,
				'required' => 'required',
				'class'    => 'is-checkradio is-' . wnd_get_config('primary_color'),
			]
		);

		/**
		 *支持批量购买商品
		 */
		if ($support_quantity) {
			$form->add_number(
				[
					'name'     => Wnd_Product::$quantity_key,
					'value'    => 1,
					'step'     => 1,
					'min'      => 1,
					'required' => 'required',
					'class'    => 'is-small',
				]
			);
		}
		$form->set_submit_button($buy_text);
		$form->build();
		return $form->html;
	}
}
