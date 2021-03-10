<?php
namespace Wnd\Module;

use Exception;
use Wnd\View\Wnd_Form_Post;

/**
 *@since 2019.01.31 发布/编辑文章通用模板
 */
class Wnd_Post_Form_Post extends Wnd_Module_Form {

	protected static function configure_form(array $args = []): object{
		$defaults = [
			'post_id'     => 0,
			'post_parent' => 0,
			'is_free'     => false,
		];
		$args = wp_parse_args($args, $defaults);
		extract($args);

		// 权限检测
		if ($post_id and !current_user_can('edit_post', $post_id)) {
			throw new Exception(__('权限错误', 'wnd'));
		}

		/**
		 *@since 2019.03.11 表单类
		 */
		$form = new Wnd_Form_Post('post', $post_id);
		// $form->add_html('<div class="columns post-form-post">');

		/**
		 *左侧栏
		 */
		$form->add_step(__('内容正文', 'wnd'));
		$form->add_post_title(__('标题', 'wnd'));
		/**
		 *@since 2019.04 富媒体编辑器仅在非ajax请求中有效
		 */
		$form->add_post_content(true);
		$form->add_step(__('发布选项', 'wnd'));

		// $form->add_post_name();
		$form->add_post_excerpt(__('摘要', 'wnd'));

		// 标签
		$form->add_post_tags('post_tag');

		// 相册
		// $form->set_thumbnail_size(100, 100);
		// $form->add_post_gallery_upload(0, 0, __('相册图集', 'wnd'));

		// 付费内容
		if (!$is_free) {
			$form->add_post_paid_file_upload();
		}

		$form->add_post_status_select();
		// $form->add_html('</div>');

		$form->add_html('<div class="field">' . wnd_modal_button(__('产品属性', 'wnd'), 'wnd_sku_form', ['post_id' => $form->get_post()->ID ?? 0]) . '</div>');
		$form->add_html('<div class="field">');
		$form->add_post_term_select(['taxonomy' => 'category'], '', true);
		$form->add_html('</div>');

		// 缩略图
		$form->set_thumbnail_size(150, 150);
		$form->add_post_thumbnail(200, 200);
		// $form->add_html('</div>');

		$form->set_post_parent($post_parent ?: $form->get_post()->post_parent);
		$form->set_submit_button(__('保存', 'wnd'));

		// 以当前函数名设置filter hook
		$form->set_filter(__CLASS__);
		return $form;
	}
}
