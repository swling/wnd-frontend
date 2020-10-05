<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Form;

/**
 *@since 2019.01.20
 *快速编辑文章状态表单
 */
class Wnd_Checkout_Form extends Wnd_Module {

	protected static function build($post_id = 0): string{
		// Demo data
		$kit = ['kit_1' => '套餐1', 'kit_2' => '套餐2'];

		$post = get_post($post_id);
		if (!$post) {
			return __('ID无效', 'wnd');
		}

		$form = new Wnd_Form();
		$form->add_hidden('module', 'Wnd_Order_Payment_Form');
		$form->add_html('<div class="field is-grouped is-grouped-centered">');
		$form->add_radio(
			[
				'name'     => 'kit',
				'options'  => array_flip($kit),
				'required' => 'required',
				'checked'  => $post->post_status,
				'class'    => 'is-checkradio is-danger',
			]
		);
		$form->add_html('</div>');

		$form->add_hidden('post_id', $post_id);
		$form->set_action('http://127.0.0.1/demo/demo.php', 'GET');
		$form->set_submit_button(__('立即购买', 'wnd'), 'is-danger');
		$form->set_submit_button(__('加入购物车', 'wnd'), 'is-danger');
		$form->build();

		return $form->html;
	}
}
