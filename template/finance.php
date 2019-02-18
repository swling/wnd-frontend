<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.02.18 封装用户财务中心
 */
function _wnd_user_fin($args = array()) {

	// ajax请求类型
	$ajax_type = $_POST['ajax_type'] ?? 'modal';
	$user_id = get_current_user_id();

	$defaults = array(
		'type' => 'recharge',
		'status' => '',
	);
	$args = wp_parse_args($args, $defaults);
	$type = $_REQUEST['type'] ?? $args['type'];
	$status = $_REQUEST['status'] ?? $args['status'];

	$expense_is_active = $type == 'expense' ? 'class="is-active"' : '';
	$recharge_is_active = $type == 'recharge' ? 'class="is-active"' : '';
?>
<div id="user-fin">
	<nav class="level">
	  <div class="level-item has-text-centered">
	    <div>
	      <p class="heading">余额</p>
	      <p class="title"><?php echo wnd_get_user_money($user_id);?></p>
	    </div>
	  </div>
	  <div class="level-item has-text-centered">
	    <div>
	      <p class="heading">消费</p>
	      <p class="title"><?php echo wnd_get_user_expense($user_id);?></p>
	    </div>
	  </div>
	  <div class="level-item has-text-centered">
	    <div>
	      <p class="heading">佣金</p>
	      <p class="title"><?php echo wnd_get_user_commission($user_id);?></p>
	    </div>
	  </div>
	</nav>

	<div class="level">
		<div class="level-item">
			<button class="button" onclick="wnd_ajax_modal('recharge_form')">充值</button>
		</div>
	</div>

	<div class="tabs is-centered">
		<ul>
		<?php		
		if (wp_doing_ajax()) {
			if ($ajax_type == 'modal') {
				echo '<li ' . $expense_is_active . ' ><a onclick="wnd_ajax_modal(\'user_fin\',\'type=expense\');">消费记录</a></li>';
				echo '<li ' . $recharge_is_active . ' ><a onclick="wnd_ajax_modal(\'user_fin\',\'type=recharge\');">充值记录</a></li>';
			} else {
				echo '<li ' . $expense_is_active . ' ><a onclick="wnd_ajax_embed(\'#user-fin\',\'user_fin\',\'type=expense\');">消费记录</a></li>';
				echo '<li ' . $recharge_is_active . ' ><a onclick="wnd_ajax_embed(\'#user-fin\',\'user_fin\',\'type=recharge\');">充值记录</a></li>';
			}
		} else {
			echo '<li ' . $expense_is_active . ' ><a href="' . add_query_arg('type', 'expense',remove_query_arg('pages')) . '">消费记录</a></li>';
			echo '<li ' . $recharge_is_active . ' ><a href="' . add_query_arg('tab', 'recharge',remove_query_arg('pages')) . '">充值记录</a></li>';
		}
		?>
		</ul>
	</div>
	<div id="user-fin-list">
	<?php _wnd_user_fin_list($args);?>
	</div>
</div>
<?php

}

/**
 *@since 2019.02.17
 *以表格形式输出用户财务信息列表
 * 列表必须放在 #user-fin-list 容器中，否则ajax环境中无法实现翻页
 */
function _wnd_user_fin_list($args) {

	$user_id = get_current_user_id();
	$defaults = array(
		'type' => 'recharge',
		'status' => '',
		'pages'	=> 1
	);
	$args = wp_parse_args($args, $defaults);

	$type = $args['type'];
	$status = $args['status'];
	$pages = $_GET['pages'] ?? $args['pages'];

	$per_page = 3;
	$offset = $per_page * ($pages - 1);

	global $wpdb;
	if ($status) {
		$objects = $wpdb->get_results("
		SELECT * FROM $wpdb->wnd_objects WHERE user_id = {$user_id} AND type = '{$type}' and status = '{$status}' ORDER BY time DESC LIMIT {$offset},{$per_page}",
			OBJECT
		);
	} else {
		$objects = $wpdb->get_results("
		SELECT * FROM $wpdb->wnd_objects WHERE user_id = {$user_id} AND type = '{$type}' ORDER BY time DESC LIMIT {$offset},{$per_page}",
			OBJECT
		);
	}
	$object_count = count($objects);

	if ($objects) {

		?>
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
	<?php foreach ($objects as $object) {?>
		<tr>
			<td class="is-narrow"><?php echo date('m-d:H:i', $object->time); ?></td>
			<td class="is-narrow"><?php echo $object->value; ?></td>
			<td><a href ="<?php if ($object->object_id) {
				echo get_permalink($object->object_id);
			} else {
				echo '#';
			}
			?>" target="_blank"><?php echo $object->title; ?></a></td>
			<td class="is-narrow">
				<a onclick="wnd_ajax_modal('post_info','post_id=<?php echo $object->ID; ?>&color=danger')">预览</a>
				<a onclick="wnd_ajax_modal('post_status_form','<?php echo $object->ID; ?>')">[管理]</a>
			</td>
		</tr>
	<?php }	unset($object); ?>
	</tbody>
</table>
<?php 
if(!wp_doing_ajax()){
	_wnd_next_page($per_page, $object_count, 'pages');
}else{
	_wnd_ajax_next_page(__FUNCTION__, $args);
}
?>
<?php } else { ?>
<div class="message is-primary"><div class="message-body">没有匹配的内容！</div></div>
<?php }?>
<?php

}

/**
 *@since 2019.01.21 充值表单
 */
function _wnd_recharge_form() {

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
	<?php do_action('_wnd_recharge_form');?>
	<?php wp_nonce_field('wnd_payment');?>
	<div class="field is-grouped is-grouped-centered">
		<button type="submit" name="submit" class="button">确认充值</button>
	</div>
</form>
<?php

}