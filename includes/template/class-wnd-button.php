<?php
namespace Wnd\Template;

use Exception;
use Wnd\View\Wnd_Form_WP;

/**
 *@since 2020.03.21
 *
 *付费按钮
 */
class Wnd_Button {

	/**
	 *定义付费相关公共变量
	 */
	protected static function get_payment_var($post_id): array{
		$post_id = $post_id;
		$post    = $post_id ? get_post($post_id) : false;
		if (!$post) {
			throw new Exception(__('Post ID无效', 'wnd'));
		}

		$user_id       = get_current_user_id();
		$price         = wnd_get_post_price($post_id);
		$user_money    = wnd_get_user_money($user_id);
		$user_has_paid = wnd_user_has_paid($user_id, $post_id);
		$primary_color = 'is-' . wnd_get_option('wnd', 'wnd_primary_color');
		$second_color  = 'is-' . wnd_get_option('wnd', 'wnd_second_color');

		return compact('post', 'user_id', 'price', 'user_money', 'user_has_paid', 'primary_color', 'second_color');
	}

	/**
	 * 付费下载
	 *@since 2018.09.17
	 *
	 *价格：wp post meta 	-> price
	 *文件：wnd post meta 	-> file
	 *
	 */
	public static function build_paid_download_button($post_id) {
		extract(self::get_payment_var($post_id));

		$button = '';
		$file   = wnd_get_post_meta($post_id, 'file');
		// 价格为空且没有文件
		if (!$file) {
			return;
		}

		// 未登录用户
		if (!$user_id) {
			$button_text = '请登录后下载';
			$button .= $price ? '<div class="message ' . $second_color . '"><div class="message-body">付费下载：¥' . $price . '</div></div>' : '';
			$button .= '<div class="field is-grouped is-grouped-centered"><button class="button is-warning" onclick="wnd_ajax_modal(\'wnd_user_center\',\'do=login\')">' . $button_text . '</button></div>';
			return $button;
		}

		if ($user_has_paid) {
			$button_text = '您已购买点击下载';

		} elseif ($user_id == $post->post_author) {
			$button_text = '您发布的下载文件';

		} elseif ($price > 0) {
			$button_text = '付费下载 ¥' . $price;

		} else {
			$button_text = '免费下载';
		}

		// 判断支付
		if ($price > $user_money and !$user_has_paid) {
			$msg = '<div class="message ' . $second_color . ' has-text-centered"><div class="message-body">';
			$msg .= '<p>¥ ' . $price . ' （可用余额：¥ ' . $user_money . '）</p>';
			if (wnd_get_option('wnd', 'wnd_alipay_appid')) {
				$msg .= '<a class="button ' . $primary_color . '" href="' . wnd_order_link($post_id) . '">在线支付</a>';
				$msg .= '&nbsp;&nbsp;';
				$msg .= '<a class="button ' . $primary_color . ' is-outlined" onclick="wnd_ajax_modal(\'wnd_user_recharge_form\')">余额充值</a>';
			} else {
				$msg .= '余额不足';
			}
			$msg .= '</div></div>';

			$button .= $msg;

			// 无论是否已支付，均需要提交下载请求，是否扣费将在wnd_ajax_pay_for_download内部判断
		} else {
			$form = new Wnd_Form_WP();
			$form->add_hidden('post_id', $post_id);
			$form->set_action('wnd_pay_for_downloads');
			$form->set_submit_button($button_text);
			$form->build();

			$button .= $form->html;
		}
		return $button;
	}

	/**
	 *付费阅读
	 */
	public static function build_paid_reading_button($post_id) {
		extract(self::get_payment_var($post_id));

		$button = '';

		// 未登录用户
		if (!$user_id) {
			$button .= '<div class="paid-content"><div class="message ' . $second_color . '"><div class="message-body">付费内容：¥' . $price . '</div></div></div>';
			$button = '<div class="field is-grouped is-grouped-centered"><button class="button is-warning" onclick="wnd_ajax_modal(\'wnd_user_center\',\'do=login\')">请登录</button></div>';
			return $button;
		}

		// 已支付
		if ($user_has_paid) {
			$button .= '<div class="message ' . $second_color . '"><div class="message-body">您已付费：¥' . $price . '</div></div>';
			return $button;
		}

		// 作者本人
		if ($user_id == $post->post_author) {
			$button .= '<div class="message ' . $second_color . '"><div class="message-body">您的付费文章：¥' . $price . '</div></div>';
			return $button;
		}

		// 已登录未支付
		$button_text = '付费阅读： ¥' . wnd_get_post_price($post_id);
		if ($price > $user_money) {
			$msg = '<div class="message ' . $second_color . ' has-text-centered"><div class="message-body">';
			$msg .= '<p>¥ ' . $price . ' （可用余额：¥ ' . $user_money . '）</p>';
			if (wnd_get_option('wnd', 'wnd_alipay_appid')) {
				$msg .= '<a class="button ' . $primary_color . '" href="' . wnd_order_link($post_id) . '">在线支付</a>';
				$msg .= '&nbsp;&nbsp;';
				$msg .= '<a class="button ' . $primary_color . ' is-outlined" onclick="wnd_ajax_modal(\'wnd_user_recharge_form\')">余额充值</a>';
			} else {
				$msg .= '余额不足';
			}
			$msg .= '</div></div>';

			$button .= $msg;
		} else {
			$form = new Wnd_Form_WP();
			$form->add_hidden('post_id', $post_id);
			$form->set_action('wnd_pay_for_reading');
			$form->set_submit_button($button_text);
			$form->build();

			$button .= $form->html;
		}
		return $button;
	}
}
