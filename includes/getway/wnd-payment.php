<?php
namespace Wnd\Getway;

use Exception;
use Wnd\Getway\Wnd_Payment_Getway;
use Wnd\Model\Wnd_Transaction;

/**
 * 支付模块
 * @since 0.9.32
 */
abstract class Wnd_Payment {

	// 站内交易记录对象 Wnd_Transaction
	protected $transaction;

	// 基于Post ID生成，发送至第三方平台的订单号
	protected $out_trade_no;

	// 订单金额
	protected $total_amount;

	// 订单标题
	protected $subject;

	// App ID：站外支付可能需要设置此属性（如微信小程序、公众号支付）@since 0.9.56.6
	protected $app_id;

	// 站点前缀，用于区分订单号
	protected static $site_prefix;

	// 站点名
	protected static $site_name;

	/**
	 * 根据支付平台，并自动选择子类处理当前业务
	 */
	public static function get_instance(Wnd_Transaction $transaction, string $app_id = ''): Wnd_Payment {
		static::$site_name   = get_bloginfo('name');
		static::$site_prefix = static::build_site_prefix();
		$payment_gateway     = Wnd_Payment_Getway::get_payment_gateway($transaction->get_transaction_id());

		/**
		 * 新增 filter 以实现通过插件对支付接口的拓展
		 * @since 2020.07.12
		 */
		$class_name = '\Wnd\Getway\Payment\\' . $payment_gateway;
		$class_name = apply_filters('wnd_payment_handler', $class_name, $payment_gateway);
		if (class_exists($class_name)) {
			return new $class_name($transaction, $app_id);
		} else {
			throw new Exception(__('未定义支付接口处理类', 'wnd') . ':' . $class_name);
		}
	}

	/**
	 * @param $Wnd_Transaction Wnd_Transaction 设定站内交易对象
	 * @param $app_id          string          设置当前付款的 App ID. 如微信小程序、公众号支付，通常与站内微信支付 AppID 不同. @since 0.9.56.6
	 */
	public function __construct(Wnd_Transaction $transaction, string $app_id = '') {
		$this->transaction  = $transaction;
		$this->total_amount = $this->transaction->get_total_amount();
		$this->out_trade_no = $this->get_out_trade_no();
		$this->subject      = $this->get_subject();
		$this->app_id       = $app_id;
	}

	/**
	 * 构建第三方平台支付接口，如支付表单，支付二维码等。交付对应子类实现。
	 */
	abstract public function build_interface(): string;

	/**
	 * 支付验签
	 * - 通常应包含同步验签及异步验签
	 * - 若验签失败，需抛出异常中止支付流程
	 * - 验签通过后，根据支付报文，解析出站内交易订单对象
	 */
	abstract public static function verify_payment(): Wnd_Transaction;

	/**
	 * 支付验签通过后，更新站内记录
	 */
	public function update_transaction() {
		$verify_type = ('POST' == $_SERVER['REQUEST_METHOD']) ? __('异步', 'wnd') : __('同步', 'wnd');
		$subject     = $this->transaction->get_subject() . '(' . $verify_type . ')';
		$this->transaction->set_subject($subject);
		$this->transaction->verify();
	}

	/**
	 * 从第三方支付平台订单号，解析出本站对应的Post ID
	 * @param  	string 	$out_trade_no  	支付平台订单号
	 * @return 	int|0  	order|recharge Post ID
	 */
	public static function parse_out_trade_no(string $out_trade_no): int {
		$site_prefix = static::$site_prefix ?: static::build_site_prefix();

		if (false === strpos($out_trade_no, $site_prefix . '-')) {
			return 0;
		}

		list($prefix, $ID, $timestamp) = explode('-', $out_trade_no, 3);
		if ($prefix != $site_prefix) {
			return 0;
		}

		return (int) $ID;
	}

	/**
	 * 构建包含当前站点标识的订单号码作为发送至三方支付平台的订单号
	 * @since 0.9.56 订单号加入时间戳。解决微信支付订单创建后，若修改金额再次支付，报错：201 订单重复
	 *               该时间戳需要在商户系统保存，以备后续调用（如退款，查询等），故不可设置为随机时间戳
	 */
	public function get_out_trade_no() {
		$ID        = $this->transaction->get_transaction_id();
		$timestamp = $this->transaction->get_timestamp();
		if (!$ID) {
			throw new Exception(__('站内支付数据尚未写入', 'wnd'));
		}

		return static::$site_prefix . '-' . $ID . '-' . $timestamp;
	}

	/**
	 * 在站内标题基础上加上站点名称，便于用户在第三方支付平台识别
	 * @since 2019.12.21
	 */
	public function get_subject() {
		return static::$site_name . ' - ' . $this->transaction->get_subject();
	}

	/**
	 * 构建包含当前站点标识的订单号码作为发送至三方支付平台的订单号
	 *
	 * 为防止多站点公用一个支付应用id，或测试环境与正式环境中产生重复的支付订单id，在充值id的前缀前，添加了基于该站点 site_url() 的前缀字符
	 * 不采用别名做订单的原因：在WordPress中，不同类型的post type别名可以是重复的值，会在一定程度上导致不确定性，同时根据别名查询post的语句也更复杂
	 * 该前缀对唯一性要求不高，仅用于区分上述情况下的冲突
	 * build_site_prefix基于md5，组成为：数字字母，post_id为整数，因而分割字符需要回避数字和字母
	 * @since 2019.03.04
	 * @since 2019.03.04
	 */
	private static function build_site_prefix(): string {
		return strtoupper(substr(md5(site_url()), 0, 4));
	}

	/**
	 * 同步回调跳转链接
	 */
	public function return () {
		if ('GET' != $_SERVER['REQUEST_METHOD']) {
			return false;
		}

		$object_id = $this->transaction->get_object_id();
		$type      = $this->transaction->get_type();

		// 订单
		if ($object_id) {
			$url = get_permalink($object_id) ?: home_url();
		} else {
			$url = wnd_get_front_page_url() ?: home_url();
		}

		// Filter
		$return_url = apply_filters('wnd_pay_return_url', $url, $type, $object_id);

		header('Location:' . add_query_arg('from', $type . '_successful', $return_url));
		exit;
	}

	/**
	 * 统一构支付界面
	 * @since 0.9.56.7
	 */
	public static function build_payment_interface(int $payment_id, string $payment_interface, string $title): string {
		return '<div><h3 id="payment-title">' . $title . '</h3>' . $payment_interface . '</div>';
	}

}
