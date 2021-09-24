<?php
namespace Wnd\Model;

use Exception;
use Wnd\Getway\Wnd_Payment_Getway;
use Wnd\Model\Wnd_Finance;
use Wnd\Model\Wnd_Order_Product;
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
	protected $sku_id;

	// 是否为全新订单（用户创建而尚未支付的订单，再次调用支付时，为复用订单，非全新订单）
	protected $is_new_order;

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
		/**
		 * 处理订单 SKU 属性
		 * @since 0.8.76
		 */
		$this->handle_order_sku_props();

		/**
		 * 订单标题
		 */
		$this->subject = $this->subject ?: (__('订单：', 'wnd') . get_the_title($this->object_id) . '[' . $this->quantity . ']');

		/**
		 * @since 2019.03.31 查询符合当前条件，但尚未完成的付款订单
		 */
		$this->transaction_id = $this->get_reusable_transaction_id();

		/**
		 * 若当前订单为：未完成付款的订单再次调用，则不是新订单
		 */
		$this->is_new_order = !$this->transaction_id;
	}

	/**
	 * 根据 SKU 变量定义本次订单属性：$this->sku_id、$this->total_amount
	 * @since 0.8.76
	 */
	protected function handle_order_sku_props() {
		$this->sku_id = $this->props[Wnd_Order_Product::$sku_id_key] ?? '';
		$object_sku   = Wnd_SKU::get_object_sku($this->object_id);

		if ($object_sku) {
			if (!$this->sku_id or !in_array($this->sku_id, array_keys($object_sku))) {
				throw new Exception(__('SKU ID 无效', 'wnd'));
			}

			$price = Wnd_SKU::get_single_sku_price($this->object_id, $this->sku_id);
		} else {
			$price = wnd_get_post_price($this->object_id);
		}

		$this->total_amount = $price * $this->quantity;
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
		if ($this->is_new_order and $this->object_id) {
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

		// 写入消费记录
		wnd_inc_user_expense($user_id, $total_amount);

		// 站内直接消费，无需支付平台支付校验，记录扣除账户余额、在线支付则不影响当前余额
		if (Wnd_Payment_Getway::is_internal_payment($ID)) {
			wnd_inc_user_money($user_id, $total_amount * -1, false);
		}

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

		return $ID;
	}

	/**
	 * 查询订单是否已完成支付
	 * @since 0.9.32
	 *
	 * @return bool
	 */
	public static function has_paid(int $user_id, int $object_id): bool{
		$args = [
			'posts_per_page' => 1,
			'post_type'      => 'order',
			'post_parent'    => $object_id,
			'author'         => $user_id,
			'post_status'    => [static::$completed_status, static::$pending_status],
		];

		return !empty(get_posts($args));
	}
}
