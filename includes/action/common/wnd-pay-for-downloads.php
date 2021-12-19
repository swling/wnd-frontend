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

	protected function execute(): array{
		/**
		 * 组合ajax验证下载参数:该url地址并非文件实际下载地址，而是一个调用参数的请求
		 * 前端接收后跳转至该网址（status == 6 是专为下载类ajax请求设置的代码前端响应），以实现ajax下载
		 * @since 2019.02.12
		 */
		$download_url = Wnd_Endpoint_Action::build_request_url('wnd_paid_download', ['post_id' => $this->data['post_id']]);

		return ['status' => 6, 'msg' => 'ok', 'data' => ['redirect_to' => $download_url]];
	}

	/**
	 * 下载权限检测
	 * 权限检查将在 $this->excute() 之前执行
	 * @since 0.9.31
	 */
	protected function check() {
		// 获取文章
		$post_id = (int) $this->data['post_id'];
		$post    = get_post($post_id);

		// filter
		$wnd_can_download = apply_filters('wnd_can_download', ['status' => 1, 'msg' => ''], $post_id);
		if (0 === $wnd_can_download['status']) {
			throw new Exception($wnd_can_download['msg']);
		}

		/**
		 * 新增 SKU ID
		 * @since 0.8.76
		 */
		$sku_id = $this->data[Wnd_Order_Props::$sku_id_key] ?? '';
		$price  = wnd_get_post_price($post_id, $sku_id);
		if (!$post) {
			throw new Exception(__('ID无效', 'wnd'));
		}

		/**
		 * 权限检测
		 */
		if ($price and !wnd_user_has_paid($this->user_id, $post_id) and $post->post_author != $this->user_id) {
			throw new Exception(__('尚未支付', 'wnd'));
		}

		// 获取文章附件
		$file = wnd_get_paid_file($post_id);
		if (!$file) {
			throw new Exception(__('获取文件失败', 'wnd'));
		}
	}
}
