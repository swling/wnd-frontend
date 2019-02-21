<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}
/*
*采用自定义post_type: recharge-card 
*post_type => recharge-card
*post_name => $recharge_card['card'];
*post_password => $recharge_card['password'];
*post_content => $recharge_card['value']
*post_status => publish（正常）private(已用)
*/

/**
 *@since 2019.02.20 创建指定金额的成对充值卡号和密码
 *@return array
 */
function wnd_create_recharge_card($value) {

	$random_str = $_SERVER['REQUEST_TIME'] . wnd_random(6);
	$hash = strtoupper(hash('sha256', $random_str));
	$card = substr($hash, 0, 4) . '-' . substr($hash, 8, 4) . '-' . substr($hash, 12, 4) . '-' . substr($hash, 16, 4) . '-' . substr($hash, 20, 4);

	$password = strtoupper(wnd_random(6));

	return array('card' => $card, 'password' => $password, 'value' => $value);
}

/**
 *@since 2019.02.21 将充值卡数据写入 objects数据表
 */
function wnd_insert_recharge_card($value) {

	$recharge_card = wnd_create_recharge_card($value);

	$object_data['post_name'] = $recharge_card['card'];
	$object_data['post_password'] = $recharge_card['password'];
	$object_data['post_content'] = $recharge_card['value'];
	$object_data['post_type'] = 'recharge-card';
	$object_data['post_status'] = 'publish';

	return wp_insert_post($object_data);

}

/**
 *@since 2019.02.21 检测充值卡并充值
 */
function wnd_verity_recharge_card($card, $password) {

	if(!is_user_logged_in()){
		return array('status' => 0, 'msg' => '请登录！');
	}

	$recharge_card = wnd_get_post_by_slug($card, 'recharge-card', 'publish');
	if (!$recharge_card) {
		return array('status' => 0, 'msg' => '无效的充值卡！');
	}

	if ($password != $recharge_card->post_password) {
		return array('status' => 0, 'msg' => '卡号密码不匹配！');
	}

	// 写入充值记录
	wnd_insert_recharge(get_current_user_id(), $recharge_card->post_content, $object_id = 0, $status = 'success', $title = '充值卡充值');
	// 更新充值卡
	wp_update_post(array('ID'=>$recharge_card->ID,'post_status'=>'private','post_title'=>'0'));

	return array('status' => 1, 'msg' => '成功充值：¥' . $recharge_card->content);

}
