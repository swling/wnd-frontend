<?php
namespace Wnd\Endpoint;

use Wnd\Model\Wnd_Order_Props;
use Wnd\Utility\Wnd_OSS_Handler;

/**
 * @since 2019.02.12 文件校验下载
 */
class Wnd_Paid_Download extends Wnd_Endpoint_Action {

	private $post_id;
	private $sku_id;
	private $user_id;
	private $price;
	private $file;
	private $in_private_oss;

	protected function do() {
		//1、免费，或者已付费
		if (!$this->price or wnd_user_has_paid($this->user_id, $this->post_id)) {
			wnd_inc_wnd_post_meta($this->post_id, 'download_count', 1);
			return $this->download_url();
		}

		//2、 作者直接下载
		if (get_post_field('post_author', $this->post_id) == $this->user_id) {
			return $this->download_url();
		}

		// 校验失败
		wp_die(__('权限错误', 'wnd'), get_option('blogname'));
	}

	/**
	 * 新增 SKU ID
	 * @since 0.8.76
	 */
	protected function check() {
		parent::check();

		$this->sku_id         = $this->data[Wnd_Order_Props::$sku_id_key] ?? '';
		$this->post_id        = (int) $this->data['post_id'] ?? 0;
		$this->user_id        = get_current_user_id();
		$this->price          = wnd_get_post_price($this->post_id, $this->sku_id);
		$this->file           = wnd_get_paid_file($this->post_id);
		$this->in_private_oss = $this->in_private_oss();
		if (!$this->file) {
			wp_die(__('获取文件失败', 'wnd'), get_option('blogname'));
		}
	}

	private function in_private_oss(): bool{
		$file_id     = wnd_get_paid_file_id($this->post_id);
		$oss_handler = Wnd_OSS_Handler::get_instance();
		return $oss_handler->is_private_storage($file_id);
	}

	private function download_url() {
		if ($this->in_private_oss) {
			header('Location: ' . $this->file);
			exit;
		}
		return wnd_download_file($this->file, $this->post_id);
	}
}
