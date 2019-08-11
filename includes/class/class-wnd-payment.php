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

// 创建支付
$payment = new Wnd_Payment();

$payment->total_amount = 10;
// or
$payment->set_object_id(616);

$payment->create();


// 验证支付
$payment = new Wnd_Payment();
$payment->total_amount = 11;
$payment->set_out_trade_no($out_trade_no = $payment->site_prefix.'-616');
$payment->verify();

 */
class Wnd_Payment {

	// 商户订单号，对应WordPress 写入 recharge/order 后产生的 Post ID
	public $ID;

	// 站点用户ID
	public $user_id;

	// 产品ID 对应WordPress产品类型Post ID
	public $object_id;

	// 支付标题：产品标题 / 充值标题 / 其他自定义
	public $subject;

	// 金额
	public $total_amount;

	// 基于$this->ID生成，发送至第三方平台的订单号
	public $out_trade_no;

	// 站点前缀，用于区分订单号
	public $site_prefix;

	/**
	 *@since 2019.08.11
	 *构造函数
	 */
	public function __construct() {
		$this->user_id = get_current_user_id();

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
		 */
		$this->site_prefix = strtoupper(substr(md5(home_url()), 0, 4));
	}

	/**
	 *@since 2019.08.11
	 **/
	public function set_object_id(int $object_id) {
		$this->object_id = $object_id;
	}

	/**
	 *构建包含当前站点标识的订单号码作为发送至三方支付平台的订单号
	 */
	public function set_out_trade_no($out_trade_no) {
		$this->out_trade_no = $out_trade_no;
	}

	/**
	 *从第三方支付平台订单号，解析出本站对应的Post ID
	 */
	protected function parse_out_trade_no($out_trade_no) {
		if (false === strpos($out_trade_no, $this->site_prefix . '-')) {
			return 0;
		}

		list($prefix, $ID) = explode('-', $out_trade_no, 2);
		if ($prefix != $this->site_prefix) {
			return 0;
		}
		return (int) $ID;
	}

	/**
	 *@since 2019.02.17 创建在线支付信息 订单 / 充值
	 *
	 *若设置了object_id 调用：insert_order 否则调用: insert_recharge
	 *
	 *@param int 		$this->user_id  	required
	 *@param float  	$this->total_money	required when !$object_id
	 *@param int 		$this->object_id  	option
	 *@param string 	$this->subject 		option
	 */
	public function create() {
		if (!$this->user_id) {
			throw new Exception('请登录！');
		}

		// 在线订单 / 充值
		if ($this->object_id) {
			$order = new Wnd_Order();
			$order->object_id = $this->object_id;
			$order->status = 'pending';
			$order->create();

			$this->ID = $order->ID;
			$this->subject = $order->subject;

		} else {
			$recharge = new Wnd_Recharge();
			$recharge->total_amount = $this->total_amount;
			$recharge->status = 'pending';
			$recharge->create();

			$this->ID = $recharge->ID;
			$this->subject = $recharge->subject;

		}

	}

	/**
	 *@since 2019.02.11
	 *充值付款校验
	 *@return bool 		true if success
	 *
	 *@param int 		$this->ID  				required if !$this->out_trade_no
	 *@param string 	$this->out_trade_no	  	required if !$this->ID
	 *@param float  	$this->total_money		required
	 */
	public function verify() {
		$type = !empty($_POST) ? '异步' : '同步';
		$this->ID = $this->ID ?: $this->parse_out_trade_no($this->out_trade_no);

		// 校验
		$payment = get_post($this->ID);
		if (!$this->ID or !$payment) {
			throw new Exception('ID无效：' . $this->ID);
		}
		if ($payment->post_content != $this->total_amount) {
			throw new Exception('金额不匹配！');
		}

		// 定义变量
		$this->ID = $payment->ID;
		$this->subject = $payment->post_title . ' - ' . $type;
		$this->object_id = $payment->post_parent;

		// 订单支付状态检查
		if ('success' == $payment->post_status) {
			return true;
		}
		if ($payment->post_status != 'pending') {
			throw new Exception('订单状态无效！');
		}

		// 更新 订单/充值
		if ($payment->post_parent) {
			$order = new Wnd_Order();
			$order->ID = $this->ID;
			$order->status = 'success';
			$order->update();

		} else {
			$recharge = new Wnd_Recharge();
			$recharge->ID = $this->ID;
			$recharge->status = 'success';
			$recharge->update();
		}

		/**
		 * @since 2019.06.30
		 *成功完成付款后
		 */
		do_action('wnd_payment_verified', $payment);
		return true;
	}

	/**
	 *构建包含当前站点标识的订单号码作为发送至三方支付平台的订单号
	 */
	public function get_out_trade_no() {
		return $this->site_prefix . '-' . $this->ID;
	}

	/**
	 *构建包含当前站点标识的订单号码作为发送至三方支付平台的订单号
	 */
	public function get_subject() {
		return $this->subject;
	}
}
