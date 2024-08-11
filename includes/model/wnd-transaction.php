<?php
namespace Wnd\Model;

use Exception;
use Wnd\WPDB\Wnd_Transaction_DB;
use WP_User;

/**
 * 定义订单站内数据库记录的抽象基类
 * 对应 WP Post 数据参见方法：insert_transaction();
 *
 * @since 2019.09.24
 */
abstract class Wnd_Transaction {

	protected $db_handler;

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

	// 等待付款（订单已创建，库存已扣除）
	public static $pending_status = 'pending';

	// 付款完成、交易等待（实体商品交易中：待收货 / 待确认）
	public static $processing_status = 'processing';

	// 交易完成
	public static $completed_status = 'completed';

	// 交易退款
	public static $refunded_status = 'refunded';

	// 交易取消（交易未完成：取消订单 \ 无效订单）
	public static $cancelled_status = 'cancelled';

	// 交易关闭（交易完成后：因某种原因关闭）
	public static $closed_status = 'closed';

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
		$this->user_id    = get_current_user_id();
		$this->db_handler = Wnd_Transaction_DB::get_instance();
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
				$instance = $user_id ? new Wnd_Order() : new Wnd_Order_Anonymous();
				break;

			case 'recharge':
				$instance = $user_id ? new Wnd_Recharge() : new Wnd_Recharge_Anonymous();
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
	public function set_transaction_id(int $ID): object {
		$this->transaction_id = $ID;
		$this->transaction    = $this->db_handler->get($ID);
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
	public function set_user_id(int $user_id): WP_User {
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
	 * - 统一 Transaction 数据
	 * - 调用子类 generate_transaction_data() 方法，对交易数据做最后处理
	 * - 写入数据库
	 * - 交易完成，执行相关操作
	 * @since 2019.02.11
	 *
	 * @param 	bool 	$is_completed 	是否直接完成订单
	 */
	public function create(bool $is_completed = false): object {
		/**
		 * 检测创建权限
		 * @since 0.9.51
		 */
		$this->check_create();

		/**
		 * 全局统一 Transaction 数据设定：
		 * - @since 0.9.37 设定状态
		 */
		$this->status = $is_completed ? (static::$completed_status) : (static::$pending_status);

		// 写入数据
		$this->generate_transaction_data();
		$this->insert_transaction();

		// 完成
		if ($is_completed) {
			$this->complete();
		}

		// 返回创建的 WP Post Object
		return $this->transaction;
	}

	/**
	 * 检测创建权限
	 * - 默认必须登录
	 * - 如需不同权限，请在子类中复写本方法
	 * @since 0.9.51
	 */
	protected function check_create() {
		if (!$this->user_id) {
			throw new Exception(__('请登录', 'wnd'));
		}
	}

	/**
	 * 此方法用于补充、修改、核查外部通过方法设定的交易数据，组成最终写入数据库的数据。完整的交易记录构造如下所示：
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
	abstract protected function generate_transaction_data();

	/**
	 * 写入数据库
	 * - 构建 $this->transaction
	 * - 构建 $this->transaction_id
	 *
	 * @since 0.9.32
	 */
	private function insert_transaction(): object {
		if (!$this->transaction_type) {
			throw new Exception('Invalid transaction type');
		}

		$post_arr = [
			'ID'              => $this->transaction_id,
			'type'            => $this->transaction_type,
			'user_id'         => $this->user_id,
			'object_id'       => $this->object_id,
			'total_amount'    => $this->total_amount,
			'payment_gateway' => $this->payment_gateway,
			'status'          => $this->status,
			'subject'         => $this->subject,
			'slug'            => $this->transaction_slug ?: uniqid(),
			'time'            => time(),
			'props'           => json_encode($this->props, JSON_UNESCAPED_UNICODE),
		];
		$ID = $this->db_handler->insert($post_arr);
		if (!$ID) {
			global $wpdb;
			throw new Exception('Failed to write to the database : ' . $wpdb->last_error);
		}

		/**
		 * - 构建 $this->transaction
		 * - 构建 $this->transaction_id
		 * @since 0.9.37.2
		 */
		$this->set_transaction_id($ID);

		/**
		 * 设置订单属性
		 * - 设置自定义 meta （含 wp_meta 及 wnd_meta）
		 * - 设置 Terms
		 * - 前端在交易创建时，按常规 Post 设定请求数据，即可设置对应对应属性
		 * @since 0.9.52
		 */
		// Wnd_Post::set_meta_and_terms($this->transaction_id, $this->props);

		return $this->transaction;
	}

	/**
	 * 通常校验用于需要跳转第三方支付平台的交易
	 * - 已经完成的订单：终止操作
	 * - 其他不合法状态：抛出异常
	 * @since 2019.02.11
	 */
	public function verify() {
		// 订单支付状态检查：已完成、已关闭、已退款的订单，终止
		$status = $this->get_status();
		if (static::$completed_status == $status or static::$closed_status == $status or static::$refunded_status == $status) {
			return;
		}

		if (static::$pending_status != $status) {
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
	private function update_transaction_status(string $status): bool {
		$data = [
			'status'  => $status,
			'subject' => $this->subject ?: $this->get_subject(),
		];
		$ID = $this->db_handler->update($data, ['ID' => $this->get_transaction_id()]);
		if (!$ID) {
			throw new Exception(__('数据更新失败', 'wnd'));
		}

		return true;
	}

	/**
	 * 交易完成
	 *
	 *  ## 属性 $this->transaction_id、$this->transaction 赋值：
	 * - 站内直接完成：$this->insert_transaction()
	 * - 在线交易验证：static::get_instance($type = '', $transaction_id)
	 * - @see $this->set_transaction_id();
	 *
	 * @since 0.9.37 新增统一钩子 'wnd_transaction_completed'
	 */
	private function complete() {
		$this->complete_transaction();

		// 统一的交易钩子
		do_action('wnd_transaction_completed', $this->transaction_id, $this->transaction_type, $this->transaction);

		// 按类型区分的交易钩子
		do_action('wnd_' . $this->transaction_type . '_completed', $this->transaction_id, $this->transaction);
	}

	/**
	 * 交易完成，执行相关操作。具体方法在子类中实现
	 * @since 2020.06.10
	 */
	abstract protected function complete_transaction(): int;

	/**
	 * 关闭交易（交易完成后）
	 * @since 0.9.57
	 */
	public function close() {
		$this->update_transaction_status(static::$closed_status);
	}

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
		return $this->transaction->subject;
	}

	/**
	 * 获取关联产品/服务Post ID
	 * @since 2019.08.12
	 */
	public function get_object_id() {
		return $this->transaction->object_id;
	}

	/**
	 * 获取消费金额
	 * @since 2019.08.12
	 */
	public function get_total_amount(): float {
		return number_format(floatval($this->transaction->total_amount), 2, '.', '');
	}

	/**
	 * 获取创建时间戳
	 * @since 0.9.57.9
	 */
	public function get_timestamp(): int {
		return $this->transaction->time ?: 0;
	}

	/**
	 * 获取用户ID
	 * @since 2020.06.20
	 */
	public function get_user_id() {
		return $this->transaction->user_id;
	}

	/**
	 * 获取交易记录状态
	 * @since 2020.06.21
	 */
	public function get_status() {
		return $this->transaction->status;
	}

	/**
	 * 获取交易记录类型
	 * @since 2020.06.20
	 */
	public function get_type() {
		return $this->transaction->type;
	}

	/**
	 * 获取第三方支付接口标识
	 * @since 2023.10.30
	 */
	public function get_payment_gateway() {
		return $this->transaction->payment_gateway;
	}

	/**
	 * 根据 id 获取交易 Type
	 * @since 0.9.32
	 */
	private static function get_type_by_transaction_id(int $transaction_id): string {
		$transaction = static::query_db(['ID' => $transaction_id]);
		if (!$transaction) {
			throw new Exception(__('订单ID无效', 'wnd') . ' : ' . $transaction_id);
		}

		return $transaction->type ?? '';
	}

	public static function query_db(array $where) {
		$db_handler = Wnd_Transaction_DB::get_instance();
		return $db_handler->query($where);
	}

	public static function get_results(array $where, int $limit = 0) {
		$db_handler = Wnd_Transaction_DB::get_instance();
		return $db_handler->get_results($where, $limit);
	}

	public static function get(int $ID) {
		$db_handler = Wnd_Transaction_DB::get_instance();
		return $db_handler->get($ID);
	}

	public static function delete(int $ID) {
		$db_handler = Wnd_Transaction_DB::get_instance();
		return $db_handler->delete($ID);
	}

}
