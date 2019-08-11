<?php
/**
 *@since 2019.08.11
 *订单模块
 *
 *	# 自定义文章类型
 *	post_type recharge 为私有属性('public' => false)，因此在WordPress后台无法查看到
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

// 创建支付订单
$order = new Wnd_Order();
$order->set_object_id(616);
$order->create();

// 订单完成
$order = new Wnd_Order();
$order->ID = 10;
$order->verify();

// 创建并完成订单
$order = new Wnd_Order();
$order->set_object_id(616);
$order->create($is_success =true);

 */
class Wnd_Order {

	// 站点用户ID
	public $user_id;

	// 产品ID 对应WordPress产品类型Post ID
	public $object_id;

	// 执行后生成的订单ID
	public $ID;

	// 支付标题：产品标题 / 充值标题 / 其他自定义
	public $subject;

	// 支付用途将写入对应 (Wnd)Post Meta
	public $use_to;

	/**
	 *@since 2019.08.11
	 *构造函数
	 */
	public function __construct() {
		$this->user_id = get_current_user_id();
	}

	/**
	 *@since 2019.08.11
	 **/
	public function set_object_id(int $object_id) {
		$this->object_id = $object_id;
	}

	/**
	 *@since 2019.02.11
	 *用户本站消费数据(含余额消费，或直接第三方支付消费)
	 *
	 *@param int 		$this->user_id  	required
	 *@param int 		$this->object_id  	required
	 *@param string 	$status 			option
	 *@param string 	$this->subject 		option
	 */
	public function create(bool $is_success = false) {
		if (!$this->user_id) {
			throw new Exception('请登录！');
		}
		if (!$this->object_id or !get_post($this->object_id)) {
			throw new Exception('订单未指定产品，或产品无效！');
		}

		$price = wnd_get_post_price($this->object_id);
		$status = $is_success ? 'success' : 'pending';
		$this->subject = $this->subject ?: get_the_title($this->object_id);

		//@since 2019.03.31 查询符合当前条件，但尚未完成的付款订单
		$old_orders = get_posts(
			array(
				'author' => $this->user_id,
				'post_parent' => $this->object_id,
				'post_status' => 'pending',
				'post_type' => 'order',
				'posts_per_page' => 1,
			)
		);
		if ($old_orders) {
			$this->ID = $old_orders[0]->ID;
		} else {
			/**
			 *@since 2019.06.04
			 *新增订单统计
			 *插入订单时，无论订单状态均新增订单统计，以实现某些场景下需要限定订单总数时，锁定数据，预留支付时间
			 *获取订单统计时，删除超时未完成的订单，并减去对应订单统计 @see wnd_get_order_count($object_id)
			 */
			wnd_inc_wnd_post_meta($this->object_id, 'order_count', 1);
		}

		$post_arr = array(
			'ID' => $this->ID ?: 0,
			'post_author' => $this->user_id,
			'post_parent' => $this->object_id,
			'post_content' => $price ?: '免费',
			'post_status' => $status,
			'post_title' => $this->subject,
			'post_type' => 'order',
			'post_name' => uniqid(),
		);
		$ID = wp_insert_post($post_arr);
		if (is_wp_error($ID) or !$ID) {
			throw new Exception('创建订单失败！');
		}

		// 标记用途
		if ($this->use_to) {
			wnd_update_post_meta($ID, 'use_to', $this->use_to);
		}

		/**
		 *@since 2019.02.17
		 *success表示直接余额消费，更新用户余额
		 *pending 则表示通过在线直接支付订单，需要等待支付平台验证返回后更新支付 @see wnd_update_order();
		 */
		if ('success' == $status) {
			wnd_inc_user_money($this->user_id, $price * -1);

			/**
			 * @since 2019.07.14
			 *订单完成
			 */
			do_action('wnd_order_completed', $ID);
		}

		/**
		 *@since 2019.06.04
		 *删除对象缓存
		 **/
		wp_cache_delete($this->user_id . $this->object_id, 'user_has_paid');

		return $ID;
	}

	/**
	 *@since 2019.02.11
	 *确认消费订单
	 *@return int or false
	 *
	 *@param int 		$this->ID  			required
	 *@param string 	$this->subject 		option
	 */
	public function verify() {
		$post = get_post($this->ID);
		if (!$this->ID or $post->post_type != 'order') {
			throw new Exception('当前订单ID无效！');
		}

		$before_status = $post->post_status;
		$total_amount = $post->post_content;

		$post_arr = array(
			'ID' => $this->ID,
			'post_status' => 'success',
			'post_title' => $this->subject ?: $post->post_title,
		);
		$ID = wp_update_post($post_arr);
		if (is_wp_error($ID) or !$ID) {
			throw new Exception('更新订单失败！');
		}

		/**
		 *@since 2019.02.17
		 *当消费订单，从pending更新到 success，表示该消费订单是通过在线支付，而非余额支付，无需扣除用户余额
		 *由于此处没有触发 wnd_inc_user_money 因此需要单独统计财务信息
		 */
		if ('pending' == $before_status) {
			wnd_update_fin_stats($total_amount * -1);

			/**
			 * @since 2019.07.14
			 *订单完成
			 */
			do_action('wnd_order_completed', $ID);
		}

		/**
		 *@since 2019.06.04
		 *删除对象缓存
		 **/
		wp_cache_delete($post->post_author . $post->post_parent, 'user_has_paid');

		return $ID;
	}
}
