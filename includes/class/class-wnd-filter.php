<?php

/**
 * @since 2019.07.30
 * 多重筛选类
 */
class Wnd_Filter {

	public $html = '';

	public $post_type_filter_args;
	public $post_status_filter_args;
	public $taxonomy_filter_args;
	public $related_tags_filter_args;
	public $meta_filter_args;
	public $orderby_filter_args;

	public $remove_query_arg = array('paged', 'pages');
	public $wp_query_args = array(
		'orderby' => 'date',
		'order' => 'DESC',
		'meta_query' => array(),
		'tax_query' => array(),
		'meta_key' => '',
		'meta_value' => '',
		'post_type' => 'post',
		'post_status' => 'publish',
	);

	public $is_ajax;

	function __construct($is_ajax = false) {
		
		$this->wp_query_args = wp_parse_args(wnd_parse_http_wp_query(array()), $this->wp_query_args);
		$this->is_ajax = $is_ajax;

		// 仅可查询当前用户自己的非公开post，管理员除外
		if ($this->wp_query_args['post_status'] != 'publish' and !is_super_admin()) {
			if (!is_user_logged_in()) {
				throw new Exception('未登录用户，仅可查询公开信息！');
			}
			$this->wp_query_args['author'] = get_current_user_id();
		}
	}

	/**
	 *@since 2019.7.31
	 *设置输出模板函数
	 **/
	public function set_post_template() {

	}

	/**
	 *@since 2019.07.31
	 *设置post列表嵌入容器
	 **/
	public function set_post_container() {

	}

	public function get_tabs() {

		if ($this->is_ajax) {
			return '<div class="wnd-filter-tabs is-ajax">' . $this->html . '</div>';
		} else {
			return '<div class="wnd-filter-tabs">' . $this->html . '</div>';
		}
	}

	/**
	 *@param array $args 需要筛选的类型数组
	 */
	public function add_post_type_filter($args = array()) {

		$this->post_type_filter_args = $args;

		$html = '<div class="tabs is-boxed post-type-tabs">';
		$html .= '<ul class="tab">';

		// 输出tabs
		foreach ($args as $post_type) {

			// 根据类型名，获取完整的类型信息
			$post_type = get_post_type_object($post_type);

			$class = 'post-type-' . $post_type->name;
			$class .= (isset($this->wp_query_args['post_type']) and $this->wp_query_args['post_type'] == $post_type->name) ? ' is-active' : '';

			/**
			 *@since 2019.02.27
			 * 切换类型时，需要从当前网址移除的参数（用于在多重筛选时，移除仅针对当前类型有效的参数）
			 *切换post type时移除term / orderby / order
			 *taxonomy filter 生成的GET参数为：'_term_' . $taxonomy
			 */
			$remove_query_arg = array_merge(array('orderby', 'order'), $this->remove_query_arg);
			if (isset($this->wp_query_args['post_type'])) {
				$taxonomies = get_object_taxonomies($this->wp_query_args['post_type'], $output = 'names');
				if ($taxonomies) {
					foreach ($taxonomies as $taxonomy) {
						array_push($remove_query_arg, '_term_' . $taxonomy);
					}
					unset($taxonomy);
				}
			}

			/**
			 *@since 2019.3.14 移除meta查询
			 */
			foreach ($_GET as $key => $value) {
				if (strpos($key, '_meta_') === 0) {
					array_push($remove_query_arg, $key);
					continue;
				}
				if (strpos($key, 'meta_') === 0) {
					array_push($remove_query_arg, $key);
					continue;
				}
			}
			unset($key, $value);

			$html .= '<li class="' . $class . '">';
			$html .= '<a data-key="type" data-value="' . $post_type->name . '" href="' . add_query_arg('type', $post_type->name, remove_query_arg($remove_query_arg)) . '">' . $post_type->label . '</a>';
			$html .= '</li>';

		}
		unset($post_type);

		// 输出结束
		$html .= '</ul>';
		$html .= '</div>';
		$this->html .= $html;

		return $html;
	}

