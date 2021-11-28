<?php
namespace Wnd\View;

use Exception;
use Wnd\Model\Wnd_Tag_Under_Category;
use Wnd\View\Wnd_Filter_Query;
use Wnd\View\Wnd_Pagination;
use WP_Query;

/**
 * Posts 多重筛选抽象基类
 * - 参数解析
 * - 权限检测
 * - 定义可用的筛选项方法
 * - 执行 WP_Query（仅在非依赖型）
 * - 定义子类中必须实现的抽象方法
 * @since 0.9.25
 *
 * @param bool 	$independent 	是否为独立 WP Query
 */
abstract class Wnd_Filter_Abstract {

	// 当前请求基本 URL （移除 WP 默认伪静态分页参数)
	protected $wp_base_url;

	/**
	 * WP_Query 查询结果：
	 * @see $this->query();
	 */
	protected $wp_query;

	/**
	 * 是否为独立的、不依赖当前页面的 WP_Query
	 * @since 0.8.64
	 */
	protected $independent;

	// 当前post type的主分类taxonomy 约定：post(category) / 自定义类型 （$post_type . '_cat'）
	public $category_taxonomy;

	// Wnd\View\Wnd_Filter_Query 查询类实例化对象;
	private $filter_query;

	/**
	 * Constructor.
	 *
	 * @param bool 	$independent	是否为独立 WP Query
	 */
	public function __construct(bool $independent = true) {
		$this->independent = $independent;
		$this->wp_base_url = get_pagenum_link(1, false);

		// 初始化查询参数
		$defaults = [
			'orderby'       => 'date',
			'order'         => 'DESC',
			'meta_query'    => [],
			'tax_query'     => [],
			'date_query'    => [],
			'meta_key'      => '',
			'meta_value'    => '',
			'post_type'     => '',
			'post_status'   => '',
			'no_found_rows' => true,
			'paged'         => 1,
		];

		/**
		 * - 独立型 WP Query：分页需要自定义处理
		 * - 依赖型 WP Query：获取全局 $wp_query，并读取全局查询参数赋值到当前筛选环境，以供构建与之匹配的 tabs
		 * @since 0.8.64
		 */
		if ($this->independent) {
			$this->wp_base_url = remove_query_arg(Wnd_Pagination::$page_query_var, $this->wp_base_url);
		} else {
			global $wp_query;
			if (!$wp_query->query_vars) {
				throw new Exception(__('当前环境需执行独立 WP Query', 'wnd'));
			}

			$this->wp_query = $wp_query;
			$defaults       = array_merge($defaults, $wp_query->query_vars);
		}

		/**
		 * 初始化查询类
		 * @since 0.9.32
		 */
		$this->filter_query = new Wnd_Filter_Query($defaults);

		/**
		 * 定义当前post type的主分类：$category_taxonomy
		 */
		$post_type = $this->get_post_type_query();
		if ($post_type) {
			$this->category_taxonomy = ('post' == $post_type) ? 'category' : $post_type . '_cat';
		}
	}

	/**
	 * 封装添加新的请求参数的方法，便于在外部以更统一的方式调用
	 * @see Wnd_Filter_query::add_query_vars();
	 */
	public function add_query_vars(array $query = []) {
		$this->filter_query->add_query_vars($query);
	}

	/**
	 * 设定指定查询参数
	 *
	 * 本方法与 $this->add_query_vars() 区别在于：
	 * - 本方法主要用于系统设定，即设定初始化时的参数
	 * - 相关设定参数不会对外界暴露
	 * - 典型用途：在完成对外部查询参数的解析后，强制添加或修改某些参数
	 *
	 * @since 0.9.38
	 */
	public function set_query_var(string $var, $value) {
		return $this->filter_query->set_query_var($var, $value);
	}

	/**
	 * 读取指定查询参数值
	 * @since 0.9.38
	 */
	public function get_query_var(string $var) {
		return $this->filter_query->get_query_var($var);
	}

