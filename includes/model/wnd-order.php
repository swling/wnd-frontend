<?php
namespace Wnd\Model;

use Exception;
use Wnd\Getway\Wnd_Payment_Getway;
use Wnd\Model\Wnd_Finance;
use Wnd\Model\Wnd_Order_Props;
use Wnd\Model\Wnd_Product;
use Wnd\Model\Wnd_SKU;
use WP_Post;

/**
 * 订单模块
 * @since 2019.08.11
 */
class Wnd_Order extends Wnd_Transaction {

	protected $transaction_type = 'order';

	// SKU ID
	private $sku_id;

	// 是否为全新订单（用户创建而尚未支付的订单，再次调用支付时，为复用订单，非全新订单）
	private $is_new_order;

	/**
	 * 此方法用于补充、修改、核查外部通过方法设定的交易数据，组成最终写入数据库的数据。完整的交易记录构造如下所示：
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
	 *     'transaction_slug'    => $this->transaction_slug ?: uniqid(),
	 * ];
	 *
	 * @since 0.9.32
	 */
	protected function generate_transaction_data() {
		if (!$this->object_id) {
			if (!$this->total_amount) {
				throw new Exception('When Object ID is not set, Total amount must be set');
			}
			if (!$this->subject) {
				throw new Exception('When Object ID is not set, Subject must be set');
			}
		}

		/**
		 * 处理订单 SKU 属性
		 * @since 0.8.76
		 */
		$this->sku_id       = $this->props[Wnd_Order_Props::$sku_id_key] ?? '';
		$this->total_amount = $this->calculate_total_amount();

		// 解析订单产品属性
		if ($this->props) {
			$this->props = Wnd_Order_Props::parse_order_props($this->object_id, $this->props);
		}

		/**
		 * 订单标题
		 */
		$this->subject = $this->subject ?: (__('订单：', 'wnd') . get_the_title($this->object_id) . '[' . $this->quantity . ']');

		/**
		 * 若当前订单为：未完成付款的订单再次调用，则不是新订单
		 */
		$this->is_new_order = !$this->transaction_id;
	}

	/**
	 * 计算本次订单总金额
	 * @since 0.9.52
	 */
	private function calculate_total_amount(): float {
		if (!$this->object_id) {
			return $this->total_amount;
		}

		$object_sku = Wnd_SKU::get_object_sku($this->object_id);

		if ($object_sku) {
			if (!$this->sku_id or !in_array($this->sku_id, array_keys($object_sku))) {
				throw new Exception(__('SKU ID 无效', 'wnd'));
			}

			$price = Wnd_SKU::get_single_sku_price($this->object_id, $this->sku_id);
		} else {
			$price = wnd_get_post_price($this->object_id);
		}

		return (float) $price * $this->quantity;
	}

	/**
	 * - 调用父类方法创建交易
	 * - 更新订单及库存统计
	 * @since 0.9.32
	 */
	public function create(bool $is_completed = false): WP_Post{
		// 调用父类方法，写入数据库
		$transaction = parent::create($is_completed);

		/**
		 * 全新订单：
		 * - 更新订单统计
		 * - 更新库存统计
		 */
		if ($this->is_new_order) {
			/**
			 * 新增订单统计
			 * 插入订单时，无论订单状态均新增订单统计，以实现某些场景下需要限定订单总数时，锁定数据，预留支付时间
			 * 获取订单统计时，删除超时未完成的订单，并减去对应订单统计 @see Wnd_Product::get_order_count($object_id)
			 * @since 2019.06.04
			 */
			Wnd_Product::inc_order_count($this->object_id, 1);

			/**
			 * 扣除库存
			 * 插入订单时，无论订单状态均新更新库存统计，以实现锁定数据，预留支付时间
			 * 获取库存时，会清空超时未支付的订单 @see Wnd_Product::get_object_props($object_id);
			 * @since 0.9.0
			 */
			Wnd_SKU::reduce_single_sku_stock($this->object_id, $this->sku_id, $this->quantity);
		}

		return $transaction;
	}

	/**
	 * 订单成功后，执行的统一操作
	 * @since 2020.06.10
	 *
	 * @param $this->transaction
	 * @param object               	$this->transaction		required 	订单记录Post
	 */
	protected function complete_transaction(): int{
		/**
		 * 本方法可能在站内直接支付，或者站外验证支付中调用。
		 * 在线订单校验时，由支付平台发起请求，仅指定订单ID，需根据订单ID设置对应变量。
		 * 故不可直接读取相关属性
		 */
		$ID           = $this->get_transaction_id();
		$user_id      = $this->get_user_id();
		$total_amount = $this->get_total_amount();
		$object_id    = $this->get_object_id();

		/**
		 * 产品订单：更新总销售额、设置原作者佣金
		 * @since 2019.06.04
		 */
		if ($object_id) {
			wnd_inc_post_total_sales($object_id, $total_amount);

			// 设置对象缓存：不能将布尔值直接做为缓存结果，会导致无法判断是否具有缓存，转为整型 0/1
			Wnd_Finance::set_user_paid_cache($user_id, $object_id, 1);

			// 文章作者新增佣金
			$commission = (float) wnd_get_order_commission($ID);
			if ($commission > 0) {
				$object   = get_post($object_id);
				$recharge = new Wnd_Recharge();
				$recharge->set_object_id($object->ID); // 设置佣金来源
				$recharge->set_user_id($object->post_author);
				$recharge->set_total_amount($commission);
				$recharge->create(true); // 直接写入余额
			}
		}

		// 写入消费记录（即使是匿名订单也需要此操作，否则不会更新整站消费统计）
		wnd_inc_user_expense($user_id, $total_amount);

		// 站内直接消费，无需支付平台支付校验，记录扣除账户余额、在线支付则不影响当前余额
		if (Wnd_Payment_Getway::is_internal_payment($ID)) {
			wnd_inc_user_balance($user_id, $total_amount * -1, false);
		}

		return $ID;
	}

	/**
	 * 查询订单是否已完成支付
	 * @since 0.9.32
	 *
	 * @return bool
	 */
	public static function has_paid(int $user_id, int $object_id): bool {
		return !empty(static::get_user_valid_orders($user_id, $object_id));
	}

	/**
	 * 获取指定用户在指定产品下的有效订单合集
	 * @since 0.9.57
	 *
	 * @return array 有效订单的合集 [order_post_object]
	 */
	public static function get_user_valid_orders(int $user_id, int $object_id): array{
		$args = [
			'posts_per_page' => 1,
			'post_type'      => 'order',
			'post_parent'    => $object_id,
			'author'         => $user_id,
			'post_status'    => [static::$completed_status, static::$processing_status],
		];

		return get_posts($args) ?: [];
	}
}
