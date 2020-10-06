<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Form_WP;

/**
 *@since 0.8.73
 *
 *商品购买表单
 */
class Wnd_Order_Form extends Wnd_Module {

	protected static function build(): string{
		$defaults = [
			'post_id' => 0,
			'ajax'    => true,
		];

		$args = wp_parse_args(static::$args, $defaults);
		extract($args);

		/**
		 *套餐单选项
		 */
		// Demo data
		$kit = [
			'kit_1' => ['title' => '套餐1', 'price' => '0.1'],
			'kit_2' => ['title' => '套餐2', 'price' => '0.2'],
		];
		foreach ($kit as $key => $value) {
			$options[$value['title']] = $key;
		}

		$post = get_post($post_id);
		if (!$post) {
			return __('ID无效', 'wnd');
		}

		$form = new Wnd_Form_WP($ajax);
		$form->add_hidden('module', 'wnd_payment_form');
		$form->add_html('<div class="field is-grouped is-grouped-centered">');
		$form->add_radio(
			[
				'name'     => 'kit',
				'options'  => $options,
				'required' => 'required',
				'checked'  => $post->post_status,
				'class'    => 'is-checkradio is-danger',
			]
		);
		$form->add_html('</div>');
		$form->add_hidden('post_id', $post_id);

		if (!$ajax) {
			$form->set_action(get_permalink(wnd_get_config('ucenter_page')), 'GET');
		}

		$form->set_submit_button(__('立即购买', 'wnd'), 'is-danger');
		$form->set_submit_button(__('加入购物车', 'wnd'), 'is-danger');
		$form->build();

		return $form->html;
	}
}
