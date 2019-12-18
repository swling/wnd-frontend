<?php
namespace Wnd\View;

use Wnd\Model\Wnd_Post;
use Wnd\Module\Wnd_Term;

/**
 *适配本插件的ajax Post表单类
 *@since 2019.03.11
 *@param $post_type 			string 	option 		类型
 *@param $post_id 				int 	option 		ID
 *@param $input_fields_only 	bool 	option 		是否只生成表单字段（不添加post form 属性字段）
 */
class Wnd_Form_Post extends Wnd_Form_WP {

	protected $post_id;

	protected $post_type;

	protected $post_parent;

	protected $post;

	static protected $default_post = [
		'ID'                    => 0,
		'post_author'           => 0,
		'post_date'             => null,
		'post_date_gmt'         => null,
		'post_content'          => null,
		'post_title'            => null,
		'post_excerpt'          => null,
		'post_status'           => null,
		'comment_status'        => null,
		'ping_status'           => null,
		'post_password'         => null,
		'post_name'             => null,
		'to_ping'               => null,
		'pinged'                => null,
		'post_modified'         => null,
		'post_modified_gmt'     => null,
		'post_content_filtered' => null,
		'post_parent'           => 0,
		'guid'                  => null,
		'menu_order'            => 0,
		'post_type'             => null,
		'post_mime_type'        => null,
		'comment_count'         => 0,
	];

