<?php
namespace Wnd\Model;

use Exception;
use WP_Post;
use WP_User;

/**
 *@since 2019.09.24
 *定义站内订单、充值、支付公共部分的抽象类
 *
 */
abstract class Wnd_Transaction {

	// order / recharge Post ID
	protected $ID;

	// order / recharge WP Post object
	protected $post;

	// 站点用户ID
	protected $user_id;

	// 产品ID 对应WordPress产品类型Post ID
	protected $object_id;

	// 金额
	protected $total_amount;

	// 支付标题：产品标题 / 充值标题 / 其他自定义
	protected $subject;

	// 状态
	protected $status;

	// 类型
	protected $type;

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
	public function set_ID(int $ID): WP_Post{
		$this->ID   = $ID;
		$this->post = get_post($ID);
		if (!$ID or !$this->post) {
			throw new Exception(__('交易ID无效', 'wnd'));
		}

		return $this->post;
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
	 *@since 2019.02.11
	 *创建：具体实现在子类中定义
	 *@return object WP Post Object
	 */
	abstract public function create(): WP_Post;

	/**
	 *@since 2019.02.11
	 *校验：具体实现在子类中定义
	 *通常校验用于需要跳转第三方支付平台的交易
	 *@param $payment_gateway（记录支付平台如：alipay、wepay）
	 */
	abstract public function verify();

	/**
	 *订单成功后，执行的统一操作
	 *@since 2020.06.10
	 *
	 */
	abstract protected function complete(): int;

	/**
	 *获取WordPress order/recharge post ID
	 */
	public function get_ID() {
		return $this->post->ID;
	}

	/**
	 *获取支付订单标题
	 */
	public function get_subject() {
		return $this->post->post_title;
	}

	/**
	 *@since 2019.08.12
	 *获取关联产品/服务Post ID
	 **/
	public function get_object_id() {
		return $this->post->post_parent;
	}

	/**
	 *@since 2019.08.12
	 *获取消费金额
	 **/
	public function get_total_amount(): float {
		return number_format($this->post->post_content, 2, '.', '');
	}

	/**
	 *@since 2020.06.20
	 *获取用户ID
	 *
	 */
	public function get_user_id() {
		return $this->post->post_author;
	}

	/**
	 *@since 2020.06.21
	 *获取交易记录状态
	 *
	 */
	public function get_status() {
		return $this->post->post_status;
	}

	/**
	 *@since 2020.06.20
	 *获取交易记录类型
	 *
	 */
	public function get_type() {
		return $this->post->post_type;
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
}
