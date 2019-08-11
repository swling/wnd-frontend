<?php
/**
 *@since 2019.08.11
 *支付模块
 *
 *	# 自定义文章类型
 *	以下 post_type 并未均为私有属性('public' => false)，因此在WordPress后台无法查看到
 *
 *	充值：recharge
 *	消费、订单：order
 *	整站月度财务统计：stats-re(充值)、stats-ex(消费)
 *
 *	# 状态：
 *	pending / success
 *
 *	# 充值、消费post data
 *	金额：post_content
 *	关联：post_parent
 *	标题：post_title
 *	状态：post_status: pengding / success
 *	类型：post_type：recharge / order
 *
 */
class Wnd_Payment {

	// 站点用户ID
	public $user_id;

	// 产品ID 对应WordPress产品类型Post ID
	public $object_id;

	// 支付标题：产品标题 / 充值标题 / 其他自定义
	public $subject;

	// 金额
	public $total_amount;

	// 支付用途将写入对应 recharge / order (Wnd)Post Meta
	public $use_to;

	// 商户订单号，对应WordPress 写入 recharge/order 后产生的 Post ID
	public $trade_no;

	// 基于$trade_no生成，发送至第三方平台的订单号
	public $out_trade_no;

	// 站点前缀，用于区分订单号
	public $site_prefix;

	// 支付状态：pending / success
	public $status;

	// 规定允许的状态
	public $allowed_status = array('pending', 'success');

	/**
	 *@since 2019.08.11
	 *构造函数
	 */
	public function __construct() {
		$this->user_id = get_current_user_id();
		$this->site_prefix = strtoupper(substr(md5(home_url()), 0, 4));
	}

	/**
	 *构建包含当前站点标识的订单号码作为发送至三方支付平台的订单号
	 *
	 *为防止多站点公用一个支付应用id，或测试环境与正式环境中产生重复的支付订单id，在充值id的前缀前，添加了基于该站点home_url()的前缀字符
	 *@since 2019.03.04
	 *
	 *不采用别名做订单的原因：在WordPress中，不同类型的post type别名可以是重复的值，会在一定程度上导致不确定性，同时根据别名查询post的语句也更复杂
	 *该前缀对唯一性要求不高，仅用于区分上述情况下的冲突
	 *build_site_prefix基于md5，组成为：数字字母，post_id为整数，因而分割字符需要回避数字和字母
	 *@since 2019.03.04
	 *
	 *基于当前站点的首页地址，生成四位字符站点前缀标识符
	 */

	/**
	 *构建包含当前站点标识的订单号码作为发送至三方支付平台的订单号
	 */
	protected function build_out_trade_no() {
		return $this->site_prefix . '-' . $this->trade_no;
	}

	/**
	 *从第三方支付平台订单号，解析出本站对应的Post ID
	 */
	protected function parse_out_trade_no($out_trade_no) {
		if (false === strpos($out_trade_no, $this->site_prefix . '-')) {
			return 0;
		}

		list($prefix, $trade_no) = explode('-', $out_trade_no, 2);
		if ($prefix != $this->site_prefix) {
			return 0;
		}
		return (int) $trade_no;
	}

	/**
	 *@since 2019.02.17 写入支付信息 订单 / 充值
	 *
	 *若设置了object_id 调用：insert_order 否则调用: insert_recharge
	 *
	 *@param int 		$this->user_id  	required
	 *@param float  	$this->total_money	required when !$object_id
	 *@param int 		$this->object_id  	option
	 *@param string 	$this->subject 		option
	 *@param string 	$this->status 		option
	 */
	public function insert_payment() {
		if (!$this->user_id) {
			throw new Exception('请登录！');
		}

		//@since 2019.03.31 查询符合当前条件，但尚未完成的付款订单
		$old_payments = get_posts(
			array(
				'author' => $this->user_id,
				'post_parent' => $this->object_id,
				'post_status' => 'pending',
				'post_type' => $this->object_id ? 'order' : 'recharge',
				'posts_per_page' => 1,
			)
		);
		if ($old_payments and $old_payments[0]->post_content == $this->total_amount) {
			$this->trade_no = $old_payments[0]->ID;
			return;
		}

		// 在线订单 / 充值
		if ($this->objcet_id) {
			$this->insert_order();
		} else {
			$this->insert_recharge();
		}

	}

