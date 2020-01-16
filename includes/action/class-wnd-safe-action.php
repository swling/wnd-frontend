<?php
namespace Wnd\Action;

/**
 *@since 2019.10.05
 *封装一些无关数据安全的常规操作
 *由于rest操作需要验证action nonce，因此在前端无法直接发起一个操作请求
 *本操作对应的nonce：wnd_create_nonce('wnd_safe_action') 已提前生成，因此前端可以直接获取，从而调用本控制类
 *
 *请求必须包含以下参数：
 *@param $_REQUEST['action'] string 固定值：'wnd_safe_action'
 *@param $_REQUEST['method'] string 指定本类中的方法
 */
class Wnd_Safe_Action extends Wnd_Action_Ajax {

	// 根据method参数选择处理方法
	public static function execute(): array{
		$method = $_REQUEST['method'] ?? false;
		if (!$method) {
			return ['status' => 0, 'msg' => __('未指定方法', 'wnd')];
		}

		if (!method_exists(__CLASS__, $method)) {
			return ['status' => 0, 'msg' => __('指定方法不可用', 'wnd')];
		}

		return self::$method();
	}

	/**
	 *@since 2019.01.16
	 *@param $_REQUEST['post_id']
	 */
	public static function update_views() {
		$post_id = (int) $_REQUEST['param'];
		if (!$post_id) {
			return;
		}

		// 更新字段信息
		if (wnd_inc_post_meta($post_id, 'views', 1)) {
			do_action('wnd_update_views', $post_id);
			return ['status' => 1, 'msg' => time()];

			//字段写入失败，清除对象缓存
		} else {
			wp_cache_delete($post_id, 'post_meta');
			return ['status' => 0, 'msg' => time()];
		}
	}
}
