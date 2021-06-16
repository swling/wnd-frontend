<?php
namespace Wnd\Model;

use Exception;
use Wnd\Model\Wnd_Order_Product;
use WP_Post;
use WP_User;

/**
 * 定义订单站内数据库记录的抽象基类
 *
 * 	### 自定义文章类型
 * 	以下 post_type 并未均为私有属性('public' => false)，因此在WordPress后台无法查看到
 * 	充值：recharge
 * 	消费、订单：order
 *
 * 	### 充值、消费post data
 * 	金额：post_content
 * 	关联：post_parent
 * 	标题：post_title
 * 	类型：post_type：recharge / order
 * 	接口：post_excerpt：（支付平台标识如：Alipay / Wepay）
 *
 * @since 2019.09.24
 */
abstract class Wnd_Transaction {

	// order / recharge Post ID
	protected $transaction_id;

	// order / recharge WP Post object
	protected $transaction;

	// 站点用户ID
	protected $user_id;

	// 产品ID 对应WordPress产品类型Post ID
	protected $object_id;

	// 金额
	protected $total_amount;

	// 支付标题：产品标题 / 充值标题 / 其他自定义
	protected $subject = '';

	// 交易订单对应的产品或服务属性
	protected $props = [];

	// 状态
	protected $status;

	// 交易数目
	protected $quantity = 1;

	// 付款进行中
	public static $processing_status = 'wnd-processing';

	// 付款完成、交易等待（实体商品交易中：待收货 / 待确认）
	public static $pending_status = 'wnd-pending';

	// 交易完成
	public static $completed_status = 'wnd-completed';

	// 交易退款
	public static $refunded_status = 'wnd-refunded';

	// 交易关闭（取消订单 \ 无效订单）
	public static $cancelled_status = 'wnd-cancelled';

	/**
	 * 第三方支付接口
	 *
	 * 支付接口保存在 post_excerpt 如果此处不做默认定义，即为NLL，会报错提示：post_excerpt 不能为 null
	 * @since 2020.06.21
	 */
	protected $payment_gateway = '';

	/**
	 * 构造函数
	 * @since 2019.08.11
	 */
	public function __construct() {
		$this->user_id = get_current_user_id();
	}

	/**
	 * 获取处理当前在线交易的站内 Class。插件内置：订单 Wnd_Order 及充值 Wnd_Recharge
	 * - 创建在线交易时，应指定 Type
	 * - 交易验签时，自动提取订单 Post Type
	 * @since 0.9.28
	 */
	public static function get_instance(string $type = '', int $transaction_id = 0): Wnd_Transaction {
		if (!$type and !$transaction_id) {
			throw new Exception(__('交易类型无效', 'wnd'));
		}

		// 若指定了 Transaction id，则按 id 获取 Type。典型场景：payment 回调校验
		if ($transaction_id) {
			$type = static::get_type_by_transaction_id($transaction_id);
		}

		if ('order' == $type) {
			$instance = new Wnd_Order();
		} elseif ('recharge' == $type) {
			$instance = new Wnd_Recharge();
		} else {
			$instance = false;
		}

		$instance = apply_filters('wnd_transaction_instance', $instance, $type);
		if (!$instance) {
			throw new Exception(__('无效的 Transaction 实例', 'wnd'));
		}

		/**
		 * 设定 Transaction ID
		 * @since 0.9.32
		 */
		if ($transaction_id) {
			$instance->set_transaction_id($transaction_id);
		}

		return $instance;
	}

	/**
	 * 指定Post ID (order/recharge/payment)
	 * @since 2019.08.12
	 *
	 * @return object 	WP Post Object
	 */
	public function set_transaction_id(int $ID): WP_Post{
		$this->transaction_id = $ID;
		$this->transaction    = get_post($ID);
		if (!$ID or !$this->transaction) {
			throw new Exception(__('交易ID无效', 'wnd'));
		}

		return $this->transaction;
	}

	/**
	 * 设置支付网关如（支付宝，微信支付等）
	 * @since 2020.06.19
	 */
	public function set_payment_gateway($payment_gateway) {
		$this->payment_gateway = $payment_gateway;
	}

	/**
	 * 设定金额
	 * @since 2019.08.12
	 */
	public function set_total_amount(float $total_amount) {
		if (!is_numeric($total_amount)) {
			throw new Exception(__('金额无效', 'wnd'));
		}

		$this->total_amount = $total_amount;
	}

