<?php
namespace Wnd\Model;

use Exception;
use WP_Post;

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
 *	接口：post_excerpt：（支付平台标识如：Alipay / Wepay）
 */
abstract class Wnd_Payment extends Wnd_Transaction {

	// 基于Post ID生成，发送至第三方平台的订单号
	protected $out_trade_no;

	// 站点前缀，用于区分订单号
	protected static $site_prefix;

	// 站点名
	protected static $site_name;

	/**
	 *根据支付平台，并自动选择子类处理当前业务
	 */
	public static function get_instance($payment_gateway): Wnd_Payment {
		static::$site_name       = get_bloginfo('name');
		static::$site_prefix     = static::build_site_prefix();
		static::$payment_gateway = $payment_gateway;

		$class_name = __NAMESPACE__ . '\\' . 'Wnd_Payment_' . static::$payment_gateway;
		if (class_exists($class_name)) {
			return new $class_name();
		} else {
			throw new Exception(__('未定义支付方式', 'wnd') . ':' . $class_name);
		}
	}

	/**
	 *设置支付平台的支付订单号
	 *@since 2019.08.11
	 *@param string 	$out_trade_no 	支付平台订单号
	 *
	 *构建：$this->ID
	 *构建：$this->post
	 *
	 *@return object WP Post Object
	 */
	public function set_out_trade_no($out_trade_no): WP_Post{
		$this->out_trade_no = $out_trade_no;
		$this->ID           = static::parse_out_trade_no($out_trade_no);
		$this->post         = get_post($this->ID);
		if (!$ID or !$this->post) {
			throw new Exception(__('支付ID无效：', 'wnd') . $ID);
		}

		return $this->post;
	}

	/**
	 *从第三方支付平台订单号，解析出本站对应的Post ID
	 *@param 	string 	$out_trade_no 	支付平台订单号
	 *@return 	int|0 	order|recharge Post ID
	 */
	public static function parse_out_trade_no($out_trade_no): int{
		$site_prefix = static::$site_prefix ?: static::build_site_prefix();

		if (false === strpos($out_trade_no, $site_prefix . '-')) {
			return 0;
		}

		list($prefix, $ID) = explode('-', $out_trade_no, 2);
		if ($prefix != $site_prefix) {
			return 0;
		}

		return (int) $ID;
	}

	/**
	 *创建支付
	 *
	 *@param float  	$this->total_money			required when !$object_id
	 *@param int 		$this->object_id  			option
	 *
	 *@return object WP Post Object
	 */
	public function pay() {
		// 写入站内数据记录
		$this->create();

		// 发起支付
		$this->do_pay();
	}

	/**
	 *验证支付并执行相关站内业务
	 *
	 *@param $this->out_trade_no
	 *@param $this->total_amount
	 */
	public function verify() {
		if ($this->total_amount != $this->get_total_amount()) {
			throw new Exception(__('金额不匹配', 'wnd'));
		}

		/**
		 *支付平台回调验签
		 *
		 *WordPress 始终开启了魔法引号，因此需要对post 数据做还原处理
		 *@link https://developer.wordpress.org/reference/functions/stripslashes_deep/
		 */
		if ('POST' == $_SERVER['REQUEST_METHOD']) {
			$_POST = stripslashes_deep($_POST);
			if ($this->check_notify()) {
				$this->complete();
			}
		} else {
			$_GET = stripslashes_deep($_GET);
			if ($this->check_return()) {
				$this->complete();
				$this->return();
			}
		}
	}

	/**
	 *发起第三方平台支付方法，交付对应子类实现
	 */
	abstract protected function do_pay();

	/**
	 *第三方平台异步验签，交付对应子类实现
	 */
	abstract protected function check_notify(): bool;

	/**
	 *第三方平台同步验签，交付对应子类实现
	 */
	abstract protected function check_return(): bool;

	/**
	 *@since 2019.02.17 创建在线支付信息 订单 / 充值
	 *
	 *若设置了object_id 调用：Wnd_Order 否则调用: Wnd_Recharge
	 *
	 *@param float  	$this->total_money			required when !$object_id
	 *@param int 		$this->object_id  			option
	 */
	public function create(): WP_Post{
		$payment = $this->object_id ? new Wnd_Order() : new Wnd_Recharge();
		$payment->set_object_id($this->object_id);
		$payment->set_total_amount($this->total_amount);
		$payment->set_payment_gateway(static::$payment_gateway);

		// 写入数据库后构建ID及Post属性，供外部调用属性向支付平台发起请求
		$this->post = $payment->create();
		return $this->post;
	}

	/**
	 *@since 2019.02.11
	 *充值付款校验
	 *@return int 		WP Post ID
	 *
	 *@param object		$this->post 	required 	WP Post Object
	 */
	protected function complete(): int{
		$type = ('POST' == $_SERVER['REQUEST_METHOD']) ? __('异步', 'wnd') : __('同步', 'wnd');

		// 定义变量 本类中，标题方法添加了站点名称，用于支付平台。故此调用父类方法用于站内记录
		$ID        = $this->get_ID();
		$subject   = parent::get_subject() . '(' . $type . ')';
		$object_id = $this->get_object_id();
		$status    = $this->get_status();

		/**
		 *订单支付状态检查
		 *
		 * - 已经完成的订单：返回订单ID，中止操作
		 * - 其他不合法状态：抛出异常
		 *
		 */
		if ('success' == $status) {
			return $ID;
		}

		if ('pending' != $status) {
			throw new Exception(__('订单状态无效', 'wnd'));
		}

		// 更新 订单/充值
		$payment = $object_id ? new Wnd_Order() : new Wnd_Recharge();
		$payment->set_ID($ID);
		$payment->set_subject($subject);
		$payment->verify();

		/**
		 * @since 2019.06.30
		 *成功完成付款后
		 */
		do_action('wnd_payment_verified', $ID);
		return $ID;
	}

	/**
	 *构建包含当前站点标识的订单号码作为发送至三方支付平台的订单号
	 */
	public function get_out_trade_no() {
		$ID = $this->get_ID();
		if (!$ID) {
			throw new Exception(__('站内支付数据尚未写入', 'wnd'));
		}

		return static::$site_prefix . '-' . $ID;
	}

	/**
	 *@since 2019.12.21
	 *在站内标题基础上加上站点名称，便于用户在第三方支付平台识别
	 *
	 */
	public function get_subject() {
		return static::$site_name . ' - ' . parent::get_subject();
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
	 */
	public static function build_site_prefix(): string {
		return strtoupper(substr(md5(home_url()), 0, 4));
	}

	/**
	 *@since 2020.04.12
	 *支付成功后返回链接
	 *@param int $object_id 支付产品ID；为空则为充值
	 */
	public static function get_return_url($object_id = 0) {
		// 订单
		if ($object_id) {
			$url = get_permalink($object_id) ?: (wnd_get_config('pay_return_url') ?: home_url());

			// 充值
		} else {
			$url = wnd_get_config('pay_return_url') ?: home_url();
		}

		return $url;
	}

	/**
	 *同步回调跳转链接
	 */
	protected function return () {
		$url = static::get_return_url($this->get_object_id());
		header('Location:' . add_query_arg('from', 'payment_successful', $url));
		exit;
	}
}
