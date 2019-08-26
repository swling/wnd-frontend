<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.02.11 查询是否已经支付
 *@param int 	$user_id 	用户ID
 *@param int 	$object_id  Post ID
 *
 *@return bool 	是否已支付
 **/
function wnd_user_has_paid($user_id, $object_id) {
	if (!$user_id or !$object_id) {
		return false;
	}

	$user_has_paid = wp_cache_get($user_id . $object_id, 'user_has_paid');

	if (false === $user_has_paid) {
		$args = array(
			'posts_per_page' => 1,
			'post_type' => 'order',
			'post_parent' => $object_id,
			'author' => $user_id,
			'post_status' => 'success',
		);

		// 不能将布尔值直接做为缓存结果，会导致无法判断是否具有缓存，转为整型 0/1
		$user_has_paid = empty(get_posts($args)) ? 0 : 1;
		wp_cache_set($user_id . $object_id, $user_has_paid, 'user_has_paid');
	}

	return ($user_has_paid === 1 ? true : false);
}

/**
 *@since 2019.03.29 查询订单统计
 *@param 	int 	$object_id 	商品ID
 *
 *@return 	int 	order count
 **/
function wnd_get_order_count($object_id) {

	// 删除15分钟前未完成的订单，并扣除订单统计
	$args = array(
		'posts_per_page' => -1,
		'post_type' => 'order',
		'post_parent' => $object_id,
		'post_status' => 'pending',
		'date_query' => array(
			array(
				'column' => 'post_date',
				'before' => date('Y-m-d H:i:s', current_time('timestamp', $gmt = 0) - 900),
				'inclusive' => true,
			),
		),
	);
	foreach (get_posts($args) as $post) {
		/**
		 * 此处不直接修正order_count，而是在删除订单时，通过action修正order_count @see wnd_action_deleted_post
		 * 以此确保订单统计的准确性，如用户主动删除，或其他原因人为删除订单时亦能自动修正订单统计
		 */
		wp_delete_post($post->ID, $force_delete = true);
	}
	unset($post, $args);

	// 返回清理过期数据后的订单统计
	return wnd_get_post_meta($object_id, 'order_count') ?: 0;
}

/**
 * 充值成功 写入用户 字段
 *
 *@param 	int 	$user_id 	用户ID
 *@param 	float 	$money 		金额
 *
 */
function wnd_inc_user_money($user_id, $money) {
	$new_money = wnd_get_user_money($user_id) + $money;
	$new_money = number_format($new_money, 2, '.', '');
	wnd_update_user_meta($user_id, 'money', $new_money);

	// $money 为负数 更新消费金额记录
	if ($money < 0) {
		wnd_inc_wnd_user_meta($user_id, 'expense', number_format($money, 2, '.', '') * -1);
	}

	// 整站按月统计充值和消费
	wnd_update_fin_stats($money);
}

/**
 *获取用户账户金额
 *@param 	int 	$user_id 	用户ID
 *@return 	float 	用户余额
 */
function wnd_get_user_money($user_id) {
	$money = wnd_get_user_meta($user_id, 'money');
	$money = is_numeric($money) ? $money : 0;
	return number_format($money, 2, '.', '');
}

/**
 *获取用户消费
 *@param 	int 	$user_id 	用户ID
 *@return 	float 	用户消费
 *
 */
function wnd_get_user_expense($user_id) {
	$expense = wnd_get_user_meta($user_id, 'expense');
	$expense = is_numeric($expense) ? $expense : 0;
	return number_format($expense, 2, '.', '');
}

/**
 *@since 2019.02.22
 *写入用户佣金
 *@param 	int 	$user_id 	用户ID
 *@param 	float 	$money 		金额
 */
function wnd_inc_user_commission($user_id, $money) {
	wnd_inc_wnd_user_meta($user_id, 'commission', number_format($money, 2, '.', ''));
}

/**
 *@since 2019.02.18 获取用户佣金
 *@param 	int 	$user_id 	用户ID
 *
 *@return 	float 	用户佣金
 */
