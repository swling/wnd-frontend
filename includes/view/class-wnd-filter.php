<?php
namespace Wnd\View;

use Exception;
use Wnd\Model\Wnd_Tag_Under_Category;
use WP_Query;

/**
 * @since 2019.07.30
 * 多重筛选类
 * 样式基于bulma css
 * @param bool 		$is_ajax 	是否为ajax筛选（需要对应的前端支持）
 * @param string 	$uniqid 	HTML容器识别ID。默认值 uniqid() @see build_pagination() / get_tabs()
 */
class Wnd_Filter {

	// bool 是否ajax
	protected static $is_ajax;

	// bool 是否正处于ajax环境中
	protected static $doing_ajax;

	// string 当前筛选器唯一标识
	protected $uniqid;

	// string html class
	protected $class;

	/**
	 * 现有方法之外，其他新增的查询参数
	 * 将在筛选容器，及分页容器上出现，以绑定点击事件，发送到api接口
	 * 以data-{key}="{value}"形式出现，ajax请求中，将转化为 url请求参数 ?{key}={value}
	 */
	protected $add_query = array();

	/**
	 *meta 查询参数需要供current filter查询使用
	 **/
	protected $meta_filter_args;

	// 默认切换筛选项时需要移除的参数
	protected $remove_query_args = array('paged', 'page');

	/**
	 *根据配置设定的wp_query查询参数
	 *默认值将随用户设定而改变
	 *
	 *参数中包含自定义的非wp_query参数以"wnd"前缀区分
	 */
	protected $wp_query_args = array(
		'orderby'            => 'date',
		'order'              => 'DESC',
		'meta_query'         => array(),
		'tax_query'          => array(),
		'date_query'         => array(),
		'meta_key'           => '',
		'meta_value'         => '',
		'post_type'          => '',
		'post_status'        => '',
		'no_found_rows'      => true,
		'paged'              => 1,

		// 自定义
		'wnd_ajax_container' => '',
		'wnd_post_tpl'       => '',
		'wnd_posts_tpl'      => '',
		'wnd_uniqid'         => '',
	);

	/**
	 *WP_Query 查询结果：
	 *@see $this->query();
	 */
	public $wp_query;

	// 当前post type的主分类taxonomy 约定：post(category) / 自定义类型 （$post_type . '_cat'）
	public $category_taxonomy;

	// 筛选项HTML
	protected $tabs = '';

	// 筛选结果HTML
	protected $posts = '';

	// 分页导航HTML
	protected $pagination = '';

	/**
	 * Constructor.
	 *
	 * @param bool $is_ajax 是否为ajax查询
	 */
	public function __construct(bool $is_ajax = false, string $uniqid = '') {
		self::$is_ajax    = $is_ajax;
		self::$doing_ajax = wnd_doing_ajax();
		$this->class .= self::$is_ajax ? 'ajax-filter' : '';

		// 解析GET参数为wp_query参数并与默认参数合并，以防止出现参数未定义的警告信息
		$this->wp_query_args = array_merge($this->wp_query_args, self::parse_query_vars());

		// 初始化时生成uniqid，并加入query参数，以便在ajax生成的新请求中保持一致
		$this->uniqid = $uniqid ?: $this->wp_query_args['wnd_uniqid'] ?: uniqid();
		$this->add_query(array('wnd_uniqid' => $this->uniqid));

		/**
		 *定义当前post type的主分类：$category_taxonomy
		 */
		if ($this->wp_query_args['post_type']) {
			$this->category_taxonomy = ($this->wp_query_args['post_type'] == 'post') ? 'category' : $this->wp_query_args['post_type'] . '_cat';
		}

		// 非管理员，仅可查询当前用户自己的非公开post
		if (!in_array($this->wp_query_args['post_status'], array('publish', 'close')) and !is_super_admin()) {
			if (!is_user_logged_in()) {
				throw new Exception('未登录用户，仅可查询公开信息！');
			}
			$this->wp_query_args['author'] = get_current_user_id();
		}
	}

	/**
	 * @since 2019.07.20
	 * 从GET参数中解析wp_query参数
	 *
	 * @return 	array 	wp_query $args
	 *
	 * @see 解析规则：
	 * type={post_type}
	 * status={post_status}
	 *
	 * post字段
	 * _post_{post_field}={value}
	 *
	 *meta查询
	 * _meta_{key}={$meta_value}
	 * _meta_{key}=exists
	 *
	 *分类查询
	 * _term_{$taxonomy}={term_id}
	 *
	 * 其他查询（具体参考 wp_query）
	 * $args[$key] = $value;
	 **/
	public static function parse_query_vars() {
		if (empty($_GET)) {
			return array();
		}

		$query_vars = array(
			'meta_query' => array(),
			'tax_query'  => array(),
			'date_query' => array(),
		);

		foreach ($_GET as $key => $value) {

			/**
			 *post type tabs生成的GET参数为：type={$post_type}
			 *直接用 post_type 作为参数会触发WordPress原生请求导致错误
			 */
			if ('type' === $key) {
				$query_vars['post_type'] = $value;
				continue;
			}

			/**
			 *post status tabs生成的GET参数为：status={$post_status}
			 */
			if ('status' === $key) {
				$query_vars['post_status'] = $value;
				continue;
			}

			/**
			 *@since 2019.3.07 自动匹配meta query
			 *?_meta_price=1 则查询 price = 1的文章
			 *?_meta_price=exists 则查询 存在price的文章
			 */
			if (strpos($key, '_meta_') === 0) {
				$key        = str_replace('_meta_', '', $key);
				$compare    = $value == 'exists' ? 'exists' : '=';
				$meta_query = array(
					'key'     => $key,
					'value'   => $value,
					'compare' => $compare,
				);

				/**
				 *@since 2019.04.21 当meta_query compare == exists 不能设置value
				 */
				if ('exists' == $compare) {
					unset($meta_query['value']);
				}

				$query_vars['meta_query'][] = $meta_query;
				continue;
			}

			/**
			 *categories tabs生成的GET参数为：'_term_' . $taxonomy，
			 *直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
			 */
			if (strpos($key, '_term_') === 0) {
				$term_query = array(
					'taxonomy' => str_replace('_term_', '', $key),
					'field'    => 'term_id',
					'terms'    => $value,
				);
				$query_vars['tax_query'][] = $term_query;
				continue;
			}

			/**
			 *@since 2019.05.31 post field查询
			 */
			if (strpos($key, '_post_') === 0) {
				$query_vars[str_replace('_post_', '', $key)] = $value;
				continue;
			}

			/**
			 *@since 2019.07.30
			 *分页
			 */
			if ('page' == $key) {
				$query_vars['paged'] = $value ?: 1;
				continue;
			}

			/**
			 *@since 2019.08.04
			 *ajax Orderby tabs链接请求中，orderby将发送HTTP query形式的信息，需要解析后并入查询参数
			 *
			 */
			if ('orderby' == $key and self::$is_ajax) {
				/**
				 * @see 	build_orderby_filter
				 * @since 	2019.08.18
				 *
				 * orderby=meta_value_num&meta_key=views
				 * 解析结果
				 * $query_vars['orderby'] =>meta_value_num
				 * $query_vars['meta_ket'] =>views
				 *
				 * 判断方法：此类传参，必须包含 = 符号
				 */
				if (false !== strpos($value, '=')) {
					$query_vars = wp_parse_args($value, $query_vars);

					// 常规形式直接指定orderby
				} else {
					$query_vars['orderby'] = $value;
				}

				continue;
			}

			/**
			 *@since 2019.08.17
			 *ajax请求中，数组类型查询参数，需要解析后并入查询参数
			 */
			if (in_array($key, array('tax_query', 'meta_query', 'date_query')) and self::$is_ajax) {
				$query_vars[$key] = wp_parse_args($value, $query_vars[$key]);
				continue;
			}

			// 其他、按键名自动匹配、排除指定作者的参数
			$query_vars[$key] = $value;
			continue;
		}
		unset($key, $value);

		return $query_vars;
	}

