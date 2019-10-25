<?php
namespace Wnd\Controller;

class Wnd_Update_Post_Status extends Wnd_Ajax_Controller {

	/**
	 *@since 2019.01.21
	 *@param  $_POST['post_id']
	 *@param  $_POST['post_status']
	 *@return array
	 *前端快速更改文章状态
	 *依赖：wp_update_post、wp_delete_post
	 */
	public static function execute(): array{

		// 获取数据
		$post_id     = (int) $_POST['post_id'];
		$before_post = get_post($post_id);
		if (!$before_post) {
			return array('status' => 0, 'msg' => '获取内容失败！');
		}

		$after_status = $_POST['post_status'];

		// 在现有注册的post status基础上新增 delete，该状态表示直接删除文章 @since 2019.03.03
		if (!in_array($after_status, array_merge(get_post_stati(), array('delete')))) {
			return array('status' => 0, 'msg' => '未注册的状态！');
		}

		// 权限检测
		$can_array              = array('status' => current_user_can('edit_post', $post_id) ? 1 : 0, 'msg' => '权限错误！');
		$can_update_post_status = apply_filters('wnd_can_update_post_status', $can_array, $before_post, $after_status);
		if ($can_update_post_status['status'] === 0) {
			return $can_update_post_status;
		}

		// 删除文章
		if ($after_status == 'delete') {
			// 无论是否设置了$force_delete 自定义类型的文章都会直接被删除
			$delete = wp_delete_post($post_id, true);
			if ($delete) {
				return array('status' => 5, 'msg' => '已删除！');
			} else {
				return array('status' => 0, 'msg' => '操作失败，请检查！');
			}
		}

		//执行更新
		$post_data = array(
			'ID'          => $post_id,
			'post_status' => $after_status,
		);
		$update = wp_update_post($post_data);

		/**
		 *@since 2019.06.11 置顶操作
		 */
		if (wnd_is_manager()) {
			$action = $_POST['stick_post'] ?? false;
			if ('stick' == $action and 'publish' == $after_status) {
				wnd_stick_post($post_id);

			} elseif ('unstick' == $action) {
				wnd_unstick_post($post_id);

			}
		}

		// 完成更新
		if ($update) {
			return array('status' => 4, 'msg' => '更新成功！');

			//更新失败
		} else {
			return array('status' => 0, 'msg' => '更新数据失败！');
		}
	}
}