	/**
	 *@since 2019.02.11
	 *充值付款校验
	 *@return array
	 *当支付信息中包含 object id表示为订单支付，否则为余额充值
	 *订单支付，返回 status=> 2, msg => object_id
	 *
	 *@param int 		$this->trade_no  	required
	 *@param float  	$this->total_money	required
	 */
	public function verify_payment() {
		$type = !empty($_POST) ? '异步' : '同步';
		$this->trade_no = $this->parse_out_trade_no($this->out_trade_no);

		// 校验
		$payment = get_post($this->trade_no);
		if (!$this->trade_no or !$payment) {
			throw new Exception('ID无效！');
		}
		if ($payment->post_content != $this->total_amount) {
			throw new Exception('金额不匹配！');
		}

		// 定义变量
		$this->trade_no = $payment->ID;
		$this->status = 'success';
		$this->subject = $payment->post_title . ' - ' . $type;
		$this->object_id = $payment->post_parent;

		//订单已经更新过
		if ($payment->post_status == 'success') {
			return true;
		}

		// 订单支付状态检查
		if ($payment->post_status == 'pending') {

			// 更新 订单/充值
			if ($payment->post_parent) {
				$update = $this->update_order();
			} else {
				$update = $this->update_recharge();
			}

			//  写入用户账户信息
			if ($update) {

				/**
				 * @since 2019.06.30
				 *成功完成付款后
				 */
				do_action('wnd_payment_verified', $payment);

				return true;
			} else {
				throw new Exception('金额不匹配！');
			}

		} else {
			throw new Exception('订单状态无效！');
		}

	}

	/**
	 *@since 2019.01.30
	 *金额：post_content
	 *关联：post_parent
	 *状态：post_status
	 *类型：post_type (recharge / order)
	 *用户通过第三方金融平台充值付款到本站
	 *创建时：post_status=>pending，验证成功后：post_status=>success
	 *写入post时需要设置别名，否则更新时会自动根据标题设置别名，而充值类标题一致，会导致WordPress持续循环查询并设置 -2、-3这类自增标题，产生大量查询
	 *
	 *@param int 		$this->user_id  	required
	 *@param float  	$this->total_money	required
	 *@param string 	$this->subject 		option
	 *@param string 	$this->status 		option
	 *@param int 		$this->object_id  	option
	 *
	 *@return int object ID
	 */
	public function insert_recharge() {
		if (!$this->user_id) {
			throw new Exception('请登录！');
		}
		if (!$this->total_amount) {
			throw new Exception('获取充值金额失败！');
		}

		$post_arr = array(
			'post_author' => $this->user_id,
			'post_parent' => $this->object_id,
			'post_content' => $this->total_amount,
			'post_status' => $this->status ?: 'pending',
			'post_title' => $this->subject ?: '充值：' . $this->total_amount,
			'post_type' => 'recharge',
			'post_name' => uniqid(),
		);
		$recharge_id = wp_insert_post($post_arr);
		if (is_wp_error($recharge_id) or !$recharge_id) {
			throw new Exception('创建充值订单失败！');
		}

		// 标记用途
		if ($this->use_to) {
			wnd_update_post_meta($recharge_id, 'use_to', $this->use_to);
		}

		// 当充值包含关联object 如post，表示收入来自站内，如佣金收入
		if ('success' == $this->status) {
			if ($this->object_id) {
				wnd_inc_user_commission($this->user_id, $this->total_amount);
			} else {
				wnd_inc_user_money($this->user_id, $this->total_amount);
			}
		}

		return $recharge_id;
	}

	/**
	 *@since 2019.02.11
	 *更新支付订单状态
	 *@return int or false
	 *
	 *@param int 		$this->trade_no  	required
	 *@param string 	$this->status 		required
	 *@param string 	$this->subject 		option
	 */
	public function update_recharge() {
		$post = get_post($this->trade_no);
		if (!$this->trade_no or $post->post_type != 'recharge') {
			throw new Exception('当前充值订单ID无效！');
		}
		if (!in_array($this->status, $this->allowed_status)) {
			throw new Exception('指定更新状态不合法！');
		}

		$before_status = $post->post_status;
		$total_amount = $post->post_content;

		$post_arr = array(
			'ID' => $this->trade_no,
			'post_status' => $this->status,
			'post_title' => $this->subject ?: $post->post_title,
		);
		$recharge_id = wp_update_post($post_arr);

		// 当充值订单，从pending更新到 success，表示充值完成，更新用户余额
		if ($recharge_id and 'pending' == $before_status and 'success' == $this->status) {
			wnd_inc_user_money($post->post_author, $total_amount);
		}

		return $recharge_id;
	}