	/**
	 *@since 2019.07.31
	 *设置ajax post列表嵌入容器
	 *@param string $container posts列表ajax嵌入容器
	 **/
	public function set_ajax_container($container) {
		$this->add_query(array('wnd_ajax_container' => $container));
	}

	/**
	 *@since 2019.07.31
	 *设置ajax post列表嵌入容器
	 *@param int $posts_per_page 每页post数目
	 **/
	public function set_posts_per_page($posts_per_page) {
		$this->add_query(array('posts_per_page' => $posts_per_page));
	}

	/**
	 *@since 2019.08.02
	 *设置列表post模板函数，传递$post对象
	 *@param string $template post模板函数名
	 **/
	public function set_post_template($template) {
		$this->add_query(array('wnd_post_tpl' => $template));
	}

	/**
	 *@since 2019.08.16
	 *文章列表页整体模板函数，传递wp_query查询结果
	 *设置模板后，$this->get_posts() 即为被该函数返回值
	 *@param string $template posts模板函数名
	 **/
	public function set_posts_template($template) {
		$this->add_query(array('wnd_posts_tpl' => $template));
	}

	/**
	 *@since 2019.07.31
	 *添加新的请求参数
	 *添加的参数，将覆盖之前的设定，并将在所有请求中有效，直到被新的设定覆盖
	 *
	 *@param array $query array(key=>value)
	 *
	 *
	 *在非ajax环境中，直接将写入$wp_query_args[key]=value
	 *
	 *在ajax环境中，将对应生成html data属性：data-{key}="{value}" 通过JavaScript获取后将转化为 ajax url请求参数 ?{key}={value}，
	 *ajax发送到api接口，再通过parse_query_vars() 解析后，写入$wp_query_args[key]=value
	 **/
	public function add_query($query = array()) {
		foreach ($query as $key => $value) {
			// $_GET参数优先，无法重新设置
			if (in_array($key, array_keys($_GET))) {
				continue;
			}

			// 数组参数，合并元素；非数组参数，赋值 （php array_merge：相同键名覆盖，未定义键名或以整数做键名，则新增)
			if (is_array($this->wp_query_args[$key] ?? false) and is_array($value)) {
				$this->wp_query_args[$key] = array_merge($this->wp_query_args[$key], $value);
			} else {
				$this->wp_query_args[$key] = $value;
			}

			// 在html data属性中新增对应属性，以实现在ajax请求中同步添加参数
			$this->add_query[$key] = $value;
		}
		unset($key, $value);
	}

	/**
	 *@param array 	$args 需要筛选的类型数组
	 *@param bool 	$with_any_tab 是否包含全部选项
	 */
	public function add_post_type_filter($args = array(), $with_any_tab = false) {

		/**
		 *若当前请求未指定post_type，设置第一个post_type为默认值；若筛选项也为空，最后默认post
		 *post_type/post_status 在所有筛选中均需要指定默认值，若不指定，WordPress也会默认设定
		 *
		 * 当前请求为包含post_type参数时，当前的主分类（category_taxonomy）无法在构造函数中无法完成定义，需在此处补充
		 */
		if (!$this->wp_query_args['post_type']) {
			$this->wp_query_args['post_type'] = $with_any_tab ? 'any' : ($args ? reset($args) : 'post');
			$this->category_taxonomy          = ($this->wp_query_args['post_type'] == 'post') ? 'category' : $this->wp_query_args['post_type'] . '_cat';
		}

		$tabs = $this->build_post_type_filter($args, $with_any_tab);
		$this->tabs .= $tabs;

		return $tabs;
	}

	/**
	 *状态筛选
	 *@param array $args 需要筛选的文章状态数组
	 */
	public function add_post_status_filter($args = array()) {

		/**
		 *若当前请求未指定post_status，设置第一个post_status为默认值；若筛选项也为空，最后默认publish
		 *post_type/post_status 在所有筛选中均需要指定默认值，若不指定，WordPress也会默认设定
		 */
		$default_status = $this->wp_query_args['post_status'] ?: ($args ? reset($args) : 'publish');
		$this->add_query(array('post_status' => $default_status));

		$tabs = $this->build_post_status_filter($args);
		$this->tabs .= $tabs;
		return $tabs;
	}