	/**
	 * 读取全部查询参数数组
	 * @since 0.9.38
	 */
	public function get_query_vars(): array{
		return $this->filter_query->get_query_vars();
	}

	/**
	 * 获取新增的查询参数
	 * @since 0.9.38
	 */
	public function get_add_query_vars(): array{
		return $this->filter_query->get_add_query_vars();
	}

	/**
	 * 设置ajax post列表嵌入容器
	 * @since 2019.07.31
	 *
	 * @param int $posts_per_page 每页post数目
	 */
	public function set_posts_per_page(int $posts_per_page) {
		$this->add_query_vars(['posts_per_page' => $posts_per_page]);
	}

	/**
	 * 搜索框：在子类中实现
	 * @since 2020.05.11
	 */
	abstract public function add_search_form(string $button = 'Search', string $placeholder = '');

	/**
	 * @param array 	$args 需要筛选的类型数组
	 * @param bool  	$any  是否包含全部选项
	 */
	public function add_post_type_filter(array $args = [], bool $any = false) {
		/**
		 * 若当前请求未指定post_type，设置第一个post_type为默认值；若筛选项也为空，最后默认post
		 * post_type/post_status 在所有筛选中均需要指定默认值，若不指定，WordPress也会默认设定
		 *
		 * 当前请求为包含post_type参数时，当前的主分类（category_taxonomy）无法在构造函数中无法完成定义，需在此处补充
		 */
		if (!$this->get_post_type_query()) {
			$default_type = $any ? 'any' : ($args ? reset($args) : 'post');
			$this->add_query_vars(['post_type' => $default_type]);
			$this->category_taxonomy = ('post' == $default_type) ? 'category' : $default_type . '_cat';
		}

		/**
		 * 仅筛选项大于2时，构建HTML
		 */
		if (count($args) < 2) {
			return;
		}

		// 构建 Tabs 数据
		$key     = 'type';
		$label   = __('类型：', 'wnd');
		$options = [];
		foreach ($args as $post_type) {
			$post_type                  = get_post_type_object($post_type);
			$options[$post_type->label] = $post_type->name;
		}
		unset($post_type);

		return $this->build_tabs($key, $options, $label, $any, ['all']);
	}

	/**
	 * 状态筛选
	 * @param array $args 需要筛选的文章状态数组
	 */
	public function add_post_status_filter(array $args = [], bool $any = true) {
		$this->add_query_vars(['post_status' => $args]);

		/**
		 * 仅筛选项大于2时，构建HTML
		 */
		if (count($args) < 2) {
			return;
		}

		// 构建 Tabs 数据
		$label   = __('状态', 'wnd');
		$key     = 'status';
		$options = $args;
		return $this->build_tabs($key, $options, $label, $any);
	}

	/**
	 * 若查询的taxonomy与当前post type未关联，则不输出
	 * @since 2019.02.28
	 *
	 * @param $args 	array get_terms 参数
	 */
	public function add_taxonomy_filter(array $args) {
		$args['parent'] = $args['parent'] ?? 0;
		$taxonomy       = $args['taxonomy'] ?? '';
		if (!$taxonomy) {
			return;
		}

		// 初始筛选 term tabs
		$tabs = $this->build_taxonomy_filter($args);

		/**
		 * 遍历当前tax query 查询是否设置了对应的taxonomy查询，若存在则查询其对应子类
		 * @since 2019.03.12
		 */
		$taxonomy_query = false;
		foreach ($this->get_tax_query() as $key => $tax_query) {
			// WP_Query tax_query参数可能存在：'relation' => 'AND', 'relation' => 'OR',参数，需排除 @since 2019.06.14
			if (!isset($tax_query['terms'])) {
				continue;
			}

			if (array_search($taxonomy, $tax_query) !== false) {
				$taxonomy_query         = true;
				$current_taxonomy_terms = $tax_query['terms'];
				break;
			}
		}
		unset($key, $tax_query);

		if (!$taxonomy_query) {
			return $tabs;
		}

		/**
		 * 当前 term 的父类的 child term， 即（层级）：当前筛选 term <= and > 初始筛选 Term
		 *  - WP 函数 get_ancestors() 获取的父类 id 是从低到高，此处需要反转为从高到低 @see array_reverse()
		 *    以符合: 子类 => 孙类 之上而下的 tabs 排序
		 *
		 */
		$ancestors = array_reverse($this->get_tax_query_ancestors()[$taxonomy]);
		foreach ($ancestors as $parent) {
			$parent_args = [
				'taxonomy' => $taxonomy,
				'parent'   => $parent,
			];
			$this->build_taxonomy_filter($parent_args);
		}

		// 当前 term 的 child term
		$sub_args = [
			'taxonomy' => $taxonomy,
			'parent'   => $current_taxonomy_terms,
		];
		$this->build_taxonomy_filter($sub_args);

		return $tabs;
	}

