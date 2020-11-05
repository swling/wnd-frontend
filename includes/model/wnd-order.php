<?php
namespace Wnd\Model;

use Exception;
use Wnd\Model\Wnd_Payment_Getway;
use Wnd\Model\Wnd_Product;
use WP_Post;

/**
 *@since 2019.08.11
 *订单模块
 *
 *	# 自定义文章类型
 *	post_type属性('public' => false)，因此在WordPress后台无法查看到
 *	订单：order
 *
 *	# 消费post data
 *	金额：		post_content
 *	关联：		post_parent
 *	标题：		post_title
 *	类型：		post_type：order
 * 	匿名cookie：post_name
 *	接口：		post_excerpt：（支付平台标识如：Alipay / Wepay）
 *
 */
class Wnd_Order extends Wnd_Transaction {

	// 用户同条件历史订单复用时间限制
	protected $date_query = [];

	// 订单记录 post name
	protected $post_name;

	// SKU ID
	protected $sku_id;

	// 定义匿名支付cookie名称
	protected static $anon_cookie_name_prefix = 'anon_order';

	/**
	 *@since 2019.08.11
	 *构造函数
	 */
	public function __construct() {
		parent::__construct();

		if (!$this->user_id and !wnd_get_config('enable_anon_order')) {
			throw new Exception(__('请登录', 'wnd'));
		}
	}

	/**
	 *@since 2019.02.11
	 *用户本站消费数据(含余额消费，或直接第三方支付消费)
	 *
	 *@param int 		$this->user_id  		required
	 *@param int 		$this->object_id  		option
	 *@param string 	$this->subject 			option
	 *@param string 	$this->payment_gateway	option 	支付平台标识
	 *@param bool 	 	$is_completed 			option 	是否直接写入，无需支付平台校验
	 *
	 *@return object WP Post Object
	 */
	protected function insert_record(bool $is_completed): WP_Post {
		// 处理匿名订单属性
		if (!$this->user_id) {
			$this->handle_anon_order_props();
		}

		/**
		 *@since 0.8.76
		 *处理订单 SKU 属性
		 */
		$this->handle_order_sku_props();

		/**
		 *订单状态及标题
		 */
		$this->subject = $this->subject ?: (__('订单：', 'wnd') . get_the_title($this->object_id) . '[' . $this->quantity . ']');
		$this->status  = $is_completed ? static::$completed_status : static::$processing_status;

		/**
		 *@since 2019.03.31 查询符合当前条件，但尚未完成的付款订单
		 */
		$old_orders = get_posts(
			[
				'author'         => $this->user_id,
				'post_parent'    => $this->object_id,
				'post_status'    => static::$processing_status,
				'post_type'      => 'order',
				'posts_per_page' => 1,
				'date_query'     => $this->date_query,
			]
		);

		if ($old_orders) {
			$ID = $old_orders[0]->ID;
		} elseif ($this->object_id) {
			/**
			 *@since 2019.06.04
			 *新增订单统计
			 *插入订单时，无论订单状态均新增订单统计，以实现某些场景下需要限定订单总数时，锁定数据，预留支付时间
			 *获取订单统计时，删除超时未完成的订单，并减去对应订单统计 @see Wnd_Product::get_order_count($object_id)
			 */
			Wnd_Product::inc_order_count($this->object_id, 1);

			/**
			 *@since 0.9.0
			 *扣除库存
			 *插入订单时，无论订单状态均新更新库存统计，以实现锁定数据，预留支付时间
			 *获取库存时，会清空超时未支付的订单 @see Wnd_Product::get_object_props($object_id);
			 */
			Wnd_Product::reduce_single_sku_stock($this->object_id, $this->sku_id, $this->quantity);
		}

		$post_arr = [
			'ID'           => $ID ?? 0,
			'post_author'  => $this->user_id,
			'post_parent'  => $this->object_id,
			'post_content' => $this->total_amount ?: __('免费', 'wnd'),
			'post_excerpt' => $this->payment_gateway,
			'post_status'  => $this->status,
			'post_title'   => $this->subject,
			'post_type'    => 'order',
			'post_name'    => $this->post_name ?: uniqid(),
		];
		$ID = wp_insert_post($post_arr);
		if (is_wp_error($ID) or !$ID) {
			throw new Exception(__('创建订单失败', 'wnd'));
		}

		// 构建Post
		return get_post($ID);
	}

	/**
	 *匿名支付订单cookie name
	 */
	public static function get_anon_cookie_name(int $object_id) {
		return static::$anon_cookie_name_prefix . '_' . $object_id;
	}