	/**
	 *@since 2019.02.28
	 *@param $args 	array get_terms 参数
	 *若查询的taxonomy与当前post type未关联，则不输出
	 */
	public function add_taxonomy_filter(array $args) {
		$args['parent'] = $args['parent'] ?? 0;
		$taxonomy       = $args['taxonomy'] ?? null;
		if (!$taxonomy) {
			return;
		}

		$tabs = $this->build_taxonomy_filter($args);

		/**
		 *@since 2019.03.12
		 *遍历当前tax query 查询是否设置了对应的taxonomy查询，若存在则查询其对应子类
		 */
		$taxonomy_query = false;
		foreach ($this->wp_query_args['tax_query'] as $key => $tax_query) {
			// WP_Query tax_query参数可能存在：'relation' => 'AND', 'relation' => 'OR',参数，需排除 @since 2019.06.14
			if (!isset($tax_query['terms'])) {
				continue;
			}

			if (array_search($taxonomy, $tax_query) !== false) {
				$taxonomy_query = true;
				break;
			}
		}
		unset($key, $tax_query);

		if (!$taxonomy_query) {
			$this->tabs .= $tabs;
			return $tabs;
		}

		// 获取当前taxonomy子类tabs
		$sub_tabs = $this->get_sub_taxonomy_tabs()[$taxonomy];

		$this->tabs .= $tabs . $sub_tabs;
		return $tabs . $sub_tabs;
	}

	/**
	 * 标签筛选
	 * 定义taxonomy：{$post_type}.'_tag'
	 * 读取wp_query中tax_query 提取taxonomy为{$post_type}.'_cat'的分类id，并获取对应的关联标签(需启用标签分类关联功能)
	 * 若未设置关联分类，则查询所有热门标签
	 *@since 2019.03.25
	 */
	public function add_related_tags_filter($limit = 10) {
		$tabs = $this->build_related_tags_filter($limit);
		$this->tabs .= $tabs;
		return $tabs;
	}

	/**
	 *@since 2019.04.18 meta query
	 *@param 自定义： array args meta字段筛选:
	 *		暂只支持单一 meta_key 暂仅支持 = 、exists 两种compare
	 *
	 *	$args = array(
	 *		'label' => '文章价格',
	 *		'key' => 'price',
	 *		'options' => array(
	 *			'10' => '10',
	 *			'0.1' => '0.1',
	 *		),
	 *		'compare' => '=',
	 *	);
	 *
	 *	查询一个字段是否存在：options只需要设置一个：其作用为key值显示为选项文章，value不参与查询，可设置为任意值
	 *	$args = array(
	 *		'label' => '文章价格',
	 *		'key' => 'price',
	 *		'options' => array(
	 *			'包含' => 'exists',
	 *		),
	 *		'compare' => 'exists',
	 *	);
	 *
	 */
	public function add_meta_filter($args) {
		$tabs = $this->build_meta_filter($args);
		$this->tabs .= $tabs;
		return $tabs;
	}

	/**
	 *@since 2019.04.21 排序
	 *@param 自定义： array args
	 *
	 *	$args = array(
	 *		'label' => '排序',
	 *		'options' => array(
	 *			'发布时间' => 'date', //常规排序 date title等
	 *			'浏览量' => array( // 需要多个参数的排序
	 *				'orderby'=>'meta_value_num',
	 *				'meta_key'   => 'views',
	 *			),
	 *		),
	 *		'order' => 'DESC',
	 *	);
	 *
	 */
	public function add_orderby_filter($args) {
		$tabs = $this->build_orderby_filter($args);
		$this->tabs .= $tabs;
		return $tabs;
	}

	/**
	 *@since 2019.08.10 排序方式
	 *@param 自定义： array args
	 *
	 *	$args = array(
	 *		'降序' => 'DESC',
	 *		'升序' =>'ASC'
	 *	);
	 *
	 *@param string $label 选项名称
	 */
	public function add_order_filter($args, $label = '排序') {
		$tabs = $this->build_order_filter($args, $label);
		$this->tabs .= $tabs;
		return $tabs;
	}

	/**
	 *@since 2019.03.26
	 *遍历当前查询参数，输出取消当前查询链接
	 */
	public function add_current_filter() {
		$tabs = $this->build_current_filter();
		$this->tabs .= $tabs;
		return $tabs;
	}

	/**
	 *
	 *@since 2019.08.02
	 *构造HTML data属性
	 *获取新增查询，并转化为html data属性，供前端读取后在ajax请求中发送到api
	 */
	protected function build_html_data() {
		$data = '';
		foreach ($this->add_query as $key => $value) {
			$value = is_array($value) ? http_build_query($value) : $value;
			$data .= 'data-' . $key . '="' . $value . '" ';
		}

		return $data;
	}

	/**
	 *@param array $args 需要筛选的类型数组
	 *
	 *$args = array('post','page')
	 *
	 *@param bool 	$with_any_tab 是否包含全部选项
	 */
	protected function build_post_type_filter($args = array(), $with_any_tab = false) {

		/**
		 *@since 2019.08.06
		 *post type切换时，表示完全新的筛选，故此移除所有GET参数
		 */
		$uri = strtok($_SERVER['REQUEST_URI'], '?');

		// 若筛选项少于2个，即无需筛选post type：隐藏tabs
		$tabs = '<div class="tabs is-boxed post-type-tabs ' . (count($args) < 2 ? 'is-hidden' : '') . '">';
		$tabs .= '<ul class="tab">';
		if ($with_any_tab) {
			$class = ('any' == $this->wp_query_args['post_type']) ? ' is-active' : '';
			$tabs .= '<li class="all' . $class . '">';
			$tabs .= '<a data-key="type" data-value="any" href="' . add_query_arg('type', 'any', $uri) . '">全部</a>';
			$tabs .= '</li>';
		}

		// 输出tabs
		foreach ($args as $post_type) {
			// 根据类型名，获取完整的类型信息
			$post_type = get_post_type_object($post_type);

			$class = 'post-type-' . $post_type->name;
			$class .= ($this->wp_query_args['post_type'] == $post_type->name) ? ' is-active' : '';
			$post_type_link = self::$doing_ajax ? '' : add_query_arg('type', $post_type->name, $uri);

			$tabs .= '<li class="' . $class . '">';
			$tabs .= '<a data-key="type" data-value="' . $post_type->name . '" href="' . $post_type_link . '">' . $post_type->label . '</a>';
			$tabs .= '</li>';
		}
		unset($post_type);

		// 输出结束
		$tabs .= '</ul>';
		$tabs .= '</div>';
		return $tabs;
	}

