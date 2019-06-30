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
 *类型：post_type (recharge / order)
 *用户通过第三方金融平台充值付款到本站
 *创建时：post_status=>pending，验证成功后：post_status=>success
 *写入post时需要设置别名，否则更新时会自动根据标题设置别名，而充值类标题一致，会导致WordPress持续循环查询并设置 -2、-3这类自增标题，产生大量查询
 *@return int object ID
 */
function wnd_insert_recharge($args = array()) {

	$defaults = array(
		'user_id' => 0,
		'money' => 0,
		'status' => 'pending',
		'title' => '',
		'object_id' => 0,
	);
	$args = wp_parse_args($args, $defaults);

	$post_arr = array(
		'post_author' => $args['user_id'],
		'post_parent' => $args['object_id'],
		'post_content' => $args['money'],
		'post_status' => $args['status'],
		'post_title' => $args['title'],
		'post_type' => 'recharge',
		'post_name' => uniqid(),
	);

	// 写入object数据库
	$recharge_id = wp_insert_post($post_arr);

	if ($recharge_id and $args['status'] == 'success') {

		// 当充值包含关联object 如post，表示收入来自站内，如佣金收入
		if ($args['object_id']) {
			wnd_inc_user_commission($args['user_id'], $args['money']);
		} else {
			wnd_inc_user_money($args['user_id'], $args['money']);
		}
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
		$post_arr['post_title'] = $title;
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
 *@return string or false ：wnd_get_site_prefix() .'-'. $post_id or false on failed
 */
function wnd_insert_payment($user_id, $money, $object_id = 0) {

	/**
	 *充值支付订单的id基于WordPress post id，因而在一些情况下，可能会产生重复ID如：不同站点共用一个支付宝app id，测试环境与正式环境等
	 *@see 不采用别名做订单的原因：在WordPress中，不同类型的post type别名可以是重复的值，会在一定程度上导致不确定性，同时根据别名查询post的语句也更复杂
	 *该前缀对唯一性要求不高，仅用于区分上述情况下的冲突
	 *@see wnd_get_prefix() 基于 站点的 home_url
	 *wnd_get_prefix() 基于md5，组成为：数字字母，post_id为整数，因而分割字符需要回避数字和字母
	 *@since 2019.03.04
	 */

	//@since 2019.03.31 查询符合当前条件，但尚未完成的付款订单
	$old_payment = get_posts(
		array(
			'author' => $user_id,
			'post_parent' => $object_id,
			'post_status' => 'pending',
			'post_type' => $object_id ? 'order' : 'recharge',
			'posts_per_page' => 1,
		)
	);
	if ($old_payment and $old_payment[0]->post_content == $money) {
		return wnd_get_site_prefix() . '-' . $old_payment[0]->ID;
	}

	// 在线订单
	if ($object_id) {

		$order_id = wnd_insert_order(
			array(
				'user_id' => $user_id,
				'money' => $money,
				'object_id' => $object_id,
				'status' => 'pending',
			)
		);

		if ($order_id and !is_wp_error($order_id)) {
			return wnd_get_site_prefix() . '-' . $order_id;
		} else {
			return false;
		}

		//余额充值
	} else {

		$recharge_id = wnd_insert_recharge(
			array(
				'user_id' => $user_id,
				'money' => $money,
				'status' => 'pending',
			)
		);

		if ($recharge_id and !is_wp_error($recharge_id)) {
			return wnd_get_site_prefix() . '-' . $recharge_id;
		} else {
			return false;
		}
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

	/**
	 *切割订单号，获取WordPress充值post id
	 *为防止多站点公用一个支付应用id，或测试环境与正式环境中产生重复的支付订单id，在充值id的前缀前，添加了基于该站点home_url()的前缀字符
	 *@see wnd_get_site_prefix()
	 *@since 2019.03.04
	 */
	list($prefix, $post_id) = explode('-', $out_trade_no, 2);

	if ($prefix != wnd_get_site_prefix()) {
		return array('status' => 0, 'msg' => '当前支付信息非本站点订单！');
	}

	$post = get_post($post_id);
	if (!$post) {
		return array('status' => 0, 'msg' => 'ID无效！');
	}

	//如果订单金额匹配
	if ($post->post_content != $amount) {
		return array('status' => 0, 'msg' => '金额不匹配！');
	}

	//订单已经更新过
	if ($post->post_status == 'success') {

		if ($post->post_parent) {
			return array('status' => 2, 'msg' => $post->post_parent);
		} else {
			return array('status' => 1, 'msg' => '余额充值成功！');
		}

	}

	// 订单支付状态检查
	if ($post->post_status == 'pending') {

		// 订单
		if ($post->post_parent) {
			$update = wnd_update_order($post->ID, 'success', $post->post_title . ' - ' . $type);

			//充值
		} else {
			$update = wnd_update_recharge($post->ID, 'success', '充值 - ' . $type);
		}

		//  写入用户账户信息
		if ($update) {
			if ($post->post_parent) {
				return array('status' => 2, 'msg' => $post->post_parent);
			} else {
				return array('status' => 1, 'msg' => '余额充值成功！');
			}

			/**
			 * @since 2019.06.30
			 *成功完成付款后*
			 */
			do_action('wnd_verified_payment', $post);
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
function wnd_insert_order($args = array()) {

	$defaults = array(
		'user_id' => 0,
		'money' => 0,
		'status' => 'pending',
		'title' => '',
		'object_id' => 0,
	);
	$args = wp_parse_args($args, $defaults);
	$args['title'] = $args['title'] ?: ($args['object_id'] ? get_the_title($args['object_id']) : '');

	$post_arr = array(
		'post_author' => $args['user_id'],
		'post_parent' => $args['object_id'],
		'post_content' => $args['money'] ?: '免费',
		'post_status' => $args['status'],
		'post_title' => $args['title'],
		'post_type' => 'order',
		'post_name' => uniqid(),
	);

	$order_id = wp_insert_post($post_arr);

	/**
	 *@since 2019.06.04
	 *新增订单统计
	 *插入订单时，无论订单状态均新增订单统计，以实现某些场景下需要限定订单总数时，锁定数据，预留支付时间
	 *获取订单统计时，删除超时未完成的订单，并减去对应订单统计 @see wnd_get_order_count($object_id)
	 */
	wnd_inc_wnd_post_meta($args['object_id'], 'order_count', 1);

	/**
	 *@since 2019.02.17
	 *success表示直接余额消费，更新用户余额
	 *pending 则表示通过在线直接支付订单，需要等待支付平台验证返回后更新支付 @see wnd_update_order();
	 */
	if ($order_id && $args['status'] == 'success') {
		wnd_inc_user_money($args['user_id'], $args['money'] * -1);
	}

	/**
	 *@since 2019.06.04
	 *删除对象缓存
	 **/
	wp_cache_delete($args['user_id'] . $args['object_id'], $group = 'user_has_paid');

	return $order_id;

}

/**
 *@since 2019.02.11
 *更新消费订单状态
 *@return int or false
 */
function wnd_update_order($ID, $status, $title = '') {

	$post = get_post($ID);
	if ($post->post_type != 'order') {
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

	$order_id = wp_update_post($post_arr);

	/**
	 *@since 2019.02.17
	 *当消费订单，从pending更新到 success，表示该消费订单是通过在线支付，而非余额支付，无需扣除用户余额
	 *由于此处没有触发 wnd_inc_user_money 因此需要单独统计财务信息
	 */
	if ($order_id and $before_status == 'pending' and $status == 'success') {
		wnd_update_fin_stats($money * -1);
	}

	/**
	 *@since 2019.06.04
	 *删除对象缓存
	 **/
	wp_cache_delete($post->post_author . $post->post_parent, $group = 'user_has_paid');

	return $order_id;

}

/**
 *@since 2019.02.11 查询是否已经支付
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
 *@param $object_id int 商品ID
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

// 充值成功 写入用户 字段
function wnd_inc_user_money($user_id, $money) {

	$new_money = wnd_get_user_money($user_id) + $money;
	$new_money = round($new_money, 2);
	wnd_update_user_meta($user_id, 'money', $new_money);

	// $money 为负数 更新消费金额记录
	if ($money < 0) {
		wnd_inc_wnd_user_meta($user_id, 'expense', round($money, 2) * -1);
	}

	// 整站按月统计充值和消费
	wnd_update_fin_stats($money);

}

// 获取用户账户金额
function wnd_get_user_money($user_id) {

	$money = wnd_get_user_meta($user_id, 'money');
	$money = is_numeric($money) ? $money : 0;
	return round($money, 2);
}

// 获取用户消费
function wnd_get_user_expense($user_id) {

	$expense = wnd_get_user_meta($user_id, 'expense');
	$expense = is_numeric($expense) ? $expense : 0;
	return round($expense, 2);
}

/**
 *@since 2019.02.22
 *写入用户佣金
 */
function wnd_inc_user_commission($user_id, $money) {

	wnd_inc_wnd_user_meta($user_id, 'commission', round($money, 2));

}

/**
 *@since 2019.02.18 获取用户佣金
 */
function wnd_get_user_commission($user_id) {

	$commission = wnd_get_user_meta($user_id, 'commission');
	$commission = is_numeric($commission) ? $commission : 0;
	return round($commission, 2);
}

/**
 *@since 2019.02.13
 *文章价格
 *@return 两位数的价格信息 或者 0
 */
function wnd_get_post_price($post_id) {

	$price = wnd_get_post_meta($post_id, 'price') ?: get_post_meta($post_id, 'price', 1) ?: false;
	$price = is_numeric($price) ? round($price, 2) : 0;
	return apply_filters('wnd_get_post_price', $price, $post_id);

}

/**
 *@since 2019.02.12
 *用户佣金分成
 */
function wnd_get_post_commission($post_id) {

	$commission_rate = is_numeric(wnd_get_option('wnd', 'wnd_commission_rate')) ? wnd_get_option('wnd', 'wnd_commission_rate') : 0;
	$commission = wnd_get_post_price($post_id) * $commission_rate;
	$commission = round($commission, 2);
	return apply_filters('wnd_get_post_commission', $commission, $post_id);

}

/**
 *@since 2019.02.22
 *管理员手动新增用户金额
 */
function wnd_admin_recharge($user_field, $money, $remarks = '') {

	if (!is_super_admin()) {
		return array('status' => 0, 'msg' => '仅超级管理员可执行当前操作！');
	}

	if (!is_numeric($money)) {
		return array('status' => 0, 'msg' => '请输入一个有效的充值金额！');
	}

	// 根据邮箱，手机，或用户名查询用户
	$user = wnd_get_user_by($user_field);

	if (!$user) {
		return array('status' => 0, 'msg' => '用户不存在！');
	}

	// 写入充值记录
	if (wnd_insert_recharge(array('user_id' => $user->ID, 'money' => $money, 'status' => 'success', 'title' => $remarks))) {
		return array('status' => 1, 'msg' => $user->display_name . ' 充值：¥' . $money);
	} else {
		return array('status' => 0, 'msg' => '充值失败！');
	}

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
		$new_money = round($new_money, 2);
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
