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

	// $paged = $_REQUEST[$pages_key] ?? $args['paged'] ?? 1;
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
			<th class="is-narrow"><abbr title="Position">日期</abbr></th>
			<th>标题</th>
			<th class="is-narrow">状态</th>
			<th class="is-narrow">操作</th>
		</tr>
	</thead>
	<tbody>
		<?php while ($query->have_posts()): $query->the_post(); ?>
		<tr>
			<td class="is-narrow"><?php the_time('m-d H:i');?></td>
			<td><a href="<?php echo the_permalink(); ?>" target="_blank"><?php the_title();?></a></td>
			<th class="is-narrow"><?php echo get_post_status(); ?></th>
			<td class="is-narrow">
				<a onclick="wnd_ajax_modal('post_info','post_id=<?php the_ID();?>&color=<?php echo $color; ?>')">预览</a>
				<?php if (current_user_can('edit_post', get_the_ID())) {?>
				<a onclick="wnd_ajax_modal('post_status_form','<?php the_ID();?>')">[管理]</a>
				<?php }?>
			</td>
		</tr>
		<?php endwhile;?>
	</tbody>
	<?php wp_reset_postdata(); //重置查询?>
</table>
<?php

	// 分页
	if (!wp_doing_ajax()) {
		_wnd_next_page($posts_per_page, $query->post_count, $pages_key);
	} else {
		_wnd_ajax_next_page(__FUNCTION__, $args);
	}

// 没有内容
	else :
		$no_more_text = ($args['paged'] >= 2) ? '没有更多内容！' : '没有匹配的内容！';
		echo '<div class="message is-primary"><div class="message-body">' . $no_more_text . '</div></div>';
	endif;

	?>
<?php
// end function

}

/**
 *@since 2019.02.17
 *以表格形式输出当前用户自定义常规object列表
 *$pages_key = 'pages', $color = 'is-primary' 仅在非ajax状态下有效
 */
function _wnd_list_objects($args = array(), $pages_key = 'pages', $color = 'is-primary') {

	$defaults = array(
		'posts_per_page' => get_option('posts_per_page'),
		'type' => 'expense',
		'status' => 'success',
		'paged' => 1,
	);
	$args = wp_parse_args($args, $defaults);

	$user_id = get_current_user_id();
	$type = $args['type'];
	$status = $args['status'];

	// 分页
	$posts_per_page = $args['posts_per_page'];
	$paged = $_REQUEST[$pages_key] ?? $args['paged'] ?? 1;
	$offset = $posts_per_page * ($paged - 1);

	global $wpdb;
	$objects = $wpdb->get_results(
		"SELECT * FROM $wpdb->wnd_objects WHERE user_id = {$user_id} AND type = '{$type}' and status = '{$status}' ORDER BY time DESC LIMIT {$offset},{$posts_per_page}",
		OBJECT
	);
	$object_count = count($objects);

	if ($objects):

?>
<table class="table is-fullwidth is-hoverable is-striped">
	<thead>
		<tr>
			<th class="is-narrow"><abbr title="Position">日期</abbr></th>
			<th>标题</th>
			<th class="is-narrow">操作</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($objects as $object) { ?>
		<tr>
			<td class="is-narrow"><?php echo date('m-d:H:i', $object->time); ?></td>
			<td><a href ="<?php if ($object->object_id) echo get_permalink($object->object_id); else echo '#';?>" target="_blank"><?php echo $object->title; ?></a></td>
			<td class="is-narrow">
				<a onclick="wnd_ajax_modal('post_info','post_id=<?php echo $object->ID; ?>&color=<?php echo $color; ?>')">预览</a>
				<a onclick="wnd_ajax_modal('post_status_form','<?php echo $object->ID; ?>')">[管理]</a>
			</td>
		</tr>
		<?php }	unset($object); ?>
	</tbody>
</table>
<?php

	// 分页
	if (!wp_doing_ajax()) {
		_wnd_next_page($posts_per_page, $object_count, $pages_key);
	} else {
		_wnd_ajax_next_page(__FUNCTION__, $args);
	}

// 没有内容
	else :
		$no_more_text = ($paged >= 2) ? '没有更多内容！' : '没有匹配的内容！';
		echo '<div class="message is-primary"><div class="message-body">' . $no_more_text . '</div></div>';
	endif;
	?>
<?php

// end function
}

/**
 *@since 2019.02.19 封装前端当前用户内容管理
 *@param array or string ：wp_query args
 */
function _wnd_list_user_posts($args = array()) {

	if (!is_user_logged_in()) {
		echo '<div class="message is-danger"><div class="message-body">请登录！</div></div>';
		return;
	}

	// ajax请求类型
	$ajax_type = $_POST['ajax_type'] ?? 'modal';

	// 查询参数
	$defaults = array(
		'post_status' => 'any',
		'post_type' => 'post',
	);
	$args = wp_parse_args($args, $defaults);
	$args['post_type'] = $_REQUEST['tab'] ?? $args['post_type']; // 类型
	$args['author'] = get_current_user_id(); // 不可通过外部参数更改的参数（仅查询当前用户内容）

	// 容器开始
	echo '<div id="user-posts">';
	echo '<div class="tabs"><ul class="tab">';

	// 查询内容并输出导航链接
	$post_types = get_post_types(array('public' => true), $output = 'objects', $operator = 'and');
	unset($post_types['page'], $post_types['attachment']); // 排除页面和附件

	foreach ($post_types as $post_type) {

		$active = ($args['post_type'] == $post_type->name) ? 'class="is-active"' : '';

		// 配置ajax请求参数
		$ajax_args = array_merge($args, array('post_type' => $post_type->name));
		$ajax_args = http_build_query($ajax_args);

		if (wp_doing_ajax()) {
			if ($ajax_type == 'modal') {
				echo '<li><a onclick="wnd_ajax_modal(\'list_posts\',\'' . $ajax_args . '\');">' . $post_type->label . '</a></li>';
			} else {
				echo '<li><a onclick="wnd_ajax_embed(\'#user-posts .posts-list\',\'list_posts\',\'' . $ajax_args . '\');">' . $post_type->label . '</a></li>';
			}
		} else {
			echo '<li ' . $active . '><a href="' . add_query_arg('tab', $post_type->name, remove_query_arg('pages')) . '">' . $post_type->label . '</a></li>';
		}

	}
	unset($post_type);
	echo '</ul></div>';

	echo '<div class="posts-list">';
	_wnd_list_posts($args);
	echo '</div>';

	// 容器结束
	echo '</div>';

}