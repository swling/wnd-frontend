<?php
namespace Wnd\Action;

use Exception;
use Wnd\Model\Wnd_Post;

class Wnd_Update_Post_Status extends Wnd_Action {

	protected $post_id;
	protected $after_status;
	protected $remarks;
	protected $stick_post;
	protected $before_post;

	/**
	 *@since 2019.01.21
	 *
	 *@return array
	 *前端快速更改文章状态
	 *依赖：wp_update_post、wp_delete_post
	 */
	public function execute(): array{
		// 获取数据
		$this->post_id      = (int) $this->data['post_id'];
		$this->after_status = $this->data['post_status'];
		$this->remarks      = $this->data['remarks'] ?? '';
		$this->stick_post   = $this->data['stick_post'] ?? '';
		$this->before_post  = get_post($this->post_id);

		if (!$this->before_post) {
			throw new Exception(__('无效的Post', 'wnd'));
		}

		// 在现有注册的post status基础上新增 delete，该状态表示直接删除文章 @since 2019.03.03
		if (!in_array($this->after_status, array_merge(get_post_stati(), ['delete']))) {
			throw new Exception(__('无效的Post状态', 'wnd'));
		}

		// 权限检测
		$can_array              = ['status' => current_user_can('edit_post', $this->post_id) ? 1 : 0, 'msg' => __('权限错误', 'wnd')];
		$can_update_post_status = apply_filters('wnd_can_update_post_status', $can_array, $this->before_post, $this->after_status);
		if (0 === $can_update_post_status['status']) {
			return $can_update_post_status;
		}

		// 更新Post
		if ('delete' == $this->after_status) {
			return $this->delete_post();
		} else {
			return $this->update_status();
		}
	}

	/**
	 *更新状态
	 */
	protected function update_status() {
		//执行更新：如果当前post为自定义版本，将版本数据更新到原post
		if ('publish' == $this->after_status and Wnd_Post::is_revision($this->post_id)) {
			$update = Wnd_Post::restore_post_revision($this->post_id, $this->after_status);
		} else {
			$update = wp_update_post(['ID' => $this->post_id, 'post_status' => $this->after_status]);
		}

		/**
		 *@since 2019.06.11 置顶操作
		 */
		$this->stick_post();

		// 站内信
		$this->send_mail();

		// 完成更新
		if ($update) {
			return ['status' => 4, 'msg' => __('更新成功', 'wnd')];
		} else {
			throw new Exception(__('写入数据失败', 'wnd'));
		}
	}

	/**
	 *删除文章 无论是否设置了$force_delete 自定义类型的文章都会直接被删除
	 */
	protected function delete_post() {
		$delete = wp_delete_post($this->post_id, true);
		if ($delete) {
			$this->send_mail();

			return ['status' => 5, 'msg' => __('已删除', 'wnd')];
		} else {
			throw new Exception(__('操作失败', 'wnd'));
		}
	}

	/**
	 *@since 2019.06.11 置顶操作
	 */
	protected function stick_post() {
		if (wnd_is_manager()) {
			return;
		}

		if ('stick' == $this->stick_post and 'publish' == $this->after_status) {
			wnd_stick_post($this->post_id);

		} elseif ('unstick' == $this->stick_post) {
			wnd_unstick_post($this->post_id);
		}
	}

	/**
	 *@since 2020.05.23
	 *站内信
	 */
	protected function send_mail() {
		if ($this->user_id == $this->before_post->post_author) {
			return false;
		}

		$post_type_name = get_post_type_object($this->before_post->post_type)->label;

		if ('pending' == $this->before_post->post_status and 'draft' == $this->after_status) {
			$subject = $post_type_name . '[ID' . $this->post_id . ']' . __('审核失败', 'wnd');
			$content = wnd_message($this->remarks . '<p><a href="' . get_permalink($this->post_id) . '" target="_blank">查看</a></p>', 'is-danger');
		} elseif ('delete' == $this->after_status) {
			$subject = $post_type_name . '[ID' . $this->post_id . ']' . __('已被删除', 'wnd');
			$content = wnd_message('<p>Title:《' . $this->before_post->post_title . '》</p>' . $this->remarks, 'is-danger');
		} else {
			return false;
		}

		return wnd_mail($this->before_post->post_author, $subject, $content);
	}
}
