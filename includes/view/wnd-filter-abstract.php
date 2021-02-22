<?php
namespace Wnd\View;

use Exception;
use Wnd\Model\Wnd_Tag_Under_Category;
use WP_Query;

/**
 * @since 0.9.25
 * Posts 多重筛选抽象基类
 * - 参数解析
 * - 权限检测
 * - 定义可用的筛选项方法
 * - 执行 WP_Query（仅在非依赖型）
 * - 定义子类中必须实现的抽象方法
 *
 * @param bool 	$independent 	是否为独立 WP Query
 */
abstract class Wnd_Filter_Abstract {

	use Wnd_Filter_Query_Trait;

	// 当前请求基本 URL （移除 WP 默认伪静态分页参数)
	protected $wp_base_url;

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

	/**
	 *Constructor.
	 *
	 *@param bool 	$independent	是否为独立 WP Query
	 */
	public function __construct(bool $independent = true) {
		static::$request_query_vars = static::parse_query_vars();
		$this->independent          = $independent;
		$this->wp_base_url          = get_pagenum_link(1, false);

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
		 *@since 0.8.64
		 *
		 *- 独立型 WP Query：分页需要自定义处理
		 *- 依赖型 WP Query：获取全局 $wp_query，并读取全局查询参数赋值到当前筛选环境，以供构建与之匹配的 tabs
		 */
		if ($this->independent) {
			$this->wp_base_url = remove_query_arg('page', $this->wp_base_url);
			$this->query_args  = array_merge($defaults, static::$request_query_vars);
		} else {
			global $wp_query;
			if (!$wp_query->query_vars) {
				throw new Exception(__('当前环境需执行独立 WP Query', 'wnd'));
			}

			$this->wp_query   = $wp_query;
			$this->query_args = array_merge($defaults, $wp_query->query_vars, static::$request_query_vars);
		}

		/**
		 *定义当前post type的主分类：$category_taxonomy
		 */
		if ($this->query_args['post_type']) {
			$this->category_taxonomy = ('post' == $this->query_args['post_type']) ? 'category' : $this->query_args['post_type'] . '_cat';
		}

		// 权限检测
		$this->check_permission();
	}

	/**
	 *@since 0.9.25
	 *
	 *权限检测：非管理员，仅可查询publish及close状态(作者本身除外)
	 */
	protected function check_permission() {
		if (is_super_admin()) {
			return;
		}

		// 数组查询，如果包含publish及closed之外的状态，指定作者为当前用户
		if (is_array($this->query_args['post_status'])) {
			foreach ($this->query_args['post_status'] as $key => $post_status) {
				if (!in_array($post_status, ['publish', 'wnd-closed'])) {
					if (!is_user_logged_in()) {
						throw new Exception(__('未登录用户，仅可查询公开信息', 'wnd'));
					} else {
						$this->query_args['author'] = get_current_user_id();
					}
					break;
				}
			}unset($key, $post_status);

			// 单个查询
		} elseif (!in_array($this->query_args['post_status'] ?: 'publish', ['publish', 'wnd-closed'])) {
			if (!is_user_logged_in()) {
				throw new Exception(__('未登录用户，仅可查询公开信息', 'wnd'));
			} else {
				$this->query_args['author'] = get_current_user_id();
			}
		}
	}

	/**
	 *@since 2019.07.31
	 *设置ajax post列表嵌入容器
	 *@param int $posts_per_page 每页post数目
	 **/
	public function set_posts_per_page(int $posts_per_page) {
		$this->add_query_vars(['posts_per_page' => $posts_per_page]);
	}

	/**
	 *@param array 	$args 需要筛选的类型数组
	 *@param bool 	$with_any_tab 是否包含全部选项
	 */
	public function add_post_type_filter(array $args = [], bool $with_any_tab = false) {
		/**
		 *若当前请求未指定post_type，设置第一个post_type为默认值；若筛选项也为空，最后默认post
		 *post_type/post_status 在所有筛选中均需要指定默认值，若不指定，WordPress也会默认设定
		 *
		 * 当前请求为包含post_type参数时，当前的主分类（category_taxonomy）无法在构造函数中无法完成定义，需在此处补充
		 */
		if (!$this->query_args['post_type']) {
			$default_type = $with_any_tab ? 'any' : ($args ? reset($args) : 'post');
			$this->add_query_vars(['post_type' => $default_type]);
			$this->category_taxonomy = ('post' == $this->query_args['post_type']) ? 'category' : $this->query_args['post_type'] . '_cat';
		}

		/**
		 *仅筛选项大于2时，构建HTML
		 */
		if (count($args) < 2) {
			return;
		}

		// 构建 Tabs 数据
		$key     = 'type';
		$title   = __('类型：', 'wnd');
		$options = [];
		foreach ($args as $post_type) {
			$post_type                  = get_post_type_object($post_type);
			$options[$post_type->label] = $post_type->name;
		}
		unset($post_type);

		return $this->build_tabs($key, $options, $title, $with_any_tab, ['all']);
	}

