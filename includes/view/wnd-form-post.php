<?php
namespace Wnd\View;

use Wnd\Model\Wnd_Post;
use Wnd\Model\Wnd_Term;

/**
 * 适配本插件的ajax Post表单类
 * @since 2019.03.11
 *
 * @param $post_type         	string option 		类型
 * @param $post_id           	int    option 		ID
 * @param $input_fields_only 	bool   option 		是否只生成表单字段（不添加post form 属性字段）
 */
class Wnd_Form_Post extends Wnd_Form_WP {

	protected $post_id = 0;

	protected $post_type = 'post';

	protected $post_parent = 0;

	protected $post;

	/**
	 * 当post已选的Terms
	 * [
	 * 	${taxonomy}=>[term_id1,term_id2]
	 * ]
	 */
	protected $current_terms = [];

	// 当前post 支持的 taxonomy
	protected $taxonomies = [];

	protected static $default_post = [
		'ID'                    => 0,
		'post_author'           => 0,
		'post_date'             => '',
		'post_date_gmt'         => '',
		'post_content'          => '',
		'post_title'            => '',
		'post_excerpt'          => '',
		'post_status'           => '',
		'comment_status'        => '',
		'ping_status'           => '',
		'post_password'         => '',
		'post_name'             => '',
		'to_ping'               => '',
		'pinged'                => '',
		'post_modified'         => '',
		'post_modified_gmt'     => '',
		'post_content_filtered' => '',
		'post_parent'           => 0,
		'guid'                  => '',
		'menu_order'            => 0,
		'post_type'             => '',
		'post_mime_type'        => '',
		'comment_count'         => 0,
	];

	// 初始化构建
	public function __construct($post_type = 'post', $post_id = 0, $input_fields_only = false) {
		/**
		 * 表单提交验证码
		 * @since 0.9.0
		 */
		$enable_captcha = apply_filters('enable_post_form_captcha', !is_user_logged_in(), $post_type, $post_id);

		// 继承父类构造
		parent::__construct(true, $enable_captcha, true);

		// 初始化属性
		$this->thumbnail_width  = 200;
		$this->thumbnail_height = 200;

		// 初始化 Post Data
		$this->setup_postdata($post_type, $post_id);

		// 文章表单固有字段
		if (!$input_fields_only) {
			$this->add_hidden('_post_ID', $this->post_id);
			$this->add_hidden('_post_post_type', $this->post_type);
			$this->set_route('action', 'wnd_insert_post');
		}

		// revision
		$revision_id = Wnd_Post::get_revision_id($post_id);
		if ($revision_id) {
			$this->set_message(wnd_notification('<a href="' . get_edit_post_link($revision_id) . '">' . __('编辑版本', 'wnd') . '</a>', 'is-danger'));
		}
	}

	/**
	 * 初始化 Post 数据
	 */
	private function setup_postdata($post_type, $post_id) {
		/**
		 * 用于不需要文件上传的表单以降低数据库操作
		 * 其余情况未指定ID，创建新草稿
		 * @since 2019.12.16 若传参false，表示表单不需要创建草稿
		 */
		if (false === $post_id) {
			$post_id = 0;
		} else {
			$post_id = $post_id ?: Wnd_Post::get_draft($post_type);
		}

		/**
		 * 当创建草稿失败，$this->post_id = 0 $this->post获取得到的将是WordPress当前页面
		 * 当指定post_id无效，get_post将返回null
		 * 上述两种情况均初始化一个空白的对象
		 * 2019.07.16
		 * @see WordPress get_post()
		 */
		$this->post    = $post_id ? get_post($post_id) : (object) static::$default_post;
		$this->post    = $this->post ?: (object) static::$default_post;
		$this->post_id = $this->post->ID;

		/**
		 * 将post id 写入表单自定义属性，供前端渲染使用
		 * @since 0.9.25
		 */
		$this->add_form_attr('data-post-id', $this->post_id);

		/**
		 * 文章类型：
		 * 若指定了id，则获取对应id的post type
		 * 若无则外部传入参数
		 *
		 */
		$this->post_type = $this->post_id ? $this->post->post_type : $post_type;

		/**
		 * 获取当前Post_type 的所有 Taxonomy
		 * 获取当前post 已选term数据
		 * @since 2020.04.19
		 */
		$this->taxonomies    = get_object_taxonomies($this->post_type, 'names');
		$this->current_terms = $this->taxonomies ? $this->get_current_terms() : [];
	}

