<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@see
 *自定义一些标准模块以便在页面或ajax请求中快速调用
 *函数均以$html .=直接输出返回
 *以_wnd_做前缀的函数可用于ajax请求，无需nonce校验，因此相关模板函数中不应有任何数据库操作，仅作为界面响应输出。
 */

/**
 *@since 2019.02.16 封装：用户中心
 *@param string or array ：action => reg / login / lostpassword, tab => string :profile / account
 *@return $html .= el
 */
function _wnd_user_center($args = array()) {

	$defaults = array(
		'action' => 'reg',
		'tab' => 'profile',
	);
	$args = wp_parse_args($args, $defaults);
	$action = $_GET['action'] ?? $args['action'];
	$tab = $_GET['tab'] ?? $args['tab'];

	// ajax请求类型
	$ajax_type = $_POST['ajax_type'] ?? 'modal';

	$html = '<div id="user-center">';

	//1、 未登录用户面板
	if (!is_user_logged_in()) {

		switch ($action) {

		case 'reg':

			// 关闭邮箱注册强制短信注册
			$type = wnd_get_option('wnd', 'wnd_disable_email_reg') == 1 ? 'sms' : ($_GET['type'] ?? $args['type'] ?? 'email');

			$html .= _wnd_reg_form($type);

			$html .= '<div class="user-form"><div class="message is-' . wnd_get_option('wnd', 'wnd_second_color') . '"><div class="message-body">';
			if (wnd_doing_ajax()) {
				//是否在ajax中
				if ($ajax_type == 'modal') {

					if ($type == 'email' and wnd_get_option('wnd', 'wnd_sms_enable') == 1) {
						$html .= '<a onclick="wnd_ajax_modal(\'_wnd_user_center\',\'action=reg&type=sms\');">手机注册</a> | ';
					} elseif ($type == 'sms' and wnd_get_option('wnd', 'wnd_disable_email_reg') != 1) {
						$html .= '<a onclick="wnd_ajax_modal(\'_wnd_user_center\',\'action=reg&type=email\');">邮箱注册</a> | ';
					}
					$html .= '已有账户？<a onclick="wnd_ajax_modal(\'_wnd_user_center\',\'action=login\');">登录</a>';

				} else {

					if ($type == 'email' and wnd_get_option('wnd', 'wnd_sms_enable') == 1) {
						$html .= '<a onclick="wnd_ajax_embed(\'#user-center\',\'_wnd_user_center\',\'action=reg&type=sms\');">手机注册</a> | ';
					} elseif ($type == 'sms' and wnd_get_option('wnd', 'wnd_disable_email_reg') != 1) {
						$html .= '<a onclick="wnd_ajax_embed(\'#user-center\',\'_wnd_user_center\',\'action=reg&type=email\');">邮箱注册</a> | ';
					}
					$html .= '已有账户？<a onclick="wnd_ajax_embed(\'#user-center\',\'_wnd_user_center\',\'action=login\');">登录</a>';

				}

			} else {

				if ($type == 'email' and wnd_get_option('wnd', 'wnd_sms_enable') == 1) {
					$html .= '<a href="' . add_query_arg('type', 'sms') . '">手机注册</a> | ';
				} elseif ($type == 'sms' and wnd_get_option('wnd', 'wnd_disable_email_reg') != 1) {
					$html .= '<a href="' . add_query_arg('type', 'email') . '">邮箱注册</a> | ';
				}
				$html .= '已有账户？<a href="' . add_query_arg('action', 'login') . '">登录</a>';

			}
			$html .= '</div></div></div>';

			break;

		default:case 'login':

			$html .= _wnd_login_form();

			$html .= '<div class="user-form"><div class="message is-' . wnd_get_option('wnd', 'wnd_second_color') . '"><div class="message-body">';
			if (wnd_doing_ajax()) {
				if ($ajax_type == 'modal') {
					$html .= '没有账户？<a onclick="wnd_ajax_modal(\'_wnd_user_center\',\'action=reg\');">立即注册</a> | ';
					$html .= '<a onclick="wnd_ajax_modal(\'_wnd_user_center\',\'action=lostpassword\');">忘记密码？</a>';
				} else {
					$html .= '没有账户？<a onclick="wnd_ajax_embed(\'#user-center\',\'_wnd_user_center\',\'action=reg\');">立即注册</a> | ';
					$html .= '<a onclick="wnd_ajax_embed(\'#user-center\',\'_wnd_user_center\',\'action=lostpassword\');">忘记密码</a>';
				}
			} else {
				$html .= '没有账户？<a href="' . add_query_arg('action', 'reg') . '">立即注册</a> | ';
				$html .= '<a href="' . add_query_arg('action', 'lostpassword') . '">忘记密码？</a>';
			}
			$html .= '</div></div></div>';

			break;

		case 'lostpassword':

			$type = $_GET['type'] ?? $args['type'] ?? 'email';
			$html .= _wnd_lostpassword_form($type);

			$html .= '<div class="user-form"><div class="message is-' . wnd_get_option('wnd', 'wnd_second_color') . '"><div class="message-body">';
			if (wnd_doing_ajax()) {
				if ($ajax_type == 'modal') {

					if ($type == 'email' and wnd_get_option('wnd', 'wnd_sms_enable') == 1) {
						$html .= '<a onclick="wnd_ajax_modal(\'_wnd_user_center\',\'action=lostpassword&type=sms\');">手机验证找回</a> | ';
					} elseif ($type == 'sms') {
						$html .= '<a onclick="wnd_ajax_modal(\'_wnd_user_center\',\'action=lostpassword&type=email\');">邮箱验证找回</a> | ';
					}
					$html .= '<a onclick="wnd_ajax_modal(\'_wnd_user_center\',\'action=login\');">登录</a>';

				} else {

					if ($type == 'email' and wnd_get_option('wnd', 'wnd_sms_enable') == 1) {
						$html .= '<a onclick="wnd_ajax_embed(\'#user-center\',\'_wnd_user_center\',\'action=lostpassword&type=sms\');">手机验证找回</a> | ';
					} elseif ($type == 'sms') {
						$html .= '<a onclick="wnd_ajax_embed(\'#user-center\',\'_wnd_user_center\',\'action=lostpassword&type=email\');">邮箱验证找回</a> | ';
					}

					$html .= '<a onclick="wnd_ajax_embed(\'#user-center\',\'_wnd_user_center\',\'action=login\');">登录</a>';
				}
			} else {

				if ($type == 'email' and wnd_get_option('wnd', 'wnd_sms_enable') == 1) {
					$html .= '<a href="' . add_query_arg('type', 'sms') . '">手机验证找回</a> | ';
				} elseif ($type == 'sms') {
					$html .= '<a href="' . add_query_arg('type', 'email') . '">邮箱验证找回</a> | ';
				}
				$html .= '<a href="' . add_query_arg('action', 'login') . '">登录</a>';

			}
			$html .= '</div></div></div>';

			break;

		}

		//2、已登录用户面板
	} else {

		switch ($tab) {

		case 'profile':default:

			$html .= '<div class="tabs is-boxed"><ul class="tab">';
			if (wnd_doing_ajax()) {
				if ($ajax_type == 'modal') {
					$html .= '<li class="is-active"><a onclick="wnd_ajax_modal(\'_wnd_user_center\',\'tab=profile\');">资料</a></li>';
					$html .= '<li><a onclick="wnd_ajax_modal(\'_wnd_user_center\',\'tab=account\');">账户</a></li>';
				} else {
					$html .= '<li class="is-active"><a onclick="wnd_ajax_embed(\'#user-center\',\'_wnd_user_center\',\'tab=profile\');">资料</a></li>';
					$html .= '<li><a onclick="wnd_ajax_embed(\'#user-center\',\'_wnd_user_center\',\'tab=account\');">账户</a></li>';
				}
			} else {
				$html .= '<li class="is-active"><a href="' . add_query_arg('tab', 'profile') . '">资料</a></li>';
				$html .= '<li><a href="' . add_query_arg('tab', 'account') . '">账户</a></li>';
			}
			$html .= '</ul></div>';

			$html .= _wnd_profile_form();

			break;

		case 'account':

			$html .= '<div class="tabs is-boxed"><ul class="tab">';
			if (wnd_doing_ajax()) {
				if ($ajax_type == 'modal') {
					$html .= '<li><a onclick="wnd_ajax_modal(\'_wnd_user_center\',\'tab=profile\');">资料</a></li>';
					$html .= '<li class="is-active"><a onclick="wnd_ajax_modal(\'_wnd_user_center\',\'tab=account\');">账户</a></li>';
				} else {
					$html .= '<li><a onclick="wnd_ajax_embed(\'#user-center\',\'_wnd_user_center\',\'tab=profile\');">资料</a></li>';
					$html .= '<li class="is-active"><a onclick="wnd_ajax_embed(\'#user-center\',\'_wnd_user_center\',\'tab=account\');">账户</a></li>';
				}
			} else {
				$html .= '<li><a href="' . add_query_arg('tab', 'profile') . '">资料</a></li>';
				$html .= '<li class="is-active"><a href="' . add_query_arg('tab', 'account') . '">账户</a></li>';
			}
			$html .= '</ul></div>';

			$html .= _wnd_account_form();

			break;

		}

	}

	$html .= '</div>';
	return $html;
}

