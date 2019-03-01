<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.02.15 简单分页
 *不查询总数的情况下，简单实现下一页翻页
 *翻页参数键名$pages_key 不能设置为 paged 可能会与原生WordPress翻页机制产生冲突
 */
function _wnd_next_page($posts_per_page, $current_post_count, $pages_key = 'pages') {

	$paged = (isset($_GET[$pages_key])) ? intval($_GET[$pages_key]) : 1;

	echo '<nav class="pagination is-centered" role="navigation" aria-label="pagination">';
	echo '<ul class="pagination-list">';

	if ($paged >= 2) {
		echo '<li><a class="pagination-link" href="' . add_query_arg($pages_key, $paged - 1) . '">上一页</a>';
	}
	if ($current_post_count >= $posts_per_page) {
		echo '<li><a class="pagination-link" href="' . add_query_arg($pages_key, $paged + 1) . '">下一页</a>';
	}
	echo '</ul>';
	echo '</nav>';

}

/**
 *@since 2019.02.18 ajax分页
 *在当前ajax请求的基础上，自增一个翻页参数，并再次发送(paged)
 *@param $function 需要请求的输出函数
 *@param $function 的参数
 *函数应该与请求函数，处于同一个父元素之下，父容器必须设置 id
 */
function _wnd_ajax_next_page($function, $args) {

	$current_pages = $args['paged'] ?? 1;

	// 下一页参数
	$args['paged'] = $current_pages + 1;
	$js_next_args = '\'' . $function . '\',\'' . http_build_query($args) . '\'';
	$js_next_args = str_replace('_wnd_', '', $js_next_args);

	// 上一页参数
	$args['paged'] = $current_pages - 1;
	$js_pre_args = '\'' . $function . '\',\'' . http_build_query($args) . '\'';
	$js_pre_args = str_replace('_wnd_', '', $js_pre_args);

	$ajax_type = $_POST['ajax_type'] ?? 'modal';
	if ($ajax_type == 'modal') {
		$next_onclick = 'wnd_ajax_modal(' . $js_next_args . ')';
		$pre_onclick = 'wnd_ajax_modal(' . $js_pre_args . ')';
	} else {

		// 获取翻页元素的父元素ID
		$container = '\'#\' + $(this).parents(\'nav\').parent().attr(\'id\')';

		$next_onclick = 'wnd_ajax_embed(' . $container . ',' . $js_next_args . ')';
		$pre_onclick = 'wnd_ajax_embed(' . $container . ',' . $js_pre_args . ')';
	}

	echo '<nav class="pagination is-centered" role="navigation" aria-label="pagination">';
	echo '<ul class="pagination-list">';
	if ($current_pages >= 2) {
		echo '<li><a class="pagination-link" onclick="' . $pre_onclick . '">上一页</a>';
	}
	echo '<li><a class="pagination-link" onclick="' . $next_onclick . '">下一页</a>';
	echo '</ul>';
	echo '</nav>';

}

/**
 *@since 2019.02.15
 *以表格形式输出WordPress文章列表
 *$pages_key = 'pages', $color = 'is-primary' 仅在非ajax状态下有效
 */
function _wnd_list_posts($args = '', $pages_key = 'pages', $color = 'is-primary') {

	$defaults = array(
		'posts_per_page' => get_option('posts_per_page'),
		'paged' => 1,
		'post_type' => 'post',
		'post_status' => 'publish',
		'no_found_rows' => true, //$query->max_num_pages;
	);
	$args = wp_parse_args($args, $defaults);

	// 翻页参数优先
	$args['paged'] = $_REQUEST[$pages_key] ?? $args['paged'];

	$query = new WP_Query($args);

	if ($query->have_posts()):

	?>
<table class="table is-fullwidth is-hoverable is-striped">
	<thead>
		<tr>
			<th class="is-narrow is-hidden-mobile"><abbr title="Position">日期</abbr></th>
			<th>标题</th>
			<th class="is-narrow is-hidden-mobile">状态</th>
			<th class="is-narrow is-hidden-mobile">操作</th>
		</tr>
	</thead>
	<tbody>
		<?php while ($query->have_posts()) {$query->the_post();global $post;?>
		<tr>
			<td class="is-narrow is-hidden-mobile"><?php the_time('m-d H:i');?></td>
			<td><a href="<?php echo the_permalink(); ?>" target="_blank"><?php echo $post->post_title; ?></a></td>
			<th class="is-narrow is-hidden-mobile"><?php echo apply_filters('_wnd_list_posts_status_text', $post->post_status, $post->post_type); ?></th>
			<td class="is-narrow is-hidden-mobile">
				<a onclick="wnd_ajax_modal('post_info','post_id=<?php echo $post->ID; ?>&color=<?php echo $color; ?>')">预览</a>
				<?php if (current_user_can('edit_post', $post->ID)) {?>
				<a onclick="wnd_ajax_modal('post_status_form','<?php echo $post->ID; ?>')">[管理]</a>
				<?php }?>
			</td>
		</tr>
		<?php }?>
	</tbody>
</table>
<?php

	wp_reset_postdata(); //重置查询

	// 没有内容
	else:
		$no_more_text = ($args['paged'] >= 2) ? '没有更多内容！' : '没有匹配的内容！';
		echo '<div class="message is-warning"><div class="message-body">' . $no_more_text . '</div></div>';
	endif;

	// 分页
	if (!wp_doing_ajax()) {
		_wnd_next_page($args['posts_per_page'], $query->post_count, $pages_key);
	} else {
		_wnd_ajax_next_page(__FUNCTION__, $args);
	}

	?>
<?php
// end function

}