	/**
	 *状态筛选
	 *@param array $args 需要筛选的文章状态数组
	 *
	 *	$args = array(
	 *		'公开'=>publish',
	 *		'草稿'=>draft'
	 *	)
	 */
	protected function build_post_status_filter($args = array()) {

		// 输出容器
		$tabs = '<div class="columns is-marginless is-vcentered post-status-tabs ' . (count($args) < 2 ? 'is-hidden' : '') . '">';
		$tabs .= '<div class="column is-narrow">状态：</div>';
		$tabs .= '<div class="tabs column">';
		$tabs .= '<div class="tabs">';
		$tabs .= '<ul class="tab">';

		// 输出tabs
		foreach ($args as $label => $post_status) {
			$class = 'post-status-' . $post_status;
			$class .= (isset($this->wp_query_args['post_status']) and $this->wp_query_args['post_status'] == $post_status) ? ' is-active' : '';
			$status_link = self::$doing_ajax ? '' : add_query_arg('status', $post_status, remove_query_arg($this->remove_query_args));

			$tabs .= '<li class="' . $class . '">';
			$tabs .= '<a data-key="status" data-value="' . $post_status . '" href="' . $status_link . '">' . $label . '</a>';
			$tabs .= '</li>';
		}
		unset($label, $post_status);

		// 输出结束
		$tabs .= '</ul>';
		$tabs .= '</div>';
		$tabs .= '</div>';
		$tabs .= '</div>';

		return $tabs;
	}

	/**
	 *@since 2019.08.09
	 *@param array 		$args  		WordPress get_terms() 参数
	 *@param string 	$class 		额外设置的class
	 *若查询的taxonomy与当前post type未关联，则不输出
	 */
	protected function build_taxonomy_filter(array $args, $class = '') {
		if (!isset($args['taxonomy'])) {
			return;
		}
		$terms = get_terms($args);
		if (!$terms or is_wp_error($terms)) {
			return;
		}

		$taxonomy = $args['taxonomy'];
		$parent   = $args['parent'] ?? 0;

		/**
		 *@since 2019.07.30
		 *如果当前指定的taxonomy并不存在指定的post type中，非ajax环境直接中止，ajax环境中隐藏输出（根据post_type动态切换是否显示）
		 */
		$current_post_type_taxonomies = get_object_taxonomies($this->wp_query_args['post_type'], $output = 'names');
		if (!in_array($taxonomy, $current_post_type_taxonomies)) {
			if (!self::$is_ajax) {
				return;
			} else {
				$class .= ' is-hidden';
			}
		}

		// 标记主分类
		if ($taxonomy == $this->category_taxonomy) {
			$class .= ' main-category-tabs'; //不可为 category-tabs，会与post默认分类法category重复
		}

		/**
		 * 遍历当前tax query 查询是否设置了对应的taxonomy查询
		 */
		$all_class = 'class="is-active"';
		foreach ($this->wp_query_args['tax_query'] as $key => $tax_query) {
			// WP_Query tax_query参数可能存在：'relation' => 'AND', 'relation' => 'OR',参数，需排除 @since 2019.06.14
			if (!isset($tax_query['terms'])) {
				continue;
			}

			// 当前taxonomy在tax query中是否已设置参数，若设置，取消全部选项class: is-active
			if (array_search($taxonomy, $tax_query) !== false) {
				$all_class = '';
				break;
			}
		}
		unset($key, $tax_query);

		/**
		 * 切换主分类时，需要移除分类关联标签查询
		 * @since 2019.07.30
		 */
		if ($taxonomy == $this->category_taxonomy) {
			$remove_query_args = array_merge(array('_term_' . $this->wp_query_args['post_type'] . '_tag'), $this->remove_query_args);
		} else {
			$remove_query_args = $this->remove_query_args;
		}

		$tabs = '<div class="columns is-marginless is-vcentered taxonomy-tabs ' . $taxonomy . '-tabs ' . $class . '">';
		$tabs .= '<div class="column is-narrow ' . $taxonomy . '-label">' . get_taxonomy($taxonomy)->label . '：</div>';
		$tabs .= '<div class="tabs column">';
		$tabs .= '<ul class="tab">';

		/**
		 * 全部选项
		 * @since 2019.03.07
		 */
		if (!$parent) {
			$all_link = self::$doing_ajax ? '' : remove_query_arg('_term_' . $taxonomy, remove_query_arg($remove_query_args));
			$tabs .= '<li ' . $all_class . '><a data-key="_term_' . $taxonomy . '" data-value="" href="' . $all_link . '">全部</a></li>';
		}

		// 输出tabs
		foreach ($terms as $term) {
			$class = 'term-id-' . $term->term_id;

			// 遍历当前tax query查询是否匹配当前tab
			foreach ($this->wp_query_args['tax_query'] as $tax_query) {
				// WP_Query tax_query参数可能存在：'relation' => 'AND', 'relation' => 'OR',参数，需排除 @since 2019.06.14
				if (!isset($tax_query['terms'])) {
					continue;
				}

				/**
				 *如果当前tax_query参数中包含当前分类，或者当前分类的子类，则添加is-active
				 */
				$parents = $this->get_tax_query_patents()[$taxonomy] ?? array();
				if ($tax_query['terms'] == $term->term_id or in_array($term->term_id, $parents)) {
					$class .= ' is-active';
					break;
				}
			}
			unset($tax_query);

			/**
			 *categories tabs生成的GET参数为：'_term_' . $taxonomy，如果直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
			 */
			$term_link = self::$doing_ajax ? '' : add_query_arg('_term_' . $taxonomy, $term->term_id, remove_query_arg($remove_query_args));
			$tabs .= '<li class="' . $class . '"><a data-key="_term_' . $taxonomy . '" data-value="' . $term->term_id . '" href="' . $term_link . '">' . $term->name . '</a></li>';

		}
		unset($term);

		// 输出结束
		$tabs .= '</ul>';
		$tabs .= '</div>';
		$tabs .= '</div>';

		return $tabs;
	}

