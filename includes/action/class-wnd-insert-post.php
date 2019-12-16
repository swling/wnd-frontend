<?php
namespace Wnd\Action;

use Exception;
use Wnd\Model\Wnd_Form_Data;
use Wnd\Model\Wnd_Post;

class Wnd_Insert_Post extends Wnd_Action_Ajax {

	/**
	 *@see README.md
	 *ajax post $_POST name规则：
	 *post field：_post_{field}
	 *post meta：
	 *_meta_{key} (*自定义数组字段)
	 *_wpmeta_{key} (*WordPress原生字段)
	 *_term_{taxonomy}(*taxonomy)
	 *
	 *@since 初始化
	 *
	 *保存提交数据
	 *@param 	array	$_POST 				全局表单数据
	 *@param 	bool 	$verify_form_nonce  是否校验表单数据来源
	 *
	 *@return 	array 						操作结果
	 *
	 */
	public static function execute($verify_form_nonce = true): array{
		// 实例化当前提交的表单数据
		try {
			$form_data     = new Wnd_Form_Data($verify_form_nonce);
			$post_array    = $form_data->get_post_array();
			$meta_array    = $form_data->get_post_meta_array();
			$wp_meta_array = $form_data->get_wp_post_meta_array();
			$term_array    = $form_data->get_term_array();
		} catch (Exception $e) {
			return ['status' => 0, 'msg' => $e->getMessage()];
		}

		// 指定ID则为更新
		$update_id = $post_array['ID'] ?? 0;

		// 更新权限判断
		if ($update_id) {
			$update_post = get_post($update_id);
			if (!$update_post) {
				return ['status' => 0, 'msg' => 'ID无效'];
			}

			if (!current_user_can('edit_post', $update_id)) {
				return ['status' => 0, 'msg' => '权限错误'];
			}
		}

		/**
		 *@since 2019.07.17
		 *attachment仅允许更新，而不能直接写入（写入应在文件上传时完成）
		 */
		if ('attachment' == $post_array['post_type']) {
			return ['status' => 0, 'msg' => '未指定文件'];
		}

		/**
		 *文章特定字段处理：
		 *
		 *1.Post一旦创建，不允许再次修改post type
		 *
		 *2.若未指定post name（slug）：已创建的Post保持原有，否则为随机码
		 *
		 *3.filter：post status
		 */
		$post_array['post_type']   = $update_id ? $update_post->post_type : ($post_array['post_type'] ?? 'post');
		$post_array['post_name']   = $post_array['post_name'] ?? ($update_id ? $update_post->post_name : uniqid());
		$post_array['post_status'] = apply_filters('wnd_insert_post_status', 'pending', $post_array['post_type'], $update_id);

		/**
		 *限制ajax可以创建的post类型，避免功能型post被意外创建
		 *功能型post应通常具有更复杂的权限控制，并wp_insert_post创建
		 *
		 */
		if (!in_array($post_array['post_type'], Wnd_Post::get_allowed_post_types())) {
			return ['status' => 0, 'msg' => '类型无效'];
		}

		// 写入及更新权限过滤
		$can_insert_post = apply_filters('wnd_can_insert_post', ['status' => 1, 'msg' => '默认通过'], $post_array['post_type'], $update_id);
		if ($can_insert_post['status'] === 0) {
			return $can_insert_post;
		}

		// 写入或更新文章
		if (!$update_id) {
			$post_id = wp_insert_post($post_array);
		} else {
			$post_id = wp_update_post($post_array);
		}
		if (!$post_id) {
			return ['status' => 0, 'msg' => '写入数据失败'];
		}
		if (is_wp_error($post_id)) {
			return ['status' => 0, 'msg' => $post_id->get_error_message()];
		}

		// 更新字段，分类，及标签
		Wnd_Post::update_meta_and_term($post_id, $meta_array, $wp_meta_array, $term_array);

		// 完成返回
		$permalink    = get_permalink($post_id);
		$redirect_to  = $_REQUEST['redirect_to'] ?? '';
		$status       = $redirect_to ? 3 : 2;
		$return_array = [
			'status' => $status,
			'msg'    => '发布成功',
			'data'   => [
				'id'          => $post_id,
				'url'         => $permalink,
				'redirect_to' => $redirect_to,
			],
		];

		return apply_filters('wnd_insert_post_return', $return_array, $post_array['post_type'], $post_id);
	}
}