function wnd_get_user_commission($user_id) {
	$commission = wnd_get_user_meta($user_id, 'commission');
	$commission = is_numeric($commission) ? $commission : 0;
	return number_format($commission, 2, '.', '');
}

/**
 *@since 2019.02.13
 *文章价格
 *@param 	int 	$user_id 	用户ID
 *@return  	float 	两位数的价格信息 或者 0
 */
function wnd_get_post_price($post_id) {
	$price = wnd_get_post_meta($post_id, 'price') ?: get_post_meta($post_id, 'price', 1) ?: false;
	$price = is_numeric($price) ? number_format($price, 2, '.', '') : 0;
	return apply_filters('wnd_get_post_price', $price, $post_id);
}

/**
 *@since 2019.02.12
 *用户佣金分成
 *@param 	int 	$post_id
 *@return 	float 	佣金分成
 */
function wnd_get_post_commission($post_id) {
	$commission_rate = is_numeric(wnd_get_option('wnd', 'wnd_commission_rate')) ? wnd_get_option('wnd', 'wnd_commission_rate') : 0;
	$commission = wnd_get_post_price($post_id) * $commission_rate;
	$commission = number_format($commission, 2, '.', '');
	return apply_filters('wnd_get_post_commission', $commission, $post_id);
}

/**
 *@since 2019.02.22
 *管理员手动新增用户金额
 *
 *@param 	string 		$user_field 	查询用户字段：login/emial/phone
 *@param 	float 		$total_amount 	充值金额
 *@param 	string 		$remarks 		备注
 */
function wnd_admin_recharge($user_field, $total_amount, $remarks = '') {
	if (!is_super_admin()) {
		return array('status' => 0, 'msg' => '仅超级管理员可执行当前操作！');
	}

	if (!is_numeric($total_amount)) {
		return array('status' => 0, 'msg' => '请输入一个有效的充值金额！');
	}

	// 根据邮箱，手机，或用户名查询用户
	$user = wnd_get_user_by($user_field);

	if (!$user) {
		return array('status' => 0, 'msg' => '用户不存在！');
	}

	// 写入充值记录
	try {
		$recharge = new Wnd_Recharge();
		$recharge->set_user_id($user->ID);
		$recharge->set_total_amount($total_amount);
		$recharge->set_subject($remarks);
		$recharge->create(true); // 直接写入余额
	} catch (Exception $e) {
		return array('status' => 0, 'msg' => $e->getMessage());
	}

	return array('status' => 1, 'msg' => $user->display_name . ' 充值：¥' . $total_amount);
}

/**
 *@since 初始化
 *统计整站财务数据，当用户发生充值或消费行为时触发
 *按月统计，每月生成两条post数据
 *
 *用户充值post_type:stats-re
 *用户消费post_type:stats-ex
 *根据用户金额变动>0 或者 <0 判断类型
 *用户金额记录：post_content，记录值均为正数
 *
 *写入前，按post type 和时间查询，如果存在记录则更新记录，否则写入一条记录
 *
 *@param 	float 	$money 		变动金额
 *
 **/
function wnd_update_fin_stats($money = 0) {
	if (!$money) {
		return;
	}

	if ($money > 0) {
		$post_type = 'stats-re';
	} else {
		$post_type = 'stats-ex';
	}

	$year = date('Y', time());
	$month = date('m', time());

	$slug = $year . '-' . $month . '-' . $post_type;
	$post_title = $post_type == 'stats-re' ? $year . '-' . $month . ' - 充值统计' : $year . '-' . $month . ' - 消费统计';

	$stats_post = wnd_get_post_by_slug($slug, $post_type, 'private');

	// 更新统计
	if ($stats_post) {

		$old_money = $stats_post->post_content;
		$new_money = $old_money + abs($money);
		$new_money = number_format($new_money, 2, '.', '');
		wp_update_post(array('ID' => $stats_post->ID, 'post_content' => $new_money));

		// 新增统计
	} else {
		$post_arr = array(
			'post_author' => 1,
			'post_type' => $post_type,
			'post_title' => $post_title,
			'post_content' => abs($money),
			'post_status' => 'private',
			'post_name' => $slug,
		);
		wp_insert_post($post_arr);
	}
}
