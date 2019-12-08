<?php
use Wnd\View\Wnd_Ajax_Link;

/**
 *ajax请求示例代码：
 *ajax添加收藏，取消收藏
 */

/******************************************** 添加收藏 *****************************************************/

/**
 *@since 2019.07.02
 *表现层
 *加入收藏按钮
 */
function _wndt_add_favorite($post_id) {
	$html = new Wnd_Ajax_Link();
	$html->set_action('wndt_ajax_add_favorite');
	$html->set_text('<span class="icon"><i class="far fa-heart" title="添加收藏"></i></span>');
	$html->set_param($post_id);
	$html->set_cancel_action('wndt_ajax_remove_favorite');
	$html->set_class('is-small is-danger');
	return $html->get_html();
}

/**
 *@since 2019.07.02
 *控制层
 *ajax添加收藏
 *操作成功后，再次点击，将执行cancel action
 *函数命名对应：$html->set_action('wndt_ajax_add_favorite');
 */
function wndt_ajax_add_favorite() {
	$post_id = $_POST['param'] ?? 0;

	if (wndt_add_favorite($post_id)) {
		return array(
			'status' => 2,
			'data'   => '<span class="icon"><i class="fas fa-heart" title="取消收藏"></i></span>',
			'msg'    => '收藏成功',
		);
	} else {
		return array('status' => 0, 'msg' => '操作失败');
	}
}

/**
 *业务层
 */
function wndt_add_favorite($post_id) {
	// code
}

/******************************************** 取消收藏 *****************************************************/
/**
 *@since 2019.07.02
 *取消收藏按钮
 *表现层
 *函数命名对应：$html->set_cancel_action('wndt_ajax_remove_favorite');
 */
function _wndt_remove_favorite($post_id) {
	$html = new Wnd_Ajax_Link();
	$html->set_action('wndt_ajax_remove_favorite');
	$html->set_text('<span class="icon"><i class="fas fa-heart" title="取消收藏"></i></span>');
	$html->set_param($post_id);
	$html->set_cancel_action('wndt_ajax_add_favorite');
	$html->set_class('is-small is-danger');
	return $html->get_html();
}

/**
 *@since 2019.07.02
 *ajax移除收藏
 *控制层
 */
function wndt_ajax_remove_favorite() {
	$post_id = $_POST['param'] ?? 0;

	if (wndt_remove_favorite($post_id)) {
		return array(
			'status' => 2,
			'data'   => '<span class="icon"><i class="far fa-heart" title="添加收藏"></i></span>',
			'msg'    => '取消收藏',
		);
	} else {
		return array(
			'status' => 0,
			'msg'    => '操作失败',
		);
	}
}

// 业务层
function wndt_remove_favorite() {
	// code
}

/******************************************** 封装 *****************************************************/

// 判断用户是否已收藏
function wndt_user_has_added_favorite($user_id, $post_id) {
	// code
}

/**
 *@since 2019.07.03 收藏按钮
 **/
function _wndt_set_favorite($post_id) {
	$user_id = get_current_user_id();
	if (!$user_id) {
		return;
	}

	if (wndt_user_has_added_favorite($user_id, $post_id)) {
		return _wndt_remove_favorite($post_id);
	} else {
		return _wndt_add_favorite($post_id);
	}
}

/************************************* JavaScript前端响应规则 *********************************************/
/**
 *
 *	switch (response.status) {
 *		// 常规类，展示后端提示信息
 *		case 1:
 *			_this.html(response.data);
 *			break;
 *
 *			// 弹出消息
 *		case 2:
 *			if (response.data) {
 *				_this.html(response.data);
 *			}
 *			if (!is_in_modal) {
 *				wnd_alert_msg(response.msg, 1);
 *			}
 *			break;
 *
 *			// 跳转类
 *		case 3:
 *			wnd_alert_msg("请稍后……");
 *			$(window.location).prop("href", response.data.redirect_to);
 *			break;
 *
 *			// 刷新当前页面
 *		case 4:
 *			wnd_reset_modal();
 *			window.location.reload(true);
 *			break;
 *
 *			//默认展示提示信息
 *		default:
 *			_this.html(response.data);
 *			if (!is_in_modal) {
 *				wnd_alert_msg(response.msg, 1);
 *			}
 *			break;
 *	}
 *
 *
 */