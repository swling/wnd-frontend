<?php
namespace Wnd\View;

use WP_User_Query;

/**
 * @since 2020.05.05
 * 用户筛选类
 * 主要支持排序和搜索
 *
 * 样式基于bulma css
 *
 * @link https://developer.wordpress.org/reference/classes/WP_User_Query/
 * @link https://developer.wordpress.org/reference/classes/WP_User_Query/prepare_query/
 */
class Wnd_Filter_User {

	use Wnd_Filter_Query_Trait;

	protected $before_html = '';

	protected $after_html = '';

	// string html class
	protected $class;

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
		static::$request_query_vars = static::parse_query_vars();

		/**
		 *根据配置设定的wp_user_query查询参数
		 *默认值将随用户设定而改变
		 *参数中包含自定义的非wp_user_query参数以"wnd"前缀区分
		 */
		$defaults = [
			'order'          => 'DESC',
			'orderby'        => 'registered',
			'meta_key'       => '',
			'meta_value'     => '',
			'count_total'    => false,
			'paged'          => 1,
			'number'         => 20,
			'search_columns' => [],
			'search'         => '',
		];

		// 解析GET参数为wp_user_query参数并与默认参数合并，以防止出现参数未定义的警告信息
		$this->query_args = array_merge($defaults, static::$request_query_vars);
	}

	/**
	 *@since 2019.07.31
	 *设置ajax post列表嵌入容器
	 *@param int $number 每页post数目
	 **/
	public function set_number(int $number) {
		$this->add_query_vars(['number' => $number]);
	}

	/**
	 *获取完整筛选 Tabs
	 */
	public function get_tabs() {
		return $this->tabs;
	}

	/**
	 *筛选器之前 Html
	 */
	public function add_before_html(string $html) {
		$this->before_html .= $html;
	}

	/**
	 *筛选器之后 Html
	 */
	public function add_after_html(string $html) {
		$this->after_html .= $html;
	}

	// 搜索框（未完成）wnd_get_config('primary_color')
	public function add_search_form(string $button = 'Search', string $placeholder = '') {
		return [];
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
	public function add_order_filter(array $args, bool $with_any_tab = false) {
		$key     = 'order';
		$title   = $args['label'];
		$options = $args['options'];

		return $this->build_tabs($key, $options, $title, $with_any_tab);
	}

	/**
	 *@param string $label 选项名称
	 */
	public function add_status_filter(array $args, bool $with_any_tab = false) {
		$key     = '_meta_status';
		$title   = $args['label'];
		$options = $args['options'];

		return $this->build_tabs($key, $options, $title, $with_any_tab);
	}

	/**
	 *构造 Ajax 筛选菜单数据
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
	 *@since 2019.08.01
	 *执行查询
	 */
	public function query() {
		$this->wp_user_query = new WP_User_Query($this->query_args);
		$this->users         = $this->wp_user_query->get_results();
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
	public function get_pagination(int $show_page = 5) {
		if (!$this->wp_user_query) {
			return __('未执行wp_user_query', 'wnd');
		}

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
			'add_query_vars' => $this->add_query_vars,
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
