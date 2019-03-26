<?php

/**
 *@since 2019.02.26
 *遍历文章类型，并配合外部调用函数，生成tabs查询参数，
 *（在非ajax状态中，生成 ?type=post_type，ajax中，在当前查询参数新增post_type参数，并注销paged翻页参数以实现菜单切换）
 *@param $args 外部调用函数ajax查询文章的参数
 *@param $args['wnd_post_types'] array 需要列表输出的类型数组
 *@param $ajax_list_posts_call 外部调用函数查询文章的调用函数
 *@param $ajax_embed_container 外部调用函数ajax查询文章后嵌入的html容器
 *@param 自定义：array  $args['wnd_remove_query_arg'] 需要从当前请求参数中移除的参数数组
 */
function _wnd_post_types_filter($args = array(), $ajax_list_posts_call = '', $ajax_embed_container = '') {

	// 非数组，无需显示切换标签
	if (!isset($args['wnd_post_types']) or !is_array($args['wnd_post_types'])) {
		return;
	}

	$defaults = array(
		'wnd_remove_query_arg' => array('paged', 'pages', 'tax_query', 'meta_query'),
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
	echo '<div class="tabs is-boxed"><ul class="tab">';

	// 输出tabs
	foreach ($args['wnd_post_types'] as $post_type) {

		// 根据类型名，获取完整的类型信息
		$post_type = get_post_type_object($post_type);

		$active = (isset($args['post_type']) and $args['post_type'] == $post_type->name) ? 'class="is-active"' : '';

		if (wp_doing_ajax()) {

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
				echo '<li ' . $active . '><a onclick="wnd_ajax_modal(\'' . $ajax_list_posts_call . '\',\'' . $ajax_args . '\');">' . $post_type->label . '</a></li>';
			} else {
				echo '<li ' . $active . '><a onclick="wnd_ajax_embed(\'' . $ajax_embed_container . '\',\'' . $ajax_list_posts_call . '\',\'' . $ajax_args . '\');">' . $post_type->label . '</a></li>';
			}

		} else {

			/**
			 *@since 2019.02.27
			 * 切换类型时，需要从当前网址移除的参数（用于在多重筛选时，移除仅针对当前类型有效的参数）
			 */

			/**
			 *移除term查询
			 *categories tabs生成的GET参数为：$taxonomy.'_id'，如果直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
			 */
			$taxonomies = get_object_taxonomies($args['post_type'], $output = 'names');
			if ($taxonomies) {
				foreach ($taxonomies as $taxonomy) {
					array_push($args['wnd_remove_query_arg'], $taxonomy . '_id');
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

			echo '<li ' . $active . '><a href="' . add_query_arg('type', $post_type->name, remove_query_arg($args['wnd_remove_query_arg'])) . '">' . $post_type->label . '</a></li>';
		}

	}
	unset($post_type);

	// 输出结束
	echo '</ul></div>';

}

/**
 *@since 2019.02.28
 *遍历当前post_type 具有层级关系的taxonomy，并配合外部调用函数，生成tabs查询参数，
 *（在非ajax状态中，生成 ?$taxonomy.'_id'=$term_id，ajax中，在当前查询参数新增tax query参数，并注销paged翻页参数以实现菜单切换）
 *@param $args 外部调用函数ajax查询文章的参数，其中 $args['wnd_remove_query_arg'] 参数为非ajax状态下，需要从当前网址中移除的参数数组
 *@param $ajax_list_posts_call 外部调用函数查询文章的调用函数
 *@param $ajax_embed_container 外部调用函数ajax查询文章后嵌入的html容器
 *@param array  'wnd_remove_query_arg' => array('paged', 'pages'), 需要从当前请求参数中移除的参数键名数组
 */
function _wnd_categories_filter($args = array(), $ajax_list_posts_call = '', $ajax_embed_container = '') {

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
	if ($args['wnd_taxonomies']) {

		$cat_taxonomies = $args['wnd_taxonomies'];

		// 未指定，遍历当前文章类型taxonomy，并获取具有层级的taxonomy作为输出的category
	} else {

		$cat_taxonomies = array();
		$taxonomies = get_object_taxonomies($args['post_type'], $output = 'names');
		if ($taxonomies) {
			foreach ($taxonomies as $taxonomy) {
				if (is_taxonomy_hierarchical($taxonomy)) {
					array_push($cat_taxonomies, $taxonomy);
				}
			}
			unset($taxonomy);
		}

	}

	// 循环输出（当一个文章类型注册有多个分类 taxonomy时）
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
				array_push($args['wnd_remove_query_arg'], $args['post_type'] . '_tag' . '_id');
			}
		}
		unset($key, $tax_query);

		// 输出容器
		echo '<div class="columns is-marginless ' . $taxonomy . '-tabs">';
		echo '<div class="column is-narrow ' . $taxonomy . '-label">' . get_taxonomy($taxonomy)->label . '：</div>';
		echo '<div class="tabs column"><ul class="tab">';

		/**
		 * 全部选项
		 * @since 2019.03.07
		 */
		if (wp_doing_ajax()) {

			$all_ajax_args = $args;
			if ($taxonomy_query_key !== false) {
				unset($all_ajax_args['tax_query'][$taxonomy_query_key]);
			}
			$all_ajax_args = http_build_query($all_ajax_args);

			if ($ajax_type == 'modal') {
				echo '<li ' . $all_active . '><a onclick="wnd_ajax_modal(\'' . $ajax_list_posts_call . '\',\'' . $all_ajax_args . '\');">全部</a></li>';
			} else {
				echo '<li ' . $all_active . '><a onclick="wnd_ajax_embed(\'' . $ajax_embed_container . '\',\'' . $ajax_list_posts_call . '\',\'' . $all_ajax_args . '\');">全部</a></li>';
			}
		} else {
			echo '<li ' . $all_active . '><a href="' . remove_query_arg($taxonomy . '_id', remove_query_arg($args['wnd_remove_query_arg'])) . '">全部</a></li>';
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

			if (wp_doing_ajax()) {

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
					echo '<li ' . $active . '><a onclick="wnd_ajax_modal(\'' . $ajax_list_posts_call . '\',\'' . $ajax_args . '\');">' . $term->name . '</a></li>';
				} else {
					echo '<li ' . $active . '><a onclick="wnd_ajax_embed(\'' . $ajax_embed_container . '\',\'' . $ajax_list_posts_call . '\',\'' . $ajax_args . '\');">' . $term->name . '</a></li>';
				}

			} else {
				/**
				 *categories tabs生成的GET参数为：$taxonomy.'_id'，如果直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
				 */
				echo '<li ' . $active . '><a href="' . add_query_arg($taxonomy . '_id', $term->term_id, remove_query_arg($args['wnd_remove_query_arg'])) . '">' . $term->name . '</a></li>';
			}

		}
		unset($term);

		// 输出结束
		echo '</ul></div>';
		echo '</div>';

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

		echo '<div class="columns is-marginless">';
		echo '<div class="column is-narrow">当前子类：</div>';
		echo '<div class="column"><div class="tabs"><ul class="tab">';
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
			if (wp_doing_ajax()) {

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
					echo '<li ' . $child_active . '><a onclick="wnd_ajax_modal(\'' . $ajax_list_posts_call . '\',\'' . $ajax_args . '\');">' . $child_term->name . '</a></li>';
				} else {
					echo '<li ' . $child_active . '><a onclick="wnd_ajax_embed(\'' . $ajax_embed_container . '\',\'' . $ajax_list_posts_call . '\',\'' . $ajax_args . '\');">' . $child_term->name . '</a></li>';
				}

			} else {
				/**
				 *categories tabs生成的GET参数为：$taxonomy.'_id'，如果直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
				 */
				echo '<li ' . $child_active . '><a href="' . add_query_arg($taxonomy . '_id', $child_term->term_id, remove_query_arg($args['wnd_remove_query_arg'])) . '">' . $child_term->name . '</a></li>';
			}
		}
		unset($child_term);
		echo '</ul></div></div></div>';

	}
	unset($taxonomy);

}

