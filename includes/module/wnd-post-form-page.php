<?php
namespace Wnd\Module;

use Wnd\View\Wnd_Form_Post;

/**
 *@since 0.8.76
 *
 *Page 编辑表单
 */
class Wnd_Post_Form_Page extends Wnd_Module {

	protected static function build($args = []): string{
		$defaults = [
			'post_id'     => 0,
			'post_parent' => 0,
			'is_free'     => false,
		];
		$args = wp_parse_args($args, $defaults);
		extract($args);

		/**
		 *@since 2019.03.11 表单类
		 */
		$form = new Wnd_Form_Post('page', $post_id);
		$form->add_html('<div class="columns post-form-post">');

		/**
		 *左侧栏
		 */
		$form->add_html('<div class="column">');
		$form->add_post_title();
		$form->add_post_name();
		$form->add_post_excerpt();

		// 相册
		$form->set_thumbnail_size(100, 100);
		$form->add_post_gallery_upload(0, 0, __('相册图集', 'wnd'));

		/**
		 *@since 2019.04 富媒体编辑器仅在非ajax请求中有效
		 */
		$form->add_post_content(true);
		$form->add_post_status_select();
		$form->add_html('</div>');

		/**
		 *侧边栏
		 */
		$form->add_html('<div class="column is-2">');
		$form->add_html('<div class="field">' . wnd_modal_button(__('产品属性', 'wnd'), 'wnd_product_props_form', ['post_id' => $post_id]) . '</div>');
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
