<?php

/**
 *@since 2019.02.26
 *遍历文章类型，并配合外部调用函数，生成tabs查询参数，
 *（在非ajax状态中，生成 ?type=post_type，ajax中，在当前查询参数新增post_type参数，并注销paged翻页参数以实现菜单切换）
 *@param $args 							外部调用函数ajax查询文章的参数
 *@param $args['wnd_post_types'] 		array 需要列表输出的类型数组
 *@param $args['wnd_remove_query_arg'] 	需要从当前请求参数中移除的参数数组
 *@param $ajax_call 					外部调用函数  @see js function wnd_ajax_embed()
 *@param $ajax_container 				外部调用函数ajax查询文章后嵌入的html容器
 */
function _wnd_post_types_filter($args = array(), $ajax_call = '', $ajax_container = '') {

	// 非数组，无需显示切换标签
	if (!isset($args['wnd_post_types']) or !is_array($args['wnd_post_types'])) {
		return;
	}

	$defaults = array(
		'wnd_remove_query_arg' => array('paged', 'pages', 'orderby', 'order'),
	);
	$args = wp_parse_args($args, $defaults);

	// 从指定排除的参数中添加 tax query，强制移除tax query
	$tax_query_key = array_search('tax_query', $args['wnd_remove_query_arg']);
	if (!$tax_query_key) {
		array_push($args['wnd_remove_query_arg'], 'tax_query');
	}

	// 从指定排除的参数中添加 meta query，强制移除meta query
	$tax_query_key = array_search('meta_query', $args['wnd_remove_query_arg']);
	if (!$tax_query_key) {
		array_push($args['wnd_remove_query_arg'], 'meta_query');
	}

	// 输出容器
	$html = '<div class="tabs is-boxed">';
	$html .= '<ul class="tab">';

	// 输出tabs
	foreach ($args['wnd_post_types'] as $post_type) {

		// 根据类型名，获取完整的类型信息
		$post_type = get_post_type_object($post_type);

		$active = (isset($args['post_type']) and $args['post_type'] == $post_type->name) ? 'class="is-active"' : '';

		if (wnd_doing_ajax()) {

			// ajax请求类型
			$ajax_type = $_POST['ajax_type'] ?? 'modal';

			// 配置ajax请求参数
			$ajax_args = array_merge($args, array('post_type' => $post_type->name));
			foreach ($args['wnd_remove_query_arg'] as $remove_query_arg) {
				unset($ajax_args[$remove_query_arg]);
			}
			unset($remove_query_arg);
			$ajax_args = http_build_query($ajax_args);

			if ($ajax_type == 'modal') {
				$html .= '<li ' . $active . '><a onclick="wnd_ajax_modal(\'' . $ajax_call . '\',\'' . $ajax_args . '\');">' . $post_type->label . '</a></li>';
			} else {
				$html .= '<li ' . $active . '><a onclick="wnd_ajax_embed(\'' . $ajax_container . '\',\'' . $ajax_call . '\',\'' . $ajax_args . '\');">' . $post_type->label . '</a></li>';
			}

		} else {

			/**
			 *@since 2019.02.27
			 * 切换类型时，需要从当前网址移除的参数（用于在多重筛选时，移除仅针对当前类型有效的参数）
			 */

			/**
			 *移除term查询
			 *categories tabs生成的GET参数为：'_term_' . $taxonomy，如果直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
			 */
			$taxonomies = get_object_taxonomies($args['post_type'], $output = 'names');
			if ($taxonomies) {
				foreach ($taxonomies as $taxonomy) {
					array_push($args['wnd_remove_query_arg'], '_term_' . $taxonomy);
				}
				unset($taxonomy);
			}

			/**
			 *@since 2019.3.14 移除meta查询
			 */
			foreach ($_GET as $key => $value) {
				if (strpos($key, 'meta_') === 0) {
					array_push($args['wnd_remove_query_arg'], $key);
					continue;
				}
			}
			unset($key, $value);

			$html .= '<li ' . $active . '><a href="' . add_query_arg('type', $post_type->name, remove_query_arg($args['wnd_remove_query_arg'])) . '">' . $post_type->label . '</a></li>';
		}

	}
	unset($post_type);

	// 输出结束
	$html .= '</ul>';
	$html .= '</div>';

	return $html;

}

/**
 *@since 2019.05.31
 *遍历文章类型，并配合外部调用函数，生成tabs查询参数，
 *（在非ajax状态中，生成 ?_post_status=post_status，ajax中，在当前查询参数新增post_status参数，并注销paged翻页参数以实现菜单切换）
 *@param $args 							外部调用函数ajax查询文章的参数
 *@param $args['wnd_post_status'] 		array 需要列表输出的类型数组
 *@param $args['wnd_remove_query_arg'] 	需要从当前请求参数中移除的参数数组
 *@param $ajax_call 					外部调用函数  @see js function wnd_ajax_embed()
 *@param $ajax_container 				外部调用函数ajax查询文章后嵌入的html容器
 */
