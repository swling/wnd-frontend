<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.02.18 封装用户财务中心
 */
function _wnd_user_fin_panel($args = array()) {

	if (!is_user_logged_in()) {
		return;
	}

	// ajax请求类型
	$ajax_type = $_POST['ajax_type'] ?? 'modal';
	$user_id = get_current_user_id();

	$defaults = array(
		'post_type' => 'expense',
		'post_status' => 'any',
		'posts_per_page' => get_option('posts_per_page'),
	);
	$args = wp_parse_args($args, $defaults);

	// 优先参数
	$args['post_type'] = $_REQUEST['type'] ?? $args['post_type'];
	// $status = $_REQUEST['status'] ?? $args['post_status'];

	$expense_is_active = $args['post_type'] == 'expense' ? 'class="is-active"' : '';
	$recharge_is_active = $args['post_type'] == 'recharge' ? 'class="is-active"' : '';

?>
<div id="user-fin">
	<nav class="level">
		<div class="level-item has-text-centered">
			<div>
				<p class="heading">余额</p>
				<p class="title"><?php echo wnd_get_user_money($user_id); ?></p>
			</div>
		</div>
		<div class="level-item has-text-centered">
			<div>
				<p class="heading">消费</p>
				<p class="title"><?php echo wnd_get_user_expense($user_id); ?></p>
			</div>
		</div>
		<?php if(wnd_get_option('wndwp', 'wnd_commission_rate') ) { ?>
		<div class="level-item has-text-centered">
			<div>
				<p class="heading">佣金</p>
				<p class="title"><?php echo wnd_get_user_commission($user_id); ?></p>
			</div>
		</div>
		<?php }?>
	</nav>

	<div class="level">
		<div class="level-item">
			<button class="button" onclick="wnd_ajax_modal('recharge_form')">余额充值</button>
		</div>
		<?php if(is_super_admin( )) { ?>
		<div class="level-item">
			<button class="button" onclick="wnd_ajax_modal('admin_recharge_form')">管理员充值</button>
		</div>
		<?php } ?>
	</div>

	<div class="tabs">
		<ul>
		<?php

		// 配置ajax请求参数
		$ajax_args_expense = array_merge($args, array('post_type' => 'expense'));
		unset($ajax_args_expense['paged']);
		$ajax_args_expense = http_build_query($ajax_args_expense);

		// 配置ajax请求参数
		$ajax_args_recharge = array_merge($args, array('post_type' => 'recharge'));
		unset($ajax_args_recharge['paged']);
		$ajax_args_recharge = http_build_query($ajax_args_recharge);	

		if (wp_doing_ajax()) {
			if ($ajax_type == 'modal') {
				echo '<li ' . $expense_is_active . ' ><a onclick="wnd_ajax_modal(\'user_fin_panel\',\''.$ajax_args_expense.'\');">消费记录</a></li>';
				echo '<li ' . $recharge_is_active . ' ><a onclick="wnd_ajax_modal(\'user_fin_panel\',\''.$ajax_args_recharge.'\');">充值记录</a></li>';
			} else {
				echo '<li ' . $expense_is_active . ' ><a onclick="wnd_ajax_embed(\'#user-fin\',\'user_fin_panel\',\''.$ajax_args_expense.'\');">消费记录</a></li>';
				echo '<li ' . $recharge_is_active . ' ><a onclick="wnd_ajax_embed(\'#user-fin\',\'user_fin_panel\',\''.$ajax_args_recharge.'\');">充值记录</a></li>';
			}
		} else {
			echo '<li ' . $expense_is_active . ' ><a href="' . add_query_arg('type', 'expense', remove_query_arg('pages')) . '">消费记录</a></li>';
			echo '<li ' . $recharge_is_active . ' ><a href="' . add_query_arg('type', 'recharge', remove_query_arg('pages')) . '">充值记录</a></li>';
		}
		?>
		</ul>
	</div>
	<div id="user-fin-list">
	<?php
	_wnd_list_user_fin($args);
	?>
	</div>
</div>
<?php

}

/**
 *@since 2019.02.15
 *以表格形式输出WordPress文章列表
 *$pages_key = 'pages', $color = 'is-primary' 仅在非ajax状态下有效
 */
