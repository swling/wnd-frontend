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
 *
 * // 创建支付
 * $payment = new Wnd_Payment();
 * $payment->set_total_amount(10);
 * // or
 * $payment->set_object_id(616);
 *
 * $payment->create();
 * $payment->get_out_trade_no();
 * $payment->get_subject();
 * $payment->get_total_amount();
 *
 *
 * // 获取支付平台返回数据，并完成支付
 * $payment = new Wnd_Payment();
 * $payment->set_total_amount(11);
 * $payment->set_out_trade_no($out_trade_no = $payment->site_prefix.'-616');
 * $payment->verify();
 *
 * $payment->get_object_id();
 *
 */
class Wnd_Payment {

	// 商户订单号，对应WordPress 写入 recharge/order 后产生的 Post ID
	protected $ID;

	// 站点用户ID
	protected $user_id;

	// 产品ID 对应WordPress产品类型Post ID
	protected $object_id;

	// 支付标题：产品标题 / 充值标题 / 其他自定义
	protected $subject;

	// 金额
	protected $total_amount;

	// 基于$this->ID生成，发送至第三方平台的订单号
	protected $out_trade_no;

	// 站点前缀，用于区分订单号
	protected static $site_prefix;

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
		self::$site_prefix = strtoupper(substr(md5(home_url()), 0, 4));
	}

	/**
	 *@since 2019.08.12
	 *指定Post ID
	 **/
	public function set_ID(int $ID) {
		$this->ID = $ID;
	}

	/**
	 *@since 2019.08.12
	 *设定金额
	 **/
	public function set_total_amount(float $total_amount) {
		$this->total_amount = $total_amount;
	}

	/**
	 *@since 2019.08.11
	 **/
	public function set_object_id(int $object_id) {
		$this->object_id = $object_id;
	}

	/**
	 *设置支付平台的支付订单号
	 *@since 2019.08.11
	 *@param string 	$out_trade_no 	支付平台订单号
	 */
	public function set_out_trade_no($out_trade_no) {
		$this->out_trade_no = $out_trade_no;
	}

	/**
	 *从第三方支付平台订单号，解析出本站对应的Post ID
	 *@param 	string 	$out_trade_no 	支付平台订单号
	 *@return 	int|0 	order|recharge Post ID
	 */
	protected function parse_out_trade_no($out_trade_no) {
		if (false === strpos($out_trade_no, self::$site_prefix . '-')) {
			return 0;
		}

		list($prefix, $ID) = explode('-', $out_trade_no, 2);
		if ($prefix != self::$site_prefix) {
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
			$order->set_object_id($this->object_id);
			$order->create();

			$this->ID = $order->get_ID();
			$this->subject = $order->get_subject();
			$this->total_amount = $order->get_total_amount();

		} else {
			$recharge = new Wnd_Recharge();
			$recharge->set_total_amount($this->total_amount);
			$recharge->create();

			$this->ID = $recharge->get_ID();
			$this->subject = $recharge->get_subject();
			$this->total_amount = $recharge->get_total_amount();
		}

	}

	/**
	 *@since 2019.02.11
	 *充值付款校验
	 *@return int|Exception 	order ID|recharge ID if success
	 *
	 *@param int 				$this->ID  				required if !$this->out_trade_no
	 *@param string 			$this->out_trade_no	  	required if !$this->ID
	 *@param float  			$this->total_money		required
	 */
	public function verify() {
		$type = !empty($_POST) ? '异步' : '同步';
		$this->ID = $this->ID ?: $this->parse_out_trade_no($this->out_trade_no);

		// 校验
		$post = get_post($this->ID);
		if (!$this->ID or !$post) {
			throw new Exception('ID无效：' . $this->ID);
		}
		if ($post->post_content != $this->total_amount) {
			throw new Exception('金额不匹配！');
		}

		// 定义变量
		$this->ID = $post->ID;
		$this->subject = $post->post_title . '(' . $type . ')';
		$this->object_id = $post->post_parent;

		// 订单支付状态检查
		if ('success' == $post->post_status) {
			return $this->ID;
		}
		if ($post->post_status != 'pending') {
			throw new Exception('订单状态无效！');
		}

		// 更新 订单/充值
		if ($post->post_parent) {
			$order = new Wnd_Order();
			$order->set_ID($this->ID);
			$order->set_subject($this->subject);
			$order->verify();

		} else {
			$recharge = new Wnd_Recharge();
			$recharge->set_ID($this->ID);
			$recharge->set_subject($this->subject);
			$recharge->verify();
		}

		/**
		 * @since 2019.06.30
		 *成功完成付款后
		 */
		do_action('wnd_payment_verified', $this->ID);
		return $this->ID;
	}

	/**
	 *构建包含当前站点标识的订单号码作为发送至三方支付平台的订单号
	 */
	public function get_out_trade_no() {
		return self::$site_prefix . '-' . $this->ID;
	}

	/**
	 *获取支付订单标题
	 */
	public function get_subject() {
		return $this->subject;
	}

	/**
	 *@since 2019.08.12
	 *获取支付金额
	 **/
	public function get_total_amount() {
		return $this->total_amount;
	}

	/**
	 *@since 2019.08.12
	 *获取支付款项关联Post ID
	 **/
	public function get_object_id() {
		return $this->object_id;
	}
}
