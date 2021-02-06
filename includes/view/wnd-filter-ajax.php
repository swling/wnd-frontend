<?php

namespace Wnd\View;

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
class Wnd_Filter_Ajax extends Wnd_Filter {

	public $ajax_tabs = [];

	/**
	 *@since 0.9.25
	 *主分类
	 *
	 **/
	public function add_category_filter(array $args = []) {
		$args['taxonomy'] = $this->category_taxonomy;
		return $this->add_taxonomy_filter($args);
	}

	protected function build_tabs(string $key, array $options, string $title, bool $with_any_tab): array{
		if ($with_any_tab) {
			$options['all'] = __('全部', 'wnd');
		}

		$tabs = [
			'key'     => $key,
			'title'   => $title,
			'options' => $options,
		];
		$this->ajax_tabs[] = $tabs;

		return $tabs;
	}

	/**
	 *类型筛选
	 *@param array $args 需要筛选的类型数组 $args = ['post','page']
	 *@param bool 	$with_any_tab 是否包含全部选项
	 */
	protected function build_post_type_filter($args = [], $with_any_tab = false) {
		$title   = __('类型', 'wnd');
		$key     = 'type';
		$options = [];

		foreach ($args as $post_type) {
			$post_type_object                  = get_post_type_object($post_type);
			$options[$post_type_object->label] = $post_type;
		}
		unset($post_type);

		return $this->build_tabs($key, $options, $title, $with_any_tab);
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
	protected function build_post_status_filter($args = [], $with_any_tab = false) {
		$title   = __('状态', 'wnd');
		$key     = 'status';
		$options = $args;
		return $this->build_tabs($key, $options, $title, $with_any_tab);
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
		$taxonomy = $args['taxonomy'];
		$terms    = get_terms($args);
		if (!$terms or is_wp_error($terms)) {
			return;
		}

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

		// 输出tabs
		$key     = '_term_' . $taxonomy;
		$options = [];
		$title   = get_taxonomy($taxonomy)->label;
		foreach ($terms as $term) {
			$options[$term->name] = $term->term_id;
		}
		unset($term);

		return $this->build_tabs($key, $options, $title, false);
	}

	/**
	 *@since 2019.08.09
	 *构建分类关联标签的HTML
	 */
	protected function build_related_tags_filter($limit = 10): array{
		// 标签taxonomy
		$taxonomy = $this->wp_query_args['post_type'] . '_tag';
		if (!taxonomy_exists($taxonomy)) {
			return [];
		}

		// 输出tabs
		$key     = '_term_' . $taxonomy;
		$options = [];
		$title   = get_taxonomy($taxonomy)->label;

		foreach ($this->wp_query_args['tax_query'] as $tax_query) {
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
		unset($tax_query);

		/**
		 *指定category_id时查询关联标签，否则调用热门标签
		 *@since 2019.03.25
		 */
		if (isset($category_id)) {
			$tags = Wnd_Tag_Under_Category::get_tags($category_id, $taxonomy, $limit);
		} else {
			$tags = get_terms($taxonomy, [
				'hide_empty' => true,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => $limit,
			]);
		}

		foreach ($tags as $tag) {
			$name           = $tag->name ?? get_term($tag->tag_id)->name;
			$options[$name] = $tag->term_id ?? $tag->tag_id;
		}unset($tag);

		return $this->build_tabs($key, $options, $title, false);
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
		return $this->ajax_tabs;
		// $tabs = apply_filters('wnd_filter_tabs', $this->tabs, $this->wp_query_args);
		// return '<div id="tabs-' . $this->uniqid . '" class="wnd-filter-tabs ' . $this->class . '"' . $this->build_data_attr() . '>' . $tabs . '</div>';
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
			$this->build_taxonomy_filter($args, 'sub-tabs');

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
			$template    = $this->wp_query_args['wnd_posts_tpl'];
			$this->posts = $template($this->wp_query);
			return $this->posts;
		}

		// post list
		if ($this->wp_query_args['wnd_post_tpl']) {
			$template = $this->wp_query_args['wnd_post_tpl'];
			if ($this->wp_query->have_posts()) {
				while ($this->wp_query->have_posts()): $this->wp_query->the_post();
					global $post;
					$this->posts .= $template($post);
				endwhile;
				wp_reset_postdata(); //重置查询
			}

			return $this->posts;
		}

		// 未设置输出模板，视为 API 请求，输出结果集（供前端可渲染具体 DOM）
		$this->posts = $this->wp_query->posts;
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

	public function get_add_query_vars(): array{
		return $this->add_query;
	}

	/**
	 *@since 0.9.25
	 */
	public function get_filter(): array{
		return [
			'tabs'              => $this->get_tabs(),
			'posts'             => $this->get_posts(),

			/**
			 *@since 2019.08.10
			 *当前post type的主分类筛选项 约定：post(category) / 自定义类型 （$post_type . '_cat'）
			 *
			 *动态插入主分类的情况，通常用在用于一些封装的用户面板：如果用户内容管理面板
			 *常规筛选页面中，应通过add_taxonomy_filter方法添加
			 */
			'category_tabs'     => $this->get_category_tabs(),
			'sub_taxonomy_tabs' => $this->get_sub_taxonomy_tabs(),
			'related_tags_tabs' => $this->get_related_tags_tabs(),
			'pagination'        => $this->get_pagination(),
			'post_count'        => $this->wp_query->post_count,

			/**
			 *当前post type支持的taxonomy
			 *前端可据此修改页面行为
			 */
			'taxonomies'        => get_object_taxonomies($this->wp_query->query_vars['post_type'], 'names'),

			/**
			 *@since 2019.08.10
			 *当前post type的主分类taxonomy
			 *约定：post(category) / 自定义类型 （$post_type . '_cat'）
			 */
			'category_taxonomy' => $this->category_taxonomy,

			'add_query_vars'    => $this->get_add_query_vars(),

			/**
			 *在debug模式下，返回当前WP_Query查询参数
			 **/
			'query_vars'        => WP_DEBUG ? $this->wp_query->query_vars : '请开启Debug',
		];
	}

	public function get_results(): array{
		$structure = $this->get_filter();
		unset($structure['tabs']);
		return $structure;
	}
}
