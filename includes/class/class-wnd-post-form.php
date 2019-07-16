<?php

/**
 *适配本插件的ajax Post表单类
 *@since 2019.03.11
 *@param $post_type 	string
 *@param $post_id 		int
 *@param $input_fields_only 	bool 	是否只生成表单字段（不添加post form 属性字段）
 */
class Wnd_Post_Form extends Wnd_Ajax_Form {

	public $post_id;

	public $post_type;

	public $post;

	static protected $default_post = array(
		'ID' => 0,
		'post_author' => 0,
		'post_date' => null,
		'post_date_gmt' => null,
		'post_content' => null,
		'post_title' => null,
		'post_excerpt' => null,
		'post_status' => null,
		'comment_status' => null,
		'ping_status' => null,
		'post_password' => null,
		'post_name' => null,
		'to_ping' => null,
		'pinged' => null,
		'post_modified' => null,
		'post_modified_gmt' => null,
		'post_content_filtered' => null,
		'post_parent' => 0,
		'guid' => null,
		'menu_order' => 0,
		'post_type' => null,
		'post_mime_type' => null,
		'comment_count' => 0,
	);

	// 初始化构建
	public function __construct($post_type = 'post', $post_id = 0, $input_fields_only = false) {

		// 继承基础变量
		parent::__construct();

		// 新增拓展变量
		$this->post_type = $post_type;
		$this->post_id = $post_id;
		if (!$this->post_id) {
			$action = wnd_get_draft_post($post_type, $interval_time = 3600 * 24);
			$this->post_id = $action['status'] > 0 ? $action['data'] : 0;
		}

		/**
		 *@see WordPress get_post()
		 *当创建草稿失败，$this->post_id = 0 $this->post获取得到的将是WordPress当前页面
		 *因此初始化一个空白的对象
		 *2019.07.16
		 */
		$this->post = $this->post_id ? get_post($this->post_id) : (object) Wnd_Post_Form::$default_post;

		// 文章表单固有字段
		if (!$input_fields_only) {
			parent::add_hidden('_post_ID', $this->post_id);
			parent::add_hidden('_post_post_type', $this->post_type);
			parent::set_action('wnd_ajax_insert_post');
		}
	}

	public function add_post_title($label = '', $placeholder = "请输入标题") {

		parent::add_text(
			array(
				'name' => '_post_post_title',
				'value' => $this->post->post_title == 'Auto-draft' ? '' : $this->post->post_title,
				'placeholder' => 'ID:' . $this->post_id . ' ' . $placeholder,
				'label' => $label,
				'autofocus' => 'autofocus',
				'required' => true,
			)
		);
	}

	public function add_post_excerpt($label = '', $placeholder = '内容摘要') {

		parent::add_textarea(
			array(
				'name' => '_post_post_excerpt',
				'value' => $this->post->post_excerpt,
				'placeholder' => $placeholder,
				'label' => $label,
				'required' => false,
			)
		);
	}

	public function add_post_term_select($taxonomy, $required = true) {

		$taxonomy_object = get_taxonomy($taxonomy);
		if (!$taxonomy_object) {
			return;
		}

		// 获取当前文章已选择分类id
		$current_term = get_the_terms($this->post_id, $taxonomy);
		$current_term = $current_term ? reset($current_term) : 0;
		$current_term_id = $current_term ? $current_term->term_id : 0;

		// 获取taxonomy下的term
		$terms = get_terms($args = array('taxonomy' => $taxonomy, 'hide_empty' => false));
		$options = array('— ' . $taxonomy_object->labels->name . ' —' => -1);
		foreach ($terms as $term) {
			$options[$term->name] = $term->term_id;
		}
		unset($term);

		// 新增表单字段
		parent::add_select(

			array(
				'name' => '_term_' . $taxonomy,
				'options' => $options,
				// 'label' => $taxonomy_object->labels->name . '<span class="required">*</span>',
				'required' => $required,
				'checked' => $current_term_id, //default checked value
			)
		);

	}

	public function add_post_tags($taxonomy, $placeholder = '标签') {

		$taxonomy_object = get_taxonomy($taxonomy);

		$term_list = '';
		$terms = get_the_terms($this->post_id, $taxonomy);
		if (!empty($terms)) {
			foreach ($terms as $term) {
				$term_list .= $term->name . ',';
			}unset($term);
			// 移除末尾的逗号
			$term_list = rtrim($term_list, ",");
		}

		parent::add_text(
			array(
				'id' => 'tags',
				'name' => '_term_' . $taxonomy,
				'value' => $term_list,
				'placeholder' => $placeholder,
				'label' => $taxonomy_object->labels->name,
			)
		);

		parent::add_html(_wnd_tags_editor_script(3, 20, $placeholder, $taxonomy));
	}

	public function add_post_thumbnail($width = 200, $height = 200, $label = '') {
		self::add_post_image_upload('_thumbnail_id', $width, $height, $label);
	}

	public function add_post_content($rich_media_editor = true, $required = false, $placeholder = '详情') {

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

			/**
			 *@since 2019.05.09
			 * 通过html方式直接创建的字段需要在表单input values 数据中新增一个同名names，否则无法通过nonce校验
			 */
			parent::add_hidden('_post_post_content', '');

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
	 *@since 2019.07.09 常规文章字段input
	 **/
	public function add_post_meta($meta_key, $label = '', $placeholder = '', $is_wnd_meta = false) {
		$name = $is_wnd_meta ? '_meta_' . $meta_key : '_wpmeta_' . $meta_key;
		$value = $is_wnd_meta ? wnd_get_post_meta($this->post_id, $meta_key) : get_post_meta($this->post_id, $meta_key, 1);
		parent::add_text(
			array(
				'name' => $name,
				'value' => $value,
				'label' => $label,
				'placeholder' => $placeholder,
			)
		);
	}

	public function add_post_price($label = '', $placeholder = '价格') {
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

	/**
	 *@since 2019.04.28 上传字段简易封装
	 *如需更多选项，请使用 add_image_upload、add_file_upload 方法 @see Wnd_Ajax_Form
	 */
	public function add_post_image_upload($meta_key, $width = 200, $height = 200, $label = '') {
		$args = array(
			'label' => $label,
			'thumbnail_size' => array('width' => $width, 'height' => $height),
			'thumbnail' => WND_URL . 'static/images/default.jpg',
			'data' => array(
				'post_parent' => $this->post_id,
				'meta_key' => $meta_key,
				'save_width' => $width,
				'save_height' => $height,
			),
			'delete_button' => false,
		);
		parent::add_image_upload($args);
	}

	public function add_post_file_upload($meta_key, $label = '文件上传') {
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

	/**
	 *@since 2019.05.08 上传图片集
	 */
	public function add_post_gallery_upload($thumbnail_width = 160, $thumbnail_height = 120, $label = '') {

		$args = array(
			'label' => $label,
			'thumbnail_size' => array('width' => $thumbnail_width, 'height' => $thumbnail_height),
			'data' => array(
				'post_parent' => $this->post_id,
				'save_width' => 0, //图片文件存储最大宽度 0 为不限制
				'save_height' => 0, //图片文件存储最大过度 0 为不限制
			),
		);

		parent::add_gallery_upload($args);
	}

	// 文章表头，屏蔽回车提交
	protected function build_form_header() {
		$html = '<form id="form-' . $this->id . '" action="" method="POST" data-submit-type="ajax"';
		$html .= ' onsubmit="return false" onkeydown="if(event.keyCode==13){return false;}"';

		if ($this->with_upload) {
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

		$html .= '<div class="ajax-message"></div>';

		$this->html = $html;
	}

}
