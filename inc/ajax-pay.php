<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.01.30 写入消费数据库
 */
function wnd_insert_order() {

	$post_id = (int) $_POST['post_id'];
	$post_title = $_POST['post_title'];
	$content = $_POST['content'] ?? '';
	$user_id = get_current_user_id();

	// 权限判断
	if (!$user_id) {
		return array('msg' => 0, 'msg' => '请登录后操作');
	}
	$wnd_can_insert_expense = apply_filters('wnd_can_insert_expense', array('status' => 1, 'msg' => '默认通过'), $post_id);
	if ($wnd_can_insert_expense['status'] === 0) {
		return $wnd_can_insert_expense;
	}

	// 余额判断
	$money = wnd_get_post_price($post_id);
	if($money > wnd_get_user_money($user_id)){
		return array('status' => 0, 'msg' => '余额不足！');
	}

	// 写入object数据库
	$object_id =  wnd_insert_expense($user_id, $money, $status = '', $note);

	// 支付成功
	if ($object_id) {
		// 消费：增加负数金额
		wnd_inc_money($user_id, $price * -1);
		// 增加消费记录
		// wnd_inc_wnd_user_meta($user_id, 'order_num', 1);
		return array('status' => 1, 'msg' => '支付成功！');
	} else {
		return array('status' => 0, 'msg' => '支付失败！');
	}

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
	$pay = wnd_insert_expense();
	if ($pay['status'] === 0) {
		return $pay;
	}

	// 文章作者新增资金
	$income = wnd_get_post_price($post_id, 'price');
	wnd_inc_money($post->post_author, $income);
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
	$pay = wnd_insert_expense();
	if ($pay['status'] === 0) {
		return $pay;
	}
	// 文章作者新增资金
	$income = $price;
	wnd_inc_money($post->post_author, $income);
	// 更新充值记录
	wnd_insert_recharge($user_id = $post->post_author, $money = $income, $note = '《' . $post->post_title . '》收益', $post_status = 'private');
	// 发送文件
	return wnd_download_file($file, $post_id);

}