	// 初始化构建
	public function __construct($post_type = 'post', $post_id = 0, $input_fields_only = false) {

		// 继承基础变量
		parent::__construct();

		// 初始化属性
		$this->post_parent      = 0;
		$this->thumbnail_width  = 200;
		$this->thumbnail_height = 200;

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
		$this->post    = $post_id ? get_post($post_id) : (object) self::$default_post;
		$this->post    = $this->post ?: (object) self::$default_post;
		$this->post_id = $this->post->ID;

		/**
		 *文章类型：
		 *若指定了id，则获取对应id的post type
		 *若无则外部传入参数
		 **/
		$this->post_type = $this->post_id ? $this->post->post_type : $post_type;

		// 文章表单固有字段
		if (!$input_fields_only) {
			$this->add_hidden('_post_ID', $this->post_id);
			$this->add_hidden('_post_post_type', $this->post_type);
			$this->set_action('wnd_insert_post');
		}
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

	public function add_post_title($label = '', $placeholder = "请输入标题", $required = true) {
		$this->add_text(
			[
				'name'        => '_post_post_title',
				'value'       => $this->post->post_title == 'Auto-draft' ? '' : $this->post->post_title,
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

	public function add_post_category_select($taxonomy, $label = '', $required = true) {
		$taxonomy_object = get_taxonomy($taxonomy);
		if (!$taxonomy_object) {
			return;
		}

		// 获取当前文章已选择分类id
		$current_terms   = get_the_terms($this->post_id, $taxonomy);
		$current_terms   = $current_terms ? reset($current_terms) : 0;
		$current_term_id = $current_terms ? $current_terms->term_id : 0;

		// 获取taxonomy下的term
		$terms   = get_terms($args = ['taxonomy' => $taxonomy, 'hide_empty' => false]) ?: [];
		$options = ['- ' . $taxonomy_object->labels->name . ' -' => -1];
		foreach ($terms as $term) {
			$options[$term->name] = $term->term_id;
		}
		unset($term);

		// 新增表单字段
		$this->add_select(
			[
				'name'     => '_term_' . $taxonomy,
				'options'  => $options,
				'required' => $required,
				'checked'  => $current_term_id, //default checked value
				'label'    => $label,
			]
		);
	}

	/**
	 *分类复选框
	 *
	 */
	public function add_post_category_checkbox($taxonomy, $label = '') {
		$taxonomy_object = get_taxonomy($taxonomy);
		if (!$taxonomy_object) {
			return;
		}

		// 获取当前文章已选择分类ids
		$current_terms    = get_the_terms($this->post_id, $taxonomy) ?: [];
		$current_term_ids = [];
		foreach ($current_terms as $current_term) {
			$current_term_ids[] = $current_term->term_id;
		}unset($current_terms, $current_term);

		// 获取taxonomy下的term
		$terms   = get_terms($args = ['taxonomy' => $taxonomy, 'hide_empty' => false]) ?: [];
		$options = [];
		foreach ($terms as $term) {
			$options[$term->name] = $term->term_id;
		}
		unset($term);

		$this->add_checkbox(
			[
				'name'    => '_term_' . $taxonomy . '[]',
				'options' => $options,
				'checked' => $current_term_ids,
				'label'   => $label,
			]
		);
	}

	public function add_post_tags($taxonomy, $placeholder = '标签', $required = false) {
		$taxonomy_object = get_taxonomy($taxonomy);
		$terms           = get_the_terms($this->post_id, $taxonomy) ?: [];
		if (is_wp_error($terms)) {
			return;
		}

		$term_list = '';
		foreach ($terms as $term) {
			$term_list .= $term->name . ',';
		}unset($term);
		// 移除末尾的逗号
		$term_list = rtrim($term_list, ',');

		$this->add_text(
			[
				'id'          => 'tags',
				'name'        => '_term_' . $taxonomy,
				'value'       => $term_list,
				'placeholder' => $placeholder,
				// 'label' => $taxonomy_object->labels->name,
				'required'    => $required,
			]
		);

		$this->add_html(Wnd_Term::tags_editor_script(3, 20, $placeholder, $taxonomy));
	}

	/**
	 *@param int $save_width 	缩略图保存宽度
	 *@param int $save_height 	缩略图保存高度
	 *@param int $label
	 **/
	public function add_post_thumbnail($save_width = 200, $save_height = 200, $label = '') {
		$this->add_post_image_upload('_thumbnail_id', $save_width, $save_height, $label);
	}

	public function add_post_content($rich_media_editor = true, $placeholder = '详情', $required = false) {
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
			$this->add_input_name('_post_post_content');

			echo '<div id="hidden-wp-editor" style="display: none;">';
			if ($post) {
				wp_editor($post->post_content, '_post_post_content', 'media_buttons=1');
			} else {
				wp_editor('', '_post_post_content', 'media_buttons=0');
			}
			echo '</div>';

		} else {
			$this->add_textarea(
				[
					'name'        => '_post_post_content',
					'value'       => $post->post_content ?? '',
					'placeholder' => $placeholder,
					'required'    => $required,
				]
			);
		}

		$this->add_html('<div id="wnd-wp-editor" class="field"></div>');
		$this->add_html('<script type="text/javascript">var wp_editor = $("#hidden-wp-editor").html();$("#hidden-wp-editor").remove();$("#wnd-wp-editor").html(wp_editor);</script>');
	}

	/**
	 *@since 2019.07.09 常规wnd meta文章字段
	 **/
	public function add_post_meta($meta_key, $label = '', $placeholder = '', $required = false) {
		$name  = '_meta_' . $meta_key;
		$value = wnd_get_post_meta($this->post_id, $meta_key);
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
		$value = get_post_meta($this->post_id, $meta_key, true);
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
	public function add_post_menu_order($label = '排序', $placeholder = "输入排序", $required = false) {
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
	public function add_post_name($label = '别名', $placeholder = "文章固定连接别名", $required = false) {
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
				'value'       => get_post_meta($this->post_id, 'price', true),
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
		$this->add_post_file_upload('file', '文件上传');
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
				'options' => ['存为草稿' => 'draft'],
				'class'   => 'switch is-' . self::$second_color,
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
			$this->add_html('<div class="notification">获取post ID失败，无法设置图像上传！</div>');
			return;
		}

		$args = [
			'label'          => $label,
			'thumbnail_size' => ['width' => $this->thumbnail_width, 'height' => $this->thumbnail_height],
			'thumbnail'      => WND_URL . 'static/images/default.jpg',
			'data'           => [
				'post_parent' => $this->post_id,
				'meta_key'    => $meta_key,
				'save_width'  => $save_width,
				'save_height' => $save_height,
			],
			'delete_button'  => false,
		];
		$this->add_image_upload($args);
	}

	public function add_post_file_upload($meta_key, $label = '文件上传') {
		if (!$this->post_id) {
			$this->add_html('<div class="notification">获取post ID失败，无法设置文件上传！</div>');
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
			$this->add_html('<div class="notification">获取post ID失败，无法设置相册上传！</div>');
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
}
