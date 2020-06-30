<?php
use Wnd\Model\Wnd_Finance;

/**
 *@since 2019.02.11 查询是否已经支付
 *@param int 	$user_id 	用户ID
 *@param int 	$object_id  Post ID
 *
 *@return bool 	是否已支付
 **/
function wnd_user_has_paid($user_id, $object_id) {
	return Wnd_Finance::user_has_paid($user_id, $object_id);
}

/**
 *@since 2019.03.29 查询订单统计
 *@param 	int 	$object_id 	商品ID
 *
 *@return 	int 	order count
 **/
function wnd_get_order_count($object_id) {
	return Wnd_Finance::get_order_count($object_id);
}

/**
 *@since 2019.11.27 增加订单统计
 *
 *@param 	int 	$object_id 	商品ID
 *@param 	int 	$number 	增加的数目，可为负
 **/
function wnd_inc_order_count($object_id, $number) {
	return Wnd_Finance::inc_order_count($object_id, $number);
}

/**
 * 充值成功 写入用户 字段
 *
 *@param 	int 	$user_id 	用户ID
 *@param 	float 	$money 		金额
 *
 */
function wnd_inc_user_money($user_id, $money) {
	return Wnd_Finance::inc_user_money($user_id, $money);
}

/**
 *获取用户账户金额
 *@param 	int 	$user_id 	用户ID
 *@return 	float 	用户余额
 */
function wnd_get_user_money($user_id, $format = false) {
	return Wnd_Finance::get_user_money($user_id, $format);
}

/**
 *新增用户消费记录
 *
 *@param 	int 	$user_id 	用户ID
 *@param 	float 	$money 		金额
 *
 */
function wnd_inc_user_expense($user_id, $money) {
	return Wnd_Finance::inc_user_expense($user_id, $money);
}

/**
 *获取用户消费
 *@param 	int 	$user_id 	用户ID
 *@return 	float 	用户消费
 *
 */
function wnd_get_user_expense($user_id, $format = false) {
	return Wnd_Finance::get_user_expense($user_id, $format);
}

/**
 *@since 2019.02.22
 *写入用户佣金
 *@param 	int 	$user_id 	用户ID
 *@param 	float 	$money 		金额
 */
function wnd_inc_user_commission($user_id, $money) {
	return Wnd_Finance::inc_user_commission($user_id, $money);
}

/**
 *@since 2019.02.18 获取用户佣金
 *@param 	int 	$user_id 	用户ID
 *
 *@return 	float 	用户佣金
 */
function wnd_get_user_commission($user_id, $format = false) {
	return Wnd_Finance::get_user_commission($user_id, $format);
}

/**
 *@since 2019.02.13
 *文章价格
 *@param 	int 	$user_id 	用户ID
 *@return  	float 	两位数的价格信息 或者 0
 */
function wnd_get_post_price($post_id, $format = false) {
	return Wnd_Finance::get_post_price($post_id, $format);
}

/**
 *@since 2019.02.12
 *用户佣金分成
 *@param 	int 	$post_id
 *@return 	float 	佣金分成
 */
function wnd_get_post_commission($post_id, $format = false) {
	return Wnd_Finance::get_post_commission($post_id, $format);
}

/**
 *@since 2020.06.10
 *新增本篇付费内容作者佣金总额
 *
 *@param 	int 	$post_id 	Post ID
 *@param 	float 	$money 		金额
 */
function wnd_inc_post_total_commission($post_id, $money) {
	return Wnd_Finance::inc_post_total_commission($post_id, $money);
}

/**
 *@since 2020.06.10
 *获取付费内容作者获得的佣金
 *
 *@param 	int 	$post_id  Post ID
 *@return 	float 	用户佣金
 */
function wnd_get_post_total_commission($post_id, $format = false) {
	return Wnd_Finance::get_post_total_commission($post_id, $format);
}

/**
 *@since 2020.06.10
 *新增商品总销售额
 *@param 	int 	$post_id 	Post ID
 *@param 	float 	$money 		金额
 */
function wnd_inc_post_total_sales($post_id, $money) {
	return Wnd_Finance::inc_post_total_sales($post_id, $money);
}

/**
 *@since 2020.06.10
 *获取商品总销售额
 *@param 	int 	$user_id 	用户ID
 *@return 	float 	用户佣金
 */
function wnd_get_post_total_sales($post_id, $format = false) {
	return Wnd_Finance::get_post_total_sales($post_id, $format);
}
