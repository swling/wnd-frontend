<?php
namespace Wnd\View;

use WP_User_Query;

/**
 * @since 2020.05.05
 * 用户筛选类
 * 主要支持排序和搜索
 *
 * 样式基于bulma css
 * @param bool 		$is_ajax 	是否为ajax筛选（需要对应的前端支持）
 * @param string 	$uniqid 	HTML容器识别ID。默认值 uniqid() @see build_pagination() / get_tabs()
 *
 * @link https://developer.wordpress.org/reference/classes/WP_User_Query/
 * @link https://developer.wordpress.org/reference/classes/WP_User_Query/prepare_query/
 */
class Wnd_Filter_User {

	protected $before_html = '';

	protected $after_html = '';

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
	protected $add_query_vars = [];

	// 默认切换筛选项时需要移除的参数
	protected $remove_query_args = ['paged', 'page'];

	/**
	 *根据配置设定的wp_user_query查询参数
	 *默认值将随用户设定而改变
	 *参数中包含自定义的非wp_user_query参数以"wnd"前缀区分
	 */
	protected $query_args = [
		'order'              => 'DESC',
		'orderby'            => 'registered',
		'meta_key'           => '',
		'meta_value'         => '',
		'count_total'        => false,
		'paged'              => 1,
		'number'             => 20,
		'search_columns'     => [],
		'search'             => '',

		// 自定义
		'wnd_ajax_container' => '',
		'wnd_uniqid'         => '',
	];

	// 筛选项HTML
	protected $tabs = [];

	// 筛选结果HTML
	protected $users = [];

	// 分页导航HTML
	protected $pagination = [];

	/**
	 *wp_user_query 实例化
	 *@see $this->query();
	 */
	public $wp_user_query;

	/**
	 *Constructor.
	 *
	 *@param bool 		$is_ajax 是否为ajax查询
	 *@param string 	$uniqid当前筛选器唯一标识
	 */
	public function __construct() {
		static::$http_query    = static::parse_query_vars();
		static::$primary_color = wnd_get_config('primary_color');

		// 解析GET参数为wp_user_query参数并与默认参数合并，以防止出现参数未定义的警告信息
		$this->query_args = array_merge($this->query_args, static::$http_query);
	}

