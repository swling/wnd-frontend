<?php

/**
 * @since 2019.07.30
 * 多重筛选类
 * 样式基于bulma css
 * @param bool $is_ajax 是否为ajax筛选（需要对应的前端支持）
 */
class Wnd_Filter {

	// 筛选项HTML
	public $tabs = '';

	// 筛选结果HTML
	public $posts = '';

	// bool 是否ajax
	public $is_ajax;

	// string html class
	public $class;

	/**
	 * 每次请求携带的固定的查询参数
	 * 将在筛选容器，及分页容器上出现，以绑定点击事件，发送到api接口
	 * 以data-{key}="{value}"形式出现，ajax请求中，将转化为 url请求参数 ?{key}={value}
	 */
	public $const_query = array();

	// 筛选参数
	public $post_type_filter_args;
	public $post_status_filter_args;
	public $taxonomy_filter_args;
	public $related_tags_filter_args;
	public $meta_filter_args;
	public $orderby_filter_args;

	// 默认切换筛选项时需要移除的参数
	public $remove_query_args = array('paged', 'page');

	/**
	 *根据配置设定的wp_query查询参数
	 *默认值将随用户设定而改变
	 *
	 *参数中包含自定义的非wp_query参数以"wnd"前缀区分
	 */
	public $wp_query_args = array(
		'orderby' => 'date',
		'order' => 'DESC',
		'meta_query' => array(),
		'tax_query' => array(),
		'meta_key' => '',
		'meta_value' => '',
		'post_type' => '',
		'post_status' => 'publish',
		'no_found_rows' => true,
		'paged' => 1,

		// 自定义
		'wnd_ajax_container' => '',
		'wnd_post_tpl' => '',
	);

	/**
	 *WP_Query 查询结果：
	 *@see $this->query();
	 */
	public $wp_query;

