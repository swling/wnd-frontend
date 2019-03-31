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
 *@param $function 需要请求的内容列表输出函数名
 *@param $function 的参数
 *函数应该与请求函数处于同一个父元素之下，父容器必须设置class
 */
function _wnd_ajax_next_page($function, $args, $post_count) {

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

		// 获取翻页元素的父元素Iclass
		$container = '\'.\' + $(this).parents(\'nav\').parent().attr(\'class\')';

		$next_onclick = 'wnd_ajax_embed(' . $container . ',' . $js_next_args . ')';
		$pre_onclick = 'wnd_ajax_embed(' . $container . ',' . $js_pre_args . ')';
	}

	echo '<nav class="pagination is-centered" role="navigation" aria-label="pagination">';
	echo '<ul class="pagination-list">';
	if ($current_pages >= 2) {
		echo '<li><a class="pagination-link" onclick="' . $pre_onclick . '">上一页</a>';
	}
	if ($post_count >= $args['posts_per_page']) {
		echo '<li><a class="pagination-link" onclick="' . $next_onclick . '">下一页</a>';
	}

	echo '</ul>';
	echo '</nav>';

}

/**
 *@since 2019.02.15
 *以表格形式输出WordPress文章列表
 *@param $args wp_query参数
 */
function _wnd_list_posts_by_table($args = '') {

	$args = wp_parse_args($args);

	$query = new WP_Query($args);

	if ($query->have_posts()):

	?>
<table class="table is-fullwidth is-hoverable is-striped">
	<thead>
		<tr>
			<th class="is-narrow is-hidden-mobile">日期</th>
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
			<?php if (current_user_can('edit_post', $post->ID)) {?>
			<th class="is-narrow is-hidden-mobile"><?php echo apply_filters('_wnd_list_posts_status_text', $post->post_status, $post->post_type); ?></th>
			<?php }?>
			<td class="is-narrow is-hidden-mobile">
				<a onclick="wnd_ajax_modal('post_info','post_id=<?php echo $post->ID; ?>')"><i class="fas fa-info-circle"></i></a>
				<?php if (current_user_can('edit_post', $post->ID)) {?>
				<a onclick="wnd_ajax_modal('post_status_form','<?php echo $post->ID; ?>')"><i class="fas fa-cog"></i></a>
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
		_wnd_next_page($args['posts_per_page'], $query->post_count, 'pages');
	} else {
		_wnd_ajax_next_page(__FUNCTION__, $args, $query->post_count);
	}

	?>
<?php
// end function

}

/**
 *@since 2019.03.05
 *调用主题文章输出列表模板
 *将对应的文章模板放置在主题文件夹中，具体形式：template-parts/list/list-post_type.php
 *@param $args  wp_query $args
 *@see get_template_part() @link https://developer.wordpress.org/reference/functions/get_template_part/
 */
function _wnd_list_posts_by_template($args = '') {

	$args = wp_parse_args($args);

	$query = new WP_Query($args);

	if ($query->have_posts()) {

		while ($query->have_posts()) {
			$query->the_post();

			get_template_part('template-parts/list/list', get_post_type());

			wp_reset_postdata(); //重置查询

		}

		// 没有内容
	} else {

		$no_more_text = ($args['paged'] >= 2) ? '没有更多内容！' : '没有匹配的内容！';
		echo '<div class="message is-warning"><div class="message-body">' . $no_more_text . '</div></div>';
	}

	// 分页
	if (!wp_doing_ajax()) {
		_wnd_next_page($args['posts_per_page'], $query->post_count, 'pages');
	} else {
		_wnd_ajax_next_page(__FUNCTION__, $args, $query->post_count);
	}

}