	/**
	 * @since 2020.05.05
	 * 从GET参数中解析wp_user_query参数
	 *
	 * @return 	array 	wp_user_query $args
	 *
	 * @see 解析规则：
	 *
	 *meta查询
	 * _meta_{key}={$meta_value}
	 * _meta_{key}=exists
	 *
	 * 其他查询（具体参考 wp_user_query）
	 * $args[$key] = $value;
	 **/
	public static function parse_query_vars() {
		if (empty($_GET)) {
			return [];
		}

		foreach ($_GET as $key => $value) {
			/**
			 *用户搜索关键词默认不支持模糊搜索
			 *添加星标以支持模糊搜索
			 *@since 2020.05.11
			 */
			if ('search' == $key) {
				$query_vars['search'] = '*' . $value . '*';
				continue;
			}

			/**
			 *@since 2019.3.07 自动匹配meta query
			 *?_meta_price=1 则查询 price = 1的user
			 *?_meta_price=exists 则查询 存在price的user
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
			 *@since 2019.07.30
			 *分页
			 */
			if ('page' == $key) {
				$query_vars['paged'] = $value ?: 1;
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
	 *@param int $number 每页post数目
	 **/
	public function set_number($number) {
		$this->add_query_vars(['number' => $number]);
	}

	/**
	 *@since 2019.07.31
	 *添加新的请求参数
	 *添加的参数，将覆盖之前的设定，并将在所有请求中有效，直到被新的设定覆盖
	 *
	 *@param array $query [key=>value]
	 *
	 *在非ajax环境中，直接将写入$query_args[key]=value
	 *
	 *在ajax环境中，将对应生成html data属性：data-{key}="{value}" 通过JavaScript获取后将转化为 ajax url请求参数 ?{key}={value}，
	 *ajax发送到api接口，再通过parse_query_vars() 解析后，写入$query_args[key]=value
	 **/
	public function add_query_vars($query = []) {
		foreach ($query as $key => $value) {
			// 数组参数，合并元素；非数组参数，赋值 （php array_merge：相同键名覆盖，未定义键名或以整数做键名，则新增)
			if (is_array($this->query_args[$key] ?? false) and is_array($value)) {
				$this->query_args[$key] = array_merge($this->query_args[$key], $value, static::$http_query[$key] ?? []);

			} else {
				// $_GET参数优先，无法重新设置
				$this->query_args[$key] = (static::$http_query[$key] ?? false) ?: $value;
			}

			// 在html data属性中新增对应属性，以实现在ajax请求中同步添加参数
			$this->add_query_vars[$key] = $value;
		}
		unset($key, $value);
	}

	public function add_search_form($button = 'Search', $placeholder = '') {
		$html = '<form class="wnd-filter-user-search" method="POST" action="" "onsubmit"="return false">';
		$html .= '<div class="field has-addons">';

		$html .= '<div class="control">';
		$html .= '<span class="select">';
		$html .= '<select name="search_columns[]">';
		$html .= ' <option value=""> - Field - </option>';
		$html .= ' <option value="display_name"> - Name - </option>';
		$html .= ' <option value="user_login"> - Login - </option>';
		$html .= ' <option value="user_email"> - Email - </option>';
		$html .= '</select>';
		$html .= '</span>';
		$html .= '</div>';

		$html .= '<div class="control is-expanded">';
		$html .= '<input class="input" type="text" name="search" placeholder="' . $placeholder . '" required="required">';
		$html .= '</div>';

		$html .= '<div class="control">';
		$html .= '<button type="submit" class="button is-' . static::$primary_color . '">' . $button . '</button>';
		$html .= '</div>';

		$html .= '</div>';
		$html .= '</form>';

		$this->tabs .= $html;
	}

	/**
	 *构造筛选菜单数据
	 */
	protected function build_tabs(string $key, array $options, string $title, bool $with_any_tab, array $remove_query_args = []): array{
		if (!$options) {
			return [];
		}

		// 筛选添加改变时，移除 Page 参数
		$remove_query_args[] = 'page';

		if ($with_any_tab) {
			$options = array_merge([__('全部', 'wnd') => ''], $options);
		}

		$tabs = [
			'key'               => $key,
			'title'             => $title,
			'options'           => $options,
			'remove_query_args' => $remove_query_args,
		];
		$this->tabs[] = $tabs;

		return $tabs;
	}

	/**
	 *获取完整筛选 Tabs
	 */
	public function get_tabs() {
		return $this->tabs;
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
	 *@param bool 全部选项
	 */
	public function add_orderby_filter(array $args, bool $with_any_tab = false) {
		$key     = 'orderby';
		$title   = $args['label'];
		$options = $args['options'];

		return $this->build_tabs($key, $options, $title, $with_any_tab);
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
	public function add_order_filter(array $args, $with_any_tab = false) {
		$key     = 'order';
		$title   = $args['label'];
		$options = $args['options'];

		return $this->build_tabs($key, $options, $title, $with_any_tab);
	}

	/**
	 *@param string $label 选项名称
	 */
	public function add_status_filter($args, $with_any_tab = false) {
		$key     = '_meta_status';
		$title   = $args['label'];
		$options = $args['options'];

		return $this->build_tabs($key, $options, $title, $with_any_tab);
	}

	/**
	 *@since 2019.08.01
	 *执行查询
	 */
	public function query() {
		$this->wp_user_query = new WP_User_Query($this->query_args);
		$this->users         = $this->wp_user_query->get_results();
	}

	public function get_add_query_vars(): array{
		return $this->add_query_vars;
	}

	/**
	 *执行查询
	 *
	 */
	public function get_users(): array{
		if (!$this->wp_user_query) {
			return __('未执行WP_User_Query', 'wnd');
		}

		return $this->users;
	}

	/**
	 *@since 2019.02.15
	 *分页导航
	 */
	public function get_pagination($show_page = 5) {
		if (!$this->wp_user_query) {
			return __('未执行wp_user_query', 'wnd');
		}

		$this->pagination = $this->build_pagination($show_page);
		return $this->pagination;
	}

	/**
	 *分页导航
	 */
	protected function build_pagination($show_page = 5) {
		$paged         = $this->wp_user_query->query_vars['paged'] ?: 1;
		$total         = $this->wp_user_query->get_total();
		$number        = $this->wp_user_query->query_vars['number'];
		$current_count = count($this->users);
		$max_num_pages = $total ? ($total / $number + 1) : 0;

		return [
			'paged'         => $paged,
			'max_num_pages' => $max_num_pages,
			'per_page'      => $number,
			'current_count' => $current_count,
			'show_page'     => $show_page,
		];
	}

	/**
	 *@since 0.9.25
	 *获取完整的筛选数据结构：适用于初始化筛选器
	 *
	 *@param bool $with_post_content 是否包含正文内容
	 * 		-在很多情况下 Ajax 筛选用于各类管理面板，此时仅需要获取 post 列表，无需包含正文内容，以减少网络数据发送量
	 */
	public function get_filter(): array{
		return [
			'before_html'    => $this->before_html,
			'after_html'     => $this->after_html,
			'tabs'           => $this->get_tabs(),
			'users'          => $this->get_users(),

			'pagination'     => $this->get_pagination(),
			// 'post_count'     => $this->wp_user_query->post_count,

			'add_query_vars' => $this->get_add_query_vars(),

			/**
			 *在debug模式下，返回当前WP_Query查询参数
			 **/
			'query_vars'     => $this->wp_user_query->query_vars,
		];
	}

	/**
	 *获取查询结果集：适用于已经完成初始化的筛选器，后续筛选查询（出主分类和标签筛选项外，不含其他 Tabs ）
	 */
	public function get_results(): array{
		$structure = $this->get_filter();
		unset($structure['before_html']);
		unset($structure['tabs']);
		unset($structure['after_html']);
		return $structure;
	}
}
