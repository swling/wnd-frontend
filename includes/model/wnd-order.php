<?php
namespace Wnd\Model;

use Exception;
use WP_Post;

/**
 *@since 2019.08.11
 *订单模块
 *
 *	# 自定义文章类型
 *	post_type属性('public' => false)，因此在WordPress后台无法查看到
 *	订单：order
 *
 *	# 状态：
 *	pending / success
 *
 *	# 消费post data
 *	金额：		post_content
 *	关联：		post_parent
 *	标题：		post_title
 *	状态：		post_status: pengding / success
 *	类型：		post_type：order
 * 	匿名cookie：post_name
 *	接口：		post_excerpt：（支付平台标识如：Alipay / Wepay）
 *
 */
class Wnd_Order extends Wnd_Transaction {

	// 是否启用匿名订单
	protected $enable_anon_order;

	// 定义匿名支付cookie名称
	public static $anon_cookie_name_prefix = 'anon_order';

	// 匿名用户临时支付cookie
	protected $anon_cookie;

	/**
	 *@since 2019.08.11
	 *构造函数
	 */
	public function __construct() {
		parent::__construct();

		$this->enable_anon_order = wnd_get_config('enable_anon_order');
	}

	/**
	 *匿名支付订单cookie name
	 */
	public static function get_anon_cookie_name($object_id) {
		return static::$anon_cookie_name_prefix . '_' . $object_id;
	}

	/**
	 *创建匿名支付随机码
	 */
	protected function generate_anon_cookie() {
		return md5(uniqid($this->object_id));
	}

	/**
	 *@since 2019.02.11
	 *用户本站消费数据(含余额消费，或直接第三方支付消费)
	 *
	 *@param int 		$this->user_id  		required
	 *@param int 		$this->object_id  		option
	 *@param string 	$this->subject 			option
	 *@param string 	$this->payment_gateway	option 	支付平台标识
	 *@param bool 	 	$is_success 			option 	是否直接写入，无需支付平台校验
	 *
	 *@return object WP Post Object
	 */
	public function create(bool $is_success = false): WP_Post {
		/**
		 *匿名支付Cookie：
		 *cookie_name = static::$anon_cookie_name . '-' . $this->object_id
		 *@since 2020.06.18
		 */
		if (!$this->user_id) {
			if (!$this->enable_anon_order) {
				throw new Exception(__('请登录', 'wnd'));
			}

			$this->anon_cookie = $this->generate_anon_cookie();
			setcookie(static::get_anon_cookie_name($this->object_id), $this->anon_cookie, time() + 3600 * 24, '/');
		}

		// 定义变量
		$this->total_amount = wnd_get_post_price($this->object_id);
		$this->status       = $is_success ? 'success' : 'pending';
		$this->subject      = $this->subject ?: (__('订单：', 'wnd') . get_the_title($this->object_id));

		/**
		 *@since 2019.03.31 查询符合当前条件，但尚未完成的付款订单
		 */
		$old_orders = get_posts(
			[
				'author'         => $this->user_id,
				'post_parent'    => $this->object_id,
				'post_status'    => 'pending',
				'post_type'      => 'order',
				'posts_per_page' => 1,
			]
		);

		if ($old_orders) {
			$ID = $old_orders[0]->ID;
		} elseif ($this->object_id) {
			/**
			 *@since 2019.06.04
			 *新增订单统计
			 *插入订单时，无论订单状态均新增订单统计，以实现某些场景下需要限定订单总数时，锁定数据，预留支付时间
			 *获取订单统计时，删除超时未完成的订单，并减去对应订单统计 @see wnd_get_order_count($object_id)
			 */
			wnd_inc_order_count($this->object_id, 1);
		}

		$post_arr = [
			'ID'           => $ID ?? 0,
			'post_author'  => $this->user_id,
			'post_parent'  => $this->object_id,
			'post_content' => $this->total_amount ?: __('免费', 'wnd'),
			'post_excerpt' => static::$payment_gateway,
			'post_status'  => $this->status,
			'post_title'   => $this->subject,
			'post_type'    => 'order',
			'post_name'    => $this->anon_cookie,
		];
		$ID = wp_insert_post($post_arr);
		if (is_wp_error($ID) or !$ID) {
			throw new Exception(__('创建订单失败', 'wnd'));
		}

		// 构建Post
		$this->post = get_post($ID);

		/**
		 *@since 2019.02.17
		 *success表示直接余额消费
		 *pending 则表示通过在线直接支付订单，需要等待支付平台验证返回后更新支付 @see static::verify();
		 */
		if ('success' == $this->status) {
			$this->complete();
		}

		return $this->post;
	}

	/**
	 *@since 2019.02.11
	 *确认在线消费订单
	 *@return int or false
	 *
	 *@param object 	$this->post			required 	订单记录Post
	 *@param string 	$this->subject 		option
	 */
	public function verify() {
		if ('order' != $this->get_type()) {
			throw new Exception(__('订单ID无效', 'wnd'));
		}

		// 订单支付状态检查
		if ('pending' != $this->get_status()) {
			throw new Exception(__('订单状态无效', 'wnd'));
		}

		$post_arr = [
			'ID'          => $this->get_ID(),
			'post_status' => 'success',
			'post_title'  => $this->subject ?: $this->get_subject() . __('(在线支付)', 'wnd'),
		];
		$ID = wp_update_post($post_arr);
		if (!$ID or is_wp_error($ID)) {
			throw new Exception(__('数据更新失败', 'wnd'));
		}

		// 完成本笔业务
		$this->complete();
	}

	/**
	 *订单成功后，执行的统一操作
	 *@since 2020.06.10
	 *
	 *@param $this->post
	 *@param object 	$this->post		required 	订单记录Post
	 */
	protected function complete(): int{
		/**
		 *本方法可能在站内直接支付，或者站外验证支付中调用。
		 *在线订单校验时，由支付平台发起请求，仅指定订单ID，需根据订单ID设置对应变量。
		 *故不可直接读取相关属性
		 */
		$ID           = $this->get_ID();
		$user_id      = $this->get_user_id();
		$total_amount = $this->get_total_amount();
		$object_id    = $this->get_object_id();

		// 写入消费记录
		wnd_inc_user_expense($user_id, $total_amount);

		// 站内直接消费，无需支付平台支付校验，记录扣除账户余额、在线支付则不影响当前余额
		if (!static::get_payment_gateway($ID)) {
			wnd_inc_user_money($user_id, $total_amount * -1);
		}

		/**
		 *@since 2019.06.04
		 *产品订单：更新总销售额、设置原作者佣金
		 */
		if ($object_id) {
			wnd_inc_post_total_sales($object_id, $total_amount);

			// @since 2020.06.11 废弃缓存删除，该功能已通过 WP Action post_updated HOOK实现
			// wp_cache_delete($this->user_id . '-' . $this->object_id, 'wnd_has_paid');

			// 文章作者新增佣金
			$commission = (float) wnd_get_post_commission($object_id);
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