/**
 *@since 2019.01.13 登录框
 *@since 2019.03.10 Wnd_Ajax_Form
 */
function _wnd_login_form() {

	// 已登录
	if (is_user_logged_in()) {
		return '<script>wnd_alert_msg("已登录！")</script>';
	}

	$form = new Wnd_User_Form();

	$form->set_form_title('<span class="icon"><i class="fa fa-user"></i></span>登录');
	$form->set_form_attr('class="user-form"');

	$form->add_user_login();

	$form->add_user_password();

	$form->add_checkbox(
		array(

			'name' => 'remember',
			'value' => 1,
			'label' => '保持登录',
			'checked' => '1', //default checked value
		)
	);

	$form->add_hidden('redirect_to', $_SERVER['HTTP_REFERER'] ?? home_url());

	// 与该表单数据匹配的后端处理函数
	$form->set_action('wnd_ajax_login');

	$form->set_submit_button('登录');

	// 以当前函数名设置filter hook
	$form->set_filter(__FUNCTION__);

	// 构造表单
	$form->build();

	// 输出表单
	return $form->html;

}

/**
 *@since 2019.01.21 注册表单
 *@param $type 		string 		email/sms
 */
function _wnd_reg_form($type = 'email') {

	// 已登录
	if (is_user_logged_in()) {
		return '<script>wnd_alert_msg("已登录！")</script>';

		//已关闭注册
	} elseif (!get_option('users_can_register')) {
		return '<script>wnd_alert_msg("站点已关闭注册！")</script>';

		// 关闭了邮箱注册（强制手机验证）
	} elseif ($type == 'email' and wnd_get_option('wnd', 'wnd_disable_email_reg') == 1) {

		return "<script>wnd_alert_msg('当前设置禁止邮箱注册！')</script>";

		//为开启手机验证
	} elseif ($type == 'sms' and wnd_get_option('wnd', 'wnd_sms_enable') != 1) {

		return "<script>wnd_alert_msg('当前未配置短信验证！')</script>";

	}

	$form = new Wnd_User_Form();

	$form->set_form_title('<span class="icon"><i class="fa fa-user"></i></span>注册');
	$form->set_form_attr('class="user-form"');

	$form->add_user_login('用户名');

	$form->add_user_password();

	if ($type == 'sms') {
		// $form->add_user_email($placeholder = '邮箱');
		$form->add_sms_verify($verify_type = 'reg', wnd_get_option('wnd', 'wnd_sms_template_r'));
	} else {
		$form->add_email_verify($verify_type = 'reg', $template = '');
	}
	if (wnd_get_option('wnd', 'wnd_agreement_url') or 1) {

		$form->add_checkbox(
			array(
				'name' => 'agreement',
				'value' => 1,
				'checked' => 1,
				'label' => '我已阅读并同意注册协议<a href="' . wnd_get_option('wnd', 'wnd_agreement_url') . '" target="_blank">《注册协议》</a>',
				'required' => 'required',
			)
		);
	}

	$form->set_action('wnd_ajax_reg');
	$form->set_submit_button('注册');

	// 以当前函数名设置filter hook
	$form->set_filter(__FUNCTION__);

	$form->build();

	return $form->html;

}

