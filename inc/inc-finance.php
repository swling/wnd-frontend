<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.01.30
 *金额：post_content
 *关联：post_parent
 *状态：post_status
 *类型：post_type (recharge / expense)
 *用户通过第三方金融平台充值付款到本站
 *创建时：post_status=>pending，验证成功后：post_status=>success
 *@return int object ID
 */
function wnd_insert_recharge($user_id, $money, $status = 'pending', $title = '', $object_id = 0) {

	$post_arr = array(
		'post_author' => $user_id,
		'post_parent' => $object_id,
		'post_content' => $money,
		'post_status' => $status,
		'post_title' => $title,
		'post_type' => 'recharge',
	);

	// 写入object数据库
	$recharge_id = wp_insert_post($post_arr);

	if ($recharge_id and $status == 'success') {
		wnd_inc_user_money($user_id, $money);
	}

	return $recharge_id;
}

/**
 *@since 2019.02.11
 *更新支付订单状态
 *@return int or false
 */
function wnd_update_recharge($ID, $status, $title = '') {

	$post = get_post($ID);
	if ($post->post_type != 'recharge') {
		return;
	}
	$before_status = $post->post_status;
	$money = $post->post_content;

	$post_arr = array(
		'ID' => $ID,
		'post_status' => $status,
	);
	if ($title) {
		$post_arr['title'] = $title;
	}

	$recharge_id = wp_update_post($post_arr);

	// 当充值订单，从pending更新到 success，表示充值完成，更新用户余额
	if ($recharge_id and $before_status == 'pending' and $status == 'success') {
		wnd_inc_user_money($post->post_author, $money);
	}

	return $recharge_id;

}

/**
 *@since 2019.02.17 写入支付信息
 *@return int object_id
 */
function wnd_insert_payment($user_id, $money, $post_id = 0) {

	if ($post_id) {
		// 在线订单
		return wnd_insert_expense($user_id, $money, $post_id, 'pending');
	} else {
		// 在线充值
		return wnd_insert_recharge($user_id, $money, 'pending');
	}

}

/**
 *@since 2019.02.11
 *充值付款校验
 *@return array
 *当支付信息中包含 object id表示为订单支付，否则为余额充值
 *订单支付，返回 status=> 2, msg => object_id
 */
function wnd_verify_payment($out_trade_no, $amount, $app_id = '') {

	$type = !empty($_POST) ? '异步' : '同步';

	$post = get_post($out_trade_no);
	if (!$post) {
		return array('status' => 0, 'msg' => 'ID无效！');
	}

	//如果订单金额匹配
	if ($post->post_content == $amount) {
		return array('status' => 0, 'msg' => '金额不匹配！');
	}

	//订单已经更新过
	if ($post->post_status == 'success') {
		return array('status' => 2, 'msg' => '支付已完成！');
	}

	// 订单支付状态检查
	if ($post->post_status == 'pending') {

		// 订单
		if ($post->post_parent) {
			$update = wnd_update_expense($post->ID, 'success', $post->post_title . ' - ' . $type);

			//充值
		} else {
			$update = wnd_update_recharge($post->ID, 'success', '充值 - '.$type);
		}

		//  写入用户账户信息
		if ($update) {
			if ($post->post_parent) {
				return array('status' => 2, 'msg' => $post->post_parent);
			} else {
				return array('status' => 1, 'msg' => '余额充值成功！');
			}
		} else {
			return array('status' => 0, 'msg' => $type . '写入数据失败！');
		}

	}

	//订单状态不符合校验规则
	return array('status' => 0, 'msg' => '支付状态无效！');

}

/**
 *@since 2019.02.11
 *用户本站消费数据(含余额消费，或直接第三方支付消费)
 */