	/**
	 * 主分类
	 * @since 0.9.25
	 */
	public function add_category_filter(array $args = []) {
		$args['taxonomy'] = $this->category_taxonomy;
		return $this->add_taxonomy_filter($args);
	}

	/**
	 * 标签筛选
	 * 定义taxonomy：{$post_type}.'_tag'
	 * 读取wp_query中tax_query 提取taxonomy为{$post_type}.'_cat'的分类id，并获取对应的关联标签(需启用标签分类关联功能)
	 * 若未设置关联分类，则查询所有热门标签
	 * @since 2019.03.25
	 */
	public function add_tags_filter(int $limit = 10) {
		$this->build_tags_filter($limit);
	}

	/**
	 * 	$args = [
	 * 		'label' => '文章价格',
	 * 		'key' => 'price',
	 * 		'options' => [
	 * 			'10' => '10',
	 * 			'0.1' => '0.1',
	 * 		],
	 * 		'compare' => '=',
	 * 	];
	 * 	查询一个字段是否存在：options只需要设置一个：其作用为key值显示为选项文章，value不参与查询，可设置为任意值
	 * 	$args = [
	 * 		'label' => '文章价格',
	 * 		'key' => 'price',
	 * 		'options' => [
	 * 			'包含' => 'exists',
	 * 		],
	 * 		'compare' => 'exists',
	 * 	];
	 * @since 2019.04.18 meta query
	 *
	 * @param array args meta字段筛选。暂只支持单一 meta_key 暂仅支持 = 、exists 两种compare
	 */
	public function add_meta_filter(array $args, bool $any = true) {
		$label = $args['label'];
		$key   = '_meta_' . $args['key'];

		return $this->build_tabs($key, $args['options'], $label, $any);
	}

	/**
	 * 	$args = [
	 * 		'label' => '排序',
	 * 		'options' => [
	 * 			'发布时间' => 'date', //常规排序 date title等
	 * 			'浏览量' => [ // 需要多个参数的排序
	 * 				'orderby'=>'meta_value_num',
	 * 				'meta_key'   => 'views',
	 * 			],
	 * 		],
	 * 		'order' => 'DESC',
	 * 	];
	 * @since 2019.04.21 排序
	 *
	 * @param array $args
	 */
	public function add_orderby_filter(array $args, bool $any = true) {
		$key     = 'orderby';
		$label   = $args['label'];
		$options = $args['options'];

		return $this->build_tabs($key, $options, $label, $any);
	}

	/**
	 * 	$args = [
	 * 		'降序' => 'DESC',
	 * 		'升序' =>'ASC'
	 * 	];
	 * @since 2019.08.10 排序方式
	 *
	 * @param array  $args
	 * @param string $label  选项名称
	 */
	public function add_order_filter(array $args, string $label, bool $any = true) {
		return $this->build_tabs('order', $args, $label, $any);
	}

	/**
	 * 遍历当前查询参数，输出取消当前查询链接
	 * @since 2019.03.26
	 */
	public function add_current_filter() {
		return $this->build_current_filter();
	}

