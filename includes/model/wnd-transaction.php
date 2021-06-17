<?php
namespace Wnd\Model;

use Exception;
use Wnd\Model\Wnd_Order_Product;
use WP_Post;
use WP_User;

/**
 * 定义订单站内数据库记录的抽象基类
 * 对应 WP Post 数据参见方法：insert_transaction();
 *
 * @since 2019.09.24
 */
abstract class Wnd_Transaction {

	// WP Post object
	protected $transaction;

	// WP Post ID
	protected $transaction_id = 0;

	// WP Post Type
	protected $transaction_type;

	// WP Post Name
	protected $transaction_slug;

	// 站点用户ID
	protected $user_id = 0;

	// 产品ID 对应WordPress产品类型Post ID
	protected $object_id = 0;

	// 金额
	protected $total_amount = 0.00;

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

		// 根据类型选择对应处理类
		$user_id = get_current_user_id();
		switch ($type) {
			case 'order':
				if (!$user_id and !wnd_get_config('enable_anon_order')) {
					throw new Exception(__('请登录', 'wnd'));
				}
				$instance = $user_id ? new Wnd_Order() : new Wnd_Order_Anonymous();
				break;

			case 'recharge':
				$instance = new Wnd_Recharge();
				break;

			default:
				$instance = false;
				break;
		}

		$instance = apply_filters('wnd_transaction_instance', $instance, $type);
		if (!$instance) {
			throw new Exception(__('无效的 Transaction 实例类型：', 'wnd') . $type);
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
		$this->generate_transaction_data($is_completed);
		$this->insert_transaction();

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
	 * 按需对如下数据进行构造：
	 *
	 * $post_arr = [
	 *     'ID'           => $this->transaction_id,
	 *     'post_type'    => $this->transaction_type,
	 *     'post_author'  => $this->user_id,
	 *     'post_parent'  => $this->object_id,
	 *     'post_content' => $this->total_amount,
	 *     'post_excerpt' => $this->payment_gateway,
	 *     'post_status'  => $this->status,
	 *     'post_title'   => $this->subject,
	 *     'post_name'    => $this->transaction_slug ?: uniqid(),
	 * ];
	 *
	 * @since 0.9.32
	 */
	abstract protected function generate_transaction_data(bool $is_completed);

	/**
	 * 写入数据库
	 * @since 0.9.32
	 */
	protected function insert_transaction(): WP_Post {
		if (!$this->transaction_type) {
			throw new Exception('Invalid transaction type');
		}

		$post_arr = [
			'ID'           => $this->transaction_id,
			'post_type'    => $this->transaction_type,
			'post_author'  => $this->user_id,
			'post_parent'  => $this->object_id,
			'post_content' => $this->total_amount,
			'post_excerpt' => $this->payment_gateway,
			'post_status'  => $this->status,
			'post_title'   => $this->subject,
			'post_name'    => $this->transaction_slug ?: uniqid(),
		];
		$ID = wp_insert_post($post_arr);
		if (is_wp_error($ID) or !$ID) {
			throw new Exception('Failed to write to the database');
		}

		// 构建Post
		$this->transaction = get_post($ID);
		return $this->transaction;
	}

	/**
	 * 通常校验用于需要跳转第三方支付平台的交易
	 * - 已经完成的订单：中止操作
	 * - 其他不合法状态：抛出异常
	 * @since 2019.02.11
	 */
	public function verify() {
		// 订单支付状态检查
		$status = $this->get_status();
		if (static::$completed_status == $status) {
			return;
		}
		if (static::$processing_status != $status) {
			throw new Exception(__('订单状态无效', 'wnd'));
		}

		$this->update_transaction_status(static::$completed_status);
		$this->complete();
	}

	/**
	 * 更新 Transaction 状态
	 * @since 0.9.32
	 *
	 * @return true
	 */
	protected function update_transaction_status(string $status): bool{
		$post_arr = [
			'ID'          => $this->get_transaction_id(),
			'post_status' => $status,
			'post_title'  => $this->subject ?: $this->get_subject(),
		];
		$ID = wp_update_post($post_arr);
		if (!$ID or is_wp_error($ID)) {
			throw new Exception(__('数据更新失败', 'wnd'));
		}

		return true;
	}

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
	private static function get_type_by_transaction_id(int $transaction_id): string{
		$post = get_post($transaction_id);
		if (!$post) {
			throw new Exception(__('订单ID无效', 'wnd'));
		}

		return get_post($transaction_id)->post_type ?? '';
	}

	/**
	 * 同一用户同等条件下，未完成订单复用时间限制(秒)
	 * @since 0.9.32
	 */
	protected function get_reusable_transaction_id(): int{
		/**
		 * 匿名订单用户均为0，不可短时间内复用订单记录，或者会造成订单冲突
		 * 更新自动草稿时候，modified 不会变需要查询 post_date
		 * @see get_posts()
		 * @see wp_update_post
		 */
		$date_query = [
			[
				'column' => 'post_date',
				'before' => date('Y-m-d H:i', current_time('timestamp') - 86400),
			],
		];

		/**
		 * @since 2019.03.31 查询符合当前条件，但尚未完成的付款订单
		 */
		$reusable_posts = get_posts(
			[
				'author'         => $this->user_id,
				'post_parent'    => $this->object_id,
				'post_status'    => static::$processing_status,
				'post_type'      => $this->transaction_type,
				'posts_per_page' => 1,
				'date_query'     => $this->user_id ? [] : $date_query,
			]
		);

		$transaction_id = $reusable_posts[0]->ID ?? 0;
		return $transaction_id;
	}
}