function _wnd_post_status_filter($args = array(), $ajax_call = '', $ajax_container = '') {

	// 非数组，无需显示切换标签
	if (!isset($args['wnd_post_status']) or !is_array($args['wnd_post_status'])) {
		return;
	}

	$defaults = array(
		'wnd_remove_query_arg' => array('paged', 'pages', 'orderby', 'order'),
	);
	$args = wp_parse_args($args, $defaults);
	// ajax请求类型
	$ajax_type = $_POST['ajax_type'] ?? false;

	// 输出容器
	$html = '<div class="columns is-marginless is-vcentered post-status-tabs">';
	$html .= '<div class="column is-narrow">' . get_post_type_object($args['post_type'])->label . '状态：</div>';
	$html .= '<div class="tabs column">';
	$html .= '<div class="tabs">';
	$html .= '<ul class="tab">';

	/**
	 * 全部选项
	 */
	if (wnd_doing_ajax()) {

		$all_ajax_args = $args;
		if (is_array($all_ajax_args['post_status'])) {
			$all_active = 'class="is-active"';
		} else {
			unset($all_ajax_args['post_status']);
			$all_active = '';
		}
		$all_ajax_args = http_build_query($all_ajax_args);

		if ($ajax_type == 'modal') {
			$html .= '<li ' . $all_active . '><a onclick="wnd_ajax_modal(\'' . $ajax_call . '\',\'' . $all_ajax_args . '\');">全部</a></li>';
		} else {
			$html .= '<li ' . $all_active . '><a onclick="wnd_ajax_embed(\'' . $ajax_container . '\',\'' . $ajax_call . '\',\'' . $all_ajax_args . '\');">全部</a></li>';
		}

	} else {

		$all_active = is_array($args['post_status']) ? 'class="is-active"' : null;
		$html .= '<li ' . $all_active . '><a href="' . remove_query_arg('_post_status', remove_query_arg($args['wnd_remove_query_arg'])) . '">全部</a></li>';

	}

	// 输出tabs
	foreach ($args['wnd_post_status'] as $label => $post_status) {

		$active = (isset($args['post_status']) and $args['post_status'] == $post_status) ? 'class="is-active"' : '';

		if (wnd_doing_ajax()) {

			// ajax请求类型
			$ajax_type = $_POST['ajax_type'] ?? 'modal';

			// 配置ajax请求参数
			$ajax_args = array_merge($args, array('post_status' => $post_status));
			foreach ($args['wnd_remove_query_arg'] as $remove_query_arg) {
				unset($ajax_args[$remove_query_arg]);
			}
			unset($remove_query_arg);
			$ajax_args = http_build_query($ajax_args);

			if ($ajax_type == 'modal') {
				$html .= '<li ' . $active . '><a onclick="wnd_ajax_modal(\'' . $ajax_call . '\',\'' . $ajax_args . '\');">' . $label . '</a></li>';
			} else {
				$html .= '<li ' . $active . '><a onclick="wnd_ajax_embed(\'' . $ajax_container . '\',\'' . $ajax_call . '\',\'' . $ajax_args . '\');">' . $label . '</a></li>';
			}

		} else {

			$html .= '<li ' . $active . '><a href="' . add_query_arg('_post_status', $post_status, remove_query_arg($args['wnd_remove_query_arg'])) . '">' . $label . '</a></li>';
		}

	}
	unset($label, $post_status);

	// 输出结束
	$html .= '</ul>';
	$html .= '</div>';
	$html .= '</div>';
	$html .= '</div>';

	return $html;

}

/**
 *@since 2019.02.28
 *遍历当前post_type 具有层级关系的taxonomy，并配合外部调用函数，生成tabs查询参数，
 *（在非ajax状态中，生成 ?'_term_' . $taxonomy=$term_id，ajax中，在当前查询参数新增tax query参数，并注销paged翻页参数以实现菜单切换）
 *@param $args 										外部调用函数查询文章的参数
 *@param $ajax_call 								外部调用函数查询文章的调用函数
 *@param $ajax_container 							外部调用函数ajax查询文章后嵌入的html容器
 *@param $args['wnd_remove_query_arg']		array 	需要从当前请求参数中移除的参数键名数组
 *@param $args['wnd_only_cat'] 				bool 	仅显示分类
 */
