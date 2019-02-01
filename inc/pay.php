<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.01.30 写入支付数据库
 */
function wnd_insert_payment() {

	$post_id = (int) $_POST['post_id'];
	$post_title = $_POST['post_title'];
	$comment_content = isset($_POST['comment_content']) ? $_POST['comment_content'] : '';
	$user_id = get_current_user_id();

	// 权限判断
	if (!$user_id) {
		return array('msg' => 0, 'msg' => '请登录后操作');
	}
	$wnd_can_insert_payment = apply_filters('wnd_can_insert_payment', array('status' => 1, 'msg' => '默认通过'), $post_id);
	if ($wnd_can_insert_payment['status'] === 0) {
		return $wnd_can_insert_payment;
	}

	// 写入消费记录
	$price = wnd_get_post_price($post_id);
	$expense_note = '<a href="' . get_the_permalink($post_id) . '" target="_blank">「付费」' . $post_title . '</a>';
	wnd_insert_expense($user_id, $price, $expense_note);

	// 写入payment数据库
	global $wpdb;
	$action = $wpdb->insert($wpdb->wnd_payment, array('post_id' => $post_id, 'user_id' => $user_id, 'price' => $price, 'time' => time()));

	// 支付成功
	if ($action) {
		// 消费：增加负数金额
		wnd_inc_user_money($user_id, $price * -1);
		// 增加消费记录
		wnd_inc_wnd_user_meta($user_id, 'order_num', 1);
		return array('status' => 1, 'msg' => '支付成功！');
	} else {
		return array('status' => 0, 'msg' => '支付失败！');
	}

}

/**
 *@since 2019.01.30 更新支付数据
 *采用类似wp update post的格式，必须输入主键ID，以数组形式注入更新
 */
function wnd_update_payment($payment_arr) {

	if (!$payment_arr['ID']) {
		return false;
	}

	global $wpdb;
	$payment = $wpdb->get_row("SELECT * FROM $wpdb->wnd_payment WHERE ID = {$payment_arr['ID']}", ARRAY_A);
	if (!$payment) {
		return;
	}

	$payment_arr = array_merge($payment, $payment_arr);

	$wpdb->update(
		$wpdb->wnd_payment,
		$payment_arr,
		array('ID' => $payment_arr['ID'])
	);

}

/**
 *@since 2019.01.31 获取指定ID支付数据
 */
function wnd_get_payment($payment_id) {

	if (!$payment_id) {
		return array();
	}

	global $wpdb;
	$payments = $wpdb->get_row("SELECT * FROM $wpdb->wnd_payment WHERE ID = {$payment_id}", ARRAY_A);
	return $payments;
}

/**
 *@since 2019.01.31 获取指定文章支付数据
 */
function wnd_get_payments_by_post($post_id) {

	if (!$post_id) {
		return array();
	}

	global $wpdb;
	$payments = $wpdb->get_results("SELECT * FROM $wpdb->wnd_payment WHERE post_id = {$post_id}", ARRAY_A);
	return $payments;
}

/**
 *@since 2019.01.31 获取指定用户支付数据
 */
function wnd_get_payments_by_user($user_id) {

	if (!$user_id) {
		return array();
	}

	global $wpdb;
	$payments = $wpdb->get_results("SELECT * FROM $wpdb->wnd_payment WHERE user_id = {$user_id}", ARRAY_A);
	return $payments;
}

/**
 *@since 2019.01.30 查询是否已经支付
 **/
function wnd_user_has_paid($post_id) {

	$user_id = get_current_user_id();
	if (!$user_id) {
		return false;
	}

	global $wpdb;
	$payment_ID = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->wnd_payment WHERE post_id = %d AND user_id = %d;", $post_id, $user_id));
	return $payment_ID;

}

// ##################################################### 付费阅读
function wnd_pay_for_reading() {

	$post_id = (int) $_POST['post_id'];
	$post = get_post($post_id);

	//1、已付费
	if (wnd_user_has_paid($post_id)) {
		return array('status' => 0, 'msg' => '请勿重复购买！');
	}

	// 2、支付失败
	$pay = wnd_insert_payment();
	if ($pay['status'] === 0) {
		return $pay;
	}

	// 文章作者新增资金
	$income = wnd_get_post_price($post_id, 'price');
	wnd_inc_user_money($post->post_author, $income);
	// 更新充值记录
	wnd_insert_recharge($user_id = $post->post_author, $money = $income, $note = '《' . $post->post_title . '》收益', $post_status = 'private');

	// 根据标记切割内容
	$content_array = explode('<p><!--more--></p>', $post->post_content);
	if (!isset($content_array[1])) {
		$content_array = explode('<!--more-->', $post->post_content);
	}
	$paid_content = $content_arrayn[1] ?? '获取内容出错！';

	return array('status' => 1, 'msg' => $paid_content);
}

/**
 * @since 2019.01.30
 * 付费下载
 *上传文件，并将文件id添加到wnd字段 file中
 */
function wnd_pay_for_download() {

	// 获取文章
	$post_id = (int) $_POST['post_id'];
	$post = get_post($post_id);
	if (!$post) {
		return array('status' => 0, 'msg' => 'ID无效！');
	}
	$price = get_post_meta($post_id, 'price', 1);

	// 获取文章附件
	$attachment_id = wnd_get_post_meta($post_id, 'file') ?: get_post_meta($post_id, 'file');
	$file = get_attached_file($attachment_id, $unfiltered = true);
	if (!$file) {
		return array('status' => 0, 'msg' => '获取文件失败！');
	}

	//1、免费，或者已付费
	if (!$price or wnd_user_has_paid($post_id)) {
		return wnd_download_file($file, $post_id);
	}

	//2、 作者直接下载
	if ($post->post_author == get_current_user_id()) {
		return wnd_download_file($file, $post_id);
	}

	//3、 付费下载
	$pay = wnd_insert_payment();
	if ($pay['status'] === 0) {
		return $pay;
	}
	// 文章作者新增资金
	$income = $price;
	wnd_inc_user_money($post->post_author, $income);
	// 更新充值记录
	wnd_insert_recharge($user_id = $post->post_author, $money = $income, $note = '《' . $post->post_title . '》收益', $post_status = 'private');
	// 发送文件
	return wnd_download_file($file, $post_id);

}