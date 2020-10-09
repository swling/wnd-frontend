<?php
namespace Wnd\Module;

use Wnd\Model\Wnd_Payment;
use Wnd\Model\Wnd_Product;
use Wnd\Utility\Wnd_Form_Data;
use Wnd\View\Wnd_Form_WP;

/**
 *@since 2020.06.30
 *在线支付订单表单
 *匿名支付订单默认启用人机验证
 */
class Wnd_Payment_Form extends Wnd_Module {

	protected static function build($args = []): string{
		/**
		 *订单基本信息 + 产品属性等参数
		 *移除表单签名参数
		 */
		$defaults = [
			'post_id'  => 0,
			'quantity' => 1,
		];
		unset($args[Wnd_Form_Data::$form_sign_name]);
		$args = wp_parse_args($args, $defaults);

		// 将数组元素依次定义为按键名命名的变量
		extract($args);

		/**
		 *@since 0.8.76
		 *产品属性
		 */
		$props = Wnd_Product::parse_props_data($args);
		// print_r($props);

		/**
		 *基础信息
		 */
		$user_id         = get_current_user_id();
		$gateway_options = Wnd_Payment::get_gateway_options();
		$user_money      = wnd_get_user_money($user_id);
		$post_price      = wnd_get_post_price($post_id);

		// 消费提示
		$message = $user_id ? __('当前余额：¥ ', 'wnd') . '<b>' . number_format($user_money, 2, '.', '') . '</b>&nbsp;&nbsp;' : '';
		$message .= __('本次消费：¥ ', 'wnd') . '<b>' . number_format($post_price, 2, '.', '') . '</b>';

		/**
		 *@since 0.8.73
		 *免费订单
		 */
		if ($post_price <= 0) {
			$form = new Wnd_Form_WP(true, !$user_id);
			$form->set_form_title(get_the_title($post_id), true);
			if (!$user_id) {
				$form->add_html(static::build_notification(__('您当前尚未登录，匿名订单仅24小时有效，请悉知！', 'wnd'), true));
			}
			$form->add_html('<div class="has-text-centered field">');
			$form->add_html('<p>' . $message . '</p>');
			$form->add_checkbox(
				[
					'name'     => 'agreement',
					'options'  => ['<i class="is-size-7">' . __('已阅读并同意交易协议及产品使用协议') . '</i>' => 1],
					'checked'  => true,
					'required' => 'required',
				]
			);
			$form->add_html('</div>');
			$form->set_action('wnd_pay_for_order');

			/**
			 *遍历参数信息并构建表单字段
			 */
			foreach ($args as $key => $value) {
				$form->add_hidden($key, $value);
			}

			$form->add_hidden('payment_gateway', 'internal');
			$form->set_submit_button(__('确定', 'wnd'));
			$form->build();

			return $form->html;
		}

		/**
		 *常规订单支付
		 *
		 * - 如果余额足够，提供站内支付结算方式
		 */
		if ($user_money >= $post_price) {
			$gateway_options = array_merge([__('余额支付', 'wnd') => 'internal'], $gateway_options);
		}

		$form = new Wnd_Form_WP(true, !$user_id);
		$form->set_form_title(get_the_title($post_id), true);
		if (!$user_id) {
			$form->add_html(static::build_notification(__('您当前尚未登录，匿名订单仅24小时有效，请悉知！', 'wnd'), true));
		}
		$form->add_html('<div class="has-text-centered field">');
		$form->add_html('<p>' . $message . '</p>');
		$form->add_radio(
			[
				'name'     => 'payment_gateway',
				'options'  => $gateway_options,
				'required' => 'required',
				'checked'  => $user_money >= $post_price ? 'internal' : Wnd_Payment::get_default_gateway(),
				'class'    => 'is-checkradio is-danger',
			]
		);
		$form->add_checkbox(
			[
				'name'     => 'agreement',
				'options'  => ['<i class="is-size-7">' . __('已阅读并同意交易协议及产品使用协议') . '</i>' => 1],
				'checked'  => true,
				'required' => 'required',
			]
		);
		$form->add_html('</div>');
		$form->set_action('wnd_pay_for_order');

		/**
		 *遍历参数信息并构建表单字段
		 */
		foreach ($args as $key => $value) {
			$form->add_hidden($key, $value);
		}

		$form->set_submit_button(__('确定', 'wnd'));
		$form->build();

		return $form->html;
	}
}