function _wnd_categories_filter($args = array(), $ajax_call = '', $ajax_container = '') {

	$defaults = array(
		'post_type' => 'post',
		'wnd_remove_query_arg' => array('paged', 'pages'),
		'tax_query' => array(),
	);
	$args = wp_parse_args($args, $defaults);

	// 从指定排除的参数中移除 tax query 否则分类参数添加无效
	$tax_query_key = array_search('tax_query', $args['wnd_remove_query_arg']);
	if ($tax_query_key) {
		unset($args['wnd_remove_query_arg'][$tax_query_key]);
	}

	// ajax请求类型
	$ajax_type = $_POST['ajax_type'] ?? false;

	// 需要展示的taxonomy列表 @since 2019.03.21
	if ($args['wnd_only_cat']) {

		$cat_taxonomies = $args['post_type'] == 'post' ? array('category') : array($args['post_type'] . '_cat');
		if (!get_taxonomy($cat_taxonomies[0])) {
			return;
		}

		// 遍历当前文章类型taxonomy，并获取具有层级、且show_ui为true的taxonomy 作为输出的category
	} else {

		$cat_taxonomies = array();
		$taxonomies = get_object_taxonomies($args['post_type'], $output = 'object');
		if ($taxonomies) {
			foreach ($taxonomies as $taxonomy) {
				if ($taxonomy->hierarchical and $taxonomy->show_ui) {
					array_push($cat_taxonomies, $taxonomy->name);
				}
			}
			unset($taxonomy);
		}

	}

	// 循环输出（当一个文章类型注册有多个分类 taxonomy时）
	$html = '';
	foreach ($cat_taxonomies as $taxonomy) {

		/**
		 *查找在当前的tax_query查询参数中，当前taxonomy的键名，如果没有则加入
		 *tax_query是一个无键名的数组，无法根据键名合并，因此需要准确定位
		 *(数组默认键值从0开始， 当首元素即匹配则array_search返回 0，此处需要严格区分 0 和 false)
		 *@since 2019.03.07
		 */
		$taxonomy_query_key = false;
		$all_active = 'class="is-active"';
		foreach ($args['tax_query'] as $key => $tax_query) {

			// 当前分类在tax query中的键名
			if (array_search($taxonomy, $tax_query) !== false) {
				$taxonomy_query_key = $key;
				$all_active = '';
				continue;
			}

			// 获取tag类型taxonomy的键名（切换分类时，需要移除关联分类查询）
			if (array_search($args['post_type'] . '_tag', $tax_query) !== false) {
				unset($args['tax_query'][$key]);
				array_push($args['wnd_remove_query_arg'], '_term_' . $args['post_type'] . '_tag');
			}
		}
		unset($key, $tax_query);

		// 输出容器
		$html .= '<div class="columns is-marginless is-vcentered ' . $taxonomy . '-tabs">';
		$html .= '<div class="column is-narrow ' . $taxonomy . '-label">' . get_taxonomy($taxonomy)->label . '：</div>';
		$html .= '<div class="tabs column">';
		$html .= '<ul class="tab">';

		/**
		 * 全部选项
		 * @since 2019.03.07
		 */
		if (wnd_doing_ajax()) {

			$all_ajax_args = $args;
			if ($taxonomy_query_key !== false) {
				unset($all_ajax_args['tax_query'][$taxonomy_query_key]);
			}
			$all_ajax_args = http_build_query($all_ajax_args);

			if ($ajax_type == 'modal') {
				$html .= '<li ' . $all_active . '><a onclick="wnd_ajax_modal(\'' . $ajax_call . '\',\'' . $all_ajax_args . '\');">全部</a></li>';
			} else {
				$html .= '<li ' . $all_active . '><a onclick="wnd_ajax_embed(\'' . $ajax_container . '\',\'' . $ajax_call . '\',\'' . $all_ajax_args . '\');">全部</a></li>';
			}
		} else {
			$html .= '<li ' . $all_active . '><a href="' . remove_query_arg('_term_' . $taxonomy, remove_query_arg($args['wnd_remove_query_arg'])) . '">全部</a></li>';
		}

		// 输出tabs
		foreach (get_terms(array('taxonomy' => $taxonomy, 'parent' => 0, 'orderby' => 'count', 'order' => 'DESC')) as $term) {

			$active = '';

			// 遍历当前tax query查询是否匹配当前tab
			if (isset($args['tax_query'])) {

				foreach ($args['tax_query'] as $current_term_query) {

					// 查询父级分类
					$current_parent = get_term($current_term_query['terms'])->parent;

					if ($current_term_query['terms'] == $term->term_id or $term->term_id == $current_parent) {
						$active = 'class="is-active"';
						// 当前一级分类处于active，对应term id将写入父级数组
						$current_term_parent[$taxonomy] = $term->term_id;
					}
				}
				unset($current_term_query);

			}

			if (wnd_doing_ajax()) {

				// 配置ajax请求参数
				$term_query = array(
					'taxonomy' => $taxonomy,
					'field' => 'term_id',
					'terms' => $term->term_id,
				);

				$ajax_args = $args;

				// 定位当前taxonomy查询在tax_query中的位置（数组键值从0开始，此处必须以 false array_search返回值判断）
				if ($taxonomy_query_key !== false) {
					$ajax_args['tax_query'][$taxonomy_query_key] = $term_query;
				} else {
					array_push($ajax_args['tax_query'], $term_query);
				}

				// 按配置移除参数
				foreach ($args['wnd_remove_query_arg'] as $remove_query_arg) {
					unset($ajax_args[$remove_query_arg]);
				}
				unset($remove_query_arg);

				// 构建ajax查询字符串
				$ajax_args = http_build_query($ajax_args);

				if ($ajax_type == 'modal') {
					$html .= '<li ' . $active . '><a onclick="wnd_ajax_modal(\'' . $ajax_call . '\',\'' . $ajax_args . '\');">' . $term->name . '</a></li>';
				} else {
					$html .= '<li ' . $active . '><a onclick="wnd_ajax_embed(\'' . $ajax_container . '\',\'' . $ajax_call . '\',\'' . $ajax_args . '\');">' . $term->name . '</a></li>';
				}

			} else {
				/**
				 *categories tabs生成的GET参数为：'_term_' . $taxonomy，如果直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
				 */
				$html .= '<li ' . $active . '><a href="' . add_query_arg('_term_' . $taxonomy, $term->term_id, remove_query_arg($args['wnd_remove_query_arg'])) . '">' . $term->name . '</a></li>';
			}

		}
		unset($term);

		// 输出结束
		$html .= '</ul>';
		$html .= '</div>';
		$html .= '</div>';

		/**
		 * @since 2019.03.12 当前分类的子分类
		 */
		if (!isset($current_term_parent[$taxonomy])) {
			continue;
		}

		$child_terms = get_terms(array('taxonomy' => $taxonomy, 'parent' => $current_term_parent[$taxonomy], 'orderby' => 'count', 'order' => 'DESC'));
		if (!$child_terms) {
			continue;
		}

		$html .= '<div class="columns is-marginless is-vcentered">';
		$html .= '<div class="column is-narrow">当前子类：</div>';
		$html .= '<div class="column">';
		$html .= '<div class="tabs">';
		$html .= '<ul class="tab">';
		foreach ($child_terms as $child_term) {

			$child_active = '';

			// 遍历当前tax query查询是否匹配当前tab
			if (isset($args['tax_query'])) {

				foreach ($args['tax_query'] as $current_term_query) {

					if ($current_term_query['terms'] == $child_term->term_id) {
						$child_active = 'class="is-active"';
					}
				}
				unset($current_term_query);

			}
			if (wnd_doing_ajax()) {

				// 配置ajax请求参数
				$term_query = array(
					'taxonomy' => $taxonomy,
					'field' => 'term_id',
					'terms' => $child_term->term_id,
				);

				$ajax_args = $args;

				// 定位当前taxonomy查询在tax_query中的位置（数组键值从0开始，此处必须以 false array_search返回值判断）
				if ($taxonomy_query_key !== false) {
					$ajax_args['tax_query'][$taxonomy_query_key] = $term_query;
				} else {
					array_push($ajax_args['tax_query'], $term_query);
				}

				// 按配置移除参数
				foreach ($args['wnd_remove_query_arg'] as $remove_query_arg) {
					unset($ajax_args[$remove_query_arg]);
				}
				unset($remove_query_arg);

				// 构建ajax查询字符串
				$ajax_args = http_build_query($ajax_args);

				if ($ajax_type == 'modal') {
					$html .= '<li ' . $child_active . '><a onclick="wnd_ajax_modal(\'' . $ajax_call . '\',\'' . $ajax_args . '\');">' . $child_term->name . '</a></li>';
				} else {
					$html .= '<li ' . $child_active . '><a onclick="wnd_ajax_embed(\'' . $ajax_container . '\',\'' . $ajax_call . '\',\'' . $ajax_args . '\');">' . $child_term->name . '</a></li>';
				}

			} else {
				/**
				 *categories tabs生成的GET参数为：'_term_' . $taxonomy，如果直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
				 */
				$html .= '<li ' . $child_active . '><a href="' . add_query_arg('_term_' . $taxonomy, $child_term->term_id, remove_query_arg($args['wnd_remove_query_arg'])) . '">' . $child_term->name . '</a></li>';
			}
		}
		unset($child_term);
		$html .= '</ul>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

	}
	unset($taxonomy);

	return $html;

}

