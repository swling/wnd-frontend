<?php
namespace Wnd\Model;

use Exception;
use WP_Post;

/**
 *@since 2019.08.11
 *支付模块
 *
 *	# 自定义文章类型
 *	post_type属性('public' => false)，因此在WordPress后台无法查看到
 *	充值：recharge
 *
 *	# 充值Post Data
 *	金额：post_content
 *	关联：post_parent
 *	标题：post_title
 *	类型：post_type：recharge
 *	接口：post_excerpt：（支付平台标识如：Alipay / Wepay）
 *
 *在线支付充值，设置如下参数，以区分站内充值。用于充值标识，及退款
 *post_excerpt = $payment_gateway（记录支付平台如：alipay、wepay）
 */
class Wnd_Recharge extends Wnd_Transaction {

	/**
	 *@since 2019.01.30
	 *
	 *写入post时需要设置别名，否则更新时会自动根据标题设置别名，而充值类标题一致，会导致WordPress持续循环查询并设置 -2、-3这类自增标题，产生大量查询
	 *
	 *@param int 		$this->user_id  		required
	 *@param float  	$this->total_money		required
	 *@param string 	$this->subject 			option
	 *@param int 		$this->object_id  		option
	 *@param string 	$this->payment_gateway	option 	支付平台标识
	 *@param bool 	 	$is_completed 			option 	是否直接写入，无需支付平台校验
	 *
	 *@return object WP Post Object
	 */
	public function create(bool $is_completed = false): WP_Post {
		if (!$this->user_id) {
			throw new Exception(__('请登录', 'wnd'));
		}
		if (!$this->total_amount) {
			throw new Exception(__('获取充值金额失败', 'wnd'));
		}

		// 定义变量
		$this->status  = $is_completed ? static::$completed_status : static::$processing_status;
		$this->subject = $this->subject ?: (($this->object_id ? __('佣金：¥', 'wnd') : __('充值：¥', 'wnd')) . $this->total_amount);

		/**
		 *@since 2019.03.31 查询符合当前条件，但尚未完成的付款订单
		 */
		$old_recharges = get_posts(
			[
				'author'         => $this->user_id,
				'post_parent'    => $this->object_id,
				'post_status'    => static::$processing_status,
				'post_type'      => 'recharge',
				'posts_per_page' => 1,
			]
		);
		if ($old_recharges) {
			$ID = $old_recharges[0]->ID;
		}

		$post_arr = [
			'ID'           => $ID ?? 0,
			'post_author'  => $this->user_id,
			'post_parent'  => $this->object_id,
			'post_content' => $this->total_amount,
			'post_excerpt' => $this->payment_gateway,
			'post_status'  => $this->status,
			'post_title'   => $this->subject,
			'post_type'    => 'recharge',
			'post_name'    => uniqid(),
		];
		$ID = wp_insert_post($post_arr);
		if (is_wp_error($ID) or !$ID) {
			throw new Exception(__('创建充值订单失败', 'wnd'));
		}

		// 构建Post
		$this->transaction = get_post($ID);

		// 完成充值
		if (static::$completed_status == $this->status) {
			$this->complete();
		}

		return $this->transaction;
	}

	/**
	 *@since 2019.02.11
	 *更新支付订单状态
	 *@return int or Exception
	 *
	 *@param object 	$this->transaction			required 	订单记录Post
	 *@param string 	$this->subject 		option
	 */
	public function verify() {
		if ('recharge' != $this->get_type()) {
			throw new Exception(__('充值ID无效', 'wnd'));
		}

		// 订单支付状态检查
		if (static::$processing_status != $this->get_status()) {
			throw new Exception(__('充值订单状态无效', 'wnd'));
		}

		$post_arr = [
			'ID'          => $this->get_ID(),
			'post_status' => static::$completed_status,
			'post_title'  => $this->subject ?: $this->get_subject(),
		];
		$ID = wp_update_post($post_arr);
		if (!$ID or is_wp_error($ID)) {
			throw new Exception(__('数据更新失败', 'wnd'));
		}

		// 完成本笔业务
		$this->complete();
	}

	/**
	 *完成充值
	 *
	 *在线充值：直接新增用户余额
	 *
	 *当充值包含关联object_id，表示收入来自站内佣金收入：更新用户佣金及产品总佣金统计
	 *@param object 	$this->transaction			required 	订单记录Post
	 */
	protected function complete(): int{
		// 在线订单校验时，由支付平台发起请求，并指定订单ID，需根据订单ID设置对应变量
		$ID           = $this->get_ID();
		$user_id      = $this->get_user_id();
		$total_amount = $this->get_total_amount();
		$object_id    = $this->get_object_id();

		// 当充值包含关联object_id，表示收入来自站内佣金收入：更新用户佣金及产品总佣金统计
		if ($object_id) {
			wnd_inc_user_commission($user_id, $total_amount);
			wnd_inc_post_total_commission($object_id, $total_amount);

			// 在线余额充值
		} else {
			wnd_inc_user_money($user_id, $total_amount);
		}

		/**
		 *@since 2019.08.12
		 *充值完成
		 */
		do_action('wnd_recharge_completed', $ID);

		return $ID;
	}
}
