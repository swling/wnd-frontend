<?php
namespace Wnd\View;

use Exception;
use Wnd\Model\Wnd_Tag_Under_Category;
use Wnd\View\Wnd_Pagination;
use WP_Query;

/**
 * @since 2019.07.30
 * 多重筛选类
 * 样式基于bulma css
 *
 * @param bool 		$is_ajax 		是否为ajax筛选（需要对应的前端支持）
 * @param bool 		$independent 	是否为独立 WP Query
 * @param string 	$uniqid 		HTML容器识别ID。默认值 uniqid() @see build_pagination() / get_tabs()
 */
class Wnd_Filter {

	// bool 是否ajax
	protected static $is_ajax;

	// bool 是否正处于ajax环境中
	protected static $doing_ajax;

	// 主色调
	protected static $primary_color;

	/**
	 *@since 2019.10.26
	 *URL请求参数
	 */
	protected static $http_query;

	// string 当前筛选器唯一标识
	protected $uniqid;

	// string html class
	protected $class;

	/**
	 * 现有方法之外，其他新增的查询参数
	 * 将在筛选容器，及分页容器上出现，以绑定点击事件，发送到api接口
	 * 以data-{key}="{value}"形式出现，ajax请求中，将转化为 url请求参数 ?{key}={value}
	 */
	protected $add_query = [];

	// meta 查询参数需要供current filter查询使用
	protected $meta_filter_args;

	// 当前请求基本 URL
	protected $base_url;

	/**
	 *根据配置设定的wp_query查询参数
	 *默认值将随用户设定而改变
	 *参数中包含自定义的非wp_query参数以"wnd"前缀区分
	 */
	protected $wp_query_args = [
		'orderby'            => 'date',
		'order'              => 'DESC',
		'meta_query'         => [],
		'tax_query'          => [],
		'date_query'         => [],
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
	];

	/**
	 *WP_Query 查询结果：
	 *@see $this->query();
	 */
	public $wp_query;

	/**
	 *@since 0.8.64
	 *是否为独立的、不依赖当前页面的 WP_Query
	 */
	public $independent;

	// 当前post type的主分类taxonomy 约定：post(category) / 自定义类型 （$post_type . '_cat'）
	public $category_taxonomy;

	// 筛选项HTML
	protected $tabs = '';

	// 筛选结果HTML
	protected $posts = '';

	// 分页导航HTML
	protected $pagination = '';