	/**
	 * 设置post parent
	 * @since 2019.09.04
	 *
	 * @param int 	$post_parent
	 */
	public function set_post_parent($post_parent) {
		$this->post_parent = $post_parent;
		$this->add_hidden('_post_post_parent', $this->post_parent);
	}

	public function add_post_title($label = '', $placeholder = '请输入标题', $required = true) {
		$this->add_text(
			[
				'name'        => '_post_post_title',
				'value'       => 'Auto-draft' == $this->post->post_title ? '' : $this->post->post_title,
				'placeholder' => 'ID:' . $this->post_id . ' ' . $placeholder,
				'label'       => $label,
				'autofocus'   => 'autofocus',
				'required'    => $required,
			]
		);
	}

	public function add_post_excerpt($label = '', $placeholder = '内容摘要', $required = false) {
		$this->add_textarea(
			[
				'name'        => '_post_post_excerpt',
				'value'       => $this->post->post_excerpt,
				'placeholder' => $placeholder,
				'label'       => $label,
				'required'    => $required,
			]
		);
	}

	// Term 分类单选下拉：动态无限层级联动，单个 select 不支持复选 option，仅支持 JavaScript 渲染
	public function add_post_term_select($args_or_taxonomy, $label = 'default', $required = true) {
		if (!wnd_is_rest_request()) {
			throw new \Exception('[' . __FUNCTION__ . '] Only available in rest request!');
		}

		$taxonomy        = is_array($args_or_taxonomy) ? $args_or_taxonomy['taxonomy'] : $args_or_taxonomy;
		$taxonomy_object = get_taxonomy($taxonomy);
		if (!$taxonomy_object) {
			return;
		}

		// 获取 taxonomy 下拉
		$option_data = Wnd_Term::get_post_terms_options_with_level($this->post->ID, $args_or_taxonomy);
		$selected    = Wnd_Term::get_post_terms_with_level($this->post_id, $taxonomy);

		// 新增表单字段
		$this->add_field(
			[
				'type'     => 'select_linked',
				'name'     => '_term_' . $taxonomy . '[]',
				'options'  => $option_data,
				'required' => $required,
				'selected' => $selected ?: [0 => ''], //default checked value
				'label'    => ('default' == $label) ? $taxonomy_object->labels->name : $label,
				'class'    => $taxonomy,
				'data'     => ['taxonomy' => $taxonomy],
			]
		);
	}

	/**
	 * 分类复选框
	 *
	 */
	public function add_post_term_checkbox($args_or_taxonomy, $label = '') {
		$taxonomy        = is_array($args_or_taxonomy) ? $args_or_taxonomy['taxonomy'] : $args_or_taxonomy;
		$taxonomy_object = get_taxonomy($taxonomy);
		if (!$taxonomy_object) {
			return;
		}

		// 获取taxonomy下的 term 键值对
		$option_data = Wnd_Term::get_terms_data($args_or_taxonomy);

		$this->add_checkbox(
			[
				'name'     => '_term_' . $taxonomy . '[]',
				'options'  => $option_data,
				'checked'  => array_values($this->current_terms[$taxonomy]),
				'label'    => $label,
				'class'    => $taxonomy,
				'required' => false,
			]
		);
	}

	/**
	 * 分类单选框
	 * @since 2020.04.17
	 */
	public function add_post_term_radio($args_or_taxonomy, $label = '', $required = true) {
		$taxonomy        = is_array($args_or_taxonomy) ? $args_or_taxonomy['taxonomy'] : $args_or_taxonomy;
		$taxonomy_object = get_taxonomy($taxonomy);
		if (!$taxonomy_object) {
			return;
		}

		// 获取taxonomy下的 term 键值对
		$option_data = Wnd_Term::get_terms_data($args_or_taxonomy);

		$this->add_radio(
			[
				'name'     => '_term_' . $taxonomy,
				'options'  => $option_data,
				'checked'  => $this->current_terms[$taxonomy][0] ?? false,
				'label'    => $label,
				'class'    => $taxonomy,
				'required' => $required,
			]
		);
	}