	/**
	 * 指定产品ID
	 * @since 2019.08.11
	 */
	public function set_object_id(int $object_id) {
		$post = $object_id ? get_post($object_id) : false;
		if ($object_id and !$post) {
			throw new Exception(__('商品ID无效', 'wnd'));
		}

		$this->object_id = $object_id;
	}

	/**
	 * 交易数目（同一商品）
	 * @since 0.8.76
	 */
	public function set_quantity(int $quantity) {
		$this->quantity = $quantity;
	}

	/**
	 * 指定用户，默认为当前用户
	 * @since 2019.08.11
	 */
	public function set_user_id(int $user_id): WP_User{
		$user = get_user_by('ID', $user_id);
		if (!$user) {
			throw new Exception(__('用户ID无效', 'wnd'));
		}

		$this->user_id = $user_id;
		return $user;
	}

	/**
	 * 设定订单标题
	 * @since 2019.08.12
	 */
	public function set_subject(string $subject) {
		$this->subject = $subject;
	}

	/**
	 * 设置订单产品属性
	 * @since 0.8.76
	 */
	public function set_props(array $props) {
		$this->props = $props;
	}

	/**
	 * 创建交易
	 * - 写入数据库
	 * - 保存产品属性
	 * - 交易完成，执行相关操作
	 * @since 2019.02.11
	 *
	 * @param 	bool 	$is_completed 	是否直接完成订单
	 */
	public function create(bool $is_completed = false): WP_Post{
		// 写入数据
		$this->transaction = $this->insert_record($is_completed);

		// 保存产品属性
		if ($this->props and $this->get_object_id()) {
			Wnd_Order_Product::set_order_props($this->transaction->ID, $this->props);
		}

		// 完成
		if ($is_completed) {
			$this->complete();
		}

		// 返回创建的 WP Post Object
		return $this->transaction;
	}

	/**
	 * 创建 WP Post 记录，具体实现在子类中定义
	 * @since 2019.02.11
	 *
	 * @param  	bool  	$is_completed 	是否直接完成订单
	 * @return object WP Post Object
	 */
	abstract protected function insert_record(bool $is_completed): WP_Post;

	/**
	 * 通常校验用于需要跳转第三方支付平台的交易
	 * - 校验交易是否完成
	 * - 交易完成，执行相关操作
	 * @since 2019.02.11
	 *
	 * @return int 验证成功后返回 Transaction ID。其他情况抛出异常。
	 */
	public function verify() {
		$this->verify_transaction();

		return $this->complete();
	}

	/**
	 * 校验：具体实现在子类中定义
	 * 通常校验用于需要跳转第三方支付平台的交易
	 * @since 2019.02.11
	 */
	abstract protected function verify_transaction(): bool;

	/**
	 * 交易完成，执行相关操作。具体方法在子类中实现
	 * @since 2020.06.10
	 */
	abstract protected function complete(): int;

	/**
	 * 获取WordPress order/recharge post ID
	 */
	public function get_transaction_id() {
		return $this->transaction->ID;
	}

	/**
	 * 获取支付订单标题
	 */
	public function get_subject() {
		return $this->transaction->post_title;
	}

	/**
	 * 获取关联产品/服务Post ID
	 * @since 2019.08.12
	 */
	public function get_object_id() {
		return $this->transaction->post_parent;
	}

	/**
	 * 获取消费金额
	 * @since 2019.08.12
	 */
	public function get_total_amount(): float {
		return number_format(floatval($this->transaction->post_content), 2, '.', '');
	}

	/**
	 * 获取用户ID
	 * @since 2020.06.20
	 */
	public function get_user_id() {
		return $this->transaction->post_author;
	}

	/**
	 * 获取交易记录状态
	 * @since 2020.06.21
	 */
	public function get_status() {
		return $this->transaction->post_status;
	}

	/**
	 * 获取交易记录类型
	 * @since 2020.06.20
	 */
	public function get_type() {
		return $this->transaction->post_type;
	}

	/**
	 * 根据 id 获取交易 Type
	 * @since 0.9.32
	 */
	private static function get_type_by_transaction_id(int $transaction_id): string {
		return get_post($transaction_id)->post_type ?? '';
	}
}