	/**
	 *状态筛选
	 *@param array $args 需要筛选的文章状态数组
	 */
	public function add_post_status_filter($args = array()) {

		$this->post_status_filter_args = $args;

		// 输出容器
		$html = '<div class="columns is-marginless is-vcentered post-status-tabs">';
		$html .= '<div class="column is-narrow">' . get_post_type_object($this->wp_query_args['post_type'])->label . '状态：</div>';
		$html .= '<div class="tabs column">';
		$html .= '<div class="tabs">';
		$html .= '<ul class="tab">';

		/**
		 * 全部选项
		 */
		$all_active = 'any' == $this->wp_query_args['post_status'] ? 'class="is-active"' : null;
		$html .= '<li ' . $all_active . '><a data-key="status" data-value="" href="' . remove_query_arg('_post_post_status', remove_query_arg($this->remove_query_arg)) . '">全部</a></li>';

		// 输出tabs
		foreach ($args as $label => $post_status) {

			$class = 'post-status-' . $post_status;
			$class .= (isset($this->wp_query_args['post_status']) and $this->wp_query_args['post_status'] == $post_status) ? ' is-active' : '';

			$html .= '<li class="' . $class . '">';
			$html .= '<a data-key="status" data-value="' . $post_status . '" href="' . add_query_arg('_post_post_status', $post_status, remove_query_arg($this->remove_query_arg)) . '">' . $label . '</a>';
			$html .= '</li>';

		}
		unset($label, $post_status);

		// 输出结束
		$html .= '</ul>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		$this->html .= $html;

		return $html;

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
		$remove_query_arg = array_merge(array('_term_' . $this->wp_query_args['post_type'] . '_tag'), $this->remove_query_arg);

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
		$html = '<div class="columns is-marginless is-vcentered taxonomy-tabs ' . $taxonomy . '-tabs ' . $hidden_class . '">';
		$html .= '<div class="column is-narrow ' . $taxonomy . '-label">' . get_taxonomy($taxonomy)->label . '：</div>';
		$html .= '<div class="tabs column">';
		$html .= '<ul class="tab">';

		/**
		 * 全部选项
		 * @since 2019.03.07
		 */
		$html .= '<li ' . $all_active . '><a data-key="_term_' . $taxonomy . '" data-value="" href="' . remove_query_arg('_term_' . $taxonomy, remove_query_arg($remove_query_arg)) . '">全部</a></li>';

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
			$html .= '<li class="' . $class . '"><a data-key="_term_' . $taxonomy . '" data-value="' . $term->term_id . '" href="' . add_query_arg('_term_' . $args['taxonomy'], $term->term_id, remove_query_arg($remove_query_arg)) . '">' . $term->name . '</a></li>';

		}
		unset($term);

		// 输出结束
		$html .= '</ul>';
		$html .= '</div>';
		$html .= '</div>';

		/**
		 * @since 2019.03.12 当前分类的子分类
		 */
		if (!isset($current_top_term[$taxonomy])) {
			$this->html .= $html;
			return;
		}

		$child_terms = get_terms(array('taxonomy' => $taxonomy, 'parent' => $current_top_term[$taxonomy]));
		if (!$child_terms) {
			$this->html .= $html;
			return;
		}