/**
 * 标签筛选
 * 定义taxonomy：{$post_type}.'_tag'
 * 读取wp_query中tax_query 提取taxonomy为{$post_type}.'_cat'的分类id，并获取对应的关联标签(需启用标签分类关联功能)
 * 若未设置关联分类，则查询所有热门标签
 *@since 2019.03.25
 */
function _wnd_tags_filter($args = array(), $ajax_call = '', $ajax_container = '', $limit = 10) {

	// 标签taxonomy
	$taxonomy = $args['post_type'] . '_tag';
	if (!taxonomy_exists($taxonomy)) {
		return;
	}

	// 默认参数
	$defaults = array(
		'post_type' => 'post',
		'wnd_remove_query_arg' => array('paged', 'pages'),
		'tax_query' => array(),
	);
	$args = wp_parse_args($args, $defaults);

	/**
	 *查找在当前的tax_query查询参数中，当前taxonomy的键名，如果没有则加入
	 *tax_query是一个无键名的数组，无法根据键名合并，因此需要准确定位
	 *(数组默认键值从0开始， 当首元素即匹配则array_search返回 0，此处需要严格区分 0 和 false)
	 *@since 2019.03.07
	 */
	$taxonomy_query_key = false;
	$all_active = 'class="is-active"';
	foreach ($args['tax_query'] as $key => $tax_query) {

		//遍历当前tax query 获取post type的category(格式$post_type.'_cat')	@since 2019.03.25
		if (array_search($args['post_type'] . '_cat', $tax_query) !== false) {
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

	// ajax请求类型
	$ajax_type = $_POST['ajax_type'] ?? false;

	// 输出容器
	$html = '<div class="columns is-marginless is-vcentered ' . $taxonomy . '-tabs">';
	$html .= '<div class="column is-narrow ' . $taxonomy . '-label">' . get_taxonomy($taxonomy)->label . '：</div>';
	$html .= '<div class="tabs column">';
	$html .= '<ul class="tab">';

	/**
	 * 全部选项
	 * @since 2019.03.07
	 */
	if (wnd_doing_ajax()) {

		$all_ajax_args = $args;
		if ($taxonomy_query_key !== false) {
			unset($all_ajax_args['tax_query'][$taxonomy_query_key]);
		}
		$all_ajax_args = http_build_query($all_ajax_args);

		if ($ajax_type == 'modal') {
			$html .= '<li ' . $all_active . '><a onclick="wnd_ajax_modal(\'' . $ajax_call . '\',\'' . $all_ajax_args . '\');">全部</a></li>';
		} else {
			$html .= '<li ' . $all_active . '><a onclick="wnd_ajax_embed(\'' . $ajax_container . '\',\'' . $ajax_call . '\',\'' . $all_ajax_args . '\');">全部</a></li>';
		}
	} else {
		$html .= '<li ' . $all_active . '><a href="' . remove_query_arg('_term_' . $taxonomy, remove_query_arg($args['wnd_remove_query_arg'])) . '">全部</a></li>';
	}

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
		$active = '';

		// 遍历当前tax query查询是否匹配当前tab
		if (isset($args['tax_query'])) {

			foreach ($args['tax_query'] as $current_term_query) {

				if ($current_term_query['terms'] == $term->term_id) {
					$active = 'class="is-active"';
				}
			}
			unset($current_term_query);

		}

		if (wnd_doing_ajax()) {

			// 配置ajax请求参数
			$term_query = array(
				'taxonomy' => $taxonomy,
				'field' => 'term_id',
				'terms' => $term->term_id,
			);

			$ajax_args = $args;

			// 定位当前taxonomy查询在tax_query中的位置（数组键值从0开始，此处必须以 false array_search返回值判断）
			if ($taxonomy_query_key !== false) {
				$ajax_args['tax_query'][$taxonomy_query_key] = $term_query;
			} else {
				array_push($ajax_args['tax_query'], $term_query);
			}

			// 按配置移除参数
			foreach ($args['wnd_remove_query_arg'] as $remove_query_arg) {
				unset($ajax_args[$remove_query_arg]);
			}
			unset($remove_query_arg);

			// 构建ajax查询字符串
			$ajax_args = http_build_query($ajax_args);

			if ($ajax_type == 'modal') {
				$html .= '<li ' . $active . '><a onclick="wnd_ajax_modal(\'' . $ajax_call . '\',\'' . $ajax_args . '\');">' . $term->name . '</a></li>';
			} else {
				$html .= '<li ' . $active . '><a onclick="wnd_ajax_embed(\'' . $ajax_container . '\',\'' . $ajax_call . '\',\'' . $ajax_args . '\');">' . $term->name . '</a></li>';
			}

		} else {
			/**
			 *categories tabs生成的GET参数为：'_term_' . $taxonomy，如果直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
			 */
			$html .= '<li ' . $active . '><a href="' . add_query_arg('_term_' . $taxonomy, $term->term_id, remove_query_arg($args['wnd_remove_query_arg'])) . '">' . $term->name . '</a></li>';
		}

	}
	unset($tag);

	// 输出结束
	$html .= '</ul>';
	$html .= '</div>';
	$html .= '</div>';

	return $html;

}

/**
 *@since 2019.04.18 meta query
 *@param 自定义： array args['wnd_meta_query'] meta字段筛选:
 *		暂只支持单一 meta_key
 *		非ajax状态环境中仅支持 = 、exists 两种compare
 *
 *	$args['wnd_meta_query'] = array(
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
 *	$args['wnd_meta_query'] = array(
 *		'label' => '文章价格',
 *		'key' => 'price',
 *		'options' => array(
 *			'包含' => 'exists',
 *		),
 *		'compare' => 'exists',
 *	);
 *
 */
function _wnd_meta_filter($args, $ajax_call = '', $ajax_container = '') {

	if (empty($args['wnd_meta_query'])) {
		return;
	}
	$args['wnd_remove_query_arg'] = array('paged', 'pages');

	// ajax请求类型
	$ajax_type = $_POST['ajax_type'] ?? false;

	/**
	 *查找在当前的meta_query查询参数中，当前meta key的键名，如果没有则加入
	 *meta_query是一个无键名的数组，无法根据键名合并，因此需要准确定位
	 *(数组默认键值从0开始， 当首元素即匹配则array_search返回 0，此处需要严格区分 0 和 false)
	 *@since 2019.03.07（copy）
	 */
	$meta_query_key = false;
	$all_active = 'class="is-active"';
	foreach ($args['meta_query'] as $key => $meta_query) {

		// 当前键名
		if (array_search($args['wnd_meta_query']['key'], $meta_query) !== false) {
			$meta_query_key = $key;
			$all_active = '';
			break;
		}
	}
	unset($key, $meta_query);

	// 输出容器
	$html = '<div class="columns is-marginless is-vcentered">';
	$html .= '<div class="column is-narrow">' . $args['wnd_meta_query']['label'] . '：</div>';
	$html .= '<div class="tabs column">';
	$html .= '<ul class="tab">';

	/**
	 * 全部选项
	 * @since 2019.03.07（copy）
	 */
	if (wnd_doing_ajax()) {

		$all_ajax_args = $args;
		if ($meta_query_key !== false) {
			unset($all_ajax_args['meta_query'][$meta_query_key]);
		}
		$all_ajax_args = http_build_query($all_ajax_args);

		if ($ajax_type == 'modal') {
			$html .= '<li ' . $all_active . '><a onclick="wnd_ajax_modal(\'' . $ajax_call . '\',\'' . $all_ajax_args . '\');">全部</a></li>';
		} else {
			$html .= '<li ' . $all_active . '><a onclick="wnd_ajax_embed(\'' . $ajax_container . '\',\'' . $ajax_call . '\',\'' . $all_ajax_args . '\');">全部</a></li>';
		}
	} else {
		$html .= '<li ' . $all_active . '><a href="' . remove_query_arg('_meta_' . $args['wnd_meta_query']['key'], remove_query_arg($args['wnd_remove_query_arg'])) . '">全部</a></li>';
	}

	// 输出tabs
	foreach ($args['wnd_meta_query']['options'] as $key => $value) {

		// 遍历当前meta query查询是否匹配当前tab
		$active = '';
		if (isset($args['meta_query'])) {

			foreach ($args['meta_query'] as $meta_query) {

				if ($meta_query['compare'] != 'exists' and $meta_query['value'] == $value) {
					$active = 'class="is-active"';

					// meta query compare 为 exists时，没有value值，仅查询是否包含对应key值
				} elseif ($meta_query['key'] = $args['wnd_meta_query']['key']) {
					$active = 'class="is-active"';
				}
			}
			unset($meta_query);

		}

		if (wnd_doing_ajax()) {

			// 配置ajax请求参数
			$meta_query = array(
				'key' => $args['wnd_meta_query']['key'],
				'value' => $value,
				'compare' => $args['wnd_meta_query']['compare'],
			);
			if ($args['wnd_meta_query']['compare'] == 'exists') {
				unset($meta_query['value']);
			}

			$ajax_args = $args;

			// 定位当前taxonomy查询在tax_query中的位置（数组键值从0开始，此处必须以 false array_search返回值判断）
			if ($meta_query_key !== false) {
				$ajax_args['meta_query'][$meta_query_key] = $meta_query;
			} else {
				array_push($ajax_args['meta_query'], $meta_query);
			}

			// 按配置移除参数
			foreach ($args['wnd_remove_query_arg'] as $remove_query_arg) {
				unset($ajax_args[$remove_query_arg]);
			}
			unset($remove_query_arg);

			// 构建ajax查询字符串
			$ajax_args = http_build_query($ajax_args);

			if ($ajax_type == 'modal') {
				$html .= '<li ' . $active . '><a onclick="wnd_ajax_modal(\'' . $ajax_call . '\',\'' . $ajax_args . '\');">' . $key . '</a></li>';
			} else {
				$html .= '<li ' . $active . '><a onclick="wnd_ajax_embed(\'' . $ajax_container . '\',\'' . $ajax_call . '\',\'' . $ajax_args . '\');">' . $key . '</a></li>';
			}

		} else {
			/**
			 *meta_query GET参数为：meta_{key}?=
			 */
			$html .= '<li ' . $active . '><a href="' . add_query_arg('_meta_' . $args['wnd_meta_query']['key'], $value, remove_query_arg($args['wnd_remove_query_arg'])) . '">' . $key . '</a></li>';
		}
	}
	unset($key, $value);

	// 输出结束
	$html .= '</ul>';
	$html .= '</div>';
	$html .= '</div>';

	return $html;
}

/**
 *@since 2019.04.21 排序
 *@param 自定义： array args['wnd_orderby']
 *
 *	$args['wnd_orderby'] = array(
 *		'label' => '排序',
 *		'options' => array(
 *			'发布时间' => 'date', //常规排序 date title等
 *			'浏览量' => array( // 需要多个参数的排序
 *				'orderby'=>'meta_value_num'
 *				'meta_key'   => 'views',
 *			),
 *		),
 *		'order' => 'DESC',
 *	);
 *
 */
function _wnd_orderby_filter($args, $ajax_call = '', $ajax_container = '') {

	if (empty($args['wnd_orderby'])) {
		return;
	}

	$args['wnd_remove_query_arg'] = array('paged', 'pages');

	// ajax请求类型
	$ajax_type = $_POST['ajax_type'] ?? false;

	// 全部
	$all_active = 'class="is-active"';
	if (!empty($args['orderby']) and $args['orderby'] != 'post_date') {
		$all_active = '';
	}

	// 输出容器
	$html = '<div class="columns is-marginless is-vcentered">';
	$html .= '<div class="column is-narrow">' . $args['wnd_orderby']['label'] . '：</div>';
	$html .= '<div class="tabs column">';
	$html .= '<ul class="tab">';

	/**
	 * 全部选项
	 * @since 2019.03.07（copy）
	 */
	if (wnd_doing_ajax()) {

		$all_ajax_args = $args;
		unset($all_ajax_args['orderby']);
		unset($all_ajax_args['meta_key']);
		$all_ajax_args = http_build_query($all_ajax_args);

		if ($ajax_type == 'modal') {
			$html .= '<li ' . $all_active . '><a onclick="wnd_ajax_modal(\'' . $ajax_call . '\',\'' . $all_ajax_args . '\');">默认</a></li>';
		} else {
			$html .= '<li ' . $all_active . '><a onclick="wnd_ajax_embed(\'' . $ajax_container . '\',\'' . $ajax_call . '\',\'' . $all_ajax_args . '\');">默认</a></li>';
		}
	} else {
		$html .= '<li ' . $all_active . '><a href="' . remove_query_arg(array('orderby', 'order', 'meta_key'), remove_query_arg($args['wnd_remove_query_arg'])) . '">默认</a></li>';
	}

	// 输出tabs
	foreach ($args['wnd_orderby']['options'] as $key => $orderby) {

		// 查询当前orderby是否匹配当前tab
		$active = '';
		if (isset($args['orderby'])) {

			/**
			 *	post meta排序
			 *	$args = array(
			 *		'post_type' => 'product',
			 *		'orderby'   => 'meta_value_num',
			 *		'meta_key'  => 'price',
			 *	);
			 *	$query = new WP_Query( $args );
			 */
			if (is_array($orderby) and isset($args['meta_key'])) {

				if ($orderby['meta_key'] == $args['meta_key']) {
					$active = 'class="is-active"';
				}

				// 常规排序
			} else {
				if ($orderby == $args['orderby']) {
					$active = 'class="is-active"';
				}
			}

		}

		if (wnd_doing_ajax()) {

			$ajax_args = $args;

			if (is_array($orderby)) {
				$ajax_args['orderby'] = $orderby['orderby'];
				$ajax_args['meta_key'] = $orderby['meta_key'];
			} else {
				// 常规排序，移除meta_key，保留参数会导致无法判断当前激活条件（is-active）
				unset($ajax_args['meta_key']);
				$ajax_args['orderby'] = $orderby;
			}

			// 按配置移除参数
			foreach ($args['wnd_remove_query_arg'] as $remove_query_arg) {
				unset($ajax_args[$remove_query_arg]);
			}
			unset($remove_query_arg);

			// 构建ajax查询字符串
			$ajax_args = http_build_query($ajax_args);

			if ($ajax_type == 'modal') {
				$html .= '<li ' . $active . '><a onclick="wnd_ajax_modal(\'' . $ajax_call . '\',\'' . $ajax_args . '\');">' . $key . '</a></li>';
			} else {
				$html .= '<li ' . $active . '><a onclick="wnd_ajax_embed(\'' . $ajax_container . '\',\'' . $ajax_call . '\',\'' . $ajax_args . '\');">' . $key . '</a></li>';
			}

		} else {

			// 当采用 post meta排序时，需要指定meta key，切换时需要去除
			array_push($args['wnd_remove_query_arg'], 'meta_key');

			$query_arg = is_array($orderby) ? $orderby : array('orderby' => $orderby);
			$html .= '<li ' . $active . '><a href="' . add_query_arg($query_arg, remove_query_arg($args['wnd_remove_query_arg'])) . '">' . $key . '</a></li>';
		}
	}
	unset($key, $orderby);

	// 输出结束
	$html .= '</ul>';
	$html .= '</div>';
	$html .= '</div>';

	return $html;
}

/**
 *@since 2019.03.26
 *遍历当前查询参数，输出取消当前查询链接
 */
function _wnd_current_filter($args, $ajax_call = '', $ajax_container = '') {

	// 默认参数
	$defaults = array(
		'post_type' => 'post',
		'wnd_remove_query_arg' => array('paged', 'pages'),
		'tax_query' => array(),
	);
	$args = wp_parse_args($args, $defaults);

	if (empty($args['tax_query']) and empty($args['meta_query'])) {
		return;
	}

	// ajax请求类型
	$ajax_type = $_POST['ajax_type'] ?? false;

	// 输出容器
	$html = '<div class="columns is-marginless is-vcentered">';
	$html .= '<div class="column is-narrow">当前条件：</div>';
	$html .= '<div class="column">';

	// 1、tax_query
	foreach ($args['tax_query'] as $key => $term_query) {

		$term = get_term($term_query['terms']);

		if (wnd_doing_ajax()) {

			$ajax_args = $args;

			// 定位当前taxonomy查询在tax_query中的位置，移除
			unset($ajax_args['tax_query'][$key]);

			// 按配置移除参数
			foreach ($args['wnd_remove_query_arg'] as $remove_query_arg) {
				unset($ajax_args[$remove_query_arg]);
			}
			unset($remove_query_arg);

			// 构建ajax查询字符串
			$ajax_args = http_build_query($ajax_args);

			if ($ajax_type == 'modal') {
				$html .= '<span class="tag">' . $term->name . '<button class="delete is-small" onclick="wnd_ajax_modal(\'' . $ajax_call . '\',\'' . $ajax_args . '\');"></button></span>&nbsp;&nbsp;';
			} else {
				$html .= '<span class="tag">' . $term->name . '<button class="delete is-small" onclick="wnd_ajax_embed(\'' . $ajax_container . '\',\'' . $ajax_call . '\',\'' . $ajax_args . '\');"></button></span>&nbsp;&nbsp;';
			}

		} else {
			/**
			 *categories tabs生成的GET参数为：'_term_' . $taxonomy，如果直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
			 */
			$html .= '<span class="tag">' . $term->name . '<a class="delete is-small" href="' . remove_query_arg('_term_' . $term->taxonomy, remove_query_arg($args['wnd_remove_query_arg'])) . '"></a></span>&nbsp;&nbsp;';
		}
	}
	unset($key, $term_query);

	/**
	 *@since 2019.04.18
	 *2、meta_query
	 */
	foreach ($args['meta_query'] as $meta_query) {

		// 通过wp meta query中的value值，反向查询自定义 key
		if ($meta_query['compare'] != 'exists') {
			$key = array_search($meta_query['value'], $args['wnd_meta_query']['options']);
			if (!$key) {
				continue;
			}

			// meta query compare 为 exists时，没有value值
		} else {
			$key = $args['wnd_meta_query']['label'];
		}

		if (wnd_doing_ajax()) {

			$ajax_args = $args;

			// post filter 暂只支持单一meta key查询，故直接移除所有 meta query即可
			unset($ajax_args['meta_query']);

			// 按配置移除参数
			foreach ($args['wnd_remove_query_arg'] as $remove_query_arg) {
				unset($ajax_args[$remove_query_arg]);
			}
			unset($remove_query_arg);

			// 构建ajax查询字符串
			$ajax_args = http_build_query($ajax_args);

			if ($ajax_type == 'modal') {
				$html .= '<span class="tag">' . $key . '<button class="delete is-small" onclick="wnd_ajax_modal(\'' . $ajax_call . '\',\'' . $ajax_args . '\');"></button></span>&nbsp;&nbsp;';
			} else {
				$html .= '<span class="tag">' . $key . '<button class="delete is-small" onclick="wnd_ajax_embed(\'' . $ajax_container . '\',\'' . $ajax_call . '\',\'' . $ajax_args . '\');"></button></span>&nbsp;&nbsp;';
			}

		} else {
			/**
			 *meta_query GET参数为：meta_{key}?=
			 */
			$html .= '<span class="tag">' . $key . '<a class="delete is-small" href="' . remove_query_arg('meta_' . $args['wnd_meta_query']['key'], remove_query_arg($args['wnd_remove_query_arg'])) . '"></a></span>&nbsp;&nbsp;';
		}

	}
	unset($key, $meta_query);

	// 输出结束
	$html .= '</div>';
	$html .= '</div>';

	return $html;

}

/**
 *@since 2019.03.01
 *WordPress文章多重筛选器
 *@param $args： wp_query $args
 *
 *@param 自定义： string $args['wnd_list_tpl'] 文章输出列表模板函数的名称（传递值：wp_query:$args）
 *@param 自定义： array $args['wnd_post_types']需要展示的文章类型
 *@param 自定义： array $args['wnd_post_status']需要筛选的文章状态
 *
 * $args['wnd_post_status'] = array(
 *	'已发布'=>'publish',
 *	'草稿'=>'draft',
 * )
 *
 *@param 自定义： bool args['wnd_only_cat']是否只筛选分类
 *@param 自定义： bool args['wnd_with_sidrbar']是否包含边栏
 *@param 自定义： array args['wnd_meta_query'] meta字段筛选 @link https://codex.wordpress.org/Class_Reference/WP_Query#Custom_Field_Parameters
 *		暂只支持单一 meta_key
 *		非ajax状态环境中仅支持 = 、exists 两种对比关系
 *		ajax状态支持WordPress原生 大于等于等对比关系
 *
 *	$args['wnd_meta_query'] = array(
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
 *	$args['wnd_meta_query'] = array(
 *		'label' => '文章价格',
 *		'key' => 'price',
 *		'options' => array(
 *			'包含' => 'exists',
 *		),
 *		'compare' => 'exists',
 *	);
 *
 *
 *@param 自定义： array args['wnd_orderby'] @link https://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters
 *
 *	$args['wnd_orderby'] = array(
 *		'label' => '排序',
 *		'options' => array(
 *			'发布时间' => 'date', //常规排序 date title等
 *			'浏览量' => array( // 需要多个参数的排序
 *				'orderby'=>'meta_value_num'
 *				'meta_key'   => 'views',
 *			),
 *		),
 *		'order' => 'DESC',
 *	);
 *
 *非ajax状态下：
 *自动从GET参数中获取taxonomy查询参数 (?$taxonmy_id=term_id)
 *自动从GET参数中获取meta查询参数 (?meta_$meta_key=meta_value or ?meta_$meta_key=exists)
 *自动从GET参数中获取参数，并按对应键值配置：$args[$key] = $value;
 */
function _wnd_posts_filter($args = array()) {

	// 查询参数
	$defaults = array(
		'posts_per_page' => get_option('posts_per_page'),
		'paged' => 1,
		'post_type' => '',
		'post_status' => get_post_stati(array('public' => true), 'names', 'and'),
		'tax_query' => array(),
		'meta_query' => array(),
		'no_found_rows' => true, //无需原生的分页
		'wnd_list_tpl' => '_wnd_post_list', //输出列表模板函数
		'wnd_post_types' => array(), //允许的类型数组
		'wnd_post_status' => array(), //状态筛选项
		'wnd_meta_query' => array(), //meta筛选项
		'wnd_orderby' => array(), //排序
		'wnd_only_cat' => 0, //只筛选分类
		'wnd_with_sidebar' => false, //边栏
	);
	$args = wp_parse_args($args, $defaults);

	// 如未指定类型，遍历循环输出当前站点允许的类型
	if (empty($args['wnd_post_types']) or $args['wnd_post_types'] == 'any') {
		$args['wnd_post_types'] = get_post_types();
		foreach ($args['wnd_post_types'] as $post_type) {
			if (!in_array($post_type, wnd_get_allowed_post_types())) {
				unset($args['wnd_post_types'][$post_type]);
			}
		}
		unset($post_type);

	}

	// GET参数优先;指定参数优先;当未指定类型：为数组时，默认数组第一个类型为当期查询值
	$args['post_type'] = $args['post_type'] ?: (is_array($args['wnd_post_types']) ? reset($args['wnd_post_types']) : $args['wnd_post_types']);
	$args['post_type'] = $_GET['type'] ?? $args['post_type'];
	// 分页优先参数
	$args['paged'] = $_GET['pages'] ?? $args['paged'];

	/**
	 *自动从GET参数中获取taxonomy查询参数 (?$taxonmy_id=term_id)
	 *字段参数：?meta_meta_key
	 *自动键名匹配： $args[$key] = $value;
	 */
	if (!empty($_GET)) {

		foreach ($_GET as $key => $value) {

			/**
			 *@since 2019.3.07 自动匹配meta query
			 *?meta_price=1 则查询 price = 1的文章
			 *?meta_price=exists 则查询 存在price的文章
			 */
			if (strpos($key, '_meta_') === 0) {

				$key = str_replace('_meta_', '', $key);
				$compare = $value == 'exists' ? 'exists' : '=';
				$meta_query = array(
					'key' => $key,
					'value' => $value,
					'compare' => $compare,
				);

				array_push($args['meta_query'], $meta_query);
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
				array_push($args['tax_query'], $term_query);
				continue;
			}

			/**
			 *@since 2019.05.31 post field查询
			 */
			if (strpos($key, '_post_') === 0) {
				$args[str_replace('_post_', '', $key)] = $value;
				continue;
			}

			// 其他、按键名自动匹配
			$args[$key] = $value;

		}
		unset($key, $value);
	}

	/**
	 *@since 2019.04.21 当meta_query compare == exists 不能设置value
	 */
	if (isset($args['meta_query'])) {
		foreach ($args['meta_query'] as $key => $meta_query) {
			if ($meta_query['compare'] == 'exists') {
				unset($args['meta_query'][$key]['value']);
			}
		}
	}

	// 容器开始
	$html = '<div id="wnd-filter">';

	$html .= '<div id="filter-container">';

	// post types 切换
	if (is_array($args['wnd_post_types']) and count($args['wnd_post_types']) > 1) {
		$html .= _wnd_post_types_filter($args, '_wnd_posts_filter', '#wnd-filter');
	}

	// post status
	$html .= $args['wnd_post_status'] ? _wnd_post_status_filter($args, '_wnd_posts_filter', '#wnd-filter') : null;

	// 分类 切换
	$html .= _wnd_categories_filter($args, '_wnd_posts_filter', '#wnd-filter');

	// 获取分类下关联的标签
	$html .= !$args['wnd_only_cat'] ? _wnd_tags_filter($args, '_wnd_posts_filter', '#wnd-filter') : null;

	// meta query
	$html .= _wnd_meta_filter($args, '_wnd_posts_filter', '#wnd-filter');

	// orderby
	$html .= _wnd_orderby_filter($args, '_wnd_posts_filter', '#wnd-filter');

	// 列出当前term查询，并附带取消链接
	$html .= _wnd_current_filter($args, '_wnd_posts_filter', '#wnd-filter');
	$html .= '</div>';

	$html .= '<div class="columns">';

	// 输出列表：根据_wnd_ajax_next_page，此处需设置容器及容器ID，否则ajax请求的翻页内容可能无法正确嵌入
	$html .= '<div id="post-list-container" class="column">';
	$html .= $args['wnd_list_tpl']($args);
	$html .= '</div>';

	// 边栏
	if ($args['wnd_with_sidebar']) {
		$html .= '<div class="column is-narrow">' . apply_filters('_wnd_posts_filter_sidebar', '', $args) . '</div>';
	}

	$html .= '</div>';

	$html .= '</div>';

	return $html;

}
