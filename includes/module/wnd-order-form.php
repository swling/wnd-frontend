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
			'post_id'       => 0,
			'ajax'          => true,
			'buy_text'      => __('立即购买'),
			'add_cart_text' => __('立即购买'),
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
		 */
		$form = new Wnd_Form_WP($ajax);
		$form->add_hidden('module', 'wnd_payment_form');
		$form->add_hidden('post_id', $post_id);
		$form->set_action(get_permalink(wnd_get_config('ucenter_page')), 'GET');

		$props      = Wnd_Product::get_object_props($post_id);
		$props_keys = Wnd_Product::get_props_keys();
		foreach ($props as $key => $value) {
			// 必须，否则会导致循环累积
			$options = [];

			//  SKU 为二维数组，需要额外处理
			if (Wnd_Product::$sku_key == $key and is_array($value)) {
				foreach ($value as $sku_key => $sku) {
					$sku_label           = $sku['title'] . ': ¥ ' . number_format($sku['price'], 2, '.', '');
					$options[$sku_label] = $sku_key;
				}
			} elseif ($value) {
				foreach ($value as $prop_key => $prop_value) {
					$options[$prop_value] = $prop_value;
				}
			}

			$form->add_radio(
				[
					'label'    => $props_keys[$key] ?? $key,
					'name'     => $key,
					'options'  => $options,
					'required' => 'required',
					'checked'  => $post->post_status,
					'class'    => 'is-checkradio is-danger',
				]
			);
		}unset($key, $value);

		$form->set_submit_button($buy_text);
		$form->build();
		return $form->html;
	}
}
