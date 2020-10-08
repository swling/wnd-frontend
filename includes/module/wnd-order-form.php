<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Form_WP;

/**
 *@since 0.8.73
 *
 *商品购买表单
 */
class Wnd_Order_Form extends Wnd_Module {

	protected static function build($args = []): string{
		$defaults = [
			'post_id' => 0,
			'ajax'    => true,
		];

		$args = wp_parse_args($args, $defaults);
		extract($args);

		$post = get_post($post_id);
		if (!$post) {
			return __('ID无效', 'wnd');
		}

		/**
		 *SKU 选项
		 */
		$sku         = wnd_get_post_meta($post_id, 'sku') ?: [];
		$sku_options = [];
		foreach ($sku as $key => $value) {
			$sku_options[$value['title']] = $key;
		}

		$form = new Wnd_Form_WP($ajax);
		$form->add_html('<div class="field is-grouped is-grouped-centered">');
		if ($sku_options) {
			$form->add_radio(
				[
					'name'     => 'sku',
					'options'  => $sku_options,
					'required' => 'required',
					'checked'  => $post->post_status,
					'class'    => 'is-checkradio is-danger',
				]
			);
		}
		$form->add_html('</div>');

		$form->add_hidden('post_id', $post_id);
		$form->add_hidden('module', 'wnd_payment_form');
		$form->set_action(get_permalink(wnd_get_config('ucenter_page')), 'GET');
		$form->set_submit_button(__('加入购物车', 'wnd'), 'is-danger');

		$form->build();
		return $form->html;
	}
}