	/**
	 *@since 2019.08.09
	 *构建分类关联标签的HTML
	 *
	 */
	protected function build_related_tags_filter($limit = 10) {

		// 标签taxonomy
		$taxonomy = $this->wp_query_args['post_type'] . '_tag';
		if (!taxonomy_exists($taxonomy)) {
			return;
		}

		/**
		 *查找在当前的tax_query查询参数中，当前taxonomy的键名，如果没有则加入
		 *tax_query是一个无键名的数组，无法根据键名合并，因此需要准确定位
		 *(数组默认键值从0开始， 当首元素即匹配则array_search返回 0，此处需要严格区分 0 和 false)
		 *@since 2019.03.07
		 */
		$all_class = 'class="is-active"';
		foreach ($this->wp_query_args['tax_query'] as $key => $tax_query) {
			// WP_Query tax_query参数可能存在：'relation' => 'AND', 'relation' => 'OR',参数，需排除 @since 2019.06.14
			if (!isset($tax_query['terms'])) {
				continue;
			}

			//遍历当前tax query 获取post type的主分类
			if (array_search($this->category_taxonomy, $tax_query) !== false) {
				$category_id = $tax_query['terms'];
				continue;
			}

			// 当前标签在tax query中的键名
			if (array_search($taxonomy, $tax_query) !== false) {
				$all_class = '';
				continue;
			}
		}
		unset($key, $tax_query);

		/**
		 *指定category_id时查询关联标签，否则调用热门标签
		 *@since 2019.03.25
		 */
		if (isset($category_id)) {
			$tags = Wnd_Tag_Under_Category::get_tags($category_id, $taxonomy, $limit);
		} else {
			$tags = get_terms($taxonomy, array(
				'hide_empty' => false,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => $limit,
			));
		}

		// 输出容器
		$tabs = '<div class="columns is-marginless is-vcentered related-tags taxonomy-tabs ' . $taxonomy . '-tabs">';
		$tabs .= '<div class="column is-narrow ' . $taxonomy . '-label">' . get_taxonomy($taxonomy)->label . '：</div>';
		$tabs .= '<div class="tabs column">';
		$tabs .= '<ul class="tab">';

		// 全部选项链接
		$all_link = self::$doing_ajax ? '' : remove_query_arg('_term_' . $taxonomy, remove_query_arg($this->remove_query_args));
		$tabs .= '<li ' . $all_class . '><a data-key="_term_' . $taxonomy . '" data-value="" href="' . $all_link . '">全部</a></li>';

		// 输出tabs
		foreach ($tags as $tag) {
			$term = isset($category_id) ? get_term($tag->tag_id) : $tag;

			// 遍历当前tax query查询是否匹配当前tab
			$class = 'term-id-' . $term->term_id;
			foreach ($this->wp_query_args['tax_query'] as $tax_query) {
				// WP_Query tax_query参数可能存在：'relation' => 'AND', 'relation' => 'OR',参数，需排除 @since 2019.06.14
				if (!isset($tax_query['terms'])) {
					continue;
				}

				if ($tax_query['terms'] == $term->term_id) {
					$class .= ' is-active';
				}
			}
			unset($tax_query);

			/**
			 *categories tabs生成的GET参数为：'_term_' . $taxonomy，如果直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
			 */
			$term_link = self::$doing_ajax ? '' : add_query_arg('_term_' . $taxonomy, $term->term_id, remove_query_arg($this->remove_query_args));
			$tabs .= '<li class="' . $class . '"><a data-key="_term_' . $taxonomy . '" data-value="' . $term->term_id . '" href="' . $term_link . '">' . $term->name . '</a></li>';

		}
		unset($tag);

		// 输出结束
		$tabs .= '</ul>';
		$tabs .= '</div>';
		$tabs .= '</div>';

		return $tabs;
	}

	/**
	 *@since 2019.04.18 meta query
	 *@param 自定义： array args meta字段筛选:
	 *		暂只支持单一 meta_key 暂仅支持 = 、exists 两种compare
	 *
	 *	$args = array(
	 *		'label' => '文章价格',
	 *		'key' => 'price',
	 *		'options' => array(
	 *			'10' => '10',
	 *			'0.1' => '0.1',
	 *		),
	 *		'compare' => '=',
	 *	);
	 *
	 *	查询一个字段是否存在：options只需要设置一个：其作用为key值显示为选项文章，value不参与查询，可设置为任意值
	 *	$args = array(
	 *		'label' => '文章价格',
	 *		'key' => 'price',
	 *		'options' => array(
	 *			'包含' => 'exists',
	 *		),
	 *		'compare' => 'exists',
	 *	);
	 *
	 */
	protected function build_meta_filter($args) {

		/**
		 *查找在当前的meta_query查询参数中，当前meta key的键名，如果设置则取消取消全部选项is-active
		 *(数组默认键值从0开始， 当首元素即匹配则array_search返回 0，此处需要严格区分 0 和 false)
		 *@since 2019.03.07（copy）
		 */
		$all_class = 'class="is-active"';
		foreach ($this->wp_query_args['meta_query'] as $key => $meta_query) {
			// 当前键名
			if (array_search($args['key'], $meta_query) !== false) {
				$all_class = '';
				break;
			}
		}
		unset($key, $meta_query);

		// 输出容器
		$tabs = '<div class="columns is-marginless is-vcentered meta-tabs">';
		$tabs .= '<div class="column is-narrow">' . $args['label'] . '：</div>';
		$tabs .= '<div class="tabs column">';
		$tabs .= '<ul class="tab">';

		/**
		 * 全部选项
		 * @since 2019.03.07（copy）
		 */
		$all_link = self::$doing_ajax ? '' : remove_query_arg('_meta_' . $args['key'], remove_query_arg($this->remove_query_args));
		$tabs .= '<li ' . $all_class . '><a data-key="_meta_' . $args['key'] . '" data-value="" href="' . $all_link . '">全部</a></li>';

		// 输出tabs
		foreach ($args['options'] as $key => $value) {

			// 遍历当前meta query查询是否匹配当前tab
			$class = '';
			if (isset($this->wp_query_args['meta_query'])) {
				foreach ($this->wp_query_args['meta_query'] as $meta_query) {
					if ($meta_query['compare'] != 'exists' and $meta_query['value'] == $value) {
						$class = 'class="is-active"';
						// meta query compare 为 exists时，没有value值，仅查询是否包含对应key值
					} elseif ($meta_query['key'] = $args['key']) {
						$class = 'class="is-active"';
					}
				}
				unset($meta_query);
			}

			/**
			 *meta_query GET参数为：_meta_{key}?=
			 */
			$meta_link = self::$doing_ajax ? '' : add_query_arg('_meta_' . $args['key'], $value, remove_query_arg($this->remove_query_args));
			$tabs .= '<li ' . $class . '><a data-key="_meta_' . $args['key'] . '" data-value="' . $value . '" href="' . $meta_link . '">' . $key . '</a></li>';
		}
		unset($key, $value);

		// 输出结束
		$tabs .= '</ul>';
		$tabs .= '</div>';
		$tabs .= '</div>';

		return $tabs;
	}

