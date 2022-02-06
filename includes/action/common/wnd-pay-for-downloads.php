<?php
namespace Wnd\Action\Common;

use Exception;
use Wnd\Action\Wnd_Action;
use Wnd\Endpoint\Wnd_Endpoint_Action;
use Wnd\Model\Wnd_Order_Props;

/**
 * 付费阅读下载类
 */
class Wnd_Pay_For_Downloads extends Wnd_Action {

	private $post_id;
	private $post;
	private $sku_id;
	private $price;

	protected function execute(): array{
		/**
		 * 组合ajax验证下载参数:该url地址并非文件实际下载地址，而是一个调用参数的请求
		 * 前端接收后跳转至该网址（status == 6 是专为下载类ajax请求设置的代码前端响应），以实现ajax下载
		 * @since 2019.02.12
		 */
		$download_url = Wnd_Endpoint_Action::build_request_url('wnd_paid_download', ['post_id' => $this->post_id]);

		return ['status' => 6, 'msg' => '', 'data' => ['redirect_to' => $download_url]];
	}

	protected function parse_data() {
		$this->post_id = (int) ($this->data['post_id'] ?? 0);
		$this->post    = get_post($this->post_id);
		$this->sku_id  = $this->data[Wnd_Order_Props::$sku_id_key] ?? '';
		$this->price   = wnd_get_post_price($this->post_id, $this->sku_id);

		if (!$this->post) {
			throw new Exception(__('ID无效', 'wnd'));
		}
	}

	/**
	 * 下载权限检测
	 * 权限检查将在 $this->excute() 之前执行
	 * @since 0.9.31
	 */
	protected function check() {
		// 获取文章附件
		$file = wnd_get_paid_file($this->post_id);
		if (!$file) {
			throw new Exception(__('获取文件失败', 'wnd'));
		}

		// filter
		$wnd_can_download = apply_filters('wnd_can_download', ['status' => 1, 'msg' => ''], $this->post_id);
		if (0 === $wnd_can_download['status']) {
			throw new Exception($wnd_can_download['msg']);
		}

		/**
		 * 权限检测
		 */
		if ($this->price and !wnd_user_has_paid($this->user_id, $this->post_id) and $this->post->post_author != $this->user_id) {
			throw new Exception(__('尚未支付', 'wnd'));
		}
	}
}
