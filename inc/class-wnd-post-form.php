<?php

/**
 *适配本插件的ajax Post表单类
 *@since 2019.03.11
 *@param $post_type 	string
 *@param $post_id 		int
 */
class Wnd_Post_Form extends Wnd_Ajax_Form {

	public $post_id;

	public $post_type;

	public $post_parent;

	public $post;

	// 初始化构建
	function __construct($post_type = 'post', $post_id = 0) {

		// 继承基础变量
		parent::__construct();

		// 新增拓展变量
		$this->post_id = $post_id;
		if (!$this->post_id) {
			$action = wnd_get_draft_post($post_type, $interval_time = 3600 * 24);
			$this->post_id = $action['status'] > 0 ? $action['data'] : 0;
		}

		$this->post = get_post($this->post_id);
		$this->post_type = $this->post->post_type ?? $post_type;
	}

	function build() {

		// 文章表单所需的统一格式
		parent::add_hidden('_post_ID', $this->post_id);
		parent::add_hidden('_post_post_type', $this->post_type);
		parent::set_action('wnd_ajax_insert_post');

		// 基础类
		parent::build();
	}

	// 文章表头，屏蔽回车提交
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

	function add_post_title($label = '', $placeholder = "请输入标题") {

		$post_title = $this->post->post_title ?? '';
		parent::add_text(
			array(
				'name' => '_post_post_title',
				'value' => $post_title == 'Auto-draft' ? '' : $post_title,
				'placeholder' => 'ID:' . $this->post_id . ' ' . $placeholder,
				'label' => $label,
				'autofocus' => 'autofocus',
				'required' => true,
			)
		);
	}

	function add_post_excerpt($label = '', $placeholder = '内容摘要') {

		parent::add_textarea(
			array(
				'name' => '_post_post_excerpt',
				'value' => $this->post->post_excerpt ?? '',
				'placeholder' => $placeholder,
				'label' => $label,
				'required' => false,
			)
		);
	}

	function add_post_term_select($cat_taxonomy) {

		$cat = get_taxonomy($cat_taxonomy);
		if (!$cat) {
			return;
		}

		// 获取当前文章已选择分类id
		$current_cat = get_the_terms($this->post_id, $cat_taxonomy);
		$current_cat = $current_cat ? reset($current_cat) : 0;
		$current_cat_id = $current_cat ? $current_cat->term_id : 0;

		// 获取taxonomy下的term
		$terms = get_terms($args = array('taxonomy' => $cat_taxonomy, 'hidden_empty' => 0));
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

	function add_post_tags($tag_taxonomy, $placeholder = '标签') {

		$tag = get_taxonomy($tag_taxonomy);

		$terms_list = '';
		$terms = get_the_terms($this->post_id, $tag_taxonomy);
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

	function add_post_thumbnail($size = array('width' => 200, 'height' => 200), $label = '') {
		self::add_post_image_upload('_thumbnail_id', array('width' => 200, 'height' => 200), '');
	}

	function add_post_price($label = '', $placeholder = '价格') {
		parent::add_text(
			array(
				'name' => '_wpmeta_price',
				'value' => get_post_meta($this->post_id, 'price', 1),
				'label' => $label,
				'has_icons' => 'left',
				'icon' => '<i class="fas fa-yen-sign"></i>',
				'placeholder' => $placeholder,
			)
		);
	}

	function add_post_content($rich_media_editor = true, $placeholder = '详情', $required = 0) {

		/**
		 *@since 2019.3.11 调用外部页面变量，后续更改为当前编辑的post，否则，wp_editor上传的文件将归属到页面，而非当前编辑的文章
		 */
		global $post;
		$post = $this->post;

		/**
		 *@since 2019.03.11无法直接通过方法创建 wp_editor
		 *需要提前在静态页面中创建一个 #hidden-wp-editor 包裹下的 隐藏wp_editor
		 *然后通过js提取HTML的方式实现在指定位置嵌入
		 */

		if (!wnd_doing_ajax() and $rich_media_editor and $this->post_id) {

			echo '<div id="hidden-wp-editor" style="display: none;">';
			if ($post) {
				wp_editor($post->post_content, '_post_post_content', 'media_buttons=1');
			} else {
				wp_editor('', '_post_post_content', 'media_buttons=0');
			}
			echo '</div>';

		} else {

			parent::add_textarea(
				array(
					'name' => '_post_post_content',
					'value' => $post->post_content ?? '',
					'placeholder' => $placeholder,
					'required' => $required,
				)
			);

		}

		parent::add_html('<div id="wnd-wp-editor" class="field"></div>');
		parent::add_html('<script type="text/javascript">var wp_editor = $("#hidden-wp-editor").html();$("#hidden-wp-editor").remove();$("#wnd-wp-editor").html(wp_editor);</script>');
	}

	/**
	 *@since 2019.04.28 上传字段简易封装
	 *如需更多选项，请使用 add_image_upload、add_file_upload 方法 @see Wnd_Ajax_Form
	 */
	function add_post_image_upload($meta_key, $size = array('width' => 200, 'height' => 200), $label = '') {
		$args = array(
			'label' => $label,
			'thumbnail_size' => array('width' => $size['width'], 'height' => $size['height']),
			'thumbnail' => WNDWP_URL . '/static/images/default.jpg',
			'data' => array(
				'post_parent' => $this->post_id,
				'meta_key' => $meta_key,
				'save_width' => $size['width'],
				'save_height' => $size['height'],
			),
			'delete_button' => false,
		);
		parent::add_image_upload($args);
	}

	function add_post_file_upload($meta_key, $label = '文件上传') {
		parent::add_file_upload(
			array(
				'label' => $label,
				'data' => array( // some hidden input,maybe useful in ajax upload
					'meta_key' => $meta_key,
					'post_parent' => $this->post_id, //如果设置了post parent, 则上传的附件id将保留在对应的wnd_post_meta 否则保留为 wnd_user_meta
				),
			)
		);
	}

}