	/**
	 * 若查询的taxonomy与当前post type未关联，则不输出
	 * @since 2019.08.09
	 *
	 * @param array 	$args 		WordPress get_terms() 参数
	 * @param bool  	$any  		是否包含【全部】选项
	 */
	protected function build_taxonomy_filter(array $args, bool $any = true) {
		if (!isset($args['taxonomy'])) {
			return;
		}
		$taxonomy = $args['taxonomy'];
		$terms    = get_terms($args);
		if (!$terms or is_wp_error($terms)) {
			return;
		}

		$post_type = $this->get_post_type_query();

		/**
		 * 如果当前指定的taxonomy并不存在指定的post type中，非ajax环境直接中止，ajax环境中隐藏输出（根据post_type动态切换是否显示）
		 * @since 2019.07.30
		 */
		$current_post_type_taxonomies = get_object_taxonomies($post_type, 'names');
		if (!in_array($taxonomy, $current_post_type_taxonomies)) {
			return;
		}

		/**
		 * 切换主分类时，需要移除分类关联标签查询
		 * @since 2019.07.30
		 */
		if ($taxonomy == $this->category_taxonomy) {
			$remove_args = ['_term_' . $post_type . '_tag'];
		} else {
			$remove_args = [];
		}

		// 输出tabs
		$key     = '_term_' . $taxonomy;
		$options = [];
		$label   = get_taxonomy($taxonomy)->label;
		foreach ($terms as $term) {
			$options[$term->name] = $term->term_id;
		}
		unset($term);

		return $this->build_tabs($key, $options, $label, $any, $remove_args);
	}

	/**
	 * 构建分类关联标签的HTML
	 * @since 2019.08.09
	 */
	protected function build_tags_filter(int $limit = 10) {
		// 标签taxonomy
		$taxonomy = $this->get_post_type_query() . '_tag';
		if (!taxonomy_exists($taxonomy)) {
			return;
		}

		/**
		 * 在依赖型多重筛选中，分类及标签归档页默认不再包含 tax_query 查询参数
		 * 因此，首先判断当前查询是否为分类归档页查询：
		 * - Post 分类归档页查询参数包含 	'category_name' => $slug
		 * - 自定义分类归档页查询参数包含 	{$taxonomy}		=> $slug
		 * 查找在当前的tax_query查询参数中，当前taxonomy的键名，如果没有则加入
		 * tax_query是一个无键名的数组，无法根据键名合并，因此需要准确定位
		 * (数组默认键值从0开始， 当首元素即匹配则array_search返回 0，此处需要严格区分 0 和 false)
		 * @since 0.8.70
		 * @since 2019.03.07
		 */
		$category_key   = ('category' == $this->category_taxonomy) ? 'category_name' : $this->category_taxonomy;
		$category_query = $this->get_query_var($category_key);
		if ($category_query) {
			$category    = get_term_by('slug', $category_query, $this->category_taxonomy);
			$category_id = $category ? $category->term_id : 0;
		} else {
			foreach ($this->get_tax_query() as $key => $tax_query) {
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
			}
			unset($key, $tax_query);
		}

		/**
		 * 指定category_id时查询关联标签，否则调用热门标签
		 * @since 2019.03.25
		 */
		if (isset($category_id)) {
			$terms = Wnd_Tag_Under_Category::get_tags($category_id, $taxonomy, $limit);
		} else {
			$terms = get_terms($taxonomy, [
				'hide_empty' => true,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => $limit,
			]);
		}

		$key     = '_term_' . $taxonomy;
		$options = [];
		$label   = get_taxonomy($taxonomy)->label;
		$any     = true;

		foreach ($terms as $term) {
			$term->name           = $term->name ?? get_term($term->tag_id, $taxonomy)->name;
			$term->term_id        = $term->term_id ?? $term->tag_id;
			$options[$term->name] = $term->term_id;
		}

		return $this->build_tabs($key, $options, $label, $any);
	}

	/**
	 * 遍历当前查询参数，输出取消当前查询链接
	 * @since 2019.03.26
	 * @since 0.9.25  改造尚未完成
	 */
	protected function build_current_filter() {
		if (!$this->get_tax_query() and !$this->get_meta_query()) {
			return;
		}
	}

