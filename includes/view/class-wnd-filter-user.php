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

	// bool 是否ajax
	protected static $is_ajax;

	// bool 是否正处于ajax环境中
	protected static $doing_ajax;

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

	// 默认切换筛选项时需要移除的参数
	protected $remove_query_args = ['paged', 'page'];

	/**
	 *根据配置设定的wp_user_query查询参数
	 *默认值将随用户设定而改变
	 *参数中包含自定义的非wp_user_query参数以"wnd"前缀区分
	 */
	protected $query_args = [
		'order'              => 'DESC',
		'orderby'            => 'user_registered',
		'meta_key'           => '',
		'meta_value'         => '',
		'no_found_rows'      => true,
		'paged'              => 1,
		'number'             => 20,

		// 自定义
		'wnd_ajax_container' => '',
		'wnd_uniqid'         => '',
	];

	// 筛选项HTML
	protected $tabs = '';

	// 筛选结果HTML
	protected $users = '';

	// 分页导航HTML
	protected $pagination = '';

	/**
	 *wp_user_query 查询结果：
	 *@see $this->query();
	 */
	public $wp_user_query;

	/**
	 *Constructor.
	 *
	 *@param bool 		$is_ajax 是否为ajax查询
	 *@param string 	$uniqid当前筛选器唯一标识
	 */
	public function __construct(bool $is_ajax = false, string $uniqid = '') {
		self::$is_ajax    = $is_ajax;
		self::$doing_ajax = wnd_doing_ajax();
		self::$http_query = self::parse_query_vars();
		$this->class      = self::$is_ajax ? 'ajax-filter' : 'filter';

		// 解析GET参数为wp_user_query参数并与默认参数合并，以防止出现参数未定义的警告信息
		$this->query_args = array_merge($this->query_args, self::$http_query);

		// 初始化时生成uniqid，并加入query参数，以便在ajax生成的新请求中保持一致
		$this->uniqid = $uniqid ?: $this->query_args['wnd_uniqid'] ?: uniqid();
		$this->add_query(['wnd_uniqid' => $this->uniqid]);
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
			 *@since 2019.3.07 自动匹配meta query
			 *?_meta_price=1 则查询 price = 1的user
			 *?_meta_price=exists 则查询 存在price的user
			 */
			if (strpos($key, '_meta_') === 0) {
				$key        = str_replace('_meta_', '', $key);
				$compare    = $value == 'exists' ? 'exists' : '=';
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

			// 其他：按键名自动匹配
			$query_vars[$key] = (false !== strpos($value, '=')) ? wp_parse_args($value) : $value;
			continue;
		}
		unset($key, $value);

		/**
		 *定义如何过滤HTTP请求
		 *此处定义：过滤空值，但保留0
		 *@since 2019.10.26
		 **/
		$query_vars = array_filter($query_vars, function ($value) {
			return $value or $value == 0;
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
	 *@param int $number 每页post数目
	 **/
	public function set_number($number) {
		$this->add_query(['number' => $number]);
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
	public function add_query($query = []) {
		foreach ($query as $key => $value) {
			// 数组参数，合并元素；非数组参数，赋值 （php array_merge：相同键名覆盖，未定义键名或以整数做键名，则新增)
			if (is_array($this->query_args[$key] ?? false) and is_array($value)) {
				$this->query_args[$key] = array_merge($this->query_args[$key], $value, self::$http_query[$key] ?? []);

			} else {
				// $_GET参数优先，无法重新设置
				$this->query_args[$key] = (self::$http_query[$key] ?? false) ?: $value;
			}

			// 在html data属性中新增对应属性，以实现在ajax请求中同步添加参数
			$this->add_query[$key] = $value;
		}
		unset($key, $value);
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
		$remove_query_args = array_merge(['orderby', 'order', 'meta_key'], $this->remove_query_args);

		// 全部
		$all_class = 'class="is-active"';
		if (isset($this->query_args['orderby']) and $this->query_args['orderby'] != 'post_date') {
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
			if (isset($this->query_args['orderby'])) {
				/**
				 *	post meta排序
				 *	$args = [
				 *		'post_type' => 'product',
				 *		'orderby'   => 'meta_value_num',
				 *		'meta_key'  => 'price',
				 *	];
				 *	$query = new wp_user_query( $args );
				 */
				if (is_array($orderby) and ($this->query_args['orderby'] == 'meta_value_num' or $this->query_args['orderby'] == 'meta_value')) {
					if ($orderby['meta_key'] == $this->query_args['meta_key']) {
						$class = 'class="is-active"';
					}
					// 常规排序
				} else {
					if ($orderby == $this->query_args['orderby']) {
						$class = 'class="is-active"';
					}
				}

			}

			// data-key="orderby" data-value="' . http_build_query($query_arg) . '"
			$query_arg    = is_array($orderby) ? $orderby : ['orderby' => $orderby];
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
		$all_link  = self::$doing_ajax ? '' : remove_query_arg('order', remove_query_arg($this->remove_query_args));
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
	 *@since 2019.08.01
	 *执行查询
	 */
	public function query() {
		$this->wp_user_query = new WP_User_Query($this->query_args);
	}

	/**
	 *执行查询
	 *
	 */
	public function get_users() {
		if (!$this->wp_user_query) {
			return __('未执行WP_User_Query', 'wnd');
		}

		$users = $this->wp_user_query->get_results();
		if (empty($users)) {
			$this->users .= 'No users found';
			return $this->users;
		}

		$this->users .= '<ul>';
		foreach ($users as $user) {
			$this->users .= '<li>' . $user->user_email . '</li>';
		}
		$this->users .= '</ul>';

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
	 *@since 2019.07.31
	 *合并返回：user列表及分页导航
	 */
	public function get_results() {
		return $this->get_users() . $this->get_pagination();
	}

	/**
	 *@since 2019.02.15 简单分页导航
	 *不查询总数的情况下，简单实现下一页翻页
	 *翻页参数键名page 不能设置为 paged 会与原生WordPress翻页机制产生冲突
	 */
	protected function build_pagination($show_page = 5) {
		/**
		 *$this->wp_user_query->query_vars :
		 *wp_user_query实际执行的查询参数 new wp_user_query($args) $args 经过wp_user_query解析后
		 *@see Class wp_user_query
		 */
		$paged = $this->wp_user_query->query_vars['paged'] ?: 1;

		/**
		 *未查询user总数，以上一页下一页的形式翻页(在数据较多的情况下，可以提升查询性能)
		 *在ajax环境中，动态分页较为复杂，暂统一设定为上下页的形式，前端处理更容易
		 */
		if (!$this->wp_user_query->max_num_pages) {
			$previous_link = self::$doing_ajax ? '' : add_query_arg('page', $paged - 1);
			$next_link     = self::$doing_ajax ? '' : add_query_arg('page', $paged + 1);

			$html = '<nav id="nav-' . $this->uniqid . '" class="pagination is-centered ' . $this->class . '" ' . $this->build_html_data() . '>';
			$html .= '<ul class="pagination-list">';
			if ($paged >= 2) {
				$html .= '<li><a data-key="page" data-value="' . ($paged - 1) . '" class="pagination-previous" href="' . $previous_link . '">' . __('上一页', 'wnd') . '</a>';
			}
			if ($this->wp_user_query->post_count >= $this->wp_user_query->query_vars['number']) {
				$html .= '<li><a data-key="page" data-value="' . ($paged + 1) . '" class="pagination-next" href="' . $next_link . '">' . __('下一页', 'wnd') . '</a>';
			}
			$html .= '</ul>';
			$html .= '</nav>';

			return $html;

		} else {
			/**
			 *常规分页，需要查询user总数
			 *据称，在数据量较大的站点，查询user总数会较为费时
			 */
			$first_link    = self::$doing_ajax ? '' : remove_query_arg('page');
			$previous_link = self::$doing_ajax ? '' : add_query_arg('page', $paged - 1);
			$next_link     = self::$doing_ajax ? '' : add_query_arg('page', $paged + 1);
			$last_link     = self::$doing_ajax ? '' : add_query_arg('page', $this->wp_user_query->max_num_pages);

			$html = '<div id="nav-' . $this->uniqid . '" class="pagination is-centered ' . $this->class . '" ' . $this->build_html_data() . '>';
			if ($paged > 1) {
				$html .= '<a data-key="page" data-value="' . ($paged - 1) . '" class="pagination-previous" href="' . $previous_link . '">' . __('上一页', 'wnd') . '</a>';
			} else {
				$html .= '<a class="pagination-previous" disabled="disabled">' . __('首页', 'wnd') . '</a>';
			}

			if ($paged < $this->wp_user_query->max_num_pages) {
				$html .= '<a data-key="page" data-value="' . ($paged + 1) . '" class="pagination-next" href="' . $next_link . '">' . __('下一页', 'wnd') . '</a>';
			}

			$html .= '<ul class="pagination-list">';
			$html .= '<li><a data-key="page" data-value="" class="pagination-link" href="' . $first_link . '" >' . __('首页', 'wnd') . '</a></li>';
			for ($i = $paged - 1; $i <= $paged + $show_page; $i++) {
				if ($i > 0 and $i <= $this->wp_user_query->max_num_pages) {
					$page_link = self::$doing_ajax ? '' : add_query_arg('page', $i);
					if ($i == $paged) {
						$html .= '<li><a data-key="page" data-value="' . $i . '" class="pagination-link is-current" href="' . $page_link . '"> <span>' . $i . '</span> </a></li>';
					} else {
						$html .= '<li><a data-key="page" data-value="' . $i . '" class="pagination-link" href="' . $page_link . '"> <span>' . $i . '</span> </a></li>';
					}
				}
			}
			if ($paged < $this->wp_user_query->max_num_pages - 3) {
				$html .= '<li><span class="pagination-ellipsis">&hellip;</span></li>';
			}

			$html .= '<li><a data-key="page" data-value="' . $this->wp_user_query->max_num_pages . '" class="pagination-link" href="' . $last_link . '">' . __('尾页', 'wnd') . '</a></li>';
			$html .= '</ul>';

			$html .= '</div>';

			$this->pagination = $html;
			return $this->pagination;
		}
	}
}