	/**
	 *@since 2019.04.21 排序
	 *@param 自定义： array args
	 *
	 *	$args = array(
	 *		'label' => '排序',
	 *		'options' => array(
	 *			'发布时间' => 'date', //常规排序 date title等
	 *			'浏览量' => array( // 需要多个参数的排序
	 *				'orderby'=>'meta_value_num',
	 *				'meta_key'   => 'views',
	 *			),
	 *		),
	 *	);
	 *
	 */
	protected function build_orderby_filter($args) {

		// 移除选项
		$remove_query_args = array_merge(array('orderby', 'order', 'meta_key'), $this->remove_query_args);

		// 全部
		$all_class = 'class="is-active"';
		if (isset($this->wp_query_args['orderby']) and $this->wp_query_args['orderby'] != 'post_date') {
			$all_class = '';
		}

		// 输出容器
		$tabs = '<div class="columns is-marginless is-vcentered orderby-tabs">';
		$tabs .= '<div class="column is-narrow">' . $args['label'] . '：</div>';
		$tabs .= '<div class="tabs column">';
		$tabs .= '<ul class="tab">';

		/**
		 * 全部选项
		 * @since 2019.03.07（copy）
		 */
		// $all_link = self::$doing_ajax ? '' : remove_query_arg($remove_query_args);
		// $tabs .= '<li ' . $all_class . '><a data-key="orderby" data-value="" href="' . $all_link . '">默认</a></li>';

		// 输出tabs
		foreach ($args['options'] as $key => $orderby) {

			// 查询当前orderby是否匹配当前tab
			$class = '';
			if (isset($this->wp_query_args['orderby'])) {
				/**
				 *	post meta排序
				 *	$args = array(
				 *		'post_type' => 'product',
				 *		'orderby'   => 'meta_value_num',
				 *		'meta_key'  => 'price',
				 *	);
				 *	$query = new WP_Query( $args );
				 */
				if (is_array($orderby) and ($this->wp_query_args['orderby'] == 'meta_value_num' or $this->wp_query_args['orderby'] == 'meta_value')) {
					if ($orderby['meta_key'] == $this->wp_query_args['meta_key']) {
						$class = 'class="is-active"';
					}
					// 常规排序
				} else {
					if ($orderby == $this->wp_query_args['orderby']) {
						$class = 'class="is-active"';
					}
				}

			}

			// data-key="orderby" data-value="' . http_build_query($query_arg) . '"
			$query_arg    = is_array($orderby) ? $orderby : array('orderby' => $orderby);
			$orderby_link = self::$doing_ajax ? '' : add_query_arg($query_arg, remove_query_arg($remove_query_args));
			$tabs .= '<li ' . $class . '><a data-key="orderby" data-value="' . http_build_query($query_arg) . '" href="' . $orderby_link . '">' . $key . '</a></li>';
		}
		unset($key, $orderby);

		// 输出结束
		$tabs .= '</ul>';
		$tabs .= '</div>';
		$tabs .= '</div>';

		return $tabs;
	}

	/**
	 *@since 2019.08.10 构建排序方式
	 *@param 自定义： array args
	 *
	 *	$args = array(
	 *		'降序' => 'DESC',
	 *		'升序' =>'ASC'
	 *	);
	 *
	 *@param string $label 选项名称
	 */
	protected function build_order_filter($args, $label) {

		// 输出容器
		$tabs = '<div class="columns is-marginless is-vcentered order-tabs">';
		$tabs .= '<div class="column is-narrow">' . $label . '：</div>';
		$tabs .= '<div class="tabs column">';
		$tabs .= '<ul class="tab">';

		// 是否已设置order参数
		$all_class = isset($this->wp_query_args['orderby']) ? '' : 'class="is-active"';
		$all_link  = self::$doing_ajax ? '' : remove_query_arg('order', remove_query_arg($this->remove_query_args));
		$tabs .= '<li ' . $all_class . '><a data-key="order" data-value="" href="' . $all_link . '">默认</a></li>';

		// 输出tabs
		foreach ($args as $key => $value) {

			// 遍历当前meta query查询是否匹配当前tab
			$class = '';
			if (isset($this->wp_query_args['order']) and $this->wp_query_args['order'] == $value) {
				$class = 'class="is-active"';
			}

			/**
			 *meta_query GET参数为：_meta_{key}?=
			 */
			$order_link = self::$doing_ajax ? '' : add_query_arg('order', $value, remove_query_arg($this->remove_query_args));
			$tabs .= '<li ' . $class . '><a data-key="order" data-value="' . $value . '" href="' . $order_link . '">' . $key . '</a></li>';
		}
		unset($key, $value);

		// 输出结束
		$tabs .= '</ul>';
		$tabs .= '</div>';
		$tabs .= '</div>';

		return $tabs;
	}

