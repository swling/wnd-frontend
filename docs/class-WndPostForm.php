<?php

/**
 *适配本插件的ajax Post表单类
 *@since 2019.03.11
 */
class Wnd_Post_Form extends Wnd_Ajax_Form {

	function add_post_title() {

		parent::add_text(
			array(
				'name' => '_post_post_title',
				'value' => $post->post_title != 'Auto-draft' ? $post->post_title : '',
				'placeholder' => '标题',
				'label' => '标题<span class="required">*</span>',
				// 'has_icons' => 'left', //icon position "left" orf "right"
				// 'icon' => '<i class="fas fa-user"></i>', // icon html @link https://fontawesome.com/
				'autofocus' => 'autofocus',
				'required' => true,
			)
		);
	}

	function add_post_excerpt() {

		parent::add_textarea(
			array(
				'name' => '_post_post_excerpt',
				'value' => $post->post_excerpt,
				'placeholder' => '摘要',
				'label' => '摘要',
				// 'has_icons' => 'left', //icon position "left" orf "right"
				// 'icon' => '<i class="fas fa-user"></i>', // icon html @link https://fontawesome.com/
				'autofocus' => 'autofocus',
				'required' => false,
			)
		);
	}

	function add_category_select($cat_taxonomy) {

		$cat = get_taxonomy($cat_taxonomy);
		// 获取当前文章已选择分类ID
		$current_cat = get_the_terms($post_id, $cat_taxonomy);
		$current_cat = $current_cat ? reset($current_cat) : 0;
		$current_cat_id = $current_cat ? $current_cat->term_id : 0;

		// 获取taxonomy下的term
		$terms = get_terms($args = array('taxonomy' => $cat_taxonomy, 'hide_empty' => 0));
		$options = array('—选择' . $cat->labels->name . '—' => -1);
		foreach ($terms as $term) {
			$options[$term->name] = $term->term_id;
		}
		unset($term);

		// 新增表单字段
		parent::add_select(

			array(
				'name' => '_term_' . $cat_taxonomy,
				'options' => $options,
				'label' => $cat->labels->name . '<span class="required">*</span>',
				'required' => true,
				'checked' => $current_cat_id, //default checked value
			)
		);

	}

	function add_tag($tag_taxonomy) {

		$tag = get_taxonomy($tag_taxonomy);

		parent::add_text(
			array(
				'id' => 'tags',
				'name' => '_term_' . $tag_taxonomy,
				'value' => '',
				'placeholder' => '标签',
				'label' => $tag->labels->name,
				// 'has_icons' => 'left', //icon position "left" orf "right"
				// 'icon' => '<i class="fas fa-user"></i>', // icon html @link https://fontawesome.com/
				// 'autofocus' => 'autofocus',
				// 'required' => true,
			)
		);
	}

	function add_post_thumbnail() {
		/*缩略图上传*/
		$thumbnail_defaults = array(
			'id' => 'thumbnail',
			'thumbnail_size' => array('width' => 150, 'height' => 150),
			'thumbnail' => WNDWP_URL . '/static/images/default.jpg',
			'data' => array(
				'meta_key' => '_thumbnail_id',
				'save_width' => 200,
				'savve_height' => 200,
			),
		);
		$thumbnail_args = $thumbnail_defaults;
		parent::add_image_upload($thumbnail_args);
	}

	function add_post_file($post_id, $meta_key) {

		parent::add_file_upload(
			array(
				'id' => 'file-upload', //container id
				'label' => '文件上传',
				// 'file_name' => 'file name', //文件显示名称
				// 'file_id' => 0, //data-file-id on delete button，in some situation, you want delete the file
				'data' => array( // some hidden input,maybe useful in ajax upload
					'meta_key' => 'file',
					'post_parent' => $post_id, //如果设置了post parent, 则上传的附件id将保留在对应的wnd_post_meta 否则保留为 wnd_user_meta
				),
			)
		);

	}

}