	/**
	 * 自定义标签编辑器
	 * @since 2020.05.12
	 * @since 0.9.25 : 以 Vue 重构 该字段不再支持常规 php 渲染
	 */
	public function add_post_tags($taxonomy, $label = '', $required = false, $help = '') {
		if (!wnd_is_rest_request()) {
			throw new \Exception('[' . __FUNCTION__ . '] Only available in rest request!');
		}

		$taxonomy_object = get_taxonomy($taxonomy);
		if (!$taxonomy_object) {
			return;
		}

		$help = $help ?: __('请用回车键区分多个 Tag', 'wnd');

		$args = [
			'type'     => 'tag_input',
			'value'    => array_values($this->current_terms[$taxonomy]) ?: [],
			'label'    => $label ?: $taxonomy_object->labels->name,
			'name'     => '_term_' . $taxonomy . '[]',
			'required' => $required,
			'help'     => ['text' => $help, 'class' => 'is-success'],
			'data'     => ['taxonomy' => $taxonomy, 'suggestions' => []],
		];
		$this->add_field($args);
	}

	/**
	 * @param int $save_width  	缩略图保存宽度
	 * @param int $save_height 	缩略图保存高度
	 * @param int $label
	 */
	public function add_post_thumbnail($save_width = 200, $save_height = 200, $label = '') {
		$this->add_post_image_upload('_thumbnail_id', $save_width, $save_height, $label);
	}

	/**
	 * 正文编辑器
	 */
	public function add_post_content($rich_media_editor = true, $placeholder = '详情', $required = false) {
		if ($rich_media_editor) {
			$this->add_editor(
				[
					'name'        => '_post_post_content',
					'value'       => $this->post->post_content ?? '',
					'placeholder' => $placeholder,
					'required'    => $required,
				]
			);
		} else {
			$this->add_textarea(
				[
					'name'        => '_post_post_content',
					'value'       => $this->post->post_content ?? '',
					'placeholder' => $placeholder,
					'required'    => $required,
				]
			);
		}
	}

	/**
	 * 常规wnd meta文章字段
	 * @since 2019.07.09
	 */
	public function add_post_meta($meta_key, $label = '', $placeholder = '', $required = false) {
		$name  = '_meta_' . $meta_key;
		$value = wnd_get_post_meta($this->post_id, $meta_key) ?: '';
		$this->add_text(
			[
				'name'        => $name,
				'value'       => $value,
				'label'       => $label,
				'placeholder' => $placeholder,
				'required'    => $required,
			]
		);
	}

	/**
	 * 常规WordPress原生文章字段
	 * @since 2019.08.25
	 */
	public function add_wp_post_meta($meta_key, $label = '', $placeholder = '', $required = false) {
		$name  = '_wpmeta_' . $meta_key;
		$value = get_post_meta($this->post_id, $meta_key, true) ?: '';
		$this->add_text(
			[
				'name'        => $name,
				'value'       => $value,
				'label'       => $label,
				'placeholder' => $placeholder,
				'required'    => $required,
			]
		);
	}

	/**
	 * 设置post menu_order
	 * 常用菜单、附件等排序
	 * @since 2019.07.17
	 */
	public function add_post_menu_order($label = '排序', $placeholder = '输入排序', $required = false) {
		$this->add_number(
			[
				'name'        => '_post_menu_order',
				'value'       => $this->post->menu_order ?: '',
				'placeholder' => $placeholder,
				'label'       => $label,
				'autofocus'   => 'autofocus',
				'required'    => $required,
			]
		);
	}

	/**
	 * 设置post_name 固定链接别名
	 * @since 2019.07.18
	 */
	public function add_post_name($label = '别名', $placeholder = '文章固定连接别名', $required = false) {
		$this->add_text(
			[
				'name'        => '_post_post_name',
				'value'       => $this->post->post_name ?: '',
				'placeholder' => $placeholder,
				'label'       => $label,
				'autofocus'   => 'autofocus',
				'required'    => $required,
			]
		);
	}

