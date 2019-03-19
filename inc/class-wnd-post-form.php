<?php

/**
 *适配本插件的ajax Post表单类
 *@since 2019.03.11
 */
class Wnd_Post_Form extends Wnd_Ajax_Form {

	function add_post_title($post_title = '', $label = '标题<span class="required">*</span>', $placeholder = "标题") {

		parent::add_text(
			array(
				'name' => '_post_post_title',
				'value' => $post_title,
				'placeholder' => $placeholder,
				'label' => $label,
				'has_icons' => 'left', //icon position "left" orf "right"
				'icon' => '<i class="fas fa-edit"></i>', // icon html @link https://fontawesome.com/
				'autofocus' => 'autofocus',
				'required' => true,
			)
		);
	}

	function add_post_excerpt($post_excerpt = '', $label = '摘要', $placeholder = '摘要') {

		parent::add_textarea(
			array(
				'name' => '_post_post_excerpt',
				'value' => $post_excerpt,
				'placeholder' => $placeholder,
				'label' => $label,
				'required' => false,
			)
		);
	}

	function add_post_category_select($cat_taxonomy, $post_id = 0) {

		$cat = get_taxonomy($cat_taxonomy);
		// 获取当前文章已选择分类ID
		$current_cat = get_the_terms($post_id, $cat_taxonomy);
		$current_cat = $current_cat ? reset($current_cat) : 0;
		$current_cat_id = $current_cat ? $current_cat->term_id : 0;

		// 获取taxonomy下的term
		$terms = get_terms($args = array('taxonomy' => $cat_taxonomy, 'hide_empty' => 0));
		$options = array('— ' . $cat->labels->name . ' —' => -1);
		foreach ($terms as $term) {
			$options[$term->name] = $term->term_id;
		}
		unset($term);

		// 新增表单字段
		parent::add_select(

			array(
				'name' => '_term_' . $cat_taxonomy,
				'options' => $options,
				// 'label' => $cat->labels->name . '<span class="required">*</span>',
				'required' => true,
				'checked' => $current_cat_id, //default checked value
			)
		);

	}

	function add_post_tag($tag_taxonomy, $post_id = 0) {

		$tag = get_taxonomy($tag_taxonomy);

		$terms_list = '';
		$terms = wp_get_object_terms($post_id, $tag_taxonomy);
		if (!empty($terms)) {
			foreach ($terms as $term) {
				$terms_list .= $term->name . ',';
				// 移除末尾的逗号
				echo rtrim($terms_list, ",");
			}
		}

		parent::add_text(
			array(
				'id' => 'tags',
				'name' => '_term_' . $tag_taxonomy,
				'value' => $terms_list,
				'placeholder' => '标签',
				'label' => $tag->labels->name,
			)
		);
	}

	function add_post_thumbnail($post_id, $size = array(), $label = '') {
		$thumbnail_defaults = array(
			'id' => 'thumbnail',
			'label' => $label,
			'thumbnail_size' => array('width' => $size['width'], 'height' => $size['height']),
			'thumbnail' => WNDWP_URL . '/static/images/default.jpg',
			'data' => array(
				'post_parent' => $post_id,
				'meta_key' => '_thumbnail_id',
				'save_width' => $size['width'],
				'save_height' => $size['height'],
			),
		);
		$thumbnail_args = $thumbnail_defaults;
		parent::add_image_upload($thumbnail_args);
	}

	function add_post_file($post_id, $meta_key, $label = '文件上传') {
		parent::add_file_upload(
			array(
				'id' => 'file-upload', //container id
				'label' => $label,
				'data' => array( // some hidden input,maybe useful in ajax upload
					'meta_key' => $meta_key,
					'post_parent' => $post_id, //如果设置了post parent, 则上传的附件id将保留在对应的wnd_post_meta 否则保留为 wnd_user_meta
				),
			)
		);
	}

	function add_post_price($post_id) {
		parent::add_text(
			array(
				'name' => '_wpmeta_price',
				'value' => get_post_meta($post_id, 'price', 1),
				'label' => '',
				'has_icons' => 'left',
				'icon' => '<i class="fas fa-yen-sign"></i>',
				'placeholder' => '价格',
			)
		);
	}

	function add_post_content($wp_editor = 0, $placeholder = '详情', $required = 0) {

		if ($wp_editor) {
			/**
			 *@since 2019.03.11无法直接通过方法创建 wp_editor
			 *需要提前在静态页面中创建一个 #hidden-wp-editor 包裹下的 隐藏wp_editor
			 *然后通过js提取HTML的方式实现在指定位置嵌入
			 */
			parent::add_html('<div id="wnd-wp-editor" class="field"></div>');
			parent::add_html('<script type="text/javascript">var wp_editor = $("#hidden-wp-editor").html();$("#hidden-wp-editor").remove();$("#wnd-wp-editor").html(wp_editor);</script>');
		} else {
			parent::add_textarea(
				array(
					'name' => '_post_post_content',
					'placeholder' => $placeholder,
					'required' => $required,
				)
			);
		}
	}

}
