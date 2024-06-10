<?php
namespace Wnd\Module\Post;

use Exception;
use Wnd\Module\Wnd_Module_Form;
use Wnd\View\Wnd_Form_WP;

/**
 * 快速编辑文章状态表单
 * @since 2019.01.20
 */
class Wnd_Post_Status_Form extends Wnd_Module_Form {

	protected static function configure_form(array $args = []): object {
		$post = get_post($args['post_id']);
		if (!$post) {
			throw new Exception(__('ID无效', 'wnd'));
		}

		switch ($post->post_status) {

			case 'publish':
				$status_text = __('已发布', 'wnd');
				break;

			case 'pending':
				$status_text = __('待审核', 'wnd');
				break;

			case 'draft':
				$status_text = __('草稿', 'wnd');
				break;

			case false:
				$status_text = __('已删除', 'wnd');
				break;

			default:
				$status_text = $post->post_status;
				break;
		}

		$form = new Wnd_Form_WP();
		$form->add_html('<div class="field is-grouped is-grouped-centered">');
		$form->add_radio(
			[
				'name'     => 'post_status',
				'options'  => [
					__('发布', 'wnd') => 'publish',
					__('待审', 'wnd') => 'pending',
					__('关闭', 'wnd') => 'wnd-closed',
					__('草稿', 'wnd') => 'draft',
					__('删除', 'wnd') => 'delete',
				],
				'required' => 'required',
				'checked'  => $post->post_status,
				'class'    => 'is-checkradio is-danger',
			]
		);
		$form->add_html('</div>');
		$form->add_html('
			<div class="field is-grouped is-grouped-centered">
			<a class="button" href="' . get_edit_post_link($post->ID) . '" target="_blank">' . __('编辑', 'wnd') . '</a>
			</div>');

		// 管理员权限
		if (wnd_is_manager()) {
			$form->add_textarea(
				[
					'name'        => 'remarks',
					'placeholder' => __('备注（可选）', 'wnd'),
				]
			);
		}

		$form->add_hidden('post_id', $args['post_id']);
		$form->set_route('action', 'post/wnd_update_post_status');
		$form->add_form_attr('id', 'post-status');
		$form->set_submit_button(__('提交', 'wnd'));
		return $form;
	}
}
