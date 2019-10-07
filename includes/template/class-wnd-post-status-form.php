<?php
namespace Wnd\Template;

use Wnd\View\Wnd_WP_Form;

/**
 *@since 2019.01.20
 *快速编辑文章状态表单
 */
class Wnd_Post_Status_Form extends Wnd_Template {

	public static function build($post_id = 0) {
		$post = get_post($post_id);
		if (!$post) {
			return 'ID无效！';
		}

		switch ($post->post_status) {

		case 'publish':
			$status_text = '已发布';
			break;

		case 'pending':
			$status_text = '待审核';
			break;

		case 'draft':
			$status_text = '草稿';
			break;

		case false:
			$status_text = '已删除';
			break;

		default:
			$status_text = $post->post_status;
			break;
		}

		$form = new Wnd_WP_Form();
		$form->add_html('<div class="field is-grouped is-grouped-centered">');
		$form->add_html('<script>wnd_ajax_msg(\'当前： ' . $status_text . '\', \'is-danger\', \'#post-status\')</script>');
		$form->add_radio(
			array(
				'name'     => 'post_status',
				'options'  => array(
					'发布' => 'publish',
					'待审' => 'pending',
					'关闭' => 'close',
					'草稿' => 'draft',
					'删除' => 'delete',
				),
				'required' => 'required',
				'checked'  => $post->post_status,
				'class'    => 'is-checkradio is-danger',
			)
		);
		$form->add_html('</div>');

		// 管理员权限
		if (wnd_is_manager()) {
			// 公开的post type可设置置顶
			if (in_array($post->post_type, get_post_types(array('public' => true)))) {
				$form->add_html('<div class="field is-grouped is-grouped-centered">');
				$form->add_radio(
					array(
						'name'    => 'stick_post',
						'options' => array(
							'置顶' => 'stick',
							'取消' => 'unstick',
						),
						'checked' => (array_search($post->ID, wnd_get_sticky_posts($post->post_type)) === false) ? '' : 'stick',
						'class'   => 'is-checkradio is-danger',
					)
				);
				$form->add_html('</div>');
			}

			$form->add_textarea(
				array(
					'name'        => 'remarks',
					'placeholder' => '备注（可选）',
				)
			);
		}

		if ($post->post_type == 'order') {
			$form->add_html('<div class="message is-danger"><div class="message-body">删除订单记录，不可退款，请谨慎操作！</div></div>');
		}

		$form->add_hidden('post_id', $post_id);
		$form->set_action('wnd_update_post_status');
		$form->add_form_attr('id', 'post-status');
		$form->set_submit_button('提交');
		$form->build();
		return $form->html;
	}
}
