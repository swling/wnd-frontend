<?php

/**
 *@since 2019.02.201
 *充值卡验证
 */
function _wnd_verity_recharge_card_form() {

	?>
<form id="verity-recharge-card-form" action="" method="post" onsubmit="return false">

	<div class="field">
		<div class="ajax-msg"></div>
	</div>

	<div class="field is-horizontal">
		<div class="field-body">
			<div class="field">
				<label class="label">卡号<span class="required">*</span></label>
				<div class="control">
					<input type="text" class="input" name="card" required="required" placeholder="充值卡卡号" />
				</div>
			</div>
			<div class="field">
				<label class="label">密码<span class="required">*</span></label>
				<div class="control">
					<input type="text" class="input" name="password" placeholder="充值卡密码" />
				</div>
			</div>
		</div>
	</div>

	<?php wp_nonce_field('wnd_ajax_verity_recharge_card', '_ajax_nonce');?>
	<input type="hidden" name="action" value="wnd_action">
	<input type="hidden" name="action_name" value="wnd_ajax_verity_recharge_card">
	<div class="field is-grouped is-grouped-centered">
		<button type="button" name="submit" class="button" onclick="wnd_ajax_submit('#verity-recharge-card-form')">验证并充值</button>
	</div>
</form>
<?php

}

/**
 *@since 2019.02.201
 *批量创建充值卡
 */
function _wnd_create_recharge_card_form() {

	?>
<form id="create-recharge-card-form" action="" method="post" onsubmit="return false">

	<div class="field">
		<div class="ajax-msg"></div>
	</div>

	<div class="field is-horizontal">
		<div class="field-body">
			<div class="field">
				<label class="label">金额<span class="required">*</span></label>
				<div class="control">
					<input type="number" class="input" name="value" required="required" placeholder="充值卡面值" min="1" />
				</div>
			</div>
			<div class="field">
				<label class="label">数量<span class="required">*</span></label>
				<div class="control">
					<input type="number" class="input" name="num" placeholder="充值卡数量" min="1" />
				</div>
			</div>
		</div>
	</div>

	<?php wp_nonce_field('wnd_ajax_create_recharge_card', '_ajax_nonce');?>
	<input type="hidden" name="action" value="wnd_action">
	<input type="hidden" name="action_name" value="wnd_ajax_create_recharge_card">
	<div class="field is-grouped is-grouped-centered">
		<button type="button" name="submit" class="button" onclick="wnd_ajax_submit('#create-recharge-card-form')">验证并充值</button>
	</div>
</form>
<?php

}

/**
 *@since 2019.02.15
 *以表格形式输出WordPress文章列表
 *$pages_key = 'pages', $color = 'is-primary' 仅在非ajax状态下有效
 */
function _wnd_list_recharge_card($args = array(), $pages_key = 'pages', $color = 'is-primary') {

	// 仅超级管理员
	if(!is_super_admin( )){
		return;
	}

	$defaults = array(
		'posts_per_page' => get_option('posts_per_page'),
		'paged' => 1,
		'post_status' => 'publish',
		'no_found_rows' => true, //$query->max_num_pages;
	);
	$args = wp_parse_args($args, $defaults);

	// 优先参数
	$args['paged'] = $_REQUEST[$pages_key] ?? $args['paged'];
	$args['post_status'] = $_REQUEST['tab'] ?? $args['post_status'];
	$args['post_type'] = 'recharge-card';
	// $args['post_author'] = get_current_user_id();

	$publish_is_active = $args['post_status'] == 'publish' ? 'class="is-active"' : '';
	$private_is_active = $args['post_status'] == 'private' ? 'class="is-active"' : '';	

	// 最终查询参数
	$query = new WP_Query($args);
?>
<div id="list-recharge-card">	
	<div class="tabs">
		<ul>
		<?php
		// 配置ajax请求参数
		$ajax_args_publish = array_merge($args, array('post_status' => 'publish'));
		$ajax_args_publish = http_build_query($ajax_args_publish);

		// 配置ajax请求参数
		$ajax_args_private = array_merge($args, array('post_status' => 'private'));
		$ajax_args_private = http_build_query($ajax_args_private);		

		if (wp_doing_ajax()) {
			if ($ajax_type == 'modal') {
				echo '<li ' . $publish_is_active . ' ><a onclick="wnd_ajax_modal(\'list_recharge_card\',\''.$ajax_args_publish.'\');">生效中</a></li>';
				echo '<li ' . $private_is_active . ' ><a onclick="wnd_ajax_modal(\'list_recharge_card\',\''.$ajax_args_private.'\');">已使用</a></li>';
			} else {
				echo '<li ' . $publish_is_active . ' ><a onclick="wnd_ajax_embed(\'#list-recharge-card\',\'list_recharge_card\',\''.$ajax_args_publish.'\');">生效中</a></li>';
				echo '<li ' . $private_is_active . ' ><a onclick="wnd_ajax_embed(\'#list-recharge-card\',\'list_recharge_card\',\''.$ajax_args_private.'\');">已使用</a></li>';
			}
		} else {
			echo '<li ' . $publish_is_active . ' ><a href="' . add_query_arg('tab', 'publish', remove_query_arg('pages')) . '">生效中</a></li>';
			echo '<li ' . $private_is_active . ' ><a href="' . add_query_arg('tab', 'private', remove_query_arg('pages')) . '">已使用</a></li>';
		}
		?>
		</ul>
	</div>
<?php

	if ($query->have_posts()):

?>
	<table class="table is-fullwidth is-hoverable is-striped">
		<thead>
			<tr>
				<th class="is-narrow"><abbr title="Position">日期</abbr></th>
				<th>面值</th>
				<th>卡号</th>
				<th class="is-narrow">密码</th>
				<th class="is-narrow">状态</th>
				<th class="is-narrow">操作</th>
			</tr>
		</thead>
		<tbody>
			<?php while ($query->have_posts()): $query->the_post();global $post;?>
			<tr>
				<td class="is-narrow"><?php the_time('m-d H:i');?></td>
				<td><?php echo $post->post_content; ?></td>
				<td><?php echo $post->post_name; ?></td>
				<th class="is-narrow"><?php echo $post->post_password; ?></th>
				<th class="is-narrow"><?php echo $post->post_status; ?></th>
				<td class="is-narrow">
					<?php if (current_user_can('edit_post', $post->ID)) {?>
					<a onclick="wnd_ajax_modal('post_status_form','<?php echo $post->ID; ?>')">[管理]</a>
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
</div>
<?php
// end function

}