/**
 *@since 2019.02.26
 *遍历文章类型，并配合外部调用函数，生成tabs查询参数，
 *（在非ajax状态中，生成 ?type=post_type，ajax中，在当前查询参数新增post_type参数，并注销paged翻页参数以实现菜单切换）
 *@param $args 外部调用函数ajax查询文章的参数
 *@param $ajax_list_posts_call 外部调用函数查询文章的调用函数
 *@param $ajax_embed_container 外部调用函数ajax查询文章后嵌入的html容器
 *@param 自定义：array  $args['wnd_remove_query_arg'] 需要从当前请求参数中移除的参数数组
 */
function _wnd_post_types_tabs($args = array(), $ajax_list_posts_call = '', $ajax_embed_container = '') {

	$defaults = array(
		'wnd_remove_query_arg' => array('paged', 'pages', 'tax_query'),
	);
	$args = wp_parse_args($args, $defaults);

	// 从指定排除的参数中移除 tax query 否则分类参数添加无效
	$tax_query_key = array_search('tax_query', $args['wnd_remove_query_arg']);
	if (!$tax_query_key) {
		array_push($args['wnd_remove_query_arg'], 'tax_query');
	}

	// 查询post types
	$post_types = get_post_types(array('public' => true, 'show_ui' => true), $output = 'objects', $operator = 'and');
	unset($post_types['page'], $post_types['attachment']); // 排除页面和附件
	// 仅输出允许的post types
	foreach ($post_types as $post_type) {
		if (!in_array($post_type->name, wnd_get_allowed_post_types())) {
			unset($post_types[$post_type->name]);
		}
	}
	unset($post_type);

	// 输出容器
	echo '<div class="tabs is-boxed"><ul class="tab">';

	// 输出tabs
	foreach ($post_types as $post_type) {

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

			/*
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
 *（在非ajax状态中，生成 ?$taxonomy.'_id'=$term_id，ajax中，在当前查询参数新增post_type参数，并注销paged翻页参数以实现菜单切换）
 *@param $args 外部调用函数ajax查询文章的参数，其中 $args['wnd_remove_query_arg'] 参数为非ajax状态下，需要从当前网址中移除的参数数组
 *@param $ajax_list_posts_call 外部调用函数查询文章的调用函数
 *@param $ajax_embed_container 外部调用函数ajax查询文章后嵌入的html容器
 *@param array  'wnd_remove_query_arg' => array('paged', 'pages'), 需要从当前请求参数中移除的参数键名数组
 */
function _wnd_categories_tabs($args = array(), $ajax_list_posts_call = '', $ajax_embed_container = '') {

	$defaults = array(
		'post_type' => 'post',
		'wnd_remove_query_arg' => array('paged', 'pages'),
	);
	$args = wp_parse_args($args, $defaults);

	// 从指定排除的参数中移除 tax query 否则分类参数添加无效
	$tax_query_key = array_search('tax_query', $args['wnd_remove_query_arg']);
	if ($tax_query_key) {
		unset($args['wnd_remove_query_arg'][$tax_query_key]);
	}

	// 遍历当前文章类型taxonomy，并获取具有层级的taxonomy作为输出的category
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

	// 循环输出（当一个文章类型注册有多个分类 taxonomy时）
	foreach ($cat_taxonomies as $taxonomy) {

		// 输出容器
		echo '<div class="tabs"><ul class="tab">';

		// 输出tabs
		foreach (get_terms(array('taxonomy' => $taxonomy)) as $term) {

			$active = '';

			// 遍历当前tax query查询是否匹配当前tab
			if (isset($args['tax_query'])) {

				foreach ($args['tax_query'] as $term_query) {

					if ($term_query['terms'] == $term->term_id) {
						$active = 'class="is-active"';
					}
				}
				unset($term_query);

			}

			if (wp_doing_ajax()) {

				// ajax请求类型
				$ajax_type = $_POST['ajax_type'] ?? 'modal';

				// 配置ajax请求参数
				$term_query = array(
					'taxonomy' => $taxonomy,
					'field' => 'term_id',
					'terms' => $term->term_id,
				);
				$ajax_args = array_merge($args, array('tax_query' => array($term_query)));

				foreach ($args['wnd_remove_query_arg'] as $remove_query_arg) {
					unset($ajax_args[$remove_query_arg]);
				}
				unset($remove_query_arg);
				$ajax_args = http_build_query($ajax_args);

				if ($ajax_type == 'modal') {
					echo '<li ' . $active . '><a onclick="wnd_ajax_modal(\'' . $ajax_list_posts_call . '\',\'' . $ajax_args . '\');">' . $term->name . '</a></li>';
				} else {
					echo '<li ' . $active . '><a onclick="wnd_ajax_embed(\'' . $ajax_embed_container . '\',\'' . $ajax_list_posts_call . '\',\'' . $ajax_args . '\');">' . $term->name . '</a></li>';
				}

			} else {
				/**
				 *@since 2019.02.27
				 * 切换类型时，需要从当前网址移除的参数（用于在多重筛选时，移除仅针对当前类型有效的参数）
				 *categories tabs生成的GET参数为：$taxonomy.'_id'，如果直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
				 */
				echo '<li ' . $active . '><a href="' . add_query_arg($taxonomy . '_id', $term->term_id, remove_query_arg($args['wnd_remove_query_arg'])) . '">' . $term->name . '</a></li>';
			}

		}
		unset($term);

	}
	unset($taxonomy);

	// 输出结束
	echo '</ul></div>';

}

/**
 *@since 2019.03.01
 *输出同时带有 poet_type和分类切换标签的文章列表
 *@param $args wp_query $args
 *@param 自定义： string $args['wnd_list_template'] 文章输出列表模板函数的名称（传递值：wp_query:$args）
 *@see:
 *仅ajax状态下自动切换
 */
function _wnd_list_posts_with_tabs($args = array()) {

	// post types 过滤
	$post_types = get_post_types(array('public' => true), $output = 'objects', $operator = 'and');
	unset($post_types['page'], $post_types['attachment']); // 排除页面和附件
	foreach ($post_types as $post_type) {
		if (!in_array($post_type->name, wnd_get_allowed_post_types())) {
			unset($post_types[$post_type->name]);
		}
	}
	unset($post_type);

	// 查询参数
	$defaults = array(
		'post_type' => reset($post_types)->name, //$post_types 为多维数组，获取第一个type 的 name
		'wnd_list_template' => '_wnd_list_posts', //输出列表模板
		'tax_query' => array(),
		'meta_query' => array(),
	);
	$args = wp_parse_args($args, $defaults);

	// 优先参数
	$args['post_type'] = $_REQUEST['type'] ?? $args['post_type'];

	// 自动获取taxonomy查询参数 (?$taxonmy=term_name)
	if (!empty($_GET)) {

		$cat_taxonomies = array();
		$tag_taxonomies = array();
		$taxonomies = get_object_taxonomies($args['post_type'], $output = 'names');
		if ($taxonomies) {
			foreach ($taxonomies as $taxonomy) {
				if (is_taxonomy_hierarchical($taxonomy)) {
					array_push($cat_taxonomies, $taxonomy);
				} else {
					array_push($tag_taxonomies, $taxonomy);
				}
			}
			unset($taxonomy);
		}

		foreach ($_GET as $key => $value) {

			/*
				*categories tabs生成的GET参数为：$taxonomy.'_id'，如果直接用 $taxonomy 作为参数会触发WordPress原生分类请求导致错误
			*/
			$key = str_replace('_id', '', $key);

			if (in_array($key, $cat_taxonomies)) {

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
	echo '<div id="list-posts-with-tabs" class="list-posts">';

	// post types 切换
	_wnd_post_types_tabs($args, $ajax_list_posts_call = 'list_posts_with_tabs', $ajax_embed_container = '#list-posts-with-tabs');

	// 分类 切换
	_wnd_categories_tabs($args, $ajax_list_posts_call = 'list_posts_with_tabs', $ajax_embed_container = '#list-posts-with-tabs');

	// 输出列表
	$args['wnd_list_template']($args);

	// 容器结束
	echo '</div>';
}