/**
 *@since 2019.01.28 找回密码
 *@param $type 		string 		email/sms
 */
function _wnd_lostpassword_form($type = 'email') {

	if ($type == 'sms') {
		//1、验证短信重置密码
		if (wnd_get_option('wnd', 'wnd_sms_enable') != 1) {
			return '<script type="text/javascript">wnd_alert_msg(\'短信验证功能未启用！\')</script>';
		}
	}

	$form = new Wnd_User_Form();

	if ($type == 'sms') {
		$form->set_form_title('<span class="icon"><i class="fa fa-phone-square"></i></span>手机验证');
	} else {
		$form->set_form_title('<span class="icon"><i class="fa fa-at"></i></span>邮箱验证</h3>');
	}

	$form->set_form_attr('class="user-form"');

	if ($type == 'sms') {
		$form->add_sms_verify($verify_type = 'reset_pass', wnd_get_option('wnd', 'wnd_sms_template_v'));
	} else {
		$form->add_email_verify($verify_type = 'reset_pass', $template = '');
	}

	$form->add_user_new_password();

	$form->add_user_new_password_repeat();

	$form->set_action('wnd_ajax_reset_password');
	$form->set_submit_button('重置密码');
	$form->build();

	return $form->html;

}

/*
########################################################################## part2： 已登录用户
 */

