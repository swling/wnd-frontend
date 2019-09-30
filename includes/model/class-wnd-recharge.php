<?php
namespace Wnd\Model;
use \Exception;

/**
 *@since 2019.08.11
 *支付模块
 *
 *	# 自定义文章类型
 *	post_type属性('public' => false)，因此在WordPress后台无法查看到
 *	充值：recharge
 *
 *	# 状态：
 *	pending / success
 *
 *	# 充值Post Data
 *	金额：post_content
 *	关联：post_parent
 *	标题：post_title
 *	状态：post_status: pengding / success
 *	类型：post_type：recharge
 *
 */
class Wnd_Recharge extends Wnd_Finance {

	/**
	 *@since 2019.08.11
	 *构造函数
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 *@since 2019.01.30
	 *金额：post_content
	 *关联：post_parent
	 *状态：post_status
	 *类型：post_type recharge
	 *用户通过第三方金融平台充值付款到本站
	 *创建时：post_status=>pending，验证成功后：post_status=>success
	 *写入post时需要设置别名，否则更新时会自动根据标题设置别名，而充值类标题一致，会导致WordPress持续循环查询并设置 -2、-3这类自增标题，产生大量查询
	 *
	 *@param int 		$this->user_id  	required
	 *@param float  	$this->total_money	required
	 *@param string 	$this->subject 		option
	 *@param int 		$this->object_id  	option
	 *@param bool 	 	$is_success 		option 	是否直接写入，无需支付平台校验
	 *
	 *@return int object ID
	 */
	public function create(bool $is_success = false) {
		if (!$this->user_id) {
			throw new Exception('请登录！');
		}
		if (!$this->total_amount) {
			throw new Exception('获取充值金额失败！');
		}

		// 定义变量
		$status        = $is_success ? 'success' : 'pending';
		$this->subject = $this->subject ?: '充值：' . $this->total_amount;

		/**
		 *@since 2019.03.31 查询符合当前条件，但尚未完成的付款订单
		 */
		$old_recharges = get_posts(
			array(
				'author'         => $this->user_id,
				'post_parent'    => $this->object_id,
				'post_status'    => 'pending',
				'post_type'      => 'recharge',
				'posts_per_page' => 1,
			)
		);
		if ($old_recharges) {
			$this->ID = $old_recharges[0]->ID;
		}

		$post_arr = array(
			'ID'           => $this->ID ?: 0,
			'post_author'  => $this->user_id,
			'post_parent'  => $this->object_id,
			'post_content' => $this->total_amount,
			'post_status'  => $status ?: 'pending',
			'post_title'   => $this->subject,
			'post_type'    => 'recharge',
			'post_name'    => uniqid(),
		);
		$ID = wp_insert_post($post_arr);
		if (is_wp_error($ID) or !$ID) {
			throw new Exception('创建充值订单失败！');
		}

		// 当充值包含关联object 如post，表示收入来自站内佣金收入
		if ('success' == $status) {
			if ($this->object_id) {
				wnd_inc_user_commission($this->user_id, $this->total_amount);
			} else {
				wnd_inc_user_money($this->user_id, $this->total_amount);
			}

			/**
			 *@since 2019.08.12
			 *充值完成
			 */
			do_action('wnd_recharge_completed', $ID);
		}

		$this->ID = $ID;
		return $ID;
	}

	/**
	 *@since 2019.02.11
	 *更新支付订单状态
	 *@return int or Exception
	 *
	 *@param int 		$this->ID  			required
	 *@param string 	$this->subject 		option
	 */
	public function verify() {
		$post = get_post($this->ID);
		if (!$this->ID or $post->post_type != 'recharge') {
			throw new Exception('当前充值订单ID无效！');
		}

		$before_status = $post->post_status;
		$total_amount  = $post->post_content;

		$post_arr = array(
			'ID'          => $this->ID,
			'post_status' => 'success',
			'post_title'  => $this->subject ?: $post->post_title,
		);
		$ID = wp_update_post($post_arr);

		// 当充值订单，从pending更新到 success，表示充值完成，更新用户余额
		if ($ID and 'pending' == $before_status) {
			wnd_inc_user_money($post->post_author, $total_amount);

			/**
			 *@since 2019.08.12
			 *充值完成
			 */
			do_action('wnd_recharge_completed', $ID);
		}

		return $ID;
	}
}