	/**
	 * Constructor.
	 *
	 * @param bool $is_ajax 是否为ajax查询
	 */
	public function __construct($is_ajax = false) {
		$this->is_ajax = $is_ajax;
		$this->class .= $this->is_ajax ? 'ajax-filter' : '';

		// 解析GET参数为wp_query参数并与默认参数合并
		$this->wp_query_args = wp_parse_args($this->parse_url_to_wp_query(), $this->wp_query_args);

		// 非管理员除，仅可查询当前用户自己的非公开post
		if ($this->wp_query_args['post_status'] != 'publish' and !is_super_admin()) {
			if (!is_user_logged_in()) {
				throw new Exception('未登录用户，仅可查询公开信息！');
			}
			$this->wp_query_args['author'] = get_current_user_id();
		}
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
	 *ajax发送到api接口，再通过parse_url_to_wp_query() 解析后，写入$wp_query_args[key]=value
	 **/
	public function add_query($query = array()) {
		foreach ($query as $key => $value) {
			$this->wp_query_args[$key] = $value;

			// 在html data属性中新增对应属性，以实现在ajax请求中同步添加参数
			$this->const_query[$key] = $value;
		}
		unset($key, $value);
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
	 *@param string $container posts列表ajax嵌入容器
	 **/
	public function set_posts_per_page($posts_per_page) {
		$this->add_query(array('posts_per_page' => $posts_per_page));
	}

	/**
	 *@since 2019.08.02
	 *设置列表post样式函数
	 *@param string $template posts模板 函数名
	 **/
	public function set_post_template($template) {
		$this->add_query(array('wnd_post_tpl' => $template));
	}

	/**
	 *@param array $args 需要筛选的类型数组
	 */
	public function add_post_type_filter($args = array()) {

		// 属性赋值以供其他方法查询
		$this->post_type_filter_args = $args;

		/**
		 *若当前请求未指定post_type，设置第一个post_type为默认值；若筛选项也为空，最后默认post
		 *post_type/post_status 在所有筛选中均需要指定默认值，若不指定，WordPress也会默认设定
		 */
		$default_type = $this->wp_query_args['post_type'] ?: ($args ? reset($args) : 'post');
		$this->add_query(array('post_type' => $default_type));

		// 需要移除的查询参数
		$remove_query_args = array_merge(array('orderby', 'order', 'status'), $this->remove_query_args);

		// 若筛选项少于2个，即无需筛选post type：隐藏tabs
		$tabs = '<div class="tabs is-boxed post-type-tabs ' . (count($args) < 2 ? 'is-hidden' : '') . '">';
		$tabs .= '<ul class="tab">';

		// 输出tabs
		foreach ($args as $post_type) {
			// 根据类型名，获取完整的类型信息
			$post_type = get_post_type_object($post_type);
			$class = 'post-type-' . $post_type->name;
			$class .= ($this->wp_query_args['post_type'] == $post_type->name) ? ' is-active' : '';

			/**
			 *@since 2019.02.27
			 *切换类型时，需要从当前网址移除的参数（用于在多重筛选时，移除仅针对当前类型有效的参数）
			 *切换post type时移除term / orderby / order / status
			 *taxonomy filter 生成的GET参数为：'_term_' . $taxonomy
			 */
			if (isset($this->wp_query_args['post_type'])) {
				$taxonomies = get_object_taxonomies($this->wp_query_args['post_type'], $output = 'names');
				if ($taxonomies) {
					foreach ($taxonomies as $taxonomy) {
						array_push($remove_query_args, '_term_' . $taxonomy);
					}
					unset($taxonomy);
				}
			}

			/**
			 *@since 2019.3.14 移除meta查询
			 */
			foreach ($_GET as $key => $value) {
				if (strpos($key, '_meta_') === 0) {
					array_push($remove_query_args, $key);
					continue;
				}
				if (strpos($key, 'meta_') === 0) {
					array_push($remove_query_args, $key);
					continue;
				}
			}
			unset($key, $value);

			$tabs .= '<li class="' . $class . '">';
			$tabs .= '<a data-key="type" data-value="' . $post_type->name . '" href="' . add_query_arg('type', $post_type->name, remove_query_arg($remove_query_args)) . '">' . $post_type->label . '</a>';
			$tabs .= '</li>';

		}
		unset($post_type);

		// 输出结束
		$tabs .= '</ul>';
		$tabs .= '</div>';
		$this->tabs .= $tabs;

		return $tabs;
	}

	/**
	 *状态筛选
	 *@param array $args 需要筛选的文章状态数组
	 */
	public function add_post_status_filter($args = array()) {

		$this->post_status_filter_args = $args;

		/**
		 *若当前请求未指定post_status，设置第一个post_status为默认值；若筛选项也为空，最后默认publish
		 *post_type/post_status 在所有筛选中均需要指定默认值，若不指定，WordPress也会默认设定
		 */
		$default_status = $this->wp_query_args['status'] ?? ($args ? reset($args) : 'publish');
		$this->add_query(array('post_status' => $default_status));

		// 输出容器
		$tabs = '<div class="columns is-marginless is-vcentered post-status-tabs ' . (count($args) < 2 ? 'is-hidden' : '') . '">';
		$tabs .= '<div class="column is-narrow">' . get_post_type_object($this->wp_query_args['post_type'])->label . '状态：</div>';
		$tabs .= '<div class="tabs column">';
		$tabs .= '<div class="tabs">';
		$tabs .= '<ul class="tab">';

		/**
		 * 全部选项
		 */
		$all_active = 'any' == $this->wp_query_args['post_status'] ? 'class="is-active"' : null;
		$tabs .= '<li ' . $all_active . '><a data-key="status" data-value="" href="' . remove_query_arg('status', remove_query_arg($this->remove_query_args)) . '">全部</a></li>';

		// 输出tabs
		foreach ($args as $label => $post_status) {
			$class = 'post-status-' . $post_status;
			$class .= (isset($this->wp_query_args['post_status']) and $this->wp_query_args['post_status'] == $post_status) ? ' is-active' : '';

			$tabs .= '<li class="' . $class . '">';
			$tabs .= '<a data-key="status" data-value="' . $post_status . '" href="' . add_query_arg('status', $post_status, remove_query_arg($this->remove_query_args)) . '">' . $label . '</a>';
			$tabs .= '</li>';
		}
		unset($label, $post_status);

		// 输出结束
		$tabs .= '</ul>';
		$tabs .= '</div>';
		$tabs .= '</div>';
		$tabs .= '</div>';

		$this->tabs .= $tabs;
		return $tabs;
	}

	/**
	 *@since 2019.02.28
	 *@param $args 	array get_terms 参数
	 *若查询的taxonomy与当前post type未关联，则不输出
	 */
	public function add_taxonomy_filter($args = array()) {

		$this->taxonomy_filter_args = $args;
		$taxonomy = $args['taxonomy'];

		/**
		 *@since 2019.07.30
		 *如果当前指定的taxonomy并不存在指定的post type中，非ajax环境直接中止，ajax环境中隐藏输出（根据post_type动态切换是否显示）
		 */
		$hidden_class = '';
		$current_post_type_taxonomies = get_object_taxonomies($this->wp_query_args['post_type'], $output = 'names');
		if (!in_array($taxonomy, $current_post_type_taxonomies)) {
			if (!$this->is_ajax) {
				return;
			} else {
				$hidden_class = 'is-hidden';
			}
		}

		/**
		 * 切换分类时，需要移除关联分类查询
		 * @since 2019.07.30
		 */
		$remove_query_args = array_merge(array('_term_' . $this->wp_query_args['post_type'] . '_tag'), $this->remove_query_args);

		/**
		 * 遍历当前tax query 查询是否设置了对应的taxonomy查询
		 */
		$all_active = 'class="is-active"';
		foreach ($this->wp_query_args['tax_query'] as $key => $tax_query) {
			// WP_Query tax_query参数可能存在：'relation' => 'AND', 'relation' => 'OR',参数，需排除 @since 2019.06.14
			if (!is_array($tax_query)) {
				continue;
			}

			// 当前taxonomy在tax query中是否已设置参数，若设置，取消全部选项class: is-active
			if (array_search($taxonomy, $tax_query) !== false) {
				$all_active = '';
				continue;
			}
		}
		unset($key, $tax_query);

		// 输出容器
		$tabs = '<div class="columns is-marginless is-vcentered taxonomy-tabs ' . $taxonomy . '-tabs ' . $hidden_class . '">';
		$tabs .= '<div class="column is-narrow ' . $taxonomy . '-label">' . get_taxonomy($taxonomy)->label . '：</div>';
		$tabs .= '<div class="tabs column">';
		$tabs .= '<ul class="tab">';

		/**
		 * 全部选项
		 * @since 2019.03.07
		 */
		$tabs .= '<li ' . $all_active . '><a data-key="_term_' . $taxonomy . '" data-value="" href="' . remove_query_arg('_term_' . $taxonomy, remove_query_arg($remove_query_args)) . '">全部</a></li>';

		// 输出tabs
		foreach (get_terms($args) as $term) {

			$class = 'term-id-' . $term->term_id;
			// 遍历当前tax query查询是否匹配当前tab
			if (isset($this->wp_query_args['tax_query'])) {
				foreach ($this->wp_query_args['tax_query'] as $tax_query) {
					// WP_Query tax_query参数可能存在：'relation' => 'AND', 'relation' => 'OR',参数，需排除 @since 2019.06.14
					if (!is_array($tax_query)) {
						continue;
					}

					// 查询父级分类
					$current_parent = get_term($tax_query['terms'])->parent;
					if ($tax_query['terms'] == $term->term_id or $term->term_id == $current_parent) {
						$class .= ' is-active';
						// 当前一级分类处于active，对应term id将写入父级数组、用于下一步查询当前分类是否具有子分类
						$current_top_term[$taxonomy] = $term->term_id;
					}
				}
				unset($tax_query);
			}

			// 本层循环只展示一级分类
			if ($term->parent) {
				continue;
			}

			/**
			 *categories tabs生成的GET参数为：'_term_' . $taxonomy，如果直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
			 */
			$tabs .= '<li class="' . $class . '"><a data-key="_term_' . $taxonomy . '" data-value="' . $term->term_id . '" href="' . add_query_arg('_term_' . $args['taxonomy'], $term->term_id, remove_query_arg($remove_query_args)) . '">' . $term->name . '</a></li>';

		}
		unset($term);

		// 输出结束
		$tabs .= '</ul>';
		$tabs .= '</div>';
		$tabs .= '</div>';

		/**
		 * @since 2019.03.12 当前分类的子分类
		 */
		if (!isset($current_top_term[$taxonomy])) {
			$this->tabs .= $tabs;
			return;
		}

		$child_terms = get_terms(array('taxonomy' => $taxonomy, 'parent' => $current_top_term[$taxonomy]));
		if (!$child_terms) {
			$this->tabs .= $tabs;
			return;
		}

		$tabs .= '<div class="columns is-marginless is-vcentered">';
		$tabs .= '<div class="column is-narrow">当前子类：</div>';
		$tabs .= '<div class="column">';
		$tabs .= '<div class="tabs">';
		$tabs .= '<ul class="tab">';
		foreach ($child_terms as $child_term) {
			$child_class = 'term-id-' . $child_term->term_id;

			// 遍历当前tax query查询是否匹配当前tab
			if (isset($this->wp_query_args['tax_query'])) {
				foreach ($this->wp_query_args['tax_query'] as $tax_query) {
					if ($tax_query['terms'] == $child_term->term_id) {
						$child_class .= ' is-active';
					}
				}
				unset($tax_query);
			}

			/**
			 *categories tabs生成的GET参数为：'_term_' . $taxonomy，如果直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
			 */
			$tabs .= '<li class="' . $child_class . '"><a href="' . add_query_arg('_term_' . $taxonomy, $child_term->term_id, remove_query_arg($remove_query_args)) . '">' . $child_term->name . '</a></li>';
		}
		unset($child_term);
		$tabs .= '</ul>';
		$tabs .= '</div>';
		$tabs .= '</div>';
		$tabs .= '</div>';

		$this->tabs .= $tabs;
		return $tabs;
	}

	/**
	 * 标签筛选
	 * 定义taxonomy：{$post_type}.'_tag'
	 * 读取wp_query中tax_query 提取taxonomy为{$post_type}.'_cat'的分类id，并获取对应的关联标签(需启用标签分类关联功能)
	 * 若未设置关联分类，则查询所有热门标签
	 *@since 2019.03.25
	 */
	public function add_related_tags_filter($limit = 10) {

		$this->related_tags_filter_args = $limit;

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
		$all_active = 'class="is-active"';
		foreach ($this->wp_query_args['tax_query'] as $key => $tax_query) {

			// WP_Query tax_query参数可能存在：'relation' => 'AND', 'relation' => 'OR',参数，需排除 @since 2019.06.14
			if (!is_array($tax_query)) {
				continue;
			}

			//遍历当前tax query 获取post type的category(格式$post_type.'_cat')	@since 2019.03.25
			if (array_search($this->wp_query_args['post_type'] . '_cat', $tax_query) !== false) {
				$category_id = $tax_query['terms'];
				continue;
			}

			// 当前标签在tax query中的键名
			if (array_search($taxonomy, $tax_query) !== false) {
				$taxonomy_query_key = $key;
				$all_active = '';
				continue;
			}
		}
		unset($key, $tax_query);

		// 输出容器
		$tabs = '<div class="columns is-marginless is-vcentered taxonomy-tabs ' . $taxonomy . '-tabs">';
		$tabs .= '<div class="column is-narrow ' . $taxonomy . '-label">' . get_taxonomy($taxonomy)->label . '：</div>';
		$tabs .= '<div class="tabs column">';
		$tabs .= '<ul class="tab">';

		/**
		 * 全部选项
		 * @since 2019.03.07
		 */
		$tabs .= '<li ' . $all_active . '><a data-key="_term_' . $taxonomy . '" data-value="" href="' . remove_query_arg('_term_' . $taxonomy, remove_query_arg($this->remove_query_args)) . '">全部</a></li>';

		/**
		 *指定category_id时查询关联标签，否则调用热门标签
		 *@since 2019.03.25
		 */
		if (isset($category_id)) {
			$tags = wnd_get_tags_under_category($category_id, $taxonomy, $limit);
		} else {
			$tags = get_terms($taxonomy, array(
				'hide_empty' => false,
				'orderby' => 'count',
				'order' => 'DESC',
				'number' => $limit,

			));
		}

		// 输出tabs
		foreach ($tags as $tag) {
			$term = isset($category_id) ? get_term($tag->tag_id) : $tag;
			// 遍历当前tax query查询是否匹配当前tab
			$class = '';

			if (isset($this->wp_query_args['tax_query'])) {
				foreach ($this->wp_query_args['tax_query'] as $tax_query) {
					$class .= 'term-id-' . $term->term_id;

					// WP_Query tax_query参数可能存在：'relation' => 'AND', 'relation' => 'OR',参数，需排除 @since 2019.06.14
					if (!is_array($tax_query)) {
						continue;
					}

					if ($tax_query['terms'] == $term->term_id) {
						$class .= ' is-active';
					}
				}
				unset($tax_query);

			}

			/**
			 *categories tabs生成的GET参数为：'_term_' . $taxonomy，如果直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
			 */
			$tabs .= '<li class="' . $class . '"><a data-key="_term_' . $taxonomy . '" data-value="' . $term->term_id . '" href="' . add_query_arg('_term_' . $taxonomy, $term->term_id, remove_query_arg($this->remove_query_args)) . '">' . $term->name . '</a></li>';

		}
		unset($tag);

		// 输出结束
		$tabs .= '</ul>';
		$tabs .= '</div>';
		$tabs .= '</div>';

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

		$this->meta_filter_args = $args;

		/**
		 *查找在当前的meta_query查询参数中，当前meta key的键名，如果没有则加入
		 *meta_query是一个无键名的数组，无法根据键名合并，因此需要准确定位
		 *(数组默认键值从0开始， 当首元素即匹配则array_search返回 0，此处需要严格区分 0 和 false)
		 *@since 2019.03.07（copy）
		 */
		$all_active = 'class="is-active"';
		foreach ($this->wp_query_args['meta_query'] as $key => $meta_query) {

			// 当前键名
			if (array_search($args['key'], $meta_query) !== false) {
				$all_active = '';
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
		$tabs .= '<li ' . $all_active . '><a data-key="_meta_' . $args['key'] . '" data-value="" href="' . remove_query_arg('_meta_' . $args['key'], remove_query_arg($this->remove_query_args)) . '">全部</a></li>';

		// 输出tabs
		foreach ($args['options'] as $key => $value) {

			// 遍历当前meta query查询是否匹配当前tab
			$active = '';
			if (isset($this->wp_query_args['meta_query'])) {
				foreach ($this->wp_query_args['meta_query'] as $meta_query) {
					if ($meta_query['compare'] != 'exists' and $meta_query['value'] == $value) {
						$active = 'class="is-active"';
						// meta query compare 为 exists时，没有value值，仅查询是否包含对应key值
					} elseif ($meta_query['key'] = $args['key']) {
						$active = 'class="is-active"';
					}
				}
				unset($meta_query);
			}

			/**
			 *meta_query GET参数为：_meta_{key}?=
			 */
			$tabs .= '<li ' . $active . '><a data-key="_meta_' . $args['key'] . '" data-value="' . $value . '" href="' . add_query_arg('_meta_' . $args['key'], $value, remove_query_arg($this->remove_query_args)) . '">' . $key . '</a></li>';
		}
		unset($key, $value);

		// 输出结束
		$tabs .= '</ul>';
		$tabs .= '</div>';
		$tabs .= '</div>';

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

		$this->orderby_filter_args = $args;

		// 移除选项
		$remove_query_args = array_merge(array('orderby', 'order', 'meta_key'), $this->remove_query_args);

		// 全部
		$all_active = 'class="is-active"';
		if (isset($this->wp_query_args['orderby']) and $this->wp_query_args['orderby'] != 'post_date') {
			$all_active = '';
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
		$tabs .= '<li ' . $all_active . '><a data-key="orderby" data-value="" href="' . remove_query_arg($remove_query_args) . '">默认</a></li>';

		// 输出tabs
		foreach ($args['options'] as $key => $orderby) {

			// 查询当前orderby是否匹配当前tab
			$active = '';
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
						$active = 'class="is-active"';
					}
					// 常规排序
				} else {
					if ($orderby == $this->wp_query_args['orderby']) {
						$active = 'class="is-active"';
					}
				}

			}

			// data-key="orderby" data-value="' . http_build_query($query_arg) . '"
			$query_arg = is_array($orderby) ? $orderby : array('orderby' => $orderby);
			$tabs .= '<li ' . $active . '><a data-key="orderby" data-value="' . http_build_query($query_arg) . '" href="' . add_query_arg($query_arg, remove_query_arg($remove_query_args)) . '">' . $key . '</a></li>';
		}
		unset($key, $orderby);

		// 输出结束
		$tabs .= '</ul>';
		$tabs .= '</div>';
		$tabs .= '</div>';

		$this->tabs .= $tabs;
		return $tabs;
	}

	/**
	 *@since 2019.03.26
	 *遍历当前查询参数，输出取消当前查询链接
	 */
	public function add_current_filter() {

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
			if (!is_array($tax_query)) {
				continue;
			}

			$term = get_term($tax_query['terms']);

			/**
			 *categories tabs生成的GET参数为：'_term_' . $taxonomy，如果直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
			 */
			$tabs .= '<span class="tag">' . $term->name . '<a data-key="_term_' . $term->taxonomy . '" data-value="" class="delete is-small" href="' . remove_query_arg('_term_' . $term->taxonomy, remove_query_arg($this->remove_query_args)) . '"></a></span>&nbsp;&nbsp;';
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
			$tabs .= '<span class="tag">' . $key . '<a data-key="_meta_' . $this->meta_filter_args['key'] . '" data-value="" class="delete is-small" href="' . remove_query_arg('_meta_' . $this->meta_filter_args['key'], remove_query_arg($this->remove_query_args)) . '"></a></span>&nbsp;&nbsp;';

		}
		unset($key, $meta_query);

		// 输出结束
		$tabs .= '</div>';
		$tabs .= '</div>';

		$this->tabs .= $tabs;
		return $tabs;
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
	public function parse_url_to_wp_query() {

		if (empty($_GET)) {
			return array();
		}

		foreach ($_GET as $key => $value) {

			/**
			 *post type tabs生成的GET参数为：type={$post_type}
			 *直接用 post_type 作为参数会触发WordPress原生请求导致错误
			 */
			if ('type' === $key) {
				$this->wp_query_args['post_type'] = $value;
				continue;
			}

			/**
			 *post status tabs生成的GET参数为：status={$post_status}
			 */
			if ('status' === $key) {
				$this->wp_query_args['post_status'] = $value;
				continue;
			}

			/**
			 *@since 2019.3.07 自动匹配meta query
			 *?_meta_price=1 则查询 price = 1的文章
			 *?_meta_price=exists 则查询 存在price的文章
			 */
			if (strpos($key, '_meta_') === 0) {
				$key = str_replace('_meta_', '', $key);
				$compare = $value == 'exists' ? 'exists' : '=';
				$meta_query = array(
					'key' => $key,
					'value' => $value,
					'compare' => $compare,
				);

				/**
				 *@since 2019.04.21 当meta_query compare == exists 不能设置value
				 */
				if ('exists' == $compare) {
					unset($meta_query['value']);
				}

				array_push($this->wp_query_args['meta_query'], $meta_query);
				continue;
			}

			/**
			 *categories tabs生成的GET参数为：'_term_' . $taxonomy，
			 *直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
			 */
			if (strpos($key, '_term_') === 0) {
				$term_query = array(
					'taxonomy' => str_replace('_term_', '', $key),
					'field' => 'term_id',
					'terms' => $value,
				);
				array_push($this->wp_query_args['tax_query'], $term_query);
				continue;
			}

			/**
			 *@since 2019.05.31 post field查询
			 */
			if (strpos($key, '_post_') === 0) {
				$this->wp_query_args[str_replace('_post_', '', $key)] = $value;
				continue;
			}

			/**
			 *@since 2019.07.30
			 *分页
			 */
			if ('page' == $key) {
				$this->wp_query_args['paged'] = $value ?: 1;
				continue;
			}

			/**
			 *@since 2019.08.04
			 *ajax中，orderby将发送数组形式的信息而非单个
			 */
			if ('orderby' == $key and $this->is_ajax) {
				$this->wp_query_args = wp_parse_args($value, $this->wp_query_args);
				continue;
			}

			// 其他、按键名自动匹配、排除指定作者的参数
			if ($key != 'author') {
				$this->wp_query_args[$key] = $value;
				continue;
			}

		}
		unset($key, $value);

		return $this->wp_query_args;
	}

	/**
	 *@since 2019.08.01
	 *执行查询
	 */
	public function query() {
		$this->wp_query = new WP_Query($this->wp_query_args);
	}

	/**
	 *
	 *@since 2019.08.02
	 *构造HTML data属性
	 *获取查询常量，并转化为html data属性，供前端读取后在ajax请求中发送到api
	 */
	public function build_html_data() {
		$data = '';
		foreach ($this->const_query as $key => $value) {
			$data .= 'data-' . $key . '="' . $value . '" ';
		}

		return $data;
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
		return '<div class="wnd-filter-tabs ' . $this->class . '" ' . $this->build_html_data() . '>' . $this->tabs . '</div>';
	}

	/**
	 *@since 2019.07.31
	 *获取筛结果HTML
	 *
	 *合并返回：文章列表及分页导航
	 */
	public function get_results() {
		return $this->get_posts() . $this->get_pagination($show_page = 5);
	}

	/**
	 *@since 2019.07.31
	 *获取筛结果HTML
	 */
	public function get_posts() {
		if (!$this->wp_query) {
			return '未执行WP_Query';
		}

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

	/**
	 *@since 2019.02.15 简单分页导航
	 *不查询总数的情况下，简单实现下一页翻页
	 *翻页参数键名page 不能设置为 paged 会与原生WordPress翻页机制产生冲突
	 */
	public function get_pagination($show_page = 5) {
		if (!$this->wp_query and !$this->is_ajax) {
			return '未执行WP_Query';
		}

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
			$html = '<nav class="pagination is-centered ' . $this->class . '" ' . $this->build_html_data() . '>';
			$html .= '<ul class="pagination-list">';

			if ($paged >= 2) {
				$html .= '<li><a data-key="page" data-value="' . ($paged - 1) . '" class="pagination-previous" href="' . add_query_arg('page', $paged - 1) . '">上一页</a>';
			}
			if ($this->wp_query->post_count >= $this->wp_query->query_vars['posts_per_page']) {
				$html .= '<li><a data-key="page" data-value="' . ($paged + 1) . '" class="pagination-next" href="' . add_query_arg('page', $paged + 1) . '">下一页</a>';
			}
			$html .= '</ul>';
			$html .= '</nav>';

			return $html;

		} else {
			/**
			 *常规分页，需要查询文章总数
			 *据称，在数据量较大的站点，查询文章总数会较为费时
			 */

			$html = '<div class="pagination is-centered ' . $this->class . '" ' . $this->build_html_data() . '>';

			if ($paged > 1) {
				$html .= '<a data-key="page" data-value="' . ($paged - 1) . '" class="pagination-previous" href="' . add_query_arg('page', $paged - 1) . '">上一页</a>';
			} else {
				$html .= '<a class="pagination-previous" disabled="disabled">第一页</a>';
			}
			if ($paged < $this->wp_query->max_num_pages) {
				$html .= '<a data-key="page" data-value="' . ($paged + 1) . '" class="pagination-next" href="' . add_query_arg('page', $paged + 1) . '">下一页</a>';
			}

			$html .= '<ul class="pagination-list">';
			$html .= '<li><a data-key="page" data-value="" class="pagination-link" href="' . remove_query_arg('page') . '" >首页</a></li>';
			for ($i = $paged - 1; $i <= $paged + $show_page; $i++) {
				if ($i > 0 && $i <= $this->wp_query->max_num_pages) {
					if ($i == $paged) {
						$html .= '<li><a data-key="page" data-value="' . $i . '" class="pagination-link is-current" href="' . add_query_arg('page', $i) . '"> <span>' . $i . '</span> </a></li>';
					} else {
						$html .= '<li><a data-key="page" data-value="' . $i . '" class="pagination-link" href="' . add_query_arg('page', $i) . '"> <span>' . $i . '</span> </a></li>';
					}
				}
			}
			if ($paged < $this->wp_query->max_num_pages - 3) {
				$html .= '<li><span class="pagination-ellipsis">&hellip;</span></li>';
			}

			$html .= '<li><a data-key="page" data-value="' . $this->wp_query->max_num_pages . '" class="pagination-link" href="' . add_query_arg('page', $this->wp_query->max_num_pages) . '">尾页</a></li>';
			$html .= '</ul>';

			$html .= '</div>';

			return $html;
		}
	}

}
