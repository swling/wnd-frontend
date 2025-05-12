<?php
namespace Wnd\Template;

use Exception;
use Wnd\View\Wnd_Form_WP;

/**
 * 付费按钮
 * - 接收传参，区分是否为付费阅读
 * - 将根据price 及 file 自动检测是否包含付费文件
 * - 支持同时设置付费阅读及付费下载
 * - 付费阅读将刷新当前页面
 *
 * @see Wnd\Action\Wnd_Pay_For_Reading
 * @see Wnd\Action\Wnd_Pay_For_Downloads
 * @since 2020.03.21
 */
class Wnd_Pay_Button {

	private $post_id;
	private $post;
	private $post_price;
	private $file;

	private $user_id;
	private $is_author;
	private $user_money;
	private $user_has_paid;

	private $message;
	private $button_text;
	private $html;

	private $primary_color;

	// 禁止按钮
	private $disabled = false;

	/**
	 * Construct
	 */
	public function __construct(\WP_Post $post, bool $with_paid_content, string $button_text = '') {
		$this->post_id       = $post->ID;
		$this->post          = $post;
		$this->user_id       = get_current_user_id();
		$this->is_author     = $this->user_id == $this->post->post_author ? true : false;
		$this->post_price    = wnd_get_post_price($this->post_id, '', true);
		$this->user_money    = wnd_get_user_balance($this->user_id, true);
		$this->user_has_paid = wnd_user_has_paid($this->user_id, $this->post_id);
		$this->file          = wnd_get_paid_file($this->post_id);
		$this->primary_color = 'is-' . wnd_get_config('primary_color');

		if (floatval($this->post_price) <= 0 or $this->user_has_paid) {
			$this->message = '<span class="icon is-size-5"><i class="fa fa-unlock"></i></span>';
		} else {
			$this->message = '<span class="icon is-size-5"><i class="fa fa-lock"></i></span>';
		}

		// 根据付费内容形式，构建对应变量：$message and $button_text
		if ($with_paid_content and $this->file) {
			$this->build_pay_button_var();
		} elseif ($this->file) {
			$this->build_paid_download_button_var();
		} elseif ($with_paid_content) {
			$this->build_paid_reading_button_var();
		} else {
			throw new Exception(__('免费且不含文件', 'wnd'));
		}

		/**
		 * 如果外部传参按钮文字，则覆盖
		 * @since 0.8.73
		 */
		$this->button_text = $button_text ?: $this->button_text;
	}

	/**
	 * 构建Html
	 *
	 */
	public function render(): string {
		// 未登录用户，且不支持匿名订单
		if (!$this->user_id and !wnd_get_config('enable_anon_order')) {
			$this->html = '<div class="wnd-pay-button box has-text-centered">';
			$this->html .= '<div class="pay-notification field">' . $this->message . '</div>';
			$this->html .= '<div class="field is-grouped is-grouped-centered">';
			$this->html .= wnd_modal_button(__('登录', 'wnd'), 'user/wnd_user_center', ['do' => 'login'], $this->primary_color);
			$this->html .= '</div>';
			$this->html .= '</div>';
			return $this->html;
		}

		$this->html = '<div class="wnd-pay-button box has-text-centered">';
		// 消费提示
		if ($this->user_id != $this->post->post_author and !$this->user_has_paid and floatval($this->post_price) > 0) {
			$this->message .= '<p>' . __('当前余额：¥ ', 'wnd') . '<b>' . $this->user_money . '</b>&nbsp;&nbsp;' .
			__('本次消费：¥ ', 'wnd') . '<b>' . $this->post_price . '</b></p>';
		}

		// 构建消息提示
		$this->html .= '<div class="pay-notification field">' . $this->message . '</div>';

		/**
		 * - 唤起支付对话框
		 * - 当包含文件时，无论是否已支付，均需要提交下载请求，是否扣费将在 Wnd\Action\Wnd_Pay_For_Downloads 判断
		 */
		if (floatval($this->post_price) > 0 and !$this->user_has_paid and !$this->is_author) {
			$this->html .= wnd_modal_button($this->button_text, 'common/wnd_payment_form', ['post_id' => $this->post_id,'is_virtual' => 1], $this->primary_color);
		} elseif (!$this->disabled and $this->file) {
			$form = new Wnd_Form_WP();
			$form->add_hidden('post_id', $this->post_id);
			$form->set_route('action', 'common/wnd_pay_for_downloads');
			$form->set_submit_button($this->button_text);
			$form->build();
			$this->html .= $form->html;
		}

		$this->html .= '</div>';
		return $this->html;
	}

	/**
	 * 付费下载
	 * 价格：wp post meta 	-> price
	 * 文件：wnd post meta 	-> file
	 * @since 2018.09.17
	 */
	private function build_paid_download_button_var() {
		// 没有文件
		if (!$this->file) {
			return;
		}

		// 已购买
		if ($this->user_has_paid) {
			$this->message .= '<p>' . __('您已付费：¥ ', 'wnd') . $this->post_price . '</p>';
			$this->button_text = __('下载', 'wnd');
			return;
		}

		// 作者
		if ($this->is_author) {
			$this->message .= '<p>' . __('您发布的文件：¥ ', 'wnd') . $this->post_price . '</p>';
			$this->button_text = __('下载', 'wnd');
			return;

		}

		// 其他情况
		if (floatval($this->post_price) > 0) {
			$this->message .= '<p>' . __('文件需付费下载：¥', 'wnd') . $this->post_price . '</p>';
			$this->button_text = __('付费下载', 'wnd');
		} else {
			$this->button_text = __('免费下载', 'wnd');
		}
	}

	/**
	 * 付费阅读
	 */
	private function build_paid_reading_button_var() {
		// 已支付
		if ($this->user_has_paid) {
			$this->message .= '<p>' . __('您已付费：¥ ', 'wnd') . $this->post_price . '</p>';
			return;
		}

		// 作者本人
		if ($this->is_author) {
			$this->message .= '<p>' . __('您的付费文章：¥ ', 'wnd') . $this->post_price . '</p>';
			return;
		}

		// 其他情况
		$this->button_text = __('付费阅读', 'wnd');
		$this->message .= '<p>' . __('以下内容需付费阅读：¥ ', 'wnd') . $this->post_price . '</p>';
	}

	/**
	 * 同时包含付费阅读及付费下载
	 * @since 2020.06.04
	 */
	private function build_pay_button_var() {
		/**
		 * 支付后，采用付费下载方法，下载文件（下载文件时，不会重复扣费）@see Wnd\Action\Wnd_Pay_For_Downloads
		 */
		if ($this->user_has_paid or $this->user_id == $this->post->post_author) {
			$this->button_text = __('下载', 'wnd');
		} else {
			$this->button_text = __('购买', 'wnd');
		}

		// 已支付
		if ($this->user_has_paid) {
			$this->message .= '<p>' . __('您已付费：¥ ', 'wnd') . $this->post_price . '</p>';
			return;
		}

		// 作者本人
		if ($this->is_author) {
			$this->message .= '<p>' . __('您的付费内容：¥ ', 'wnd') . $this->post_price . '</p>';
			return;
		}

		// 其他情况
		$this->message .= '<p>' . __('以下内容及文件需付费购买：¥ ', 'wnd') . $this->post_price . '</p>';
	}
}
