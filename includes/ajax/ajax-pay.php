<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.01.30 创建订单
 *@param $_POST['post_id']
 */
function wnd_ajax_create_order() {
	$post_id = (int) $_POST['post_id'];
	$user_id = get_current_user_id();
	if (!$post_id) {
		return array('status' => 0, 'msg' => 'ID无效！');
	}

	// 权限判断
	if (!$user_id) {
		return array('status' => 0, 'msg' => '请登录后操作！');
	}
	$wnd_can_create_order = apply_filters('wnd_can_create_order', array('status' => 1, 'msg' => '默认通过'), $post_id);
	if ($wnd_can_create_order['status'] === 0) {
		return $wnd_can_create_order;
	}

	// 余额判断
	if (wnd_get_post_price($post_id) > wnd_get_user_money($user_id)) {
		if (wnd_get_option('wnd', 'wnd_alipay_appid')) {
			return array('status' => 0, 'msg' => '余额不足！<a href="' . _wnd_order_link($post_id) . '">在线支付</a> | <a onclick="wnd_ajax_modal(\'_wnd_recharge_form\')">余额充值</a>');
		} else {
			return array('status' => 0, 'msg' => '余额不足！');
		}
	}

	// 写入消费数据
	try {
		$order = new Wnd_Order();
		$order->set_object_id($post_id);
		$order->set_subject(get_the_title($post_id) . '(余额支付)');
		$order->create($is_success = true);
	} catch (Exception $e) {
		return array('status' => 0, 'msg' => $e->getMessage());
	}

	// 支付成功
	return array('status' => 1, 'msg' => '支付成功！');
}

/**
 *@since 2019.01
 *@param $_POST['post_id']
 */
function wnd_ajax_pay_for_reading() {
	$post_id = (int) $_POST['post_id'];
	$post    = get_post($post_id);
	$user_id = get_current_user_id();

	//查找是否有more标签，否则免费部分为空（全文付费）
	$content_array = explode('<!--more-->', $post->post_content, 2);
	if (count($content_array) == 1) {
		$content_array = array('', $post->post_content);
	}
	list($free_content, $paid_content) = $content_array;

	if (!$paid_content) {
		return array('status' => 0, 'msg' => '获取付费内容出错！');
	}

	//1、已付费
	if (wnd_user_has_paid($user_id, $post_id)) {
		return array('status' => 0, 'msg' => '请勿重复购买！');
	}

	// 2、支付失败
	$order = wnd_ajax_create_order();
	if ($order['status'] === 0) {
		return $order;
	}

	// 文章作者新增资金
	$commission = wnd_get_post_commission($post_id);
	if ($commission) {
		try {
			$recharge = new Wnd_Recharge();
			$recharge->set_object_id($post->ID); // 设置充值来源
			$recharge->set_user_id($post->post_author);
			$recharge->set_total_amount($commission);
			$recharge->create(true); // 直接写入余额
		} catch (Exception $e) {
			return array('status' => 0, 'msg' => $e->getMessage());
		}
	}

	return array('status' => 1, 'msg' => $paid_content);
}

/**
 *@since 2019.01.30
 *付费下载
 *@param $_POST['post_id']
 */
function wnd_ajax_pay_for_download() {

	// 获取文章
	$post_id = (int) $_POST['post_id'];
	$post    = get_post($post_id);
	$user_id = get_current_user_id();

	if (!$post) {
		return array('status' => 0, 'msg' => 'ID无效！');
	}
	$price = get_post_meta($post_id, 'price', 1);

	// 获取文章附件
	$attachment_id = wnd_get_post_meta($post_id, 'file') ?: get_post_meta($post_id, 'file');
	$file          = get_attached_file($attachment_id, $unfiltered = true);
	if (!$file) {
		return array('status' => 0, 'msg' => '获取文件失败！');
	}

	/**
	 *@since 2019.02.12
	 *组合ajax验证下载参数:该url地址并非文件实际下载地址，而是一个调用参数的请求
	 *前端接收后跳转至该网址（status == 6 是专为下载类ajax请求设置的代码前端响应），以实现ajax下载
	 */
	$download_args = array(
		'action'   => 'wnd_ajax_paid_download',
		'post_id'  => $post_id,
		'_wpnonce' => wnd_create_nonce('wnd_ajax_paid_download'),
	);
	$download_url = add_query_arg($download_args, wnd_get_do_url());

	//1、免费，或者已付费
	if (!$price or wnd_user_has_paid($user_id, $post_id)) {
		return array('status' => 6, 'msg' => 'ok', 'data' => array('redirect_to' => $download_url));
	}

	//2、 作者直接下载
	if ($post->post_author == get_current_user_id()) {
		return array('status' => 6, 'msg' => 'ok', 'data' => array('redirect_to' => $download_url));
	}

	//3、 付费下载
	$order = wnd_ajax_create_order();
	if ($order['status'] === 0) {
		return $order;
	}

	// 文章作者新增资金
	$commission = wnd_get_post_commission($post_id);
	if ($commission) {
		try {
			$recharge = new Wnd_Recharge();
			$recharge->set_object_id($post->ID); // 设置充值来源
			$recharge->set_user_id($post->post_author);
			$recharge->set_total_amount($commission);
			$recharge->create(true); // 直接写入余额
		} catch (Exception $e) {
			return array('status' => 0, 'msg' => $e->getMessage());
		}
	}

	return array('status' => 6, 'msg' => 'ok', 'data' => array('redirect_to' => $download_url));
}