	/**
	 * 执行查询
	 * - 执行独立 WP Query
	 * - 当设置为非独立查询（依赖当前页面查询）时，查询参数将通过 'pre_get_posts' 实现修改，无需执行 WP Query @see static::action_on_pre_get_posts();
	 *  当下场景中 $this->wp_query 为 global $wp_query; @see __construct();
	 * @since 2019.08.01
	 * @since 0.8.64
	 * @since 0.9.38 将权限检测移至查询之前
	 */
	public function query() {
		$this->check_query_permission();

		if ($this->independent) {
			$this->wp_query = new WP_Query($this->get_query_vars());
		}
	}

	/**
	 * 查询权限检测：
	 * - 管理员无限制
	 * - publish 及 close 状态无限制
	 * - 其他状态仅可查看当前用户自身的内容
	 *
	 * @since 0.9.25
	 */
	private function check_query_permission() {
		if (is_super_admin()) {
			return;
		}

		$post_status     = $this->get_post_status_query() ?: 'publish';
		$current_user_id = get_current_user_id();

		// 数组查询
		if (is_array($post_status) and array_intersect($post_status, ['publish', 'wnd-closed']) == $post_status) {
			return;
		}

		// 单个查询
		if (is_string($post_status) and in_array($post_status, ['publish', 'wnd-closed'])) {
			return;
		}

		if (!$current_user_id) {
			throw new Exception('Only support querying the public status when not logged in. ' . $post_status);
		} else {
			$this->set_query_var('author', $current_user_id);
		}
	}

	/**
	 * 封装常用查询参数读取方法（私有方法，仅内部使用）
	 * @since 0.9.32
	 */
	protected function get_post_type_query() {
		return $this->get_query_var('post_type');
	}

	protected function get_post_status_query() {
		return $this->get_query_var('post_status');
	}

	protected function get_tax_query() {
		return $this->get_query_var('tax_query');
	}

	protected function get_meta_query() {
		return $this->get_query_var('meta_query');
	}

	/**
	 * 获取当前tax_query的所有父级term_id
	 * @since 2019.08.09
	 *
	 * @return array $ancestors 当前分类查询的所有父级：$ancestors[$taxonomy] = [$term_id_1, $term_id_2];
	 */
	protected function get_tax_query_ancestors(): array{
		$ancestors = [];

		// 遍历当前tax query是否包含子类
		foreach ($this->get_tax_query() as $tax_query) {
			// WP_Query tax_query参数可能存在：'relation' => 'AND', 'relation' => 'OR',参数，需排除 @since 2019.06.14
			if (!isset($tax_query['terms'])) {
				continue;
			}

			// 递归查询当前分类的父级分类
			$taxonomy             = $tax_query['taxonomy'];
			$ancestors[$taxonomy] = get_ancestors($tax_query['terms'], $taxonomy, 'taxonomy');

			// 排序 @since 0.9.27 取消排序：因为 term id 与所在层级并无关联，如创建 term 后又手动调整将较大的 ID 作为祖先
			// sort($ancestors[$taxonomy]);
		}
		unset($tax_query);

		return $ancestors;
	}

	/**
	 * 统一封装 Tabs 输出：在子类中实现
	 * @since 0.9.25
	 */
	abstract protected function build_tabs(string $key, array $options, string $label, bool $any, array $remove_args = []);

	/**
	 * 获取筛选 Tabs：在子类中实现
	 * @since 0.9.25
	 */
	abstract protected function get_tabs();

	/**
	 * 获取筛选 Posts 集：在子类中实现
	 * @since 0.9.25
	 */
	abstract protected function get_posts();

	/**
	 * 获取完整的查询结果集：通常为 Posts 及 pagination 的合集，根据具体场景在子类中实现
	 * @since 0.9.25
	 */
	abstract protected function get_results();

	/**
	 * 获取筛选分页导航：在子类中实现
	 * @since 0.9.25
	 */
	abstract protected function get_pagination();
}