/**
 *@since 2019.01.29 用户常规资料表单
 */
function _wnd_profile_form() {

	if (!is_user_logged_in()) {
		return '<script>wnd_alert_msg(\'请登录\')</script>';
	}

	$form = new Wnd_User_Form();

	// profile表单可能有较为复杂的编辑界面，阻止回车提交
	$form->set_form_attr('onsubmit="return false" onkeydown="if(event.keyCode==13){return false;}"');

	/*头像上传*/
	$form->add_user_avatar();

	$form->add_html('<div class="field is-horizontal"><div class="field-body">');
	$form->add_user_display_name();
	$form->add_user_url();
	$form->add_html('</div></div>');

	// textarea
	$form->add_user_description();

	$form->set_action('wnd_ajax_update_profile');
	$form->set_submit_button('保存');

	// 以当前函数名设置filter hook
	$form->set_filter(__FUNCTION__);
	$form->build();

	return $form->html;

}

/**
 *@since 2019.01.23 用户更新账户表单
 */
function _wnd_account_form() {
	if (!is_user_logged_in()) {
		return '<script>wnd_alert_msg(\'请登录\')</script>';
	}

	$form = new Wnd_User_Form();
	$form->set_form_title('<span class="icon"><i class="fa fa-user"></i></span>账户安全');
	$form->set_form_attr('class="user-form"');

	$form->add_user_password();

	$form->add_user_new_password();

	$form->add_user_new_password_repeat();

	$form->add_user_email();

	if (wnd_get_option('wnd', 'wnd_sms_enable') == 1) {
		$form->add_sms_verify($verify_type = 'v', wnd_get_option('wnd', 'wnd_sms_template_v'));
	} else {
		$form->add_email_verify($verify_type = 'v', $template = '');
	}

	$form->set_action('wnd_ajax_reset_password');
	$form->set_submit_button('保存');
	$form->build();

	return $form->html;

}
