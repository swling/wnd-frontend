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
	$user_id = get_current_user_id();
	$note = $_POST['note'] ?? '';

	if (!$post_id) {
		return array('msg' => 0, 'msg' => 'ID无效！');
	}

	// 权限判断
	if (!$user_id) {
		return array('msg' => 0, 'msg' => '请登录后操作！');
	}
	$wnd_can_insert_order = apply_filters('wnd_can_insert_order', array('status' => 1, 'msg' => '默认通过'), $post_id);
	if ($wnd_can_insert_order['status'] === 0) {
		return $wnd_can_insert_order;
	}

	// 余额判断
	$money = wnd_get_post_price($post_id);
	if ($money > wnd_get_user_money($user_id)) {
		return array('status' => 0, 'msg' => '余额不足！');
	}

	// 写入object数据库
	$object_id = wnd_insert_expense($user_id, $money, $post_id, $note);

	// 支付成功
	if ($object_id) {
		return array('status' => 1, 'msg' => '支付成功！');
	} else {
		return array('status' => 0, 'msg' => '支付失败！');
	}

}

// ##################################################### 付费阅读
function wnd_pay_for_reading() {

	$post_id = (int) $_POST['post_id'];
	$post = get_post($post_id);
	$user_id = get_current_user_id();

	// 根据标记切割内容
	list($free_content, $paid_content) = explode('<p><!--more--></p>', $post->post_content, 2);
	if (empty($paid_content)) {
		list($free_content, $paid_content) = explode('<p><!--more--></p>', $post->post_content, 2);
	}

	if(!$paid_content){
		return array('status' => 0, 'msg' => '获取付费内容出错！');
	}

	//1、已付费
	if (wnd_user_has_paid($user_id, $post_id)) {
		return array('status' => 0, 'msg' => '请勿重复购买！');
	}

	// 2、支付失败
	$order = wnd_insert_order();
	if ($order['status'] === 0) {
		return $order;
	}

	// 文章作者新增资金
	$income = wnd_get_post_price($post_id, 'price');
	wnd_insert_payment($post->post_author, $income, 'success', $note = '《' . $post->post_title . '》收益');

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
	$user_id = get_current_user_id();

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
	if (!$price or wnd_user_has_paid($user_id, $post_id)) {
		return wnd_download_file($file, $post_id);
	}

	//2、 作者直接下载
	if ($post->post_author == get_current_user_id()) {
		return wnd_download_file($file, $post_id);
	}

	//3、 付费下载
	$order = wnd_insert_order();
	if ($order['status'] === 0) {
		return $order;
	}

	// 文章作者新增资金
	$income = $price;
	wnd_insert_payment($post->post_author, $income, 'success', $note = '《' . $post->post_title . '》收益');

	// 发送文件
	return wnd_download_file($file, $post_id);

}