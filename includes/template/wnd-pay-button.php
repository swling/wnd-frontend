<?php
namespace Wnd\Template;

use Exception;
use Wnd\Action\Wnd_Create_Order;
use Wnd\View\Wnd_Form_WP;

/**
 *@since 2020.03.21
 *
 *付费按钮
 *
 *接收传参，区分是否为付费阅读
 *将根据price 及 file 自动检测是否包含付费文件
 *支持同时设置付费阅读及付费下载
 *
 *付费阅读将刷新当前页面
 *
 *@see Wnd\Action\Wnd_Pay_For_Reading
 *@see Wnd\Action\Wnd_Pay_For_Downloads
 */
class Wnd_Pay_Button {

	protected static $post_id;
	protected static $post;
	protected static $post_price;
	protected static $file_id;

	protected static $user_id;
	protected static $user_money;
	protected static $user_has_paid;

	protected static $second_color;

	protected static $action;
	protected static $message;
	protected static $button_text;
	protected static $html;

	// 禁止按钮
	protected static $disabled = false;

	/**
	 *构建Html
	 *
	 */
	public static function build(int $post_id, bool $with_paid_content): string {
		static::$post_id = $post_id;
		static::$post    = static::$post_id ? get_post(static::$post_id) : false;
		if (!static::$post) {
			throw new Exception(__('Post ID无效', 'wnd'));
		}

		static::$user_id       = get_current_user_id();
		static::$post_price    = wnd_get_post_price(static::$post_id);
		static::$user_money    = wnd_get_user_money(static::$user_id);
		static::$user_has_paid = wnd_user_has_paid(static::$user_id, static::$post_id);
		static::$file_id       = wnd_get_post_meta(static::$post_id, 'file');
		static::$second_color  = 'is-' . wnd_get_config('second_color');
		static::$message       = '<span class="icon is-size-5"><i class="fa ' . (static::$user_has_paid ? 'fa-unlock' : 'fa-lock') . '"></i></span>';

		// 根据付费内容形式，构建对应变量：$message and $button_text
		if ($with_paid_content and static::$file_id) {
			static::build_pay_button_var();
		} elseif (static::$file_id) {
			static::build_paid_download_button_var();
		} elseif ($with_paid_content) {
			static::build_paid_reading_button_var();
		} else {
			return '';
		}

		return static::build_html();
	}

	protected static function build_html() {
		// 未登录用户
		if (!static::$user_id and !wnd_get_config('enable_anon_order')) {
			static::$html = '<div class="wnd-pay-button box has-text-centered">';
			static::$html .= '<div class="pay-notification field">' . static::$message . '</div>';
			static::$html .= '<div class="field is-grouped is-grouped-centered">';
			static::$html .= wnd_modal_button(__('登录', 'wnd'), 'wnd_user_center', 'do=login');
			static::$html .= '</div>';
			static::$html .= '</div>';
			return static::$html;
		}

		// 消费提示
		if (static::$user_id != static::$post->post_author and !static::$user_has_paid) {
			// 订单权限检测
			try {
				Wnd_Create_Order::check_create(static::$post_id, static::$user_id);
				static::$message .= '<p>' . __('当前余额：¥ ', 'wnd') . '<b>' . static::$user_money . '</b>&nbsp;&nbsp;' .
				__('本次消费：¥ ', 'wnd') . '<b>' . static::$post_price . '</b></p>';
			} catch (Exception $e) {
				static::$message .= $e->getMessage();
				static::$disabled = true;
			}
		}

		// 构建消息提示
		static::$html = '<div class="wnd-pay-button box has-text-centered">';
		static::$html .= '<div class="pay-notification field">' . static::$message . '</div>';

		// 当包含文件时，无论是否已支付，均需要提交下载请求，是否扣费将在Wnd\Action\Wnd_Pay_For_Downloads判断
		if (!static::$disabled and (!static::$user_has_paid or static::$file_id)) {
			$form = new Wnd_Form_WP();
			$form->add_hidden('post_id', static::$post_id);
			$form->set_action(static::$action);
			$form->set_submit_button(static::$button_text);
			$form->build();

			static::$html .= $form->html;
		}

		static::$html .= '</div>';
		return static::$html;
	}

	/**
	 * 付费下载
	 *@since 2018.09.17
	 *
	 *价格：wp post meta 	-> price
	 *文件：wnd post meta 	-> file
	 *
	 */
	protected static function build_paid_download_button_var() {
		static::$action = 'wnd_pay_for_downloads';

		// 没有文件
		if (!static::$file_id) {
			return;
		}

		// 已购买
		if (static::$user_has_paid) {
			static::$message .= '<p>' . __('您已付费：¥ ', 'wnd') . static::$post_price . '</p>';
			static::$button_text = __('下载', 'wnd');
			return;
		}

		// 作者
		if (static::$user_id == static::$post->post_author) {
			static::$message .= '<p>' . __('您发布的付费下载：¥ ', 'wnd') . static::$post_price . '</p>';
			static::$button_text = __('下载', 'wnd');
			return;

		}

		// 其他情况
		static::$message .= '<p>' . __('文件需付费下载', 'wnd') . '</p>';
		if (static::$post_price > 0) {
			static::$button_text = __('付费下载', 'wnd');
		} else {
			static::$button_text = __('免费下载', 'wnd');
		}
	}

	/**
	 *付费阅读
	 */
	protected static function build_paid_reading_button_var() {
		static::$action = 'wnd_pay_for_reading';

		// 已支付
		if (static::$user_has_paid) {
			static::$message .= '<p>' . __('您已付费：¥ ', 'wnd') . static::$post_price . '</p>';
			return;
		}

		// 作者本人
		if (static::$user_id == static::$post->post_author) {
			static::$message .= '<p>' . __('您的付费文章：¥ ', 'wnd') . static::$post_price . '</p>';
			return;
		}

		// 其他情况
		static::$button_text = __('付费阅读', 'wnd');
		static::$message .= '<p>' . __('以下内容需付费阅读：¥ ', 'wnd') . static::$post_price . '</p>';
	}

	/**
	 *@since 2020.06.04
	 *同时包含付费阅读及付费下载
	 */
	protected static function build_pay_button_var() {
		/**
		 *未支付前，采用付费阅读提交支付并刷新页面
		 *支付后，采用付费下载方法，下载文件（下载文件时，不会重复扣费）@see Wnd\Action\Wnd_Pay_For_Downloads
		 */
		if (static::$user_has_paid or static::$user_id == static::$post->post_author) {
			static::$action      = 'wnd_pay_for_downloads';
			static::$button_text = __('下载', 'wnd');
		} else {
			static::$action      = 'wnd_pay_for_reading';
			static::$button_text = __('立即付费', 'wnd');
		}

		// 已支付
		if (static::$user_has_paid) {
			static::$message .= '<p>' . __('您已付费：¥ ', 'wnd') . static::$post_price . '</p>';
			return;
		}

		// 作者本人
		if (static::$user_id == static::$post->post_author) {
			static::$message .= '<p>' . __('您的付费内容：¥ ', 'wnd') . static::$post_price . '</p>';
			return;
		}

		// 其他情况
		static::$message .= '<p>' . __('以下内容及文件需付费购买：¥ ', 'wnd') . static::$post_price . '</p>';
	}
}
