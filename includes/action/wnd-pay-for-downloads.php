<?php
namespace Wnd\Action;

use Exception;
use Wnd\Endpoint\Wnd_Endpoint_Action;
use Wnd\Model\Wnd_Order_Product;

/**
 *付费阅读下载类
 */
class Wnd_Pay_For_Downloads extends Wnd_Action {

	public function execute(): array{
		// 获取文章
		$post_id = (int) $this->data['post_id'];
		$post    = get_post($post_id);

		/**
		 *@since 0.8.76
		 *新增 SKU ID
		 */
		$sku_id = $this->data[Wnd_Order_Product::$sku_id_key] ?? '';
		$price  = wnd_get_post_price($post_id, $sku_id);
		if (!$post) {
			throw new Exception(__('ID无效', 'wnd'));
		}

		/**
		 *权限检测
		 */
		if ($price and !wnd_user_has_paid($this->user_id, $post_id) and $post->post_author != $this->user_id) {
			throw new Exception(__('尚未支付', 'wnd'));
		}

		// 获取文章附件
		$attachment_id = wnd_get_post_meta($post_id, 'file') ?: get_post_meta($post_id, 'file');
		$file          = get_attached_file($attachment_id, $unfiltered = true);
		if (!$file) {
			throw new Exception(__('获取文件失败', 'wnd'));
		}

		/**
		 *@since 2019.02.12
		 *组合ajax验证下载参数:该url地址并非文件实际下载地址，而是一个调用参数的请求
		 *前端接收后跳转至该网址（status == 6 是专为下载类ajax请求设置的代码前端响应），以实现ajax下载
		 */
		$download_url = Wnd_Endpoint_Action::build_request_url('wnd_paid_download', ['post_id' => $post_id]);

		return ['status' => 6, 'msg' => 'ok', 'data' => ['redirect_to' => $download_url]];
	}
}
