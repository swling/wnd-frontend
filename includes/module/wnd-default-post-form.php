<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Form_Post;

/**
 *@since 2019.01.31 发布/编辑文章通用模板
 */
class Wnd_Default_Post_Form extends Wnd_Module {

	public static function build($args = []) {
		$defaults = [
			'post_id'     => 0,
			'post_parent' => 0,
			'is_free'     => false,
		];
		$args = wp_parse_args($args, $defaults);

		$post_id     = (int) $args['post_id'];
		$post_parent = (int) $args['post_parent'];
		$is_free     = (bool) $args['is_free'];

		/**
		 *@since 2019.03.11 表单类
		 */
		$form = new Wnd_Form_Post('post', $post_id);
		$form->add_html('<div class="columns post-form-supply">');

		/**
		 *左侧栏
		 */
		$form->add_html('<div class="column">');
		$form->add_post_title();
		$form->add_post_name();
		$form->add_post_excerpt();

		// 标签
		$form->add_post_tags('post_tag', __('标签', 'wnd'));
		$form->add_html(wnd_notification(__('请用回车键区分多个标签', 'wnd'), 'is-primary'));

		// 相册
		$form->set_thumbnail_size(100, 100);
		$form->add_post_gallery_upload(0, 0, __('相册图集', 'wnd'));

		// 付费内容
		if (!$is_free) {
			$form->add_post_paid_file_upload();
		}

		/**
		 *@since 2019.04 富媒体编辑器仅在非ajax请求中有效
		 */
		$form->add_post_content(true);
		$form->add_post_status_select();
		$form->add_html('</div>');

		/**
		 *侧边栏
		 */
		$form->add_html('<div class="column is-3">');
		// 分类
		$form->add_html('<div class="field">');
		$form->add_post_term_select(['taxonomy' => 'category'], '', true, true);
		$form->add_dynamic_sub_term_select('category', 1, '', false, __('二级分类', 'wnd'));
		$form->add_dynamic_sub_term_select('category', 2, '', false, __('三级分类', 'wnd'));
		$form->add_html('</div>');
		// 缩略图
		$form->set_thumbnail_size(150, 150);
		$form->add_post_thumbnail(200, 200);
		$form->add_html('</div>');

		$form->add_html('</div>');

		$form->set_post_parent($post_parent ?: $form->get_post()->post_parent);
		$form->set_submit_button(__('保存', 'wnd'));

		// 以当前函数名设置filter hook
		$form->set_filter(__CLASS__);
		$form->build();

		return $form->html;
	}
}