	public function add_post_price($label = '', $required = false) {
		$this->add_number(
			[
				'name'        => '_wpmeta_price',
				'value'       => get_post_meta($this->post_id, 'price', true) ?: '',
				'label'       => $label,
				'icon_left'   => '<i class="fas fa-yen-sign"></i>',
				'placeholder' => __('价格', 'wnd'),
				'required'    => $required,
				'step'        => '0.01',
				'min'         => '0',
			]
		);
	}

	/**
	 * 上传付费文件
	 * @since 2019.09.04
	 */
	public function add_post_paid_file_upload($label = '', $required = false) {
		$label = $label ?: __('付费文件', 'wnd');
		$this->add_post_file_upload('file', $label);
		$this->add_url(
			[
				'name'        => 'file_url',
				'value'       => wnd_get_post_meta($this->post_id, 'file_url') ?: '',
				'icon_left'   => '<i class="fas fa-link"></i>',
				'placeholder' => __('文件链接', 'wnd'),
			]
		);
		$this->add_post_price('', $required);
	}

	/**
	 * 设置文章状态
	 * @since 2019.09.04
	 */
	public function add_post_status_select() {
		$this->add_checkbox(
			[
				'name'    => '_post_post_status',
				'options' => [__('存为草稿', 'wnd') => 'draft'],
				'class'   => 'switch is-' . static::$second_color,
			]
		);
	}

	/**
	 * 上传字段简易封装
	 * 如需更多选项，请使用 add_image_upload、add_file_upload 方法 @see Wnd_Form_WP
	 * @since 2019.04.28
	 *
	 * @param string 	$meta_key    	meta key
	 * @param int    	$save_width  	保存图片宽度
	 * @param int    	$save_height 	保存图片高度
	 */
	public function add_post_image_upload($meta_key, $save_width = 0, $save_height = 0, $label = '') {
		if (!$this->post_id) {
			$this->add_html('<div class="notification">' . __('创建post失败，无法上传文件', 'wnd') . '</div>');
			return;
		}

		$args = [
			'label'         => $label,
			'data'          => [
				'post_parent' => $this->post_id,
				'meta_key'    => $meta_key,
				'save_width'  => $save_width,
				'save_height' => $save_height,
			],
			'delete_button' => false,
		];
		$this->add_image_upload($args);
	}

	public function add_post_file_upload($meta_key, $label = '文件上传') {
		if (!$this->post_id) {
			$this->add_html('<div class="notification">' . __('创建post失败，无法上传文件', 'wnd') . '</div>');
			return;
		}

		$this->add_file_upload(
			[
				'label' => $label,
				'data'  => [ // some hidden input,maybe useful in ajax upload
					'meta_key'    => $meta_key,
					'post_parent' => $this->post_id, //如果设置了post parent, 则上传的附件id将保留在对应的wnd_post_meta 否则保留为 wnd_user_meta
				],
			]
		);
	}

	/**
	 * 上传图片集
	 * @since 2019.05.08
	 */
	public function add_post_gallery_upload($save_width = 0, $save_height = 0, $label = '') {
		if (!$this->post_id) {
			$this->add_html('<div class="notification">' . __('创建post失败，无法上传文件', 'wnd') . '</div>');
			return;
		}

		$args = [
			'label'          => $label,
			'thumbnail_size' => ['width' => $this->thumbnail_width, 'height' => $this->thumbnail_height],
			'data'           => [
				'post_parent' => $this->post_id,
				'save_width'  => $save_width, //图片文件存储最大宽度 0 为不限制
				'save_height' => $save_height, //图片文件存储最大过度 0 为不限制
			],
		];

		$this->add_gallery_upload($args);
	}

	/**
	 * 获取Post
	 * @since 2019.08.28
	 */
	public function get_post() {
		return $this->post;
	}

	/**
	 *
	 * 获取当前 Post 全部 所选 term 分类
	 */
	public function get_current_terms() {
		foreach ($this->taxonomies as $taxonomy) {
			$current_terms[$taxonomy] = Wnd_Term::get_post_terms($this->post_id, $taxonomy);
		}

		return $current_terms;
	}
}
