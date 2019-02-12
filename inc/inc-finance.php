<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.01.30
 *用户通过第三方金融平台充值付款到本站
 *创建时：status=>pending，验证成功后：status=>success
 *@return int object ID
 */
function wnd_insert_payment($user_id, $money, $status = 'pending', $content = '') {

	$object_arr = array(
		'user_id' => $user_id,
		'value' => $money,
		'status' => $status,
		'content' => $content,
		'type' => 'payment',
	);

	// 写入object数据库
	$object_id = wnd_insert_object($object_arr);

	if ($object_id and $status == 'success') {
		wnd_inc_user_money($user_id, $money);
	}

	return $object_id;
}

/**
 *@since 2019.02.11
 *更新支付订单状态
 *@return int or false
 */
function wnd_update_payment($ID, $status, $content = '') {

	$object = wnd_get_object($ID);
	if ($object->type != 'payment') {
		return;
	}
	$before_status = $object->status;

	$object_arr = array(
		'ID' => $ID,
		'status' => $status,
		'content' => $content,
	);
	$object_id = wnd_update_object($object_arr);

	// 当充值订单，从pending更新到 success，表示充值完成，更新用户余额
	if ($object_id and $before_status == 'pending' and $status == 'success') {
		wnd_inc_user_money($user_id, $money);
	}

	return $object_id;

}

/**
 *@since 2019.02.11
 *充值付款校验
 */
function wnd_verify_payment($out_trade_no, $amount, $app_id = '') {

	$type = !empty($_POST) ? '异步' : '同步';

	$recharge = wnd_get_object($out_trade_no);
	if (!$recharge) {
		return array('status' => 0, 'msg' => 'ID无效！');
	}

	//如果订单金额匹配
	if ($recharge->value == $amount) {
		return array('status' => 0, 'msg' => '金额不匹配！');
	}

	//订单已经更新过
	if ($recharge->status == 'success') {
		return array('status' => 2, 'msg' => '支付已完成！');
	}

	// 订单支付状态检查
	if ($recharge->status == 'pending') {

		//  写入用户账户信息
		if (wnd_update_payment($recharge->ID, 'success', $type)) {

			return array('status' => 1, 'msg' => '充值已完成！');

		} else {

			return array('status' => 0, 'msg' => $type . '写入数据失败！');
		}

	}

	//订单状态不符合校验规则
	return array('status' => 0, 'msg' => '支付状态无效！');

}

/**
 *@since 2019.02.11
 *用户本站消费数据
 */
function wnd_insert_expense($user_id, $money, $object_id = 0, $content = '') {

	$object_arr = array(
		'user_id' => $user_id,
		'value' => $money,
		'object_id' => $object_id,
		'content' => $content,
		'type' => 'expense',
	);
	$object_id = wnd_insert_object($object_arr);

	// 更新用户余额
	if ($object) {
		wnd_inc_user_money($user_id, $money * -1);
	}

	return $object_id;

}

/**
 *@since 2019.02.11
 *更新消费订单状态
 *@return int or false
 */
function wnd_update_expense($ID, $status, $content = '') {

	$object = wnd_get_object($ID);
	if ($object->type != 'expense') {
		return;
	}

	$object_arr = array(
		'ID' => $ID,
		'status' => $status,
		'content' => $content,
	);
	return wnd_update_object($object_arr);

}

/**
 *@since 2019.02.11 查询是否已经支付
 **/
function wnd_user_has_paid($user_id, $object_id) {

	global $wpdb;
	$objects = $wpdb->get_var($wpdb->prepare("
		SELECT ID FROM $wpdb->wnd_objects WHERE user_id = %d AND object_id = %d AND type ='expense' LIMIT 1 ",
		$user_id,
		$object_id
	));
	return $objects;

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

	$price = wnd_get_post_meta($post_id, 'price') ?: get_post_meta($post_id, 'price', 1) ?: wnd_get_option('wndwp', 'wnd_post_default_price');
	return $price;
}

/**
*@since 2019.02.12
*用户佣金分成 默认为付费文章的全部价格收益
*/
function wnd_get_commission($post_id){

	return apply_filters( 'wnd_commission', wnd_get_post_price($post_id), $post_id );

}

/**
 *@since 初始化
 *统计整站财务数据，当用户发生充值或消费行为时触发
 *按月统计，每月生成两条post数据
 *
 *用户充值post_type:stats_re
 *用户消费post_type:stats_ex
 *根据用户金额变动>0 或者 <0 判断类型
 *用户金额记录：post_title，记录值均为正数
 *
 *写入前，按post type 和时间查询，如果存在记录则更新记录，否则写入一条记录
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
