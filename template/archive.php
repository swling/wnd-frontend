<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
*@since 2019.02.15 简单分页
*不查询总数的情况下，简单实现下一页翻页
*/
function _wnd_next_page($posts_per_page,$current_post_count,$pages_key='pages'){ 

    $paged = ( isset($_GET[$pages_key]) ) ? intval($_GET[$pages_key]) : 1;

	echo '<nav class="pagination is-centered" role="navigation" aria-label="pagination">';
	echo '<ul class="pagination-list">';

		if( $paged >= 2 ){
	        echo '<li><a class="pagination-link" href="' .add_query_arg($pages_key,$paged - 1). '">上一页</a>';
	    }
	    if( $current_post_count >= $posts_per_page ){
		    echo '<li><a class="pagination-link" href="' .add_query_arg($pages_key,$paged + 1). '">下一页</a>';
	    }
	echo '</ul>';
	echo '</nav>';

}

/**
*@since 2019.02.15
*以表格形式输出WordPress文章列表
*/
function _wnd_post_list($query_args='',$pages_key='pages',$color='is-primary'){

	$paged = ( isset($_GET[$pages_key]) ) ? intval($_GET[$pages_key]) : 1;
	$defaults = array(
		'posts_per_page' => 20,
		'paged' => $paged,
		'post_type' => 'post',
		'post_status' => 'publish',
		//'date_query'=>$date_query,
		'no_found_rows' => true, //$query->max_num_pages;
	);
	$query_args = wp_parse_args( $query_args, $defaults);
	$query = new WP_Query( $query_args );
?>	
<?php if ($query->have_posts()) :?>
<table class="table is-fullwidth is-hoverable is-striped">
	<thead>
		<tr>
			<th class="is-narrow"><abbr title="Position">日期</abbr></th>
			<th>标题</th>
			<th class="is-narrow">操作</th>
		</tr>
	</thead>
	<tbody>
		<?php while ($query->have_posts()) : $query->the_post(); ?>
		<tr>
			<td class="is-narrow"><?php the_time('m-d H:i');?></td>
			<td><a href="<?php echo the_permalink();?>" target="_blank"><?php the_title();?></a></td>
			<td class="is-narrow">
				<a onclick="wnd_ajax_modal('post_info','post_id=<?php the_ID();?>&color=<?php echo $color;?>')">预览</a>
				<?php if(current_user_can( 'edit_post',get_the_ID())){ ?>
				<a onclick="wnd_ajax_modal('post_status_form','<?php the_ID();?>')">[管理]</a>
				<?php }?>
			</td>
		</tr>
		<?php endwhile; ?>
	</tbody>
	<?php wp_reset_postdata(); //重置查询?>
</table>
<?php else : ?>
<div class="message is-primary"><div class="message-body">没有匹配的内容！</div></div>	
<?php endif; ?>
<?php if($pages_key) _wnd_next_page($args['posts_per_page'],$query->post_count,$pages_key);?>
<?php

}

/**
*@since 2019.02.17
*以表格形式输出自定义常规object列表
*/
function _wnd_objects_list($query_args, $pages_key='pages',$color='is-primary'){

	$paged = ( isset($_GET[$pages_key]) ) ? intval($_GET[$pages_key]) : 1;
	$defaults = array(
		'user_id'	=> get_current_user_id(),
		'per_page' => 20,
		'type' => '',
		'status' => '',
	);
	$query_args = wp_parse_args( $query_args, $defaults);

	$user_id = $query_args['user_id'];
	$type = $query_args['type'];
	$status = $query_args['status'];
	$per_page = $query_args['per_page'];
	$offset = $per_page * ($paged -1);

	global $wpdb;
	$objects = $wpdb->get_results("
		SELECT * FROM $wpdb->wnd_objects WHERE user_id = {$user_id} AND type = '{$type}' and status = '{$status}' LIMIT {$offset},{$per_page}",
		OBJECT
	);
	$object_count = count($objects);

?>	
<?php if ($objects) :?>
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
			<td class="is-narrow"><?php echo date('m-d:H:i',$object->time);?></td>
			<td><a href ="<?php if( $object->object_id ) echo get_permalink( $object->object_id );else echo '#'?>" target="_blank"><?php echo $object->title;?></a></td>
			<td class="is-narrow">
				<a onclick="wnd_ajax_modal('post_info','post_id=<?php echo $object->ID;?>&color=<?php echo $color;?>')">预览</a>
				<a onclick="wnd_ajax_modal('post_status_form','<?php echo $object->ID;?>')">[管理]</a>
			</td>
		</tr>
		<?php  } unset($object) ?>
	</tbody>
</table>
<?php else : ?>
<div class="message is-primary"><div class="message-body">没有匹配的内容！</div></div>	
<?php endif; ?>
<?php if($pages_key) _wnd_next_page($per_page, $object_count, $pages_key);?>
<?php

}

/**
*@since 2019.02.17
*以表格形式输出用户财务信息列表
*/
function _wnd_user_fin_list($query_args, $pages_key='pages',$color='is-primary'){

	$paged = ( isset($_GET[$pages_key]) ) ? intval($_GET[$pages_key]) : 1;
	$defaults = array(
		'user_id'	=> get_current_user_id(),
		'per_page' => 20,
		'type' => '',
		'status' => '',
	);
	$query_args = wp_parse_args( $query_args, $defaults);

	$user_id = $query_args['user_id'];
	$type = $query_args['type'];
	$status = $query_args['status'];
	$per_page = $query_args['per_page'];
	$offset = $per_page * ($paged -1);

	global $wpdb;
	$objects = $wpdb->get_results("
		SELECT * FROM $wpdb->wnd_objects WHERE user_id = {$user_id} AND type = '{$type}' and status = '{$status}' LIMIT {$offset},{$per_page}",
		OBJECT
	);
	$object_count = count($objects);

?>	
<?php if ($objects) :?>
<table class="table is-fullwidth is-hoverable is-striped">
	<thead>
		<tr>
			<th class="is-narrow"><abbr title="Position">日期</abbr></th>
			<th class="is-narrow">金额</th>
			<th>标题</th>
			<th class="is-narrow">操作</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($objects as $object) { ?>
		<tr>
			<td class="is-narrow"><?php echo date('m-d:H:i',$object->time);?></td>
			<td class="is-narrow"><?php echo $object->value;?></td>
			<td><a href ="<?php if( $object->object_id ) echo get_permalink( $object->object_id );else echo '#'?>" target="_blank"><?php echo $object->title;?></a></td>
			<td class="is-narrow">
				<a onclick="wnd_ajax_modal('post_info','post_id=<?php echo $object->ID;?>&color=<?php echo $color;?>')">预览</a>
				<a onclick="wnd_ajax_modal('post_status_form','<?php echo $object->ID;?>')">[管理]</a>
			</td>
		</tr>
		<?php  } unset($object) ?>
	</tbody>
</table>
<?php else : ?>
<div class="message is-primary"><div class="message-body">没有匹配的内容！</div></div>	
<?php endif; ?>
<?php if($pages_key) _wnd_next_page($per_page, $object_count, $pages_key);?>
<?php

}
