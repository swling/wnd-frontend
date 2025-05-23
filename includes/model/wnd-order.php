<?php
namespace Wnd\Model;

use Exception;
use Wnd\Getway\Wnd_Payment_Getway;
use Wnd\Model\Wnd_Finance;
use Wnd\Model\Wnd_Order_Props;
use Wnd\Model\Wnd_Product;
use Wnd\Model\Wnd_SKU;

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

			$price = wnd_get_post_price($this->object_id, $this->sku_id);
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
	public function create(bool $is_completed = false): object {
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
	 * @since 0.9.87
	 * 定义支付成功后的订单状态
	 * 用于区分实体订单和虚拟订单：默认虚拟订单直接完成，实体订单在支付完成后，需要处理发货等流程
	 */
	protected function get_paid_status(): string {
		$props      = json_decode($this->transaction->props);
		$is_virtual = $props->is_virtual ?? '1';

		return '1' == $is_virtual ? static::$completed_status: static::$paid_status;
	}

	/**
	 * 订单成功后，执行的统一操作
	 * @since 2020.06.10
	 *
	 * @since 0.9.64
	 *  - 在线支付回调时会进入匿名状态，因此不允许在匿名子类中复写本方法
	 *  - 即：本方法也必须兼顾注册用户与匿名用户
	 *
	 * @param $this->transaction
	 * @param object               	$this->transaction		required 	订单记录Post
	 */
	final protected function complete_transaction(): int {
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

			// 文章作者新增佣金
			$commission = (float) wnd_get_order_commission($ID);
			if ($commission > 0) {
				$object   = get_post($object_id);
				$recharge = new Wnd_Recharge();
				$recharge->set_payment_gateway('internal');
				$recharge->set_object_id($object->ID); // 设置佣金来源
				$recharge->set_user_id($object->post_author);
				$recharge->set_total_amount($commission);
				$recharge->create(true); // 直接写入余额
			}
		}

		// 匿名订单：仅更新整站消费统计
		if (!$user_id) {
			Wnd_Finance::update_fin_stats($total_amount, 'expense');
			return $ID;
		}

		// 注册用户：站内订单：扣除余额、更新消费；站外订单：仅更新消费
		if (Wnd_Payment_Getway::is_internal_payment($ID)) {
			// 创建站内订单时理应检查余额，此处额外检查用于确保高并发订单或其他异常情况
			if ($total_amount > wnd_get_user_balance($user_id)) {
				$this->update_transaction_status(static::$pending_status);
				throw new Exception(__('余额不足', 'wnd'));
			}

			wnd_inc_user_balance($user_id, $total_amount * -1, false);
		} else {
			wnd_inc_user_expense($user_id, $total_amount);
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
		$order = static::query_db(['object_id' => $object_id, 'user_id' => $user_id, 'type' => 'order', 'status' => static::$completed_status]);
		return $order ? true : false;
	}

	/**
	 * 获取指定用户在指定产品下的有效订单合集
	 * @since 0.9.57
	 *
	 * @return array 有效订单的合集 [order_post_object]
	 */
	public static function get_user_valid_orders(int $user_id, int $object_id, int $limit = 1): array {
		$where = [
			'type'      => 'order',
			'object_id' => $object_id,
			'user_id'   => $user_id,
			'status'    => static::$completed_status,
		];

		return static::get_results($where, $limit) ?: [];
	}
}
