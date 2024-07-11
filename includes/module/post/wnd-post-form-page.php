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

		$form = new Wnd_Form_Post('page', $post_id);
		$form->add_post_title(__('标题', 'wnd'));
		$form->add_post_name();
		$form->add_post_excerpt(__('摘要', 'wnd'));
		$form->set_thumbnail_size(100, 100);
		// $form->add_post_gallery_upload(0, 0, __('相册图集', 'wnd'));
		$form->add_post_price();

		// 页面模板
		$form->add_select(
			[
				'label'    => 'Template',
				'options'  => static::get_page_template(),
				'selected' => get_post_meta($post_id, '_wp_page_template', true) ?: '',
				'name'     => '_wpmeta_' . '_wp_page_template',
			]
		);

		$form->add_post_content(true);
		$form->add_post_status_select();
		$form->set_post_parent($post_parent ?: $form->get_post()->post_parent);
		$form->set_submit_button(__('保存', 'wnd'));

		// 以当前函数名设置filter hook
		$form->set_filter(__CLASS__);
		return $form;
	}

	private static function get_page_template(): array {
		$templates = array_flip(wp_get_theme()->get_page_templates(null, 'page'));

		ksort($templates);
		$options = ['Default' => 'default'];
		foreach (array_keys($templates) as $template) {
			$options[$template] = esc_attr($templates[$template]);
		}

		return $options;
	}

}