function wnd_insert_expense($user_id, $money, $object_id = 0, $status = 'success', $title = '') {

	$post_arr = array(
		'post_author' => $user_id,
		'post_content' => $money,
		'post_parent' => $object_id,
		'post_title' => $title,
		'post_status' => $status,
		'post_type' => 'expense',
	);
	$expense_id = wp_insert_post($post_arr);

	/**
	 *@since 2019.02.17
	 *success表示直接余额消费，更新用户余额
	 *pending 则表示通过在线直接支付订单，需要等待支付平台验证返回后更新支付 @see wnd_update_expense();
	 */
	if ($expense_id && $status == 'success') {
		wnd_inc_user_money($user_id, $money * -1);
	}

	return $expense_id;

}

/**
 *@since 2019.02.11
 *更新消费订单状态
 *@return int or false
 */
function wnd_update_expense($ID, $status, $title = '') {

	$post = get_post($ID);
	if ($post->post_type != 'expense') {
		return;
	}
	$before_status = $post->post_status;
	$money = $post->post_content;

	$post_arr = array(
		'ID' => $ID,
		'post_status' => $status,
	);
	if ($title) {
		$post_arr['post_title'] = $title;
	}

	$expense_id = wp_update_post($post_arr);

	/**
	 *@since 2019.02.17
	 *当消费订单，从pending更新到 success，表示该消费订单是通过在线支付，而非余额支付，无需扣除用户余额
	 *由于此处没有触发 wnd_inc_user_money 因此需要单独统计财务信息
	 */
	if ($expense_id and $before_status == 'pending' and $status == 'success') {
		// wnd_inc_user_money($user_id, $money * -1 );
		// 整站按月统计充值和消费
		wnd_fin_stats($money * -1);
	}

	return $expense_id;

}

/**
 *@since 2019.02.11 查询是否已经支付
 **/
function wnd_user_has_paid($user_id, $object_id) {

	$args = array(
		'posts_per_page' => 1,
		'post_type' => 'expense',
		'post_parent' => $object_id,
		'author' => $user_id,
		'post_status' => 'success',
	);

	if (empty(get_posts($args))) {
		return false;
	} else {
		return true;
	}

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
	// $money = $money ?: 0;
	$money = is_numeric($money) ? number_format($money, 2) : 0;
	return $money;
}

// 获取用户消费
function wnd_get_user_expense($user_id) {

	$expense = wnd_get_user_meta($user_id, 'expense');
	$expense = is_numeric($expense) ? number_format($expense, 2) : 0;
	return $expense;
}

/**
 *@since 2019.02.18 获取用户佣金
 */
function wnd_get_user_commission($user_id) {

	$commission = wnd_get_user_meta($user_id, 'commission');
	$commission = is_numeric($commission) ? number_format($commission, 2) : 0;
	return $commission;
}

/**
 *@since 2019.02.13
 *文章价格
 */
function wnd_get_post_price($post_id) {

	$price = wnd_get_post_meta($post_id, 'price') ?: get_post_meta($post_id, 'price', 1) ?: 0;
	$price = is_numeric($price) ? number_format($price, 2) : 0;
	return apply_filters('wnd_post_price', $price, $post_id);
}

/**
 *@since 2019.02.12
 *用户佣金分成 默认为付费文章的全部价格收益
 */
function wnd_get_commission($post_id) {

	return apply_filters('wnd_commission', wnd_get_post_price($post_id), $post_id);

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

	$year = date('Y', time());
	$month = date('m', time());
	$slug = $year . '-' . $month . '-' . $post_type;

	$stats_post = wnd_get_post_by_slug($slug, $post_type, 'private');

	// 更新统计
	if ($stats_post) {

		$old_money = $stats_post->post_title;
		$new_money = $old_money + abs($money);
		wp_update_post(array('ID' => $stats_post->ID, 'post_title' => $new_money));

		// 新增统计
	} else {

		$post_arr = array(
			'post_author' => 1,
			'post_type' => $post_type,
			'post_title' => abs($money),
			'post_status' => 'private',
			'post_name' => $slug,
		);
		wp_insert_post($post_arr);

	}

}