function _wnd_list_user_fin($args = array(),$pages_key = 'pages', $color = 'is-primary') {

	// $paged = $_REQUEST[$pages_key] ?? $args['paged'] ?? 1;
	$defaults = array(
		'posts_per_page' => get_option('posts_per_page'),
		'paged' => 1,
		'post_type' => 'expense',
		'post_status' => 'publish',
		'no_found_rows' => true, //$query->max_num_pages;
	);
	$args = wp_parse_args($args, $defaults);

	// 优先参数
	$args['paged'] = $_REQUEST[$pages_key] ?? $args['paged'];
	$args['post_author'] = get_current_user_id();

	$query = new WP_Query($args);

	if ($query->have_posts()):

?>
<table class="table is-fullwidth is-hoverable is-striped">
	<thead>
		<tr>
			<th class="is-narrow"><abbr title="Position">日期</abbr></th>
			<th>金额</th>
			<th>详情</th>
			<th class="is-narrow">状态</th>
			<th class="is-narrow">操作</th>
		</tr>
	</thead>
	<tbody>
		<?php while ($query->have_posts()): $query->the_post();global $post;?>
			<tr>
				<td class="is-narrow"><?php the_time('m-d H:i');?></td>
				<td><?php echo $post->post_content; ?></td>
				<?php if( $post->post_parent ){ ?>	
				<td><a href="<?php the_permalink($post->post_parent); ?>" target="_blank"><?php echo $post->post_title; ?></a></td>
				<?php } else { ?>
				<td><?php echo $post->post_title; ?></td>
			 	<?php } ?>

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

	// 分页
	if (!wp_doing_ajax()) {
		_wnd_next_page($posts_per_page, $query->post_count, $pages_key);
	} else {
		_wnd_ajax_next_page(__FUNCTION__, $args);
	}

	// 没有内容
	else :
		$no_more_text = ($args['paged'] >= 2) ? '没有更多内容！' : '没有匹配的内容！';
		echo '<div class="message is-warning"><div class="message-body">' . $no_more_text . '</div></div>';
	endif;

?>
<?php
// end function

}

/**
 *@since 2019.01.21 充值表单
 */
function _wnd_recharge_form() {

	if(wnd_get_option('wndwp','wnd_alipay_appid')){

?>
<style>
/*单选样式优化*/
.radio-toolbar,
.paytype {
	display: flex;
	align-items: center;
	justify-content: center;
}

.radio-toolbar input[type="radio"] {
	display: none;
}

.radio-toolbar label {
	display: inline-block;
	cursor: pointer;
	border-radius: 3px;
	background: #f5f5f5;
	text-align: center;
}

.radio-toolbar label {
	font-size: 18px;
	padding: 10px 20px;
	margin: 1.5%;
	min-width: 80px;
}

.radio-toolbar input[type="radio"]:checked+label {
	background-color: #00d1b2;
	color: #FFF;
}
</style>
<form id="recharge" action="<?php echo wnd_get_do_url(); ?>?action=payment" method="post">
	<div class="field">
		<div class="ajax-msg"></div>
	</div>
	<div class="radio-toolbar field level content">
		<div class="level-item">
			<input id="radio1" required="required" name="money" type="radio" value="0.01" checked="checked">
			<label for="radio1">¥0.01</label>
		</div>

		<div class="level-item">
			<input id="radio2" required="required" name="money" type="radio" value="10">
			<label for="radio2">¥10</label>
		</div>

		<div class="level-item">
			<input id="radio3" required="required" name="money" type="radio" value="100">
			<label for="radio3">¥100</label>
		</div>

		<div class="level-item">
			<input id="radio4" required="required" name="money" type="radio" value="500">
			<label for="radio4">¥500</label>
		</div>

		<div class="level-item">
			<input id="radio5" required="required" name="money" type="radio" value="1000">
			<label for="radio5">¥1000</label>
		</div>
	</div>
	<div class="paytype field level is-mobile">
		<div class="level-item">
			<label for="paytype1"><img src="https://t.alipayobjects.com/images/T1HHFgXXVeXXXXXXXX.png"></label>
			<input type="radio" name="paytype" value="alipay" checked="checked" />
		</div>
	</div>
	<?php wp_nonce_field('wnd_payment');?>
	<div class="field is-grouped is-grouped-centered">
		<button type="submit" name="submit" class="button">支付宝充值</button>
	</div>	
<?php } ?>
	<?php do_action('_wnd_recharge_form');?>
</form>
<?php

}

/**
*@since 2019.02.22
*管理员手动增加用户余额
*/
function _wnd_admin_recharge_form(){

?>
<form id="admin-recharge-form" action="" method="post" onsubmit="return false">

	<div class="field">
		<div class="ajax-msg"></div>
	</div>

	<div class="field is-horizontal">
		<div class="field-body">
			<div class="field">
				<label class="label">用户<span class="required">*</span></label>
				<div class="control">
					<input type="text" class="input" name="user_field" required="required" placeholder="用户名、ID、或邮箱" />
				</div>
			</div>
			<div class="field">
				<label class="label">金额<span class="required">*</span></label>
				<div class="control">
					<input type="text" class="input" name="money" placeholder="充值金额（负数可扣款）" />
				</div>
			</div>
		</div>
	</div>
	<div class="field">
		<div class="control">
			<input name="remarks" class="input" placeholder="备注（可选）">
		</div>
	</div>	

	<?php wp_nonce_field('wnd_ajax_admin_recharge', '_ajax_nonce');?>
	<input type="hidden" name="action" value="wnd_action">
	<input type="hidden" name="action_name" value="wnd_ajax_admin_recharge">
	<div class="field is-grouped is-grouped-centered">
		<button type="button" name="submit" class="button" onclick="wnd_ajax_submit('#admin-recharge-form')">确认充值</button>
	</div>
</form>
<?php

}