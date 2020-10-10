<?php
namespace Wnd\Model;

use Exception;
use Wnd\Model\Wnd_Product;
use WP_Post;
use WP_User;

/**
 *@since 2019.09.24
 *定义站内订单、充值、支付公共部分的抽象类
 *
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
	protected $subject;

	// 交易订单对应的产品或服务属性
	protected $props;

	// 状态
	protected $status;

	// 类型
	protected $type;

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
	 *第三方支付接口
	 *
	 *@since 2020.06.21
	 *支付接口保存在 post_excerpt 如果此处不做默认定义，即为NLL，会报错提示：post_excerpt 不能为 null
	 */
	protected $payment_gateway = '';

	/**
	 *@since 2019.08.11
	 *构造函数
	 */
	public function __construct() {
		$this->user_id = get_current_user_id();
	}

	/**
	 *@since 2019.08.12
	 *指定Post ID (order/recharge/payment)
	 *
	 *@return object 	WP Post Object
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
	 *@since 2020.06.19
	 *设置支付网关如（支付宝，微信支付等）
	 */
	public function set_payment_gateway($payment_gateway) {
		$this->payment_gateway = $payment_gateway;
	}

	/**
	 *@since 2019.08.12
	 *设定金额
	 **/
	public function set_total_amount(float $total_amount) {
		if (!is_numeric($total_amount)) {
			throw new Exception(__('金额无效', 'wnd'));
		}

		$this->total_amount = $total_amount;
	}

	/**
	 *@since 2019.08.11
	 *指定产品ID
	 **/
	public function set_object_id(int $object_id) {
		$post = $object_id ? get_post($object_id) : false;
		if ($object_id and !$post) {
			throw new Exception(__('商品ID无效', 'wnd'));
		}

		$this->object_id = $object_id;
	}

	/**
	 *@since 2019.08.11
	 *指定用户，默认为当前用户
	 **/
	public function set_user_id(int $user_id): WP_User{
		$user = get_user_by('ID', $user_id);
		if (!$user) {
			throw new Exception(__('用户ID无效', 'wnd'));
		}

		$this->user_id = $user_id;
		return $user;
	}

	/**
	 *@since 2019.08.12
	 *设定订单标题
	 **/
	public function set_subject(string $subject) {
		$this->subject = $subject;
	}

	/**
	 *@since 0.8.76
	 *
	 *设置订单产品属性
	 */
	public function set_props(array $props) {
		$this->props = $props;
	}

	/**
	 *@since 2019.02.11
	 *创建交易
	 * - 写入数据库
	 * - 保存产品属性
	 * - 交易完成，执行相关操作
	 *
	 *@param 	bool 	$is_completed 	是否直接完成订单
	 */
	public function create(bool $is_completed = false): WP_Post{
		// 写入数据
		$this->transaction = $this->insert_record($is_completed);

		// 保存产品属性
		if ($this->props and $this->transaction->ID) {
			Wnd_Product::set_order_props($this->transaction->ID, $this->props);
		}

		// 完成
		if ($is_completed) {
			$this->complete();
		}

		// 返回创建的 WP Post Object
		return $this->transaction;
	}

	/**
	 *@since 2019.02.11
	 *创建 WP Post 记录，具体实现在子类中定义
	 *
	 *@param 	bool 	$is_completed 	是否直接完成订单
	 *
	 *@return object WP Post Object
	 */
	abstract protected function insert_record(bool $is_completed): WP_Post;

	/**
	 *@since 2019.02.11
	 *通常校验用于需要跳转第三方支付平台的交易
	 *
	 * - 校验交易是否完成
	 * - 交易完成，执行相关操作
	 */
	public function verify() {
		$this->verify_transaction();

		$this->complete();
	}

	/**
	 *@since 2019.02.11
	 *校验：具体实现在子类中定义
	 *通常校验用于需要跳转第三方支付平台的交易
	 */
	abstract protected function verify_transaction();

	/**
	 *@since 2020.06.10
	 *
	 *交易完成，执行相关操作。具体方法在子类中实现
	 */
	abstract protected function complete(): int;

	/**
	 *获取WordPress order/recharge post ID
	 */
	public function get_transaction_id() {
		return $this->transaction->ID;
	}

	/**
	 *获取支付订单标题
	 */
	public function get_subject() {
		return $this->transaction->post_title;
	}

	/**
	 *@since 2019.08.12
	 *获取关联产品/服务Post ID
	 **/
	public function get_object_id() {
		return $this->transaction->post_parent;
	}

	/**
	 *@since 2019.08.12
	 *获取消费金额
	 **/
	public function get_total_amount(): float {
		return number_format(floatval($this->transaction->post_content), 2, '.', '');
	}

	/**
	 *@since 2020.06.20
	 *获取用户ID
	 *
	 */
	public function get_user_id() {
		return $this->transaction->post_author;
	}

	/**
	 *@since 2020.06.21
	 *获取交易记录状态
	 *
	 */
	public function get_status() {
		return $this->transaction->post_status;
	}

	/**
	 *@since 2020.06.20
	 *获取交易记录类型
	 *
	 */
	public function get_type() {
		return $this->transaction->post_type;
	}

	/**
	 *根据支付订单ID获取第三方支付平台接口标识
	 *
	 */
	public static function get_payment_gateway(int $payment_id): string{
		$payment = $payment_id ? get_post($payment_id) : false;
		if (!$payment) {
			return '';
		}

		return $payment->post_excerpt;
	}

	/**
	 *构建支付接口名称及标识
	 *
	 */
	public static function get_gateway_options(): array{
		$gateway_data = [
			__('支付宝', 'wnd') => wnd_get_config('alipay_qrcode') ? 'Alipay_QRCode' : 'Alipay',
		];

		return apply_filters('wnd_payment_gateway_options', $gateway_data);
	}

	/**
	 *默认支付网关
	 *
	 */
	public static function get_default_gateway(): string{
		$default_gateway = wnd_get_config('alipay_qrcode') ? 'Alipay_QRCode' : 'Alipay';
		return apply_filters('wnd_default_payment_gateway', $default_gateway);
	}

	/**
	 *用户充值金额选项
	 *@since 0.8.62
	 */
	public static function get_recharge_amount_options(): array{
		$defaults = ['0.01' => '0.01', '10.00' => '10.00', '50.00' => '50.00', '100.00' => '100.00', '500.00' => '500.00'];
		return apply_filters('wnd_recharge_amount_options', $defaults);
	}
}