/**
 * 获取当前分类下的关联标签tabs
 *@since 2019.03.25
 */
function _wnd_tags_filter($args = array(), $ajax_list_posts_call = '', $ajax_embed_container = '', $limit = 10) {

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
	echo '<div class="columns is-marginless ' . $taxonomy . '-tabs">';
	echo '<div class="column is-narrow ' . $taxonomy . '-label">' . get_taxonomy($taxonomy)->label . '：</div>';
	echo '<div class="tabs column"><ul class="tab">';

	/**
	 * 全部选项
	 * @since 2019.03.07
	 */
	if (wp_doing_ajax()) {

		$all_ajax_args = $args;
		if ($taxonomy_query_key !== false) {
			unset($all_ajax_args['tax_query'][$taxonomy_query_key]);
		}
		$all_ajax_args = http_build_query($all_ajax_args);

		if ($ajax_type == 'modal') {
			echo '<li ' . $all_active . '><a onclick="wnd_ajax_modal(\'' . $ajax_list_posts_call . '\',\'' . $all_ajax_args . '\');">全部</a></li>';
		} else {
			echo '<li ' . $all_active . '><a onclick="wnd_ajax_embed(\'' . $ajax_embed_container . '\',\'' . $ajax_list_posts_call . '\',\'' . $all_ajax_args . '\');">全部</a></li>';
		}
	} else {
		echo '<li ' . $all_active . '><a href="' . remove_query_arg($taxonomy . '_id', remove_query_arg($args['wnd_remove_query_arg'])) . '">全部</a></li>';
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

		if (wp_doing_ajax()) {

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
				echo '<li ' . $active . '><a onclick="wnd_ajax_modal(\'' . $ajax_list_posts_call . '\',\'' . $ajax_args . '\');">' . $term->name . '</a></li>';
			} else {
				echo '<li ' . $active . '><a onclick="wnd_ajax_embed(\'' . $ajax_embed_container . '\',\'' . $ajax_list_posts_call . '\',\'' . $ajax_args . '\');">' . $term->name . '</a></li>';
			}

		} else {
			/**
			 *categories tabs生成的GET参数为：$taxonomy.'_id'，如果直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
			 */
			echo '<li ' . $active . '><a href="' . add_query_arg($taxonomy . '_id', $term->term_id, remove_query_arg($args['wnd_remove_query_arg'])) . '">' . $term->name . '</a></li>';
		}

	}
	unset($tag);

	// 输出结束
	echo '</ul></div>';
	echo '</div>';

}

