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
		<?php while ($query->have_posts()): $query->the_post(); global $post;?>
		<tr>
			<td class="is-narrow"><?php the_time('m-d H:i');?></td>
			<td><a href="<?php echo the_permalink(); ?>" target="_blank"><?php echo $post->post_title;?></a></td>
			<th class="is-narrow"><?php echo $post->post_status; ?></th>
			<td class="is-narrow">
				<a onclick="wnd_ajax_modal('post_info','post_id=<?php echo $post->ID;?>&color=<?php echo $color; ?>')">预览</a>
				<?php if (current_user_can('edit_post', $post->ID)) {?>
				<a onclick="wnd_ajax_modal('post_status_form','<?php echo $post->ID;?>')">[管理]</a>
				<?php }?>
			</td>
		</tr>
		<?php endwhile;?>
	</tbody>
	<?php wp_reset_postdata(); //重置查询?>
</table>
<?php

	// 没有内容
	else :
		$no_more_text = ($args['paged'] >= 2) ? '没有更多内容！' : '没有匹配的内容！';
		echo '<div class="message is-warning"><div class="message-body">' . $no_more_text . '</div></div>';
	endif;

	// 分页
	if (!wp_doing_ajax()) {
		_wnd_next_page($posts_per_page, $query->post_count, $pages_key);
	} else {
		_wnd_ajax_next_page(__FUNCTION__, $args);
	}

	?>
<?php
// end function

}

