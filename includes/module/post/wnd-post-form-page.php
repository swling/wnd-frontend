<?php
namespace Wnd\Module\Post;

use Wnd\View\Wnd_Form_Post;

/**
 * Page 编辑表单
 * @since 0.8.76
 */
class Wnd_Post_Form_Page extends Wnd_Post_Form {

	public static $post_type = 'page';

	protected static function configure_form(array $args = []): object {
		$defaults = [
			'post_id'     => 0,
			'post_parent' => 0,
			'is_free'     => false,
		];
		$args = wp_parse_args($args, $defaults);
		extract($args);

		/**
		 * @since 2019.03.11 表单类
		 */
		$form = new Wnd_Form_Post('page', $post_id);

		/**
		 * 左侧栏
		 */
		$form->add_post_title(__('标题', 'wnd'));
		$form->add_post_name();
		$form->add_post_excerpt(__('摘要', 'wnd'));

		// 相册
		$form->set_thumbnail_size(100, 100);
		// $form->add_post_gallery_upload(0, 0, __('相册图集', 'wnd'));
		$form->add_post_price();

		$form->add_post_content(true);
		$form->add_post_status_select();
		$form->add_html('</div>');

		$form->set_post_parent($post_parent ?: $form->get_post()->post_parent);
		$form->set_submit_button(__('保存', 'wnd'));

		// 以当前函数名设置filter hook
		$form->set_filter(__CLASS__);
		return $form;
	}

}
