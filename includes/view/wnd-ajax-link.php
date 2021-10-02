<?php
namespace Wnd\View;

use Wnd\Controller\Wnd_Request;

/**
 * ajax请求链接构造类
 * @since 2019.09.28
 */
class Wnd_Ajax_Link {

	private $text;
	private $action;
	private $cancel_action;
	private $args;
	private $class;
	private $html;

	public function set_text(string $text) {
		$this->text = $text;
	}

	public function set_action(string $action) {
		$this->action = $action;
	}

	public function set_cancel_action(string $cancel_action) {
		$this->cancel_action = $cancel_action;
	}

	public function set_args(array $args) {
		$this->args = $args;
	}

	public function set_class(string $class) {
		$this->class = $class;
	}

	public function get_html() {
		if (!$this->html) {
			$this->build();
		}

		return $this->html;
	}

	/**
	 * 封装一个链接，发送ajax请求到后端
	 * 功能实现依赖对应的前端支持
	 * @since 2019.07.02
	 */private function build() {
		// Action 层需要验证表单字段签名
		$sign = Wnd_Request::sign(array_keys($this->args));

		$this->html = '<a class="ajax-link ' . $this->class . '" data-is_cancel="0" data-disabled="0"';
		$this->html .= ' data-action="' . $this->action . '"';
		$this->html .= ' data-cancel="' . $this->cancel_action . '" data-args=\'' . json_encode($this->args) . '\'';
		$this->html .= ' data-sign' . '="' . $sign . '"';
		$this->html .= '>';
		$this->html .= $this->text . '</a>';

		return $this->html;
	}
}
