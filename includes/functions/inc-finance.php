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
function wnd_get_user_money($user_id) {
	return Wnd_Finance::get_user_money($user_id);
}

/**
 *获取用户消费
 *@param 	int 	$user_id 	用户ID
 *@return 	float 	用户消费
 *
 */
function wnd_get_user_expense($user_id) {
	return Wnd_Finance::get_user_expense($user_id);
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
function wnd_get_user_commission($user_id) {
	return Wnd_Finance::get_user_commission($user_id);
}

/**
 *@since 2019.02.13
 *文章价格
 *@param 	int 	$user_id 	用户ID
 *@return  	float 	两位数的价格信息 或者 0
 */
function wnd_get_post_price($post_id) {
	return Wnd_Finance::get_post_price($post_id);
}

/**
 *@since 2019.02.12
 *用户佣金分成
 *@param 	int 	$post_id
 *@return 	float 	佣金分成
 */
function wnd_get_post_commission($post_id) {
	return Wnd_Finance::get_post_commission($post_id);
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
	return Wnd_Finance::update_fin_stats($money);
}
