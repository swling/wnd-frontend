<?php
namespace Wnd\Action;

use Exception;
use Wnd\Action\Wnd_Create_Order;
use Wnd\Model\Wnd_Recharge;

/**
 *付费阅读下载类
 *@param $_POST['post_id']  Post ID
 */
class Wnd_Pay_For_Download extends Wnd_Action_Ajax {

	public static function execute(): array{
		// 获取文章
		$post_id = (int) $_POST['post_id'];
		$post    = get_post($post_id);
		$price   = get_post_meta($post_id, 'price', true);
		$user_id = get_current_user_id();
		if (!$post) {
			return ['status' => 0, 'msg' => __('ID无效', 'wnd')];
		}

		// 获取文章附件
		$attachment_id = wnd_get_post_meta($post_id, 'file') ?: get_post_meta($post_id, 'file');
		$file          = get_attached_file($attachment_id, $unfiltered = true);
		if (!$file) {
			return ['status' => 0, 'msg' => __('获取文件失败', 'wnd')];
		}

		/**
		 *@since 2019.02.12
		 *组合ajax验证下载参数:该url地址并非文件实际下载地址，而是一个调用参数的请求
		 *前端接收后跳转至该网址（status == 6 是专为下载类ajax请求设置的代码前端响应），以实现ajax下载
		 */
		$download_args = [
			'action'   => 'wnd_paid_download',
			'post_id'  => $post_id,
			'_wpnonce' => wp_create_nonce('wnd_paid_download'),
		];
		$download_url = add_query_arg($download_args, wnd_get_do_url());

		//1、免费，或者已付费
		if (!$price or wnd_user_has_paid($user_id, $post_id)) {
			return ['status' => 6, 'msg' => 'ok', 'data' => ['redirect_to' => $download_url]];
		}

		//2、 作者直接下载
		if ($post->post_author == get_current_user_id()) {
			return ['status' => 6, 'msg' => 'ok', 'data' => ['redirect_to' => $download_url]];
		}

		//3、 付费下载
		$order = Wnd_Create_Order::execute();
		if ($order['status'] === 0) {
			return $order;
		}

		// 文章作者新增资金
		$commission = wnd_get_post_commission($post_id);
		if ($commission) {
			try {
				$recharge = new Wnd_Recharge();
				$recharge->set_object_id($post->ID); // 设置充值来源
				$recharge->set_user_id($post->post_author);
				$recharge->set_total_amount($commission);
				$recharge->create(true); // 直接写入余额
			} catch (Exception $e) {
				return ['status' => 0, 'msg' => $e->getMessage()];
			}
		}

		return ['status' => 6, 'msg' => 'ok', 'data' => ['redirect_to' => $download_url]];
	}
}
