<?php
namespace Wnd\View;

use Wnd\Model\Wnd_Post;
use Wnd\Model\Wnd_Term;

/**
 *适配本插件的ajax Post表单类
 *@since 2019.03.11
 *@param $post_type 			string 	option 		类型
 *@param $post_id 				int 	option 		ID
 *@param $input_fields_only 	bool 	option 		是否只生成表单字段（不添加post form 属性字段）
 */
class Wnd_Form_Post extends Wnd_Form_WP {

	protected $post_id = 0;

	protected $post_type = 'post';

	protected $post_parent = 0;

	protected $post;

	/**
	 * 当post已选的Terms
	 * [
	 *	${taxonomy}=>[term_id1,term_id2]
	 * ]
	 */
	protected $current_terms = [];

	// 当前post 支持的 taxonomy
	protected $taxonomies = [];

	static protected $default_post = [
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
		 *@since 0.9.0
		 *表单提交验证码
		 */
		$enable_captcha = apply_filters('enable_post_form_captcha', !is_user_logged_in(), $post_type, $post_id);

		// 继承父类构造
		parent::__construct(true, $enable_captcha);

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
	 *初始化 Post 数据
	 */
	protected function setup_postdata($post_type, $post_id) {
		/**
		 *@since 2019.12.16 若传参false，表示表单不需要创建草稿
		 *用于不需要文件上传的表单以降低数据库操作
		 *
		 *
		 *其余情况未指定ID，创建新草稿
		 *
		 */
		if (false === $post_id) {
			$post_id = 0;
		} else {
			$post_id = $post_id ?: Wnd_Post::get_draft($post_type);
		}

		/**
		 *@see WordPress get_post()
		 *当创建草稿失败，$this->post_id = 0 $this->post获取得到的将是WordPress当前页面
		 *当指定post_id无效，get_post将返回null
		 *上述两种情况均初始化一个空白的对象
		 *2019.07.16
		 */
		$this->post    = $post_id ? get_post($post_id) : (object) static::$default_post;
		$this->post    = $this->post ?: (object) static::$default_post;
		$this->post_id = $this->post->ID;

		/**
		 *@since 0.9.25
		 *将post id 写入表单自定义属性，供前端渲染使用
		 */
		$this->add_form_attr('data-post-id', $this->post_id);

		/**
		 *文章类型：
		 *若指定了id，则获取对应id的post type
		 *若无则外部传入参数
		 **/
		$this->post_type = $this->post_id ? $this->post->post_type : $post_type;

		/**
		 *@since 2020.04.19
		 *获取当前Post_type 的所有 Taxonomy
		 *获取当前post 已选term数据
		 */
		$this->taxonomies    = get_object_taxonomies($this->post_type, 'names');
		$this->current_terms = $this->taxonomies ? $this->get_current_terms() : [];
	}

	/**
	 *@since 2019.09.04
	 *设置post parent
	 *@param int 	$post_parent
	 **/
	public function set_post_parent($post_parent) {
		$this->post_parent = $post_parent;
		$this->add_hidden('_post_post_parent', $post_parent);
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

	// Term 分类单选下拉：本方法不支持复选
	public function add_post_term_select($args_or_taxonomy, $label = '', $required = true, $dynamic_sub = false) {
		$taxonomy        = is_array($args_or_taxonomy) ? $args_or_taxonomy['taxonomy'] : $args_or_taxonomy;
		$taxonomy_object = get_taxonomy($taxonomy);
		if (!$taxonomy_object) {
			return;
		}

		// 获取taxonomy下的 term 键值对
		$option_data = Wnd_Term::get_terms_data($args_or_taxonomy);
		$option_data = array_merge(['- ' . $taxonomy_object->labels->name . ' -' => ''], $option_data);

		// 新增表单字段
		$this->add_select(
			[
				'name'     => '_term_' . $taxonomy . '[]',
				'options'  => $option_data,
				'required' => $required,
				'selected' => reset($this->current_terms[$taxonomy]) ?: '', //default checked value
				'label'    => $label,
				'class'    => $taxonomy . ($dynamic_sub ? ' dynamic-sub' : false),
				'data'     => ['child_level' => 0],
			]
		);
	}

	/**
	 *动态子类下拉菜单，不支持复选
	 *其具体筛选项，将跟随上一级动态菜单而定
	 *@see Wnd\Module\Wnd_Sub_Terms_Options::render()
	 *
	 *@since 2020.04.14
	 **/
	public function add_dynamic_sub_term_select($taxonomy, $child_level = 1, $label = '', $required = false, $tips = '') {
		// 获取当前 post 已选择的 taxonomy 一级 term
		$top_level_term_id = 0;
		foreach ($this->current_terms[$taxonomy] as $current_term) {
			if (1 == Wnd_Term::get_term_level($current_term, $taxonomy)) {
				$top_level_term_id = $current_term;
				break;
			}
		}unset($current_term);

		// 根据已选择的一级 term 获取对应层级的子类 ids 并构建下拉数组对
		$option_data         = ['- ' . $tips . ' -' => ''];
		$this_level_term_ids = Wnd_Term::get_term_children_by_level($top_level_term_id, $taxonomy, $child_level);
		foreach ($this_level_term_ids as $term_id) {
			$term                     = get_term($term_id);
			$option_data[$term->name] = $term_id;
		}unset($this_level_term_ids, $term_id);

		// 新增表单字段
		$this->add_select(
			[
				'name'     => '_term_' . $taxonomy . '[]',
				'options'  => $option_data,
				'required' => $required,
				'disabled' => count($option_data) <= 1, // 当可选项小于等于1，表明当前条件下子类不可用，设置disabled属性
				'selected' => reset($this->current_terms[$taxonomy]), //default checked value
				'label'    => $label,
				'class'    => 'dynamic-sub ' . 'dynamic-sub-' . $taxonomy . ' ' . $taxonomy . '-child-' . $child_level,
				'data'     => ['child_level' => $child_level, 'tips' => $tips],
			]
		);
	}

	/**
	 *分类复选框
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
	 *分类单选框
	 *@since 2020.04.17
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
				'name'     => '_term_' . $taxonomy . '[]',
				'options'  => $option_data,
				'checked'  => $this->current_terms[$taxonomy][0] ?? false,
				'label'    => $label,
				'class'    => $taxonomy,
				'required' => $required,
			]
		);
	}

	/**
	 *自定义标签编辑器
	 *@since 2020.05.12
	 *@since 0.9.25 以 Vue 重构 该字段不再支持常规 php 渲染
	 */
	public function add_post_tags($taxonomy, $label = '', $required = false) {
		$taxonomy_object = get_taxonomy($taxonomy);
		if (!$taxonomy_object) {
			return;
		}

		$args = [
			'type'        => 'tag_input',
			'tags'        => array_values($this->current_terms[$taxonomy]) ?: [],
			'label'       => $label ?: $taxonomy_object->labels->name,
			'name'        => '_term_' . $taxonomy,
			'taxonomy'    => $taxonomy,
			'required'    => $required,
			'suggestions' => [],
		];
		$this->add_field($args);
	}

	/**
	 *@param int $save_width 	缩略图保存宽度
	 *@param int $save_height 	缩略图保存高度
	 *@param int $label
	 **/
	public function add_post_thumbnail($save_width = 200, $save_height = 200, $label = '') {
		$this->add_post_image_upload('_thumbnail_id', $save_width, $save_height, $label);
	}

	/**
	 *正文编辑器
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
	 *@since 2019.07.09 常规wnd meta文章字段
	 **/
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
	 *@since 2019.08.25 常规WordPress原生文章字段
	 **/
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
	 *@since 2019.07.17
	 *设置post menu_order
	 *常用菜单、附件等排序
	 **/
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
	 *@since 2019.07.18
	 *设置post_name 固定链接别名
	 **/
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

	public function add_post_price($label = '', $placeholder = '价格', $required = false) {
		$this->add_number(
			[
				'name'        => '_wpmeta_price',
				'value'       => get_post_meta($this->post_id, 'price', true) ?: '',
				'label'       => $label,
				'icon_left'   => '<i class="fas fa-yen-sign"></i>',
				'placeholder' => $placeholder,
				'required'    => $required,
				'step'        => '0.01',
				'min'         => '0',
			]
		);
	}

	/**
	 *@since 2019.09.04
	 *上传付费文件
	 */
	public function add_post_paid_file_upload($label = '', $placeholder = '价格', $required = false) {
		$this->add_post_file_upload('file', __('文件上传', 'wnd'));
		$this->add_post_price($label, $placeholder, $required);
	}

	/**
	 *@since 2019.09.04
	 *设置文章状态
	 **/
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
	 *@since 2019.04.28 上传字段简易封装
	 *如需更多选项，请使用 add_image_upload、add_file_upload 方法 @see Wnd_Form_WP
	 *@param string $meta_key 		meta key
	 *@param int 	$save_width 	保存图片宽度
	 *@param int 	$save_height 	保存图片高度
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
	 *@since 2019.05.08 上传图片集
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
	 *文章表头，屏蔽回车提交
	 */
	protected function build_form_header() {
		$this->add_form_attr('onkeydown', 'if(event.keyCode==13){return false;}');
		parent::build_form_header();
	}

	/**
	 *@since 2019.08.28
	 *获取Post
	 **/
	public function get_post() {
		return $this->post;
	}

	/**
	 *
	 *获取当前 Post 全部 所选 term 分类
	 */
	public function get_current_terms() {
		foreach ($this->taxonomies as $taxonomy) {
			$current_terms[$taxonomy] = Wnd_Term::get_post_terms($this->post_id, $taxonomy);
		}

		return $current_terms;
	}
}