	/**
	 *创建匿名支付随机码
	 */
	protected function generate_anon_cookie() {
		return md5(uniqid($this->object_id));
	}

	/**
	 *@since 0.9.2
	 *构建匿名订单所需的订单属性：$this->post_name、$this->date_query
	 *
	 * - 设置匿名订单 cookie
	 * - 将匿名订单 cookie 设置为订单 post name
	 * - 设置订单复用日期条件
	 */
	protected function handle_anon_order_props() {
		/**
		 *设置 Cookie
		 */
		$anon_cookie = $this->generate_anon_cookie();
		setcookie(static::get_anon_cookie_name($this->object_id), $anon_cookie, time() + 3600 * 24, '/');

		/**
		 *将 cookie 设置为订单 post name，以便后续通过 cookie 查询匿名用户订单
		 */
		$this->post_name = $anon_cookie;

		/**
		 *匿名订单用户均为0，不可短时间内复用订单记录，或者会造成订单冲突
		 *更新自动草稿时候，modified 不会变需要查询 post_date
		 *@see get_posts()
		 *@see wp_update_post
		 */
		$this->date_query = [
			[
				'column' => 'post_date',
				'before' => date('Y-m-d H:i', current_time('timestamp') - 86400),
			],
		];
	}

	/**
	 *根据 SKU 变量定义本次订单属性：$this->sku_id、$this->total_amount
	 *@since 0.8.76
	 */
	protected function handle_order_sku_props() {
		$this->sku_id = $this->props[Wnd_Product::$sku_key] ?? '';
		$object_sku   = Wnd_Product::get_object_sku($this->object_id);

		if ($object_sku) {
			if (!$this->sku_id or !in_array($this->sku_id, array_keys($object_sku))) {
				throw new Exception(__('SKU ID 无效', 'wnd'));
			}

			$price = Wnd_Product::get_single_sku_price($this->object_id, $this->sku_id);
		} else {
			$price = wnd_get_post_price($this->object_id);
		}

		$this->total_amount = $price * $this->quantity;
	}

	/**
	 *@since 2019.02.11
	 *确认在线消费订单
	 *@return true
	 *
	 *@param object 	$this->transaction			required 	订单记录Post
	 *@param string 	$this->subject 		option
	 */
	protected function verify_transaction(): bool {
		if ('order' != $this->get_type()) {
			throw new Exception(__('订单ID无效', 'wnd'));
		}

		// 订单支付状态检查
		if (static::$processing_status != $this->get_status()) {
			throw new Exception(__('订单状态无效', 'wnd'));
		}

		$post_arr = [
			'ID'          => $this->get_transaction_id(),
			'post_status' => static::$completed_status,
			'post_title'  => $this->subject ?: $this->get_subject() . __('(在线支付)', 'wnd'),
		];
		$ID = wp_update_post($post_arr);
		if (!$ID or is_wp_error($ID)) {
			throw new Exception(__('数据更新失败', 'wnd'));
		}

		return true;
	}

	/**
	 *订单成功后，执行的统一操作
	 *@since 2020.06.10
	 *
	 *@param $this->transaction
	 *@param object 	$this->transaction		required 	订单记录Post
	 */
	protected function complete(): int{
		/**
		 *本方法可能在站内直接支付，或者站外验证支付中调用。
		 *在线订单校验时，由支付平台发起请求，仅指定订单ID，需根据订单ID设置对应变量。
		 *故不可直接读取相关属性
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
		 *@since 2019.06.04
		 *产品订单：更新总销售额、设置原作者佣金
		 */
		if ($object_id) {
			wnd_inc_post_total_sales($object_id, $total_amount);

			// 删除对象缓存
			wp_cache_delete($this->user_id . '-' . $this->object_id, 'wnd_has_paid');

			// 文章作者新增佣金
			$commission = (float) wnd_get_order_commission($ID);
			if ($commission > 0) {
				$object = get_post($object_id);
				try {
					$recharge = new Wnd_Recharge();
					$recharge->set_object_id($object->ID); // 设置佣金来源
					$recharge->set_user_id($object->post_author);
					$recharge->set_total_amount($commission);
					$recharge->create(true); // 直接写入余额
				} catch (Exception $e) {
					throw new Exception($e->getMessage());
				}
			}
		}

		/**
		 *@since 2019.08.12
		 *充值完成
		 */
		do_action('wnd_order_completed', $ID);

		return $ID;
	}
}