		$html .= '<div class="columns is-marginless is-vcentered">';
		$html .= '<div class="column is-narrow">当前子类：</div>';
		$html .= '<div class="column">';
		$html .= '<div class="tabs">';
		$html .= '<ul class="tab">';
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
			$html .= '<li class="' . $child_class . '"><a href="' . add_query_arg('_term_' . $taxonomy, $child_term->term_id, remove_query_arg($remove_query_arg)) . '">' . $child_term->name . '</a></li>';
		}
		unset($child_term);
		$html .= '</ul>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		$this->html .= $html;

		return $html;

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
		$html = '<div class="columns is-marginless is-vcentered taxonomy-tabs ' . $taxonomy . '-tabs">';
		$html .= '<div class="column is-narrow ' . $taxonomy . '-label">' . get_taxonomy($taxonomy)->label . '：</div>';
		$html .= '<div class="tabs column">';
		$html .= '<ul class="tab">';

		/**
		 * 全部选项
		 * @since 2019.03.07
		 */

		$html .= '<li ' . $all_active . '><a data-key="_term_' . $taxonomy . '" data-value="" href="' . remove_query_arg('_term_' . $taxonomy, remove_query_arg($this->remove_query_arg)) . '">全部</a></li>';

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
			$html .= '<li class="' . $class . '"><a data-key="_term_' . $taxonomy . '" data-value="' . $term->term_id . '" href="' . add_query_arg('_term_' . $taxonomy, $term->term_id, remove_query_arg($this->remove_query_arg)) . '">' . $term->name . '</a></li>';

		}
		unset($tag);

		// 输出结束
		$html .= '</ul>';
		$html .= '</div>';
		$html .= '</div>';

		$this->html .= $html;

		return $html;

	}

	/**
	 *@since 2019.04.18 meta query
	 *@param 自定义： array args['wnd_meta_query'] meta字段筛选:
	 *		暂只支持单一 meta_key
	 *		非ajax状态环境中仅支持 = 、exists 两种compare
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
		$html = '<div class="columns is-marginless is-vcentered meta-tabs">';
		$html .= '<div class="column is-narrow">' . $args['label'] . '：</div>';
		$html .= '<div class="tabs column">';
		$html .= '<ul class="tab">';

		/**
		 * 全部选项
		 * @since 2019.03.07（copy）
		 */

		$html .= '<li ' . $all_active . '><a data-key="_meta_' . $args['key'] . '" data-value="" href="' . remove_query_arg('_meta_' . $args['key'], remove_query_arg($this->remove_query_arg)) . '">全部</a></li>';

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
			$html .= '<li ' . $active . '><a data-key="_meta_' . $args['key'] . '" data-value="' . $value . '" href="' . add_query_arg('_meta_' . $args['key'], $value, remove_query_arg($this->remove_query_arg)) . '">' . $key . '</a></li>';
		}
		unset($key, $value);

		// 输出结束
		$html .= '</ul>';
		$html .= '</div>';
		$html .= '</div>';

		$this->html .= $html;

		return $html;
	}

	/**
	 *@since 2019.04.21 排序
	 *@param 自定义： array args['wnd_orderby']
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

		// 移除选项
		$remove_query_arg = array_merge(array('orderby', 'order', 'meta_key'), $this->remove_query_arg);

		// 全部
		$all_active = 'class="is-active"';
		if (isset($this->wp_query_args['orderby']) and $this->wp_query_args['orderby'] != 'post_date') {
			$all_active = '';
		}

		// 输出容器
		$html = '<div class="columns is-marginless is-vcentered orderby-tabs">';
		$html .= '<div class="column is-narrow">' . $args['label'] . '：</div>';
		$html .= '<div class="tabs column">';
		$html .= '<ul class="tab">';

		/**
		 * 全部选项
		 * @since 2019.03.07（copy）
		 */
		$html .= '<li ' . $all_active . '><a data-key="orderby" data-value="" href="' . remove_query_arg($remove_query_arg) . '">默认</a></li>';

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

			// data-key="orderby" data-value="' . $orderby . '"

			$query_arg = is_array($orderby) ? $orderby : array('orderby' => $orderby);
			$html .= '<li ' . $active . '><a href="' . add_query_arg($query_arg, remove_query_arg($remove_query_arg)) . '">' . $key . '</a></li>';
		}
		unset($key, $orderby);

		// 输出结束
		$html .= '</ul>';
		$html .= '</div>';
		$html .= '</div>';

		$this->html .= $html;
		return $html;
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
		$html = '<div class="columns is-marginless is-vcentered current-tabs">';
		$html .= '<div class="column is-narrow">当前条件：</div>';
		$html .= '<div class="column">';

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
			$html .= '<span class="tag">' . $term->name . '<a data-key="_term_' . $term->taxonomy . '" data-value="" class="delete is-small" href="' . remove_query_arg('_term_' . $term->taxonomy, remove_query_arg($this->remove_query_arg)) . '"></a></span>&nbsp;&nbsp;';
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
			$html .= '<span class="tag">' . $key . '<a data-key="_meta_' . $this->meta_filter_args['key'] . '" data-value="" class="delete is-small" href="' . remove_query_arg('_meta_' . $this->meta_filter_args['key'], remove_query_arg($this->remove_query_arg)) . '"></a></span>&nbsp;&nbsp;';

		}
		unset($key, $meta_query);

		// 输出结束
		$html .= '</div>';
		$html .= '</div>';

		$this->html .= $html;

		return $html;

	}

}