	/**
	 *Constructor.
	 *
	 *@param bool 		$is_ajax  		是否为ajax查询
	 *@param bool 		$independent 	是否为独立 WP Query
	 *@param string 	$uniqid   		当前筛选器唯一标识
	 */
	public function __construct(bool $is_ajax = false, bool $independent = true, string $uniqid = '') {
		static::$is_ajax       = $is_ajax;
		static::$doing_ajax    = wnd_doing_ajax();
		static::$http_query    = static::parse_query_vars();
		static::$primary_color = wnd_get_config('primary_color');
		$this->class           = static::$is_ajax ? 'ajax-filter' : 'filter';
		$this->independent     = $independent;
		$this->base_url        = get_pagenum_link(1, false);

		/**
		 *@since 0.8.64
		 *
		 *- 独立型 WP Query：分页需要自定义处理
		 *- 依赖型 WP Query：获取全局 $wp_query，并读取全局查询参数赋值到当前筛选环境，以供构建与之匹配的 tabs
		 */
		if ($this->independent) {
			$this->base_url = remove_query_arg('page', $this->base_url);
		} else {
			global $wp_query;
			if (!$wp_query->query_vars) {
				throw new Exception(__('当前环境需执行独立 WP Query', 'wnd'));
			}

			$this->wp_query      = $wp_query;
			$this->wp_query_args = array_merge($this->wp_query_args, $wp_query->query_vars);
		}

		// 解析GET参数为wp_query参数并与默认参数合并，以防止出现参数未定义的警告信息
		$this->wp_query_args = array_merge($this->wp_query_args, static::$http_query);

		// 初始化时生成uniqid，并加入query参数，以便在ajax生成的新请求中保持一致
		$this->uniqid = $uniqid ?: ($this->wp_query_args['wnd_uniqid'] ?: uniqid());
		$this->add_query(['wnd_uniqid' => $this->uniqid]);

		/**
		 *定义当前post type的主分类：$category_taxonomy
		 */
		if ($this->wp_query_args['post_type']) {
			$this->category_taxonomy = ('post' == $this->wp_query_args['post_type']) ? 'category' : $this->wp_query_args['post_type'] . '_cat';
		}

		// 非管理员，仅可查询publish及close状态(作者本身除外)
		if (is_super_admin()) {
			return;
		}

		// 数组查询，如果包含publish及closed之外的状态，指定作者为当前用户
		if (is_array($this->wp_query_args['post_status'])) {
			foreach ($this->wp_query_args['post_status'] as $key => $post_status) {
				if (!in_array($post_status, ['publish', 'wnd-closed'])) {
					if (!is_user_logged_in()) {
						throw new Exception(__('未登录用户，仅可查询公开信息', 'wnd'));
					} else {
						$this->wp_query_args['author'] = get_current_user_id();
					}
					break;
				}
			}unset($key, $post_status);

			// 单个查询
		} elseif (!in_array($this->wp_query_args['post_status'] ?: 'publish', ['publish', 'wnd-closed'])) {
			if (!is_user_logged_in()) {
				throw new Exception(__('未登录用户，仅可查询公开信息', 'wnd'));
			} else {
				$this->wp_query_args['author'] = get_current_user_id();
			}
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
			return [];
		}

		$query_vars = [
			'meta_query' => [],
			'tax_query'  => [],
			'date_query' => [],
		];

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
			 *@since 2020.05.11
			 *
			 *添加搜索框支持
			 *直接使用s作为GET参数，会与WordPress原生请求冲突
			 */
			if ('search' === $key) {
				$query_vars['s'] = $value;
				continue;
			}

			/**
			 *@since 2019.3.07 自动匹配meta query
			 *?_meta_price=1 则查询 price = 1的文章
			 *?_meta_price=exists 则查询 存在price的文章
			 */
			if (0 === strpos($key, '_meta_')) {
				$key        = str_replace('_meta_', '', $key);
				$compare    = 'exists' == $value ? 'exists' : '=';
				$meta_query = [
					'key'     => $key,
					'value'   => $value,
					'compare' => $compare,
				];

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
			if (0 === strpos($key, '_term_')) {
				$term_query = [
					'taxonomy' => str_replace('_term_', '', $key),
					'field'    => 'term_id',
					'terms'    => $value,
				];
				$query_vars['tax_query'][] = $term_query;
				continue;
			}

			/**
			 *@since 2019.05.31 post field查询
			 */
			if (0 === strpos($key, '_post_')) {
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
			if ('orderby' == $key and static::$is_ajax) {
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
			if (in_array($key, ['tax_query', 'meta_query', 'date_query']) and static::$is_ajax) {
				$query_vars[$key] = wp_parse_args($value, $query_vars[$key]);
				continue;
			}

			// 其他：按键名自动匹配
			if (is_array($value)) {
				$query_vars[$key] = $value;
			} else {
				$query_vars[$key] = (false !== strpos($value, '=')) ? wp_parse_args($value) : $value;
			}
			continue;
		}
		unset($key, $value);

		/**
		 *定义如何过滤HTTP请求
		 *此处定义：过滤空值，但保留0
		 *@since 2019.10.26
		 **/
		$query_vars = array_filter($query_vars, function ($value) {
			return $value or 0 == $value;
		});

		return $query_vars;
	}

	/**
	 *@since 2019.07.31
	 *设置ajax post列表嵌入容器
	 *@param string $container posts列表ajax嵌入容器
	 **/
	public function set_ajax_container($container) {
		$this->add_query(['wnd_ajax_container' => $container]);
	}

	/**
	 *@since 2019.07.31
	 *设置ajax post列表嵌入容器
	 *@param int $posts_per_page 每页post数目
	 **/
	public function set_posts_per_page($posts_per_page) {
		$this->add_query(['posts_per_page' => $posts_per_page]);
	}

	/**
	 *@since 2019.08.02
	 *设置列表post模板函数，传递$post对象
	 *@param string $template post模板函数名
	 **/
	public function set_post_template($template) {
		$this->add_query(['wnd_post_tpl' => $template]);
	}

	/**
	 *@since 2019.08.16
	 *文章列表页整体模板函数，传递wp_query查询结果
	 *设置模板后，$this->get_posts() 即为被该函数返回值
	 *@param string $template posts模板函数名
	 **/
	public function set_posts_template($template) {
		$this->add_query(['wnd_posts_tpl' => $template]);
	}

	/**
	 *@since 2019.07.31
	 *添加新的请求参数
	 *添加的参数，将覆盖之前的设定，并将在所有请求中有效，直到被新的设定覆盖
	 *
	 *@param array $query [key=>value]
	 *
	 *在非ajax环境中，直接将写入$wp_query_args[key]=value
	 *
	 *在ajax环境中，将对应生成html data属性：data-{key}="{value}" 通过JavaScript获取后将转化为 ajax url请求参数 ?{key}={value}，
	 *ajax发送到api接口，再通过parse_query_vars() 解析后，写入$wp_query_args[key]=value
	 *
	 *@since 0.8.64
	 *仅在独立 WP Query （true == $this->independent）时，可在外部直接调用
	 */
	public function add_query($query = []) {
		foreach ($query as $key => $value) {
			// 数组参数，合并元素；非数组参数，赋值 （php array_merge：相同键名覆盖，未定义键名或以整数做键名，则新增)
			if (is_array($this->wp_query_args[$key] ?? false) and is_array($value)) {
				$this->wp_query_args[$key] = array_merge($this->wp_query_args[$key], $value, static::$http_query[$key] ?? []);

			} else {
				// $_GET参数优先，无法重新设置
				$this->wp_query_args[$key] = (static::$http_query[$key] ?? false) ?: $value;
			}

			// 在html data属性中新增对应属性，以实现在ajax请求中同步添加参数
			$this->add_query[$key] = $value;
		}
		unset($key, $value);
	}

	/**
	 *@since 2020.05.11
	 *搜索框
	 */
	public function add_search_form($button = 'Search', $placeholder = '') {
		if (static::$is_ajax) {
			$html = '<form class="wnd-filter-search" method="POST" action="" "onsubmit"="return false">';
		} else {
			$html = '<form class="wnd-filter-search" method="GET" action="">';
		}
		$html .= '<div class="field has-addons">';

		$html .= '<div class="control is-expanded">';
		$html .= '<input class="input" type="text" name="search" placeholder="' . $placeholder . '" required="required">';
		$html .= '</div>';
		$html .= '<div class="control">';
		$html .= '<button type="submit" class="button is-' . static::$primary_color . '">' . $button . '</button>';
		$html .= '</div>';

		$html .= '</div>';
		// 作用：在非ajax状态中，支持在指定post_type下搜索
		$html .= '<input type="hidden" name="type" value="' . ($_GET['type'] ?? '') . '">';
		$html .= '</form>';

		$this->tabs .= $html;
	}

	/**
	 *@param array 	$args 需要筛选的类型数组
	 *@param bool 	$with_any_tab 是否包含全部选项
	 */
	public function add_post_type_filter($args = [], $with_any_tab = false) {
		/**
		 *若当前请求未指定post_type，设置第一个post_type为默认值；若筛选项也为空，最后默认post
		 *post_type/post_status 在所有筛选中均需要指定默认值，若不指定，WordPress也会默认设定
		 *
		 * 当前请求为包含post_type参数时，当前的主分类（category_taxonomy）无法在构造函数中无法完成定义，需在此处补充
		 */
		if (!$this->wp_query_args['post_type']) {
			$default_type = $with_any_tab ? 'any' : ($args ? reset($args) : 'post');
			$this->add_query(['post_type' => $default_type]);
			$this->category_taxonomy = ('post' == $this->wp_query_args['post_type']) ? 'category' : $this->wp_query_args['post_type'] . '_cat';
		}

		/**
		 *仅筛选项大于2时，构建HTML
		 */
		if (count($args) < 2) {
			return;
		}
		$tabs = $this->build_post_type_filter($args, $with_any_tab);
		$this->tabs .= $tabs;
		return $tabs;
	}

	/**
	 *状态筛选
	 *@param array $args 需要筛选的文章状态数组
	 */
	public function add_post_status_filter($args = []) {
		$this->add_query(['post_status' => $args]);

		/**
		 *仅筛选项大于2时，构建HTML
		 */
		if (count($args) < 2) {
			return;
		}
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
		$taxonomy       = $args['taxonomy'] ?? '';
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
	 *@param 自定义： array args meta字段筛选。暂只支持单一 meta_key 暂仅支持 = 、exists 两种compare
	 *
	 *	$args = [
	 *		'label' => '文章价格',
	 *		'key' => 'price',
	 *		'options' => [
	 *			'10' => '10',
	 *			'0.1' => '0.1',
	 *		],
	 *		'compare' => '=',
	 *	];
	 *
	 *	查询一个字段是否存在：options只需要设置一个：其作用为key值显示为选项文章，value不参与查询，可设置为任意值
	 *	$args = [
	 *		'label' => '文章价格',
	 *		'key' => 'price',
	 *		'options' => [
	 *			'包含' => 'exists',
	 *		],
	 *		'compare' => 'exists',
	 *	];
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
	 *	$args = [
	 *		'label' => '排序',
	 *		'options' => [
	 *			'发布时间' => 'date', //常规排序 date title等
	 *			'浏览量' => [ // 需要多个参数的排序
	 *				'orderby'=>'meta_value_num',
	 *				'meta_key'   => 'views',
	 *			],
	 *		],
	 *		'order' => 'DESC',
	 *	];
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
	 *	$args = [
	 *		'降序' => 'DESC',
	 *		'升序' =>'ASC'
	 *	];
	 *
	 *@param string $label 选项名称
	 */
	public function add_order_filter($args, $label) {
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
	 *@since 2019.08.02
	 *构造HTML data属性
	 *获取新增查询，并转化为html data属性，供前端读取后在ajax请求中发送到api
	 */
	protected function build_data_attr(): string {
		if (!$this->add_query) {
			return '';
		}

		$data = ' ';
		foreach ($this->add_query as $key => $value) {
			$value = is_array($value) ? http_build_query($value) : $value;
			$data .= 'data-' . $key . '="' . $value . '" ';
		}

		return $data;
	}

	/**
	 *类型筛选
	 *@param array $args 需要筛选的类型数组 $args = ['post','page']
	 *@param bool 	$with_any_tab 是否包含全部选项
	 */
	protected function build_post_type_filter($args = [], $with_any_tab = false) {
		/**
		 *@since 2019.08.06
		 *post type切换时，表示完全新的筛选，故此移除所有GET参数
		 *
		 *@since 2020.04.10
		 *新增多语言支持，切换类型时需要保留语言参数
		 */
		$uri = strtok($_SERVER['REQUEST_URI'], '?');
		$uri = isset($_GET['lang']) ? add_query_arg('lang', $_GET['lang']) : $uri;

		// 输出容器
		$tabs = '<div class="columns is-marginless is-vcentered post-type-tabs">';
		$tabs .= '<div class="column is-narrow">' . __('类型：', 'wnd') . '</div>';
		$tabs .= '<div class="tabs column">';
		$tabs .= '<ul class="tab">';
		if ($with_any_tab) {
			$class = ('any' == $this->wp_query_args['post_type']) ? ' is-active' : '';
			$tabs .= '<li class="' . $class . '">';
			$tabs .= '<a data-key="type" data-value="any" href="' . add_query_arg('type', 'any', $uri) . '">' . __('全部', 'wnd') . '</a>';
			$tabs .= '</li>';
		}

		// 输出tabs
		foreach ($args as $post_type) {
			// 根据类型名，获取完整的类型信息
			$post_type = get_post_type_object($post_type);

			$class = 'post-type-' . $post_type->name;
			$class .= ($this->wp_query_args['post_type'] == $post_type->name) ? ' is-active' : '';
			$post_type_link = static::$doing_ajax ? '' : add_query_arg('type', $post_type->name, $uri);

			$tabs .= '<li class="' . $class . '">';
			$tabs .= '<a data-key="type" data-value="' . $post_type->name . '" href="' . $post_type_link . '">' . $post_type->label . '</a>';
			$tabs .= '</li>';
		}
		unset($post_type);

		// 输出结束
		$tabs .= '</ul>';
		$tabs .= '</div>';
		$tabs .= '</div>';
		return $tabs;
	}

	/**
	 *状态筛选
	 *@param array $args 需要筛选的文章状态数组
	 *
	 *	$args = [
	 *		'公开'=>publish',
	 *		'草稿'=>draft'
	 *	]
	 */
	protected function build_post_status_filter($args = []) {
		// 输出容器
		$tabs = '<div class="columns is-marginless is-vcentered post-status-tabs">';
		$tabs .= '<div class="column is-narrow">' . __('状态：', 'wnd') . '</div>';
		$tabs .= '<div class="tabs column">';
		$tabs .= '<ul class="tab">';

		// 输出tabs
		$class = (!$this->wp_query_args['post_status'] or is_array($this->wp_query_args['post_status'])) ? ' is-active' : '';
		$tabs .= '<li class="' . $class . '">';
		$tabs .= '<a data-key="status" data-value="" href="' . remove_query_arg('status') . '">' . __('默认', 'wnd') . '</a>';
		$tabs .= '</li>';

		foreach ($args as $label => $post_status) {
			$class = 'post-status-' . $post_status;
			$class .= (isset($this->wp_query_args['post_status']) and $this->wp_query_args['post_status'] == $post_status) ? ' is-active' : '';
			$status_link = static::$doing_ajax ? '' : add_query_arg('status', $post_status, $this->base_url);

			$tabs .= '<li class="' . $class . '">';
			$tabs .= '<a data-key="status" data-value="' . $post_status . '" href="' . $status_link . '">' . $label . '</a>';
			$tabs .= '</li>';
		}
		unset($label, $post_status);

		// 输出结束
		$tabs .= '</ul>';
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
		$class    = $class ? ' ' . $class : '';

		/**
		 *@since 2019.07.30
		 *如果当前指定的taxonomy并不存在指定的post type中，非ajax环境直接中止，ajax环境中隐藏输出（根据post_type动态切换是否显示）
		 */
		$current_post_type_taxonomies = get_object_taxonomies($this->wp_query_args['post_type'], $output = 'names');
		if (!in_array($taxonomy, $current_post_type_taxonomies)) {
			if (!static::$is_ajax) {
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
			$remove_query_args = ['_term_' . $this->wp_query_args['post_type'] . '_tag'];
		} else {
			$remove_query_args = [];
		}

		$tabs = '<div class="columns is-marginless is-vcentered taxonomy-tabs ' . $taxonomy . '-tabs' . $class . '">';
		$tabs .= '<div class="column is-narrow ' . $taxonomy . '-label">' . get_taxonomy($taxonomy)->label . '：</div>';
		$tabs .= '<div class="tabs column">';
		$tabs .= '<ul class="tab">';

		/**
		 * 全部选项
		 * @since 2019.03.07
		 */
		if (!$parent) {
			$all_link = static::$doing_ajax ? '' : remove_query_arg('_term_' . $taxonomy, remove_query_arg($remove_query_args, $this->base_url));
			$tabs .= '<li ' . $all_class . '><a data-key="_term_' . $taxonomy . '" data-value="" href="' . $all_link . '">' . __('全部', 'wnd') . '</a></li>';
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
				$parents = $this->get_tax_query_patents()[$taxonomy] ?? [];
				if ($tax_query['terms'] == $term->term_id or in_array($term->term_id, $parents)) {
					$class .= ' is-active';
					break;
				}
			}
			unset($tax_query);

			/**
			 *categories tabs生成的GET参数为：'_term_' . $taxonomy，如果直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
			 */
			$term_link = static::$doing_ajax ? '' : add_query_arg('_term_' . $taxonomy, $term->term_id, remove_query_arg($remove_query_args, $this->base_url));
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
	 */
	protected function build_related_tags_filter($limit = 10) {
		// 标签taxonomy
		$taxonomy = $this->wp_query_args['post_type'] . '_tag';
		if (!taxonomy_exists($taxonomy)) {
			return;
		}

		/**
		 *@since 0.8.70
		 *在依赖型多重筛选中，分类及标签归档页默认不再包含 tax_query 查询参数
		 *因此，首先判断当前查询是否为分类归档页查询：
		 * - Post 分类归档页查询参数包含 	'category_name' => $slug
		 * - 自定义分类归档页查询参数包含 	{$taxonomy}		=> $slug
		 *
		 *@since 2019.03.07
		 *查找在当前的tax_query查询参数中，当前taxonomy的键名，如果没有则加入
		 *tax_query是一个无键名的数组，无法根据键名合并，因此需要准确定位
		 *(数组默认键值从0开始， 当首元素即匹配则array_search返回 0，此处需要严格区分 0 和 false)
		 */
		$all_class    = 'class="is-active"';
		$category_key = ('category' == $this->category_taxonomy) ? 'category_name' : $this->category_taxonomy;
		if (isset($this->wp_query_args[$category_key])) {
			$category    = get_term_by('slug', $this->wp_query_args[$category_key], $this->category_taxonomy);
			$category_id = $category ? $category->term_id : 0;

			// 当前标签在 tax query 中的键名 若存在则移除 “全部”选项
			foreach ($this->wp_query_args['tax_query'] as $key => $tax_query) {
				if (array_search($taxonomy, $tax_query) !== false) {
					$all_class = '';
					break;
				}
			}
			unset($key, $tax_query);
		} else {
			foreach ($this->wp_query_args['tax_query'] as $key => $tax_query) {
				// WP_Query tax_query参数可能存在：'relation' => 'AND', 'relation' => 'OR',参数，需排除 @since 2019.06.14
				if (!isset($tax_query['terms'])) {
					continue;
				}

				//遍历当前tax query 获取post type的主分类
				if (array_search($this->category_taxonomy, $tax_query) !== false) {
					$term        = is_array($tax_query['terms']) ? reset($tax_query['terms']) : $tax_query['terms'];
					$category    = get_term_by($tax_query['field'], $term, $this->category_taxonomy);
					$category_id = $category ? $category->term_id : 0;
					continue;
				}

				// 当前标签在 tax query 中的键名 若存在则移除 “全部”选项
				if (array_search($taxonomy, $tax_query) !== false) {
					$all_class = '';
					continue;
				}
			}
			unset($key, $tax_query);
		}

		/**
		 *指定category_id时查询关联标签，否则调用热门标签
		 *@since 2019.03.25
		 */
		if (isset($category_id)) {
			$tags = Wnd_Tag_Under_Category::get_tags($category_id, $taxonomy, $limit);
		} else {
			$tags = get_terms($taxonomy, [
				'hide_empty' => false,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => $limit,
			]);
		}

		// 输出容器
		$tabs = '<div class="columns is-marginless is-vcentered related-tags taxonomy-tabs ' . $taxonomy . '-tabs">';
		$tabs .= '<div class="column is-narrow ' . $taxonomy . '-label">' . get_taxonomy($taxonomy)->label . '：</div>';
		$tabs .= '<div class="tabs column">';
		$tabs .= '<ul class="tab">';

		// 全部选项链接
		$all_link = static::$doing_ajax ? '' : remove_query_arg('_term_' . $taxonomy, $this->base_url);
		$tabs .= '<li ' . $all_class . '><a data-key="_term_' . $taxonomy . '" data-value="" href="' . $all_link . '">' . __('全部', 'wnd') . '</a></li>';

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
			$term_link = static::$doing_ajax ? '' : add_query_arg('_term_' . $taxonomy, $term->term_id, $this->base_url);
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
	 *@param 自定义： array args meta字段筛选。暂只支持单一 meta_key 暂仅支持 = 、exists 两种compare
	 *
	 *	$args = [
	 *		'label' => '文章价格',
	 *		'key' => 'price',
	 *		'options' => [
	 *			'10' => '10',
	 *			'0.1' => '0.1',
	 *		],
	 *		'compare' => '=',
	 *	];
	 *
	 *	查询一个字段是否存在：options只需要设置一个：其作用为key值显示为选项文章，value不参与查询，可设置为任意值
	 *	$args = [
	 *		'label' => '文章价格',
	 *		'key' => 'price',
	 *		'options' => [
	 *			'包含' => 'exists',
	 *		],
	 *		'compare' => 'exists',
	 *	];
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
		$all_link = static::$doing_ajax ? '' : remove_query_arg('_meta_' . $args['key'], $this->base_url);
		$tabs .= '<li ' . $all_class . '><a data-key="_meta_' . $args['key'] . '" data-value="" href="' . $all_link . '">' . __('全部', 'wnd') . '</a></li>';

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
			$meta_link = static::$doing_ajax ? '' : add_query_arg('_meta_' . $args['key'], $value, $this->base_url);
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
	 *	$args = [
	 *		'label' => '排序',
	 *		'options' => [
	 *			'发布时间' => 'date', //常规排序 date title等
	 *			'浏览量' => [ // 需要多个参数的排序
	 *				'orderby'=>'meta_value_num',
	 *				'meta_key'   => 'views',
	 *			],
	 *		],
	 *	];
	 *
	 */
	protected function build_orderby_filter($args) {
		// 移除选项
		$remove_query_args = ['orderby', 'order', 'meta_key'];

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
		// $all_link = static::$doing_ajax ? '' : remove_query_arg($remove_query_args, $this->base_url);
		// $tabs .= '<li ' . $all_class . '><a data-key="orderby" data-value="" href="' . $all_link . '">默认</a></li>';

		// 输出tabs
		foreach ($args['options'] as $key => $orderby) {

			// 查询当前orderby是否匹配当前tab
			$class = '';
			if (isset($this->wp_query_args['orderby'])) {
				/**
				 *	post meta排序
				 *	$args = [
				 *		'post_type' => 'product',
				 *		'orderby'   => 'meta_value_num',
				 *		'meta_key'  => 'price',
				 *	];
				 *	$query = new WP_Query( $args );
				 */
				if (is_array($orderby) and ('meta_value_num' == $this->wp_query_args['orderby'] or 'meta_value' == $this->wp_query_args['orderby'])) {
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
			$query_arg    = is_array($orderby) ? $orderby : ['orderby' => $orderby];
			$orderby_link = static::$doing_ajax ? '' : add_query_arg($query_arg, remove_query_arg($remove_query_args, $this->base_url));
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
	 *	$args = [
	 *		'降序' => 'DESC',
	 *		'升序' =>'ASC'
	 *	];
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
		$all_link  = static::$doing_ajax ? '' : remove_query_arg('order', $this->base_url);
		$tabs .= '<li ' . $all_class . '><a data-key="order" data-value="" href="' . $all_link . '">' . __('默认', 'wnd') . '</a></li>';

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
			$order_link = static::$doing_ajax ? '' : add_query_arg('order', $value, $this->base_url);
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
		$tabs .= '<div class="column is-narrow">' . __('当前：', 'wnd') . '</div>';
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
			$cancel_link = static::$doing_ajax ? '' : remove_query_arg('_term_' . $term->taxonomy, $this->base_url);
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
			 *meta_query GET参数为：_meta_{key}?=
			 */
			$cancel_link = static::$doing_ajax ? '' : remove_query_arg('_meta_' . $this->meta_filter_args['key'], $this->base_url);
			$tabs .= '<span class="tag">' . $key . '<a data-key="_meta_' . $this->meta_filter_args['key'] . '" data-value="" class="delete is-small" href="' . $cancel_link . '"></a></span>&nbsp;&nbsp;';
		}
		unset($key, $meta_query);

		// 输出结束
		$tabs .= '</div>';
		$tabs .= '</div>';

		return $tabs;
	}

	/**
	 *@since 2019.02.15
	 *分页导航
	 */
	protected function build_pagination($show_page = 5) {
		$nav = new Wnd_Pagination(static::$is_ajax, $this->uniqid, $this->independent);

		$nav->set_paged($this->wp_query->query_vars['paged'] ?: 1);
		$nav->set_max_num_pages($this->wp_query->max_num_pages);
		$nav->set_items_per_page($this->wp_query->query_vars['posts_per_page']);
		$nav->set_current_item_count($this->wp_query->post_count);
		$nav->set_show_pages($show_page);

		$nav->set_data($this->add_query);
		$nav->add_class($this->class);
		return $nav->build();
	}

	/**
	 *@since 2019.08.01
	 *执行查询
	 *
	 *@since 0.8.64
	 *- 执行独立 WP Query
	 *- 当设置为非独立查询（依赖当前页面查询）时，查询参数将通过 'pre_get_posts' 实现修改，无需执行 WP Query @see static::action_on_pre_get_posts();
	 *  当下场景中 $this->wp_query 为 global $wp_query; @see __construct();
	 */
	public function query() {
		if ($this->independent) {
			$this->wp_query = new WP_Query($this->wp_query_args);
		}
	}

	/**
	 *@since 2019.08.09
	 *获取当前tax_query的所有父级term_id
	 *@return array $parents 当前分类查询的所有父级：$parents[$taxonomy] = [$term_id_1, $term_id_2];
	 */
	protected function get_tax_query_patents() {
		$parents = [];

		// 遍历当前tax query是否包含子类
		foreach ($this->wp_query_args['tax_query'] as $tax_query) {
			// WP_Query tax_query参数可能存在：'relation' => 'AND', 'relation' => 'OR',参数，需排除 @since 2019.06.14
			if (!isset($tax_query['terms'])) {
				continue;
			}

			// 递归查询当前分类的父级分类
			$parents[$tax_query['taxonomy']] = [];
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
		return '<div id="tabs-' . $this->uniqid . '" class="wnd-filter-tabs ' . $this->class . '"' . $this->build_data_attr() . '>' . $tabs . '</div>';
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
	public function get_category_tabs($args = []) {
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
		$sub_tabs_array = [];

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
				$args = [
					'taxonomy' => $tax_query['taxonomy'],
					'parent'   => $parent,
				];
				$sub_tabs .= $this->build_taxonomy_filter($args, 'sub-tabs');
			}
			unset($parent);

			// 当前分类的子类
			$args = [
				'taxonomy' => $tax_query['taxonomy'],
				'parent'   => $tax_query['terms'],
			];
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
			return __('未执行WP_Query', 'wnd');
		}

		// Posts list
		if ($this->wp_query_args['wnd_posts_tpl']) {
			$template = $this->wp_query_args['wnd_posts_tpl'];
			if (!$template) {
				return __('未定义输出模板', 'wnd');
			}
			$this->posts = $template($this->wp_query);

			// post list
		} else {
			$template = $this->wp_query_args['wnd_post_tpl'];
			if (!$template) {
				return __('未定义输出模板', 'wnd');
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
		if (!$this->wp_query) {
			return __('未执行WP_Query', 'wnd');
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

	/**
	 *@since 0.8.64
	 *
	 *多重筛选：解析 $_GET 获取 WP_Query 参数，写入查询
	 * - 排除无 $_GET 参数的查询
	 * - 排除后台
	 * - 排除 Ajax 请求
	 * - 排除内页
	 *
	 *@since 0.8.72
	 * - 排除 WP 内置功能型 Post Type 查询
	 *
	 * 在内页或 Ajax 请求中，应且只能执行独立的 WP Query
	 */
	public static function action_on_pre_get_posts($query) {
		if (empty($_GET) or is_admin() or wnd_doing_ajax() or $query->is_singular()) {
			return $query;
		}

		$post_type = $query->query_vars['post_type'] ?? false;
		if ($post_type) {
			if (is_array($post_type)) {
				foreach ($post_type as $single_post_type) {
					if (!in_array($single_post_type, static::get_supported_post_types())) {
						return $query;
					}
				}unset($single_post_type);
			}

			if (!in_array($post_type, static::get_supported_post_types())) {
				return $query;
			}
		}

		/**
		 *解析 $_GET 获取 WP_Query 参数
		 * - 排除分页：pre_get_posts 仅适用于非独立 wp query，此种情况下分页已在 URL 中确定
		 */
		$query_vars = static::parse_query_vars();
		if (!$query_vars) {
			return $query;
		}
		unset($query_vars['paged']);

		/**
		 *依次将 $_GET 解析参数写入
		 */
		foreach ($query_vars as $key => $value) {
			/**
			 * tax_query 需要额外处理：
			 * 当在 taxonomy 归档页添加其他分类多重查询时，会导致归档类型判断错乱。
			 * 为保证归档页类型不变，需要提前获取默认 tax_query 查询参数，并保证默认查询为查询数组首元素（WP 以第一条 taxonomy 为标准）。
			 * @see WP_Query->get_queried_object();
			 */
			if ('tax_query' == $key) {
				$default_tax_query = $query->tax_query->queries ?? [];
				$query->set($key, array_merge($default_tax_query, $value));
			} else {
				$query->set($key, $value);
			}
		}unset($key, $value);

		return $query;
	}

	/**
	 *@since 0.9.0
	 * 定义多重筛选支持的 Post Types
	 * - 排除 WP 内置功能型 Post Type 查询
	 *
	 */
	protected static function get_supported_post_types(): array{
		$custom_post_types = get_post_types(['_builtin' => false]);
		return array_merge($custom_post_types, ['post' => 'post', 'page' => 'page', 'attachment' => 'attachment']);
	}
}