	/**
	 *@since 2019.03.26
	 *遍历当前查询参数，输出取消当前查询链接
	 */
	protected function build_current_filter() {
		if (empty($this->wp_query_args['tax_query']) and empty($this->wp_query_args['meta_query'])) {
			return;
		}

		// 输出容器
		$tabs = '<div class="columns is-marginless is-vcentered current-tabs">';
		$tabs .= '<div class="column is-narrow">当前条件：</div>';
		$tabs .= '<div class="column">';

		// 1、tax_query
		foreach ($this->wp_query_args['tax_query'] as $key => $tax_query) {
			// WP_Query tax_query参数可能存在：'relation' => 'AND', 'relation' => 'OR',参数，需排除 @since 2019.06.14
			if (!isset($tax_query['terms'])) {
				continue;
			}

			$term = get_term($tax_query['terms']);

			/**
			 *categories tabs生成的GET参数为：'_term_' . $taxonomy，如果直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
			 */
			$cancel_link = self::$doing_ajax ? '' : remove_query_arg('_term_' . $term->taxonomy, remove_query_arg($this->remove_query_args));
			$tabs .= '<span class="tag">' . $term->name . '<a data-key="_term_' . $term->taxonomy . '" data-value="" class="delete is-small" href="' . $cancel_link . '"></a></span>&nbsp;&nbsp;';
		}
		unset($key, $tax_query);

		/**
		 *@since 2019.04.18
		 *2、meta_query
		 */
		foreach ($this->wp_query_args['meta_query'] as $meta_query) {

			// 通过wp meta query中的value值，反向查询自定义 key
			if ($meta_query['compare'] != 'exists') {
				$key = array_search($meta_query['value'], $this->meta_filter_args['options']);
				if (!$key) {
					continue;
				}

				// meta query compare 为 exists时，没有value值
			} else {
				$key = $this->meta_filter_args['label'];
			}

			/**
			 *meta_query GET参数为：meta_{key}?=
			 */
			$cancel_link = self::$doing_ajax ? '' : remove_query_arg('_meta_' . $this->meta_filter_args['key'], remove_query_arg($this->remove_query_args));
			$tabs .= '<span class="tag">' . $key . '<a data-key="_meta_' . $this->meta_filter_args['key'] . '" data-value="" class="delete is-small" href="' . $cancel_link . '"></a></span>&nbsp;&nbsp;';
		}
		unset($key, $meta_query);

		// 输出结束
		$tabs .= '</div>';
		$tabs .= '</div>';

		return $tabs;
	}

	/**
	 *@since 2019.02.15 简单分页导航
	 *不查询总数的情况下，简单实现下一页翻页
	 *翻页参数键名page 不能设置为 paged 会与原生WordPress翻页机制产生冲突
	 */
	protected function build_pagination($show_page = 5) {

		/**
		 *$this->wp_query->query_vars :
		 *WP_Query实际执行的查询参数 new WP_query($args) $args 经过WP_Query解析后
		 *@see Class WP_Query
		 */
		$paged = $this->wp_query->query_vars['paged'] ?: 1;

		/**
		 *未查询文章总数，以上一页下一页的形式翻页(在数据较多的情况下，可以提升查询性能)
		 *在ajax环境中，动态分页较为复杂，暂统一设定为上下页的形式，前端处理更容易
		 */
		if (!$this->wp_query->max_num_pages) {
			$previous_link = self::$doing_ajax ? '' : add_query_arg('page', $paged - 1);
			$next_link     = self::$doing_ajax ? '' : add_query_arg('page', $paged + 1);

			$html = '<nav id="nav-' . $this->uniqid . '" class="pagination is-centered ' . $this->class . '" ' . $this->build_html_data() . '>';
			$html .= '<ul class="pagination-list">';
			if ($paged >= 2) {
				$html .= '<li><a data-key="page" data-value="' . ($paged - 1) . '" class="pagination-previous" href="' . $previous_link . '">上一页</a>';
			}
			if ($this->wp_query->post_count >= $this->wp_query->query_vars['posts_per_page']) {
				$html .= '<li><a data-key="page" data-value="' . ($paged + 1) . '" class="pagination-next" href="' . $next_link . '">下一页</a>';
			}
			$html .= '</ul>';
			$html .= '</nav>';

			return $html;

		} else {
			/**
			 *常规分页，需要查询文章总数
			 *据称，在数据量较大的站点，查询文章总数会较为费时
			 */
			$first_link    = self::$doing_ajax ? '' : remove_query_arg('page');
			$previous_link = self::$doing_ajax ? '' : add_query_arg('page', $paged - 1);
			$next_link     = self::$doing_ajax ? '' : add_query_arg('page', $paged + 1);
			$last_link     = self::$doing_ajax ? '' : add_query_arg('page', $this->wp_query->max_num_pages);

			$html = '<div id="nav-' . $this->uniqid . '" class="pagination is-centered ' . $this->class . '" ' . $this->build_html_data() . '>';
			if ($paged > 1) {
				$html .= '<a data-key="page" data-value="' . ($paged - 1) . '" class="pagination-previous" href="' . $previous_link . '">上一页</a>';
			} else {
				$html .= '<a class="pagination-previous" disabled="disabled">第一页</a>';
			}

			if ($paged < $this->wp_query->max_num_pages) {
				$html .= '<a data-key="page" data-value="' . ($paged + 1) . '" class="pagination-next" href="' . $next_link . '">下一页</a>';
			}

			$html .= '<ul class="pagination-list">';
			$html .= '<li><a data-key="page" data-value="" class="pagination-link" href="' . $first_link . '" >首页</a></li>';
			for ($i = $paged - 1; $i <= $paged + $show_page; $i++) {
				if ($i > 0 and $i <= $this->wp_query->max_num_pages) {
					$page_link = self::$doing_ajax ? '' : add_query_arg('page', $i);
					if ($i == $paged) {
						$html .= '<li><a data-key="page" data-value="' . $i . '" class="pagination-link is-current" href="' . $page_link . '"> <span>' . $i . '</span> </a></li>';
					} else {
						$html .= '<li><a data-key="page" data-value="' . $i . '" class="pagination-link" href="' . $page_link . '"> <span>' . $i . '</span> </a></li>';
					}
				}
			}
			if ($paged < $this->wp_query->max_num_pages - 3) {
				$html .= '<li><span class="pagination-ellipsis">&hellip;</span></li>';
			}

			$html .= '<li><a data-key="page" data-value="' . $this->wp_query->max_num_pages . '" class="pagination-link" href="' . $last_link . '">尾页</a></li>';
			$html .= '</ul>';

			$html .= '</div>';

			$this->pagination = $html;
			return $this->pagination;
		}
	}

