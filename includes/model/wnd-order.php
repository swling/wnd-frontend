<?php
namespace Wnd\Model;

use Exception;
use Wnd\Getway\Wnd_Payment_Getway;
use Wnd\Model\Wnd_Order_Product;
use Wnd\Model\Wnd_Product;
use Wnd\Model\Wnd_SKU;

/**
 * 订单模块
 * @since 2019.08.11
 */
class Wnd_Order extends Wnd_Transaction {

	protected $transaction_type = 'order';

	// 用户同条件历史订单复用时间限制
	protected $date_query = [];

	// SKU ID
	protected $sku_id;

	// 定义匿名支付cookie名称
	protected static $anon_cookie_name_prefix = 'anon_order';

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
	 *     'transaction_slug'    => $this->transaction_slug ?: uniqid(),
	 * ];
	 *
	 * @since 0.9.32
	 */
	protected function generate_transaction_data(bool $is_completed) {
		// 处理匿名订单属性
		if (!$this->user_id) {
			if (!wnd_get_config('enable_anon_order')) {
				throw new Exception(__('请登录', 'wnd'));
			}

			$this->handle_anon_order_props();
		}

		/**
		 * 处理订单 SKU 属性
		 * @since 0.8.76
		 */
		$this->handle_order_sku_props();

		/**
		 * 订单状态及标题
		 */
		$this->subject = $this->subject ?: (__('订单：', 'wnd') . get_the_title($this->object_id) . '[' . $this->quantity . ']');
		$this->status  = $is_completed ? static::$completed_status : static::$processing_status;

		/**
		 * @since 2019.03.31 查询符合当前条件，但尚未完成的付款订单
		 */
		$old_orders = get_posts(
			[
				'author'         => $this->user_id,
				'post_parent'    => $this->object_id,
				'post_status'    => static::$processing_status,
				'post_type'      => $this->transaction_type,
				'posts_per_page' => 1,
				'date_query'     => $this->date_query,
			]
		);

		if ($old_orders) {
			$ID = $old_orders[0]->ID;
		} elseif ($this->object_id) {
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
	}

	/**
	 * 匿名支付订单cookie name
	 */
	public static function get_anon_cookie_name(int $object_id) {
		return static::$anon_cookie_name_prefix . '_' . $object_id;
	}

	/**
	 * 创建匿名支付随机码
	 */
	protected function generate_anon_cookie() {
		return md5(uniqid($this->object_id));
	}

	/**
	 * 构建匿名订单所需的订单属性：$this->transaction_slug、$this->date_query
	 * - 设置匿名订单 cookie
	 * - 将匿名订单 cookie 设置为订单 post name
	 * - 设置订单复用日期条件
	 * @since 0.9.2
	 */
	protected function handle_anon_order_props() {
		/**
		 * 设置 Cookie
		 */
		$anon_cookie = $this->generate_anon_cookie();
		setcookie(static::get_anon_cookie_name($this->object_id), $anon_cookie, time() + 3600 * 24, '/');

		/**
		 * 将 cookie 设置为订单 post name，以便后续通过 cookie 查询匿名用户订单
		 */
		$this->transaction_slug = $anon_cookie;

		/**
		 * 匿名订单用户均为0，不可短时间内复用订单记录，或者会造成订单冲突
		 * 更新自动草稿时候，modified 不会变需要查询 post_date
		 * @see get_posts()
		 * @see wp_update_post
		 */
		$this->date_query = [
			[
				'column' => 'post_date',
				'before' => date('Y-m-d H:i', current_time('timestamp') - 86400),
			],
		];
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
	 * 订单成功后，执行的统一操作
	 * @since 2020.06.10
	 *
	 * @param $this->transaction
	 * @param object               	$this->transaction		required 	订单记录Post
	 */
	protected function complete(): int{
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
		if (!Wnd_Payment_Getway::get_payment_gateway($ID)) {
			wnd_inc_user_money($user_id, $total_amount * -1, false);
		}

		/**
		 * 产品订单：更新总销售额、设置原作者佣金
		 * @since 2019.06.04
		 */
		if ($object_id) {
			wnd_inc_post_total_sales($object_id, $total_amount);

			// 删除对象缓存
			wp_cache_delete($this->user_id . '-' . $this->object_id, 'wnd_has_paid');

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

		/**
		 * 充值完成
		 * @since 2019.08.12
		 */
		do_action('wnd_order_completed', $ID);

		return $ID;
	}
}