	/**
	 *状态筛选
	 *@param array $args 需要筛选的文章状态数组
	 */
	public function add_post_status_filter(array $args = [], bool $with_any_tab = true) {
		$this->add_query_vars(['post_status' => $args]);

		/**
		 *仅筛选项大于2时，构建HTML
		 */
		if (count($args) < 2) {
			return;
		}

		// 构建 Tabs 数据
		$title   = __('状态', 'wnd');
		$key     = 'status';
		$options = $args;
		return $this->build_tabs($key, $options, $title, $with_any_tab);
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

		$this->build_taxonomy_filter($args);

		/**
		 *@since 2019.03.12
		 *遍历当前tax query 查询是否设置了对应的taxonomy查询，若存在则查询其对应子类
		 */
		$taxonomy_query = false;
		foreach ($this->query_args['tax_query'] as $key => $tax_query) {
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
			return;
		}

		// 获取当前taxonomy子类tabs
		$this->get_sub_taxonomy_tabs();
	}

	/**
	 *@since 0.9.25
	 *主分类
	 *
	 **/
	public function add_category_filter(array $args = []) {
		$args['taxonomy'] = $this->category_taxonomy;
		return $this->add_taxonomy_filter($args);
	}

	/**
	 * 标签筛选
	 * 定义taxonomy：{$post_type}.'_tag'
	 * 读取wp_query中tax_query 提取taxonomy为{$post_type}.'_cat'的分类id，并获取对应的关联标签(需启用标签分类关联功能)
	 * 若未设置关联分类，则查询所有热门标签
	 *@since 2019.03.25
	 */
	public function add_tags_filter(int $limit = 10) {
		$this->build_tags_filter($limit);
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
	public function add_meta_filter(array $args, bool $with_any_tab = true) {
		$title = $args['label'];
		$key   = '_meta_' . $args['key'];

		return $this->build_tabs($key, $args['options'], $title, $with_any_tab);
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
	public function add_orderby_filter(array $args, bool $with_any_tab = true) {
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
	public function add_order_filter(array $args, string $label, bool $with_any_tab = true) {
		return $this->build_tabs('order', $args, $label, $with_any_tab);
	}

	/**
	 *@since 2019.03.26
	 *遍历当前查询参数，输出取消当前查询链接
	 */
	public function add_current_filter() {
		return $this->build_current_filter();
	}

	/**
	 *@since 2019.08.09
	 *@param array 		$args  		WordPress get_terms() 参数
	 *@param string 	$class 		额外设置的class
	 *若查询的taxonomy与当前post type未关联，则不输出
	 */
	protected function build_taxonomy_filter(array $args, bool $with_any_tab = true) {
		if (!isset($args['taxonomy'])) {
			return;
		}
		$taxonomy = $args['taxonomy'];
		$terms    = get_terms($args);
		if (!$terms or is_wp_error($terms)) {
			return;
		}

		/**
		 *@since 2019.07.30
		 *如果当前指定的taxonomy并不存在指定的post type中，非ajax环境直接中止，ajax环境中隐藏输出（根据post_type动态切换是否显示）
		 */
		$current_post_type_taxonomies = get_object_taxonomies($this->query_args['post_type'], 'names');
		if (!in_array($taxonomy, $current_post_type_taxonomies)) {
			return;
		}

		/**
		 * 切换主分类时，需要移除分类关联标签查询
		 * @since 2019.07.30
		 */
		if ($taxonomy == $this->category_taxonomy) {
			$remove_query_args = ['_term_' . $this->query_args['post_type'] . '_tag'];
		} else {
			$remove_query_args = [];
		}

		// 输出tabs
		$key     = '_term_' . $taxonomy;
		$options = [];
		$title   = get_taxonomy($taxonomy)->label;
		foreach ($terms as $term) {
			$options[$term->name] = $term->term_id;
		}
		unset($term);

		return $this->build_tabs($key, $options, $title, $with_any_tab, $remove_query_args);
	}

	/**
	 *@since 2019.08.09
	 *构建分类关联标签的HTML
	 */
	protected function build_tags_filter(int $limit = 10) {
		// 标签taxonomy
		$taxonomy = $this->query_args['post_type'] . '_tag';
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
		$category_key = ('category' == $this->category_taxonomy) ? 'category_name' : $this->category_taxonomy;
		if (isset($this->query_args[$category_key])) {
			$category    = get_term_by('slug', $this->query_args[$category_key], $this->category_taxonomy);
			$category_id = $category ? $category->term_id : 0;
		} else {
			foreach ($this->query_args['tax_query'] as $key => $tax_query) {
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
		 *指定category_id时查询关联标签，否则调用热门标签
		 *@since 2019.03.25
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

		$key          = '_term_' . $taxonomy;
		$options      = [];
		$title        = get_taxonomy($taxonomy)->label;
		$with_any_tab = true;

		foreach ($terms as $term) {
			$term->name           = $term->name ?? get_term($term->tag_id, $taxonomy)->name;
			$term->term_id        = $term->term_id ?? $term->tag_id;
			$options[$term->name] = $term->term_id;
		}

		return $this->build_tabs($key, $options, $title, $with_any_tab);
	}

	/**
	 *@since 2019.03.26
	 *遍历当前查询参数，输出取消当前查询链接
	 *
	 *@since 0.9.25  改造尚未完成
	 */
	protected function build_current_filter() {
		if (empty($this->query_args['tax_query']) and empty($this->query_args['meta_query'])) {
			return;
		}
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
			$this->wp_query = new WP_Query($this->query_args);
		}
	}

	/**
	 *@since 2019.08.09
	 *获取当前tax_query的所有父级term_id
	 *@return array $parents 当前分类查询的所有父级：$parents[$taxonomy] = [$term_id_1, $term_id_2];
	 */
	protected function get_tax_query_patents(): array{
		$parents = [];

		// 遍历当前tax query是否包含子类
		foreach ($this->query_args['tax_query'] as $tax_query) {
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
	 *@since 2019.08.09
	 *当前tax query的子类筛选项
	 *
	 *子类查询需要根据当前tax query动态生成
	 *在ajax状态中，需要经由此方法，交付api响应动态生成
	 *
	 *非ajax请求中，add_taxonomy_filter，在选择分类后，自动查询生成子类tabs
	 *
	 *@return array $sub_tabs_array[$taxonomy] = [$sub_tabs];
	 */
	public function get_sub_taxonomy_tabs() {
		$sub_tabs_array = [];

		// 遍历当前tax query是否包含子类
		foreach ($this->query_args['tax_query'] as $tax_query) {
			// WP_Query tax_query参数可能存在：'relation' => 'AND', 'relation' => 'OR',参数，需排除 @since 2019.06.14
			if (!isset($tax_query['terms'])) {
				continue;
			}

			// 查询当前分类的所有上级分类的子分类
			$sub_tabs = [];
			$parents  = $this->get_tax_query_patents()[$tax_query['taxonomy']];
			foreach ($parents as $parent) {
				$args = [
					'taxonomy' => $tax_query['taxonomy'],
					'parent'   => $parent,
				];
				$sub_tabs[] = $this->build_taxonomy_filter($args, 'sub-tabs');
			}
			unset($parent);

			// 当前分类的子类
			$args = [
				'taxonomy' => $tax_query['taxonomy'],
				'parent'   => $tax_query['terms'],
			];
			$sub_tabs[] = $this->build_taxonomy_filter($args, 'sub-tabs');

			// 构造子类查询
			$sub_tabs_array[$tax_query['taxonomy']] = $sub_tabs;
		}
		unset($tax_query);

		return $sub_tabs_array;
	}

	/**
	 *@since 2020.05.11
	 *搜索框：在子类中实现
	 */
	abstract public function add_search_form(string $button = 'Search', string $placeholder = '');

	/**
	 *@since 0.9.25
	 *统一封装 Tabs 输出：在子类中实现
	 */
	abstract protected function build_tabs(string $key, array $options, string $title, bool $with_any_tab, array $remove_query_args = []);

	/**
	 *@since 0.9.25
	 *获取筛选 Tabs：在子类中实现
	 */
	abstract protected function get_tabs();

	/**
	 *@since 0.9.25
	 *获取筛选 Posts 集：在子类中实现
	 */
	abstract protected function get_posts();

	/**
	 *@since 0.9.25
	 *获取完整的查询结果集：通常为 Posts 及 pagination 的合集，根据具体场景在子类中实现
	 */
	abstract protected function get_results();

	/**
	 *@since 0.9.25
	 *获取筛选分页导航：在子类中实现
	 */
	abstract protected function get_pagination();
}