	/**
	 *@since 2019.08.01
	 *执行查询
	 */
	public function query() {
		$this->wp_query = new WP_Query($this->wp_query_args);
	}

	/**
	 *@since 2019.08.09
	 *获取当前tax_query的所有父级term_id
	 *@return array $parents 当前分类查询的所有父级：$parents[$taxonomy] = array($term_id_1, $term_id_2);
	 */
	public function get_tax_query_patents() {
		$parents = array();

		// 遍历当前tax query是否包含子类
		foreach ($this->wp_query_args['tax_query'] as $tax_query) {
			// WP_Query tax_query参数可能存在：'relation' => 'AND', 'relation' => 'OR',参数，需排除 @since 2019.06.14
			if (!isset($tax_query['terms'])) {
				continue;
			}

			// 递归查询当前分类的父级分类
			$parents[$tax_query['taxonomy']] = array();
			$parent                          = get_term($tax_query['terms'])->parent ?? 0;
			while ($parent) {
				$parents[$tax_query['taxonomy']][] = $parent;
				$parent                            = get_term($parent)->parent;
			}

			// 排序
			sort($parents[$tax_query['taxonomy']]);
		}
		unset($tax_query);

		return $parents;
	}

	/**
	 *@since 2019.07.31
	 *获取筛选项HTML
	 *
	 *tabs筛选项由于参数繁杂，无法通过api动态生成，因此不包含在api请求响应中
	 *但已生成的相关筛选项会根据wp_query->query_var参数做动态修改
	 *
	 *@see wnd_filter_api_callback()
	 */
	public function get_tabs() {
		$tabs = apply_filters('wnd_filter_tabs', $this->tabs, $this->wp_query_args);
		return '<div id="tabs-' . $this->uniqid . '" class="wnd-filter-tabs ' . $this->class . '" ' . $this->build_html_data() . '>' . $tabs . '</div>';
	}

	/**
	 *@since 2019.08.09
	 *获取分类Tabs的HTML
	 *
	 *分类Tabs需要根据当前post type情况动态加载
	 *在ajax状态中，需要经由此方法，交付api响应动态生成
	 *
	 *非ajax请求中，直接使用 add_category_filter方法即可
	 *
	 *@see wnd_filter_api_callback()
	 */
	public function get_category_tabs($args = array()) {
		$args['taxonomy'] = $this->category_taxonomy;
		$args['parent']   = $args['parent'] ?? 0;
		return $this->build_taxonomy_filter($args);
	}

	/**
	 *@since 2019.08.09
	 *获取分类关联标签的HTML
	 *
	 *分类关联标签需要根据当前主分类筛选情况动态加载
	 *在ajax状态中，需要经由此方法，交付api响应动态生成
	 *
	 *非ajax请求中，直接使用 add_related_tags_filter方法即可
	 *
	 *@see wnd_filter_api_callback()
	 */
	public function get_related_tags_tabs($limit = 10) {
		return $this->build_related_tags_filter($limit);
	}

	/**
	 *@since 2019.08.09
	 *当前tax query的子类筛选HTML
	 *
	 *子类查询需要根据当前tax query动态生成
	 *在ajax状态中，需要经由此方法，交付api响应动态生成
	 *
	 *非ajax请求中，直接使用echo get_sub_taxonomy_filter可单独查询某个分类子类
	 *非ajax请求中，add_taxonomy_filter，在选择分类后，自动查询生成子类tabs
	 *
	 *@see wnd_filter_api_callback()
	 *@return array  $sub_tabs_array (html) tabs;
	 *$sub_tabs_array[$taxonomy] = (html) tabs;
	 */
	public function get_sub_taxonomy_tabs() {
		$sub_tabs_array = array();

		// 遍历当前tax query是否包含子类
		foreach ($this->wp_query_args['tax_query'] as $tax_query) {
			// WP_Query tax_query参数可能存在：'relation' => 'AND', 'relation' => 'OR',参数，需排除 @since 2019.06.14
			if (!isset($tax_query['terms'])) {
				continue;
			}

			// 查询当前分类的所有上级分类的子分类
			$sub_tabs = '';
			$parents  = $this->get_tax_query_patents()[$tax_query['taxonomy']];
			foreach ($parents as $parent) {
				$args = array(
					'taxonomy' => $tax_query['taxonomy'],
					'parent'   => $parent,
				);
				$sub_tabs .= $this->build_taxonomy_filter($args, 'sub-tabs');
			}
			unset($parent);

			// 当前分类的子类
			$args = array(
				'taxonomy' => $tax_query['taxonomy'],
				'parent'   => $tax_query['terms'],
			);
			$sub_tabs .= $this->build_taxonomy_filter($args, 'sub-tabs');

			// 构造子类查询
			$sub_tabs_array[$tax_query['taxonomy']] = $sub_tabs;
		}
		unset($tax_query);

		return $sub_tabs_array;
	}

	/**
	 *@since 2019.07.31
	 *获取筛结果HTML
	 */
	public function get_posts() {
		if (!$this->wp_query) {
			return '未执行WP_Query';
		}

		// Posts list
		if ($this->wp_query_args['wnd_posts_tpl']) {
			$template = $this->wp_query_args['wnd_posts_tpl'];
			if (!$template) {
				return '未定义输出模板';
			}
			$this->posts = $template($this->wp_query);

			// post list
		} else {
			$template = $this->wp_query_args['wnd_post_tpl'];
			if (!$template) {
				return '未定义输出模板';
			}
			if ($this->wp_query->have_posts()) {
				while ($this->wp_query->have_posts()): $this->wp_query->the_post();
					global $post;
					$this->posts .= $template($post);
				endwhile;
				wp_reset_postdata(); //重置查询
			}
		}

		return $this->posts;
	}

	/**
	 *@since 2019.02.15
	 *分页导航
	 */
	public function get_pagination($show_page = 5) {
		if (!$this->wp_query and !self::$is_ajax) {
			return '未执行WP_Query';
		}

		$this->pagination = $this->build_pagination($show_page);
		return $this->pagination;
	}

	/**
	 *@since 2019.07.31
	 *合并返回：文章列表及分页导航
	 */
	public function get_results() {
		return $this->get_posts() . $this->get_pagination();
	}
}
