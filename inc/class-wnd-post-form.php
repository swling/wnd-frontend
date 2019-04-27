<?php

/**
 *适配本插件的ajax Post表单类
 *@since 2019.03.11
 */
class Wnd_Post_Form extends Wnd_Ajax_Form {

	// 初始化构建
	function __construct() {

		// 继承基础变量
		parent::__construct();

		// 新增拓展变量
		// $this->input_values = array();
		// $this->form_title = null;
		// $this->id = uniqid();
		// $this->submit = 'Submit';
	}

	// 用户表单标题居中
	function build_form_header() {
		$html = '<form id="form-' . $this->id . '" action="" method="POST" data-submit-type="ajax"';
		$html .= ' onsubmit="return false" onkeydown="if(event.keyCode==13){return false;}"';

		if ($this->upload) {
			$html .= ' enctype="multipart/form-data"';
		}

		if ($this->form_attr) {
			$html .= ' ' . $this->form_attr;
		}

		$html .= '>';

		if ($this->form_title) {
			$html .= '<div class="content">';
			$html .= '<h5>' . $this->form_title . '</h5>';
			$html .= '</div>';
		}

		$html .= '<div class="ajax-msg"></div>';

		$this->html = $html;
	}

	function add_post_title($post_title = '', $label = '', $placeholder = "请输入标题") {

		parent::add_text(
			array(
				'name' => '_post_post_title',
				'value' => $post_title,
				'placeholder' => $placeholder,
				'label' => $label,
				'autofocus' => 'autofocus',
				'required' => true,
			)
		);
	}

	function add_post_excerpt($post_excerpt = '', $label = '', $placeholder = '内容摘要') {

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

	function add_post_term_select($cat_taxonomy, $post_id = 0) {

		$cat = get_taxonomy($cat_taxonomy);
		if (!$cat) {
			return;
		}

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

	function add_post_tags($tag_taxonomy, $post_id = 0, $placeholder = '标签') {

		$tag = get_taxonomy($tag_taxonomy);

		$terms_list = '';
		$terms = get_the_terms($post_id, $tag_taxonomy);
		if (!empty($terms)) {
			foreach ($terms as $term) {
				$terms_list .= $term->name . ',';
			}unset($term);
			// 移除末尾的逗号
			$terms_list = rtrim($terms_list, ",");
		}

		parent::add_text(
			array(
				'id' => 'tags',
				'name' => '_term_' . $tag_taxonomy,
				'value' => $terms_list,
				'placeholder' => $placeholder,
				'label' => $tag->labels->name,
			)
		);

		parent::add_html(_wnd_get_tags_editor_script(3, 20, $placeholder, $tag_taxonomy));
	}

	function add_post_thumbnail($post_id, $size = array('width' => 200, 'height' => 200), $label = '') {
		parent::add_post_image_upload($post_id, '_thumbnail_id', array('width' => 200, 'height' => 200), '');
	}

	function add_post_price($post_id, $label = '', $placeholder = '价格') {
		parent::add_text(
			array(
				'name' => '_wpmeta_price',
				'value' => get_post_meta($post_id, 'price', 1),
				'label' => $label,
				'has_icons' => 'left',
				'icon' => '<i class="fas fa-yen-sign"></i>',
				'placeholder' => $placeholder,
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
