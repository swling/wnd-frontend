<?php
namespace Wnd\Action\Async;

use Exception;
use Wnd\Action\Wnd_Action;

/**
 * 接收异步请求，发送邮件
 * 旨在提高需要发送邮件时的操作响应
 * @since 0.9.58.3
 */
class Wnd_Send_Mail extends Wnd_Action {

	private $to;
	private $subject;
	private $message;
	private $headers;

	protected function execute(): array{
		// 用户关闭客户端后，继续执行
		ignore_user_abort(true);

		wp_mail($this->to, $this->subject, $this->message, $this->headers);

		return ['status' => 1, 'msg' => __('发送成功', 'wnd')];
	}

	protected function parse_data() {
		$this->to      = $this->data['to'] ?? '';
		$this->subject = $this->data['subject'] ?? '';
		$this->message = $this->data['message'] ?? '';
		$this->headers = $this->data['headers'] ?? ('Content-Type: text/html; charset=' . get_option('blog_charset') . "\n");
	}

	protected function check() {
		if (!is_email($this->to)) {
			throw new Exception(__('邮件地址无效', 'wnd'));
		}

		if (!$this->subject) {
			throw new Exception(__('邮件主题无效', 'wnd'));
		}
	}
}
