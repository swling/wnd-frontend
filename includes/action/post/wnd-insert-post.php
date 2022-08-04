<?php
namespace Wnd\Action\Post;

use Exception;
use Wnd\Action\Wnd_Action;
use Wnd\Model\Wnd_Post;

/**
 * 写入或更新 WP Post
 * - 本接口为写入数据的核心接口，故特别设置属性权限为 protected 旨在方便拓展继承
 * @since 初始化
 */
class Wnd_Insert_Post extends Wnd_Action {

	protected $post_data;
	protected $meta_data;
	protected $wp_meta_data;
	protected $terms_data;
	protected $post_id;
	protected $update_post;

	protected function execute(): array{
		$this->insert();

		/**
		 * 完成文章写入后
		 * @since 0.9.37
		 */
		do_action('wnd_insert_post', $this->post_id, $this->data);

		// 完成返回
		$permalink    = get_permalink($this->post_id);
		$redirect_to  = $_REQUEST['redirect_to'] ?? '';
		$status       = ($this->post_data['ID'] ?? 0) ? ($redirect_to ? 3 : 2) : 8;
		$return_array = [
			'status' => $status,
			'msg'    => __('发布成功', 'wnd'),
			'data'   => [
				'id'          => $this->post_id,
				'url'         => $permalink,
				'redirect_to' => $redirect_to,
			],
		];

		return apply_filters('wnd_insert_post_return', $return_array, $this->data, $this->post_id);
	}

	/**
	 * 解析提交数据
	 */
	protected function parse_data() {
		$this->post_data    = $this->request->get_post_data();
		$this->meta_data    = $this->request->get_post_meta_data();
		$this->wp_meta_data = $this->request->get_wp_post_meta_data();
		$this->terms_data   = $this->request->get_terms_data();

		// 指定ID则为更新
		$this->post_id     = $this->post_data['ID'] ?? 0;
		$this->update_post = $this->post_id ? get_post($this->post_id) : false;

		/**
		 * 文章特定字段处理：
		 *
		 * 1.Post一旦创建，不允许再次修改post type
		 *
		 * 2.若未指定post name（slug）：已创建的Post保持原有，否则为随机码
		 *
		 * 3.filter：post status
		 */
		$this->post_data['post_type']   = $this->post_id ? $this->update_post->post_type : ($this->post_data['post_type'] ?? 'post');
		$this->post_data['post_name']   = ($this->post_data['post_name'] ?? false) ?: ($this->post_id ? $this->update_post->post_name : uniqid());
		$this->post_data['post_status'] = apply_filters('wnd_insert_post_status', 'pending', $this->data, $this->post_id);
	}

	/**
	 * 更新权限判断
	 */
	protected function check() {
		if ($this->post_id) {
			if (!$this->update_post) {
				throw new Exception(__('ID无效', 'wnd'));
			}

			if (!current_user_can('edit_post', $this->post_id)) {
				throw new Exception(__('权限错误', 'wnd'));
			}
		}

		/**
		 * attachment仅允许更新，而不能直接写入（写入应在文件上传时完成）
		 * @since 2019.07.17
		 */
		if ('attachment' == $this->post_data['post_type']) {
			throw new Exception(__('未指定文件', 'wnd'));
		}

		/**
		 * 限制ajax可以创建的post类型，避免功能型post被意外创建
		 * 功能型post应通常具有更复杂的权限控制，并wp_insert_post创建
		 *
		 */
		if (!in_array($this->post_data['post_type'], Wnd_Post::get_allowed_post_types())) {
			throw new Exception(__('类型无效', 'wnd'));
		}

		// 写入及更新权限过滤
		$can_insert_post = apply_filters('wnd_can_insert_post', ['status' => 1, 'msg' => ''], $this->data, $this->post_id);
		if (0 === $can_insert_post['status']) {
			throw new Exception($can_insert_post['msg']);
		}
	}

	/**
	 * 写入数据
	 */
	private function insert() {
		// 创建revision 该revision不同于WordPress原生revision：创建一个同类型Post，设置post parent，并设置wp post meta
		if ($this->should_be_update_reversion()) {
			$this->post_data['ID']                                 = Wnd_Post::get_revision_id($this->post_id);
			$this->post_data['post_parent']                        = $this->post_id;
			$this->post_data['post_name']                          = uniqid();
			$this->wp_meta_data[Wnd_Post::get_revision_meta_key()] = 'true';
		}

		// 创建或更新Post
		if ($this->post_data['ID'] ?? 0) {
			$this->post_id = wp_update_post($this->post_data);
		} else {
			$this->post_id = wp_insert_post($this->post_data);
		}

		if (!$this->post_id) {
			throw new Exception(__('写入数据失败', 'wnd'));
		}

		if (is_wp_error($this->post_id)) {
			throw new Exception($this->post_id->get_error_message());
		}

		/**
		 * 设置Meta
		 *
		 */
		Wnd_Post::set_meta($this->post_id, $this->meta_data, $this->wp_meta_data);

		/**
		 * 设置Terms
		 *
		 */
		Wnd_Post::set_terms($this->post_id, $this->terms_data);
	}

	/**
	 * 判断是否应该创建一个版本
	 * @since 2020.05.20
	 */
	private function should_be_update_reversion(): bool {
		// 非更新
		if (!$this->update_post) {
			return false;
		}

		// 当前编辑即为revision无需新建
		if (Wnd_Post::is_revision($this->post_id)) {
			return false;
		}

		// 普通用户，已公开发布的内容再次编辑，需要创建revision
		if (!wnd_is_manager() and 'publish' == $this->update_post->post_status) {
			return true;
		}

		return false;
	}
}
