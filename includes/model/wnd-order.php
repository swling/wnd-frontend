<?php
namespace Wnd\Model;

use Exception;
use Wnd\Model\Wnd_Recharge;

/**
 *@since 2019.08.11
 *订单模块
 *
 *	# 自定义文章类型
 *	post_type属性('public' => false)，因此在WordPress后台无法查看到
 *	订单：order
 *
 *	# 状态：
 *	pending / success
 *
 *	# 消费post data
 *	金额：post_content
 *	关联：post_parent
 *	标题：post_title
 *	状态：post_status: pengding / success
 *	类型：post_type：order
 *
 */
class Wnd_Order extends Wnd_Transaction {

	/**
	 *@since 2019.02.11
	 *用户本站消费数据(含余额消费，或直接第三方支付消费)
	 *
	 *@param int 		$this->user_id  	required
	 *@param int 		$this->object_id  	option
	 *@param string 	$this->subject 		option
	 *@param bool 	 	$is_success 		option 	是否直接写入，无需支付平台校验
	 */
	public function create(bool $is_success = false) {
		if (!$this->user_id) {
			throw new Exception(__('请登录', 'wnd'));
		}
		if ($this->object_id and !get_post($this->object_id)) {
			throw new Exception(__('指定商品无效', 'wnd'));
		}

		// 定义变量
		$this->total_amount = $this->total_amount ?: wnd_get_post_price($this->object_id);
		$this->status       = $is_success ? 'success' : 'pending';
		$this->subject      = $this->subject ?: __('订单：', 'wnd') . get_the_title($this->object_id);

		/**
		 *@since 2019.03.31 查询符合当前条件，但尚未完成的付款订单
		 */
		$old_orders = get_posts(
			[
				'author'         => $this->user_id,
				'post_parent'    => $this->object_id,
				'post_status'    => 'pending',
				'post_type'      => 'order',
				'posts_per_page' => 1,
			]
		);

		if ($old_orders) {
			$this->ID = $old_orders[0]->ID;
		} elseif ($this->object_id) {
			/**
			 *@since 2019.06.04
			 *新增订单统计
			 *插入订单时，无论订单状态均新增订单统计，以实现某些场景下需要限定订单总数时，锁定数据，预留支付时间
			 *获取订单统计时，删除超时未完成的订单，并减去对应订单统计 @see wnd_get_order_count($object_id)
			 */
			wnd_inc_order_count($this->object_id, 1);
		}

		$post_arr = [
			'ID'           => $this->ID ?: 0,
			'post_author'  => $this->user_id,
			'post_parent'  => $this->object_id,
			'post_content' => $this->total_amount ?: __('免费', 'wnd'),
			'post_status'  => $this->status,
			'post_title'   => $this->subject,
			'post_type'    => 'order',
			'post_name'    => uniqid(),
		];
		$this->ID = wp_insert_post($post_arr);
		if (is_wp_error($this->ID) or !$this->ID) {
			throw new Exception(__('创建订单失败', 'wnd'));
		}

		/**
		 *@since 2019.02.17
		 *success表示直接余额消费
		 *pending 则表示通过在线直接支付订单，需要等待支付平台验证返回后更新支付 @see static::verify();
		 */
		if ('success' == $this->status) {
			$this->complete(false);
		}

		return $this->ID;
	}

	/**
	 *@since 2019.02.11
	 *确认在线消费订单
	 *@return int or false
	 *
	 *@param string 	$payment_method		required 	支付平台标识
	 *@param int 		$this->ID  			required
	 *@param string 	$this->subject 		option
	 */
	public function verify($payment_method) {
		if ($this->post->post_type != 'order') {
			throw new Exception(__('订单ID无效', 'wnd'));
		}

		// 订单支付状态检查
		if ($this->post->post_status != 'pending') {
			throw new Exception(__('订单状态无效', 'wnd'));
		}

		/**
		 *在线支付的订单，设置如下参数，以区分站内订单。用于订单标识，及订单退款
		 *
		 *post_excerpt = $payment_method（记录支付平台如：alipay、wepay）
		 *post_title = $post->post_title . __('(在线支付)', 'wnd')
		 */
		$post_arr = [
			'ID'           => $this->ID,
			'post_status'  => 'success',
			'post_excerpt' => $payment_method,
			'post_title'   => $this->subject ?: $this->post->post_title . __('(在线支付)', 'wnd'),
		];
		$ID = wp_update_post($post_arr);
		if (!$ID or is_wp_error($ID)) {
			throw new Exception(__('数据更新失败', 'wnd'));
		}

		// 订单完成
		$this->complete(true);

		return $ID;
	}

	/**
	 *订单成功后，执行的统一操作
	 *@since 2020.06.10
	 *
	 *@param bool $online_payments 是否为在线支付订单
	 */
	protected function complete(bool $online_payments) {
		// 在线订单异步校验时，由支付平台发起请求，并指定订单ID，需根据订单ID设置对应变量
		if ($online_payments) {
			$this->user_id      = $this->post->post_author;
			$this->total_amount = $this->post->post_content;
			$this->object_id    = $this->post->post_parent;
		}

		// 写入消费记录
		wnd_inc_user_expense($this->user_id, $this->total_amount);

		// 站内消费，记录扣除账户余额、在线支付则不影响当前余额
		if (!$online_payments) {
			wnd_inc_user_money($this->user_id, $this->total_amount * -1);
		}

		/**
		 *@since 2019.06.04
		 *产品订单：更新总销售额、设置原作者佣金
		 */
		if ($this->object_id) {
			wnd_inc_post_total_sales($this->object_id, $this->total_amount);

			// @since 2020.06.11 废弃缓存删除，该功能已通过 WP Action post_updated HOOK实现
			// wp_cache_delete($this->user_id . '-' . $this->object_id, 'wnd_has_paid');

			// 文章作者新增佣金
			$commission = (float) wnd_get_post_commission($this->object_id);
			if ($commission <= 0) {
				return;
			}

			$object = get_post($this->object_id);
			try {
				$recharge = new Wnd_Recharge();
				$recharge->set_object_id($object->ID); // 设置佣金来源
				$recharge->set_user_id($object->post_author);
				$recharge->set_total_amount($commission);
				$recharge->create(true); // 直接写入余额
			} catch (Exception $e) {
				throw new Exception($e->getMessage());
			}
		}

		do_action('wnd_order_completed', $this->ID);
	}
}