/**
 *@since 2019.03.26
 *遍历当前查询参数中的分类查询，输出取消当前分类选项链接
 */
function _wnd_cancel_terms_query($args, $ajax_list_posts_call, $ajax_embed_container) {

	// 默认参数
	$defaults = array(
		'post_type' => 'post',
		'wnd_remove_query_arg' => array('paged', 'pages'),
		'tax_query' => array(),
	);
	$args = wp_parse_args($args, $defaults);

	if (empty($args['tax_query'])) {
		return;
	}

	// 输出容器
	echo '<div class="columns is-marginless">';
	echo '<div class="column is-narrow">当前条件：</div>';
	echo '<div class="column">';

	foreach ($args['tax_query'] as $key => $term_query) {

		$term = get_term($term_query['terms']);

		if (wp_doing_ajax()) {

			// ajax请求类型
			$ajax_type = $_POST['ajax_type'] ?? false;

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
				echo '<span class="tag">' . $term->name . '<button class="delete is-small" onclick="wnd_ajax_modal(\'' . $ajax_list_posts_call . '\',\'' . $ajax_args . '\');"></button></span>&nbsp;&nbsp;';
			} else {
				echo '<span class="tag">' . $term->name . '<button class="delete is-small" onclick="wnd_ajax_embed(\'' . $ajax_embed_container . '\',\'' . $ajax_list_posts_call . '\',\'' . $ajax_args . '\');"></button></span>&nbsp;&nbsp;';
			}

		} else {
			/**
			 *categories tabs生成的GET参数为：$taxonomy.'_id'，如果直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
			 */
			echo '<span class="tag">' . $term->name . '<a class="delete is-small" href="' . remove_query_arg($term->taxonomy . '_id', remove_query_arg($args['wnd_remove_query_arg'])) . '"></a></span>&nbsp;&nbsp;';
		}
	}
	unset($key, $term_query);

	// 输出结束
	echo '</div>';
	echo '</div>';

}