	/**
	 *@since 2019.02.11
	 *用户本站消费数据(含余额消费，或直接第三方支付消费)
	 *
	 *@param int 		$this->user_id  	required
	 *@param int 		$this->object_id  	required
	 *@param string 	$this->status 		option
	 *@param string 	$this->subject 		option
	 */
	public function insert_order() {
		if (!$this->user_id) {
			throw new Exception('请登录！');
		}
		if (!$this->object_id or !get_post($this->object_id)) {
			throw new Exception('订单未指定产品，或产品无效！');
		}

		$this->total_amount = wnd_get_post_price($this->object_id);
		$post_arr = array(
			'post_author' => $this->user_id,
			'post_parent' => $this->object_id,
			'post_content' => $this->total_amount ?: '免费',
			'post_status' => $this->status ?: 'pending',
			'post_title' => $this->subject ?: get_the_title($this->object_id),
			'post_type' => 'order',
			'post_name' => uniqid(),
		);
		$order_id = wp_insert_post($post_arr);
		if (is_wp_error($order_id) or !$order_id) {
			throw new Exception('创建订单失败！');
		}

		// 标记用途
		if ($this->use_to) {
			wnd_update_post_meta($order_id, 'use_to', $this->use_to);
		}

		/**
		 *@since 2019.06.04
		 *新增订单统计
		 *插入订单时，无论订单状态均新增订单统计，以实现某些场景下需要限定订单总数时，锁定数据，预留支付时间
		 *获取订单统计时，删除超时未完成的订单，并减去对应订单统计 @see wnd_get_order_count($object_id)
		 */
		wnd_inc_wnd_post_meta($this->object_id, 'order_count', 1);

		/**
		 *@since 2019.02.17
		 *success表示直接余额消费，更新用户余额
		 *pending 则表示通过在线直接支付订单，需要等待支付平台验证返回后更新支付 @see wnd_update_order();
		 */
		if ('success' == $this->status) {
			wnd_inc_user_money($this->user_id, $this->total_amount * -1);

			/**
			 * @since 2019.07.14
			 *订单完成
			 */
			do_action('wnd_order_completed', $order_id);
		}

		/**
		 *@since 2019.06.04
		 *删除对象缓存
		 **/
		wp_cache_delete($this->user_id . $this->object_id, 'user_has_paid');

		return $order_id;
	}

	/**
	 *@since 2019.02.11
	 *更新消费订单状态
	 *@return int or false
	 *
	 *@param int 		$this->trade_no  	required
	 *@param string 	$this->status 		required
	 *@param string 	$this->subject 		option
	 */
	public function update_order() {
		$post = get_post($this->trade_no);
		if (!$this->trade_no or $post->post_type != 'order') {
			throw new Exception('当前订单ID无效！');
		}
		if (!in_array($this->status, $this->allowed_status)) {
			throw new Exception('指定更新状态不合法！');
		}

		$before_status = $post->post_status;
		$total_amount = $post->post_content;

		$post_arr = array(
			'ID' => $this->trade_no,
			'post_status' => $this->status,
			'post_title' => $this->subject ?: $post->post_title,
		);
		$order_id = wp_update_post($post_arr);
		if (is_wp_error($order_id) or !$order_id) {
			throw new Exception('更新订单失败！');
		}

		/**
		 *@since 2019.02.17
		 *当消费订单，从pending更新到 success，表示该消费订单是通过在线支付，而非余额支付，无需扣除用户余额
		 *由于此处没有触发 wnd_inc_user_money 因此需要单独统计财务信息
		 */
		if ('pending' == $before_status and 'success' == $this->status) {
			wnd_update_fin_stats($total_amount * -1);

			/**
			 * @since 2019.07.14
			 *订单完成
			 */
			do_action('wnd_order_completed', $order_id);
		}

		/**
		 *@since 2019.06.04
		 *删除对象缓存
		 **/
		wp_cache_delete($post->post_author . $post->post_parent, 'user_has_paid');

		return $order_id;
	}

}