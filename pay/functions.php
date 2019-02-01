<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}
/**
 *@since 2019.01.30 用户余额充值
 */

// 创建充值订单
function wnd_insert_recharge($user_id, $money, $note = '', $post_status = 'pending') {
	// $user_id = get_current_user_id ();
	$postarr = array(
		'post_author' => $user_id,
		'post_content' => $note,
		'post_title' => $money,
		'post_name' => $user_id . '-' . date("ymdHis"),
		'post_status' => $post_status,
		'post_type' => 'recharge',
	);
	$post_id = wp_insert_post($postarr);

	if ($post_id) {
		return $post_id;
	} else {
		return false;
	}
}

// 充值成功更新充值订单状态 id 为数据库表id非用户字段
function wnd_update_recharge($ID, $status, $note = '') {

	$postarr = array(
		'ID' => $ID,
		'post_content' => $note,
		'post_status' => $status,
	);
	$post_id = wp_update_post($postarr);

	return $post_id;
}

// 充值成功 写入用户 字段
function wnd_inc_user_money($user_id, $money) {

	// $money 为负数时表示消费
	wnd_inc_wnd_user_meta($user_id, 'money', $money);

	// $money 为负数 更新消费金额记录
	if ($money < 0) {
		wnd_inc_wnd_user_meta($user_id, 'expense', $money * -1);
	}

	// 整站按月统计充值和消费
	wnd_fin_stats($money);

}

// 获取用户账户金额
function wnd_get_user_money($user_id) {

	$money = wnd_get_user_meta($user_id, 'money');
	$money = $money ?: 0;
	return $money;
}

// 获取用户消费
function wnd_get_user_expense($user_id) {

	$expense = wnd_get_user_meta($user_id, 'expense');
	$expense = $expense ?: 0;
	return $expense;
}

// 订单价格
function wnd_get_post_price($post_id) {

	$price = wnd_get_post_meta($post_id, 'price') ?: get_post_meta($post_id, 'price', 1);
	$price = $price ?: wnd_get_option('wndwp', 'wnd_post_default_price');
	return $price;
}

// 常规消费记录写入
function wnd_insert_expense($user_id, $price, $note) {
	$postarr = array(
		'post_author' => $user_id,
		'post_title' => $price,
		'post_content' => $note,
		'post_name' => $user_id . '-' . date("ymdHis"),
		'post_status' => 'private',
		'post_type' => 'expense',
		// 'post_parent' => $post_parent,
	);
	$expense_id = wp_insert_post($postarr);
	return $expense_id;
}

/**
统计整站财务数据，当用户发生充值或消费行为时触发
按月统计，每月生成两条post数据

用户充值post_type:stats_re
用户消费post_type:stats_ex
根据用户金额变动>0 或者 <0 判断类型
用户金额记录：post_title，记录值均为正数

写入前，按post type 和时间查询，如果存在记录则更新记录，否则写入一条记录
 **/
function wnd_fin_stats($money = 0) {

	if (!$money) {
		return;
	}

	if ($money > 0) {
		$post_type = 'stats_re';
	} else {
		$post_type = 'stats_ex';
	}

	$year = (int) date('Y', time());
	$month = (int) date('m', time());
	$slug = $post_type . '-' . $year . '-' . $month;

	// 查询统计post
	$date_query = array(
		array(
			'year' => $year,
			'month' => $month,
		),
	);
	$args = array(
		'posts_per_page' => 1,
		'name' => $slug,
		'author' => 1,
		'post_type' => $post_type,
		'post_status' => 'private',
		'date_query' => $date_query,
		'no_found_rows' => true,
	);
	$query = get_posts($args);

	// 更新统计
	if ($query) {

		$stats_post = $query[0];
		$old_money = $stats_post->post_title;
		$new_money = $old_money + abs($money);
		wp_update_post(array('ID' => $stats_post->ID, 'post_title' => $new_money));

		// 新增统计
	} else {

		wp_insert_post(array('post_author' => 1, 'post_type' => $post_type, 'post_title' => abs($money), 'post_status' => 'private', 'post_name' => $slug));

	}

}