/**
 *@since 2019.03.01
 *输出同时带有 poet_type和分类切换标签的文章列表
 *@param $args wp_query $args
 *@param 自定义： string $args['wnd_list_template'] 文章输出列表模板函数的名称（传递值：wp_query:$args）
 *@param 自定义： array $args['wnd_post_types']需要展示的文章类型
 *@param 自定义： array $args['wnd_taxonomies']需要展示的taxonomy(若不指定，则自动获取当前文章类型的所有分类)
 *非ajax状态下：
 *自动从GET参数中获取taxonomy查询参数 (?$taxonmy_id=term_id)
 *自动从GET参数中获取meta查询参数 (?meta_$meta_key=meta_value or ?meta_$meta_key=exists)
 */
function _wnd_list_posts_with_filter($args = array()) {

	// 查询参数
	$defaults = array(
		'posts_per_page' => get_option('posts_per_page'),
		'paged' => 1,
		'post_type' => '',
		'post_status' => 'publish',
		'tax_query' => array(),
		'meta_query' => array(),
		'no_found_rows' => true, //无需原生的分页
		'wnd_list_template' => '_wnd_list_posts_by_table', //输出列表模板函数
		'wnd_post_types' => array(), //允许的类型数组
		'wnd_taxonomies' => array(), //允许的taxonomy数组
	);
	$args = wp_parse_args($args, $defaults);

	// 如未指定类型，遍历循环输出当前站点允许的类型
	if (empty($args['wnd_post_types']) or $args['wnd_post_types'] == 'any') {

		$args['wnd_post_types'] = get_post_types(array('public' => true), $output = 'names', $operator = 'and');
		unset($args['wnd_post_types']['page'], $args['wnd_post_types']['attachment']); // 排除页面和附件
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

	// 自动从GET参数中获取taxonomy查询参数 (?$taxonmy_id=term_id)
	if (!empty($_GET)) {

		foreach ($_GET as $key => $value) {

			/**
			 *@since 2019.3.07 自动匹配meta query
			 *?meta_price=1 则查询 price = 1的文章
			 *?meta_price=exists 则查询 存在price的文章
			 */
			if (strpos($key, 'meta_') === 0) {

				$key = str_replace('meta_', '', $key);
				$compare = $value == 'exists' ? 'exists' : '=';
				$meta_query = array(
					'key' => $key,
					'value' => $value,
					'compare' => $compare,
				);
				if ($compare == 'exists') {
					unset($meta_query['value']);
				}

				array_push($args['meta_query'], $meta_query);
				continue;
			}

			/**
			 *categories tabs生成的GET参数为：$taxonomy.'_id'，
			 *直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
			 */
			$key = str_replace('_id', '', $key);
			if (in_array($key, get_object_taxonomies($args['post_type'], $output = 'names'))) {

				$term_query = array(
					'taxonomy' => $key,
					'field' => 'term_id',
					'terms' => $value,
				);
				array_push($args['tax_query'], $term_query);
			}

		}
		unset($key, $value);
	}

	// 容器开始
	echo '<div id="wnd-tabs" class="list-posts">';

	echo '<div class="tabs-container">';
	// post types 切换
	if (is_array($args['wnd_post_types']) and count($args['wnd_post_types']) > 1) {
		_wnd_post_types_filter($args, 'list_posts_with_filter', '#wnd-tabs');
	}

	// 分类 切换
	_wnd_categories_filter($args, 'list_posts_with_filter', '#wnd-tabs');

	// 获取分类下关联的标签
	_wnd_tags_filter($args, 'list_posts_with_filter', '#wnd-tabs');

	// 列出当前term查询，并附带取消链接
	_wnd_cancel_terms_query($args, 'list_posts_with_filter', '#wnd-tabs');
	// 容器结束
	echo '</div>';

	// 输出列表：根据_wnd_ajax_next_page，此处需设置容器及容器class，否则ajax请求的翻页内容可能无法正确嵌入
	echo '<div class="post-list-container">';
	$args['wnd_list_template']($args);
	echo '</div>';

	// 容器结束
	echo '</div>';
}
