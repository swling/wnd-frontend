<?php
namespace Wnd\Module;

/**
 *@since 2019.02.16 封装：用户中心
 *@param string or array ：
 *do => register / login / reset_password, tab => string :profile / account, type => email / phone
 *@return $html .= el
 */
class Wnd_User_Center extends Wnd_Module {

	public static function build($args = array()) {
		$ajax_type         = $_GET['ajax_type'] ?? 'modal';
		$enable_sms        = (wnd_get_option('wnd', 'wnd_enable_sms') == 1) ? true : false;
		$color             = wnd_get_option('wnd', 'wnd_second_color');
		$is_user_logged_in = is_user_logged_in();

		// 默认参数
		$defaults = array(
			'do'   => 'register',
			'tab'  => 'profile',
			'type' => $enable_sms ? 'phone' : 'email',
		);
		$args = wp_parse_args($args, $defaults);

		/**
		 *@see 2019.08.17
		 *在非ajax环境中，约定了GET参数，实现切换模块切换，故此，需要确保GET参数优先级
		 **/
		$do   = $_GET['do'] ?? $args['do'];
		$tab  = $_GET['tab'] ?? $args['tab'];
		$type = $_GET['type'] ?? $args['type'];

		/**
		 *重置密码面板：同时允许已登录及未登录用户
		 */
		if ('reset_password' == $do) {
			$html = '<div id="user-center">';
			$html .= Wnd_Reset_Password_Form::build($type);
			$html .= '<div class="user-form"><div class="message is-' . $color . '"><div class="message-body">';
			if (wnd_doing_ajax()) {
				if ($ajax_type == 'modal') {
					if ($type == 'email' and $enable_sms) {
						$html .= '<a onclick="wnd_ajax_modal(\'wnd_user_center\',\'do=reset_password&type=phone\');">手机验证找回</a>';
					} elseif ($type == 'phone') {
						$html .= '<a onclick="wnd_ajax_modal(\'wnd_user_center\',\'do=reset_password&type=email\');">邮箱验证找回</a>';
					}
					if (!$is_user_logged_in) {
						$html .= ' | <a onclick="wnd_ajax_modal(\'wnd_user_center\',\'do=login\');">登录</a>';
					}

				} else {
					if ($type == 'email' and $enable_sms) {
						$html .= '<a onclick="wnd_ajax_embed(\'#user-center\',\'wnd_user_center\',\'do=reset_password&type=phone\');">手机验证找回</a>';
					} elseif ($type == 'phone') {
						$html .= '<a onclick="wnd_ajax_embed(\'#user-center\',\'wnd_user_center\',\'do=reset_password&type=email\');">邮箱验证找回</a>';
					}
					if (!$is_user_logged_in) {
						$html .= ' | <a onclick="wnd_ajax_embed(\'#user-center\',\'wnd_user_center\',\'do=login\');">登录</a>';
					}
				}

			} else {
				if ($type == 'email' and $enable_sms) {
					$html .= '<a href="' . add_query_arg('type', 'phone') . '">手机验证找回</a>';
				} elseif ($type == 'phone') {
					$html .= '<a href="' . add_query_arg('type', 'email') . '">邮箱验证找回</a>';
				}
				if (!$is_user_logged_in) {
					$html .= ' | <a href="' . add_query_arg('do', 'login') . '">登录</a>';
				}
			}
			$html .= '</div></div></div>';
			$html .= '</div>';
			return $html;
		}

		/**
		 *其他面板
		 */
		$html = '<div id="user-center">';
		if (!$is_user_logged_in) {
			switch ($do) {
			case 'register':
				// 关闭邮箱注册强制短信注册
				$html .= Wnd_Reg_Form::build($type);
				$html .= '<div class="user-form"><div class="message is-' . $color . '"><div class="message-body">';
				if (wnd_doing_ajax()) {
					//是否在ajax中
					if ($ajax_type == 'modal') {
						if ($type == 'email' and $enable_sms) {
							$html .= '<a onclick="wnd_ajax_modal(\'wnd_user_center\',\'do=register&type=phone\');">手机注册</a> | ';
						} elseif ($type == 'phone' and wnd_get_option('wnd', 'wnd_disable_email_reg') != 1) {
							$html .= '<a onclick="wnd_ajax_modal(\'wnd_user_center\',\'do=register&type=email\');">邮箱注册</a> | ';
						}
						$html .= '已有账户？<a onclick="wnd_ajax_modal(\'wnd_user_center\',\'do=login\');">登录</a>';

					} else {
						if ($type == 'email' and $enable_sms) {
							$html .= '<a onclick="wnd_ajax_embed(\'#user-center\',\'wnd_user_center\',\'do=register&type=phone\');">手机注册</a> | ';
						} elseif ($type == 'phone' and wnd_get_option('wnd', 'wnd_disable_email_reg') != 1) {
							$html .= '<a onclick="wnd_ajax_embed(\'#user-center\',\'wnd_user_center\',\'do=register&type=email\');">邮箱注册</a> | ';
						}
						$html .= '已有账户？<a onclick="wnd_ajax_embed(\'#user-center\',\'wnd_user_center\',\'do=login\');">登录</a>';

					}

				} else {
					if ($type == 'email' and $enable_sms) {
						$html .= '<a href="' . add_query_arg('type', 'phone') . '">手机注册</a> | ';
					} elseif ($type == 'phone' and wnd_get_option('wnd', 'wnd_disable_email_reg') != 1) {
						$html .= '<a href="' . add_query_arg('type', 'email') . '">邮箱注册</a> | ';
					}
					$html .= '已有账户？<a href="' . add_query_arg('do', 'login') . '">登录</a>';

				}
				$html .= '</div></div></div>';
				break;

			default:
			case 'login':
				$html .= Wnd_Login_Form::build();
				$html .= '<div class="user-form"><div class="message is-' . $color . '"><div class="message-body">';
				if (wnd_doing_ajax()) {
					if ($ajax_type == 'modal') {
						$html .= '没有账户？<a onclick="wnd_ajax_modal(\'wnd_user_center\',\'do=register\');">立即注册</a> | ';
						$html .= '<a onclick="wnd_ajax_modal(\'wnd_user_center\',\'do=reset_password\');">忘记密码？</a>';
					} else {
						$html .= '没有账户？<a onclick="wnd_ajax_embed(\'#user-center\',\'wnd_user_center\',\'do=register\');">立即注册</a> | ';
						$html .= '<a onclick="wnd_ajax_embed(\'#user-center\',\'wnd_user_center\',\'do=reset_password\');">忘记密码</a>';
					}
				} else {
					$html .= '没有账户？<a href="' . add_query_arg('do', 'register') . '">立即注册</a> | ';
					$html .= '<a href="' . add_query_arg('do', 'reset_password') . '">忘记密码？</a>';
				}
				$html .= '</div></div></div>';
				break;
			}

		} else {
			switch ($tab) {
			default:
			case 'profile':
				$html .= '<div class="tabs is-boxed"><ul class="tab">';
				if (wnd_doing_ajax()) {
					if ($ajax_type == 'modal') {
						$html .= '<li class="is-active"><a onclick="wnd_ajax_modal(\'wnd_user_center\',\'tab=profile\');">资料</a></li>';
						$html .= '<li><a onclick="wnd_ajax_modal(\'wnd_user_center\',\'tab=account\');">账户</a></li>';
					} else {
						$html .= '<li class="is-active"><a onclick="wnd_ajax_embed(\'#user-center\',\'wnd_user_center\',\'tab=profile\');">资料</a></li>';
						$html .= '<li><a onclick="wnd_ajax_embed(\'#user-center\',\'wnd_user_center\',\'tab=account\');">账户</a></li>';
					}
				} else {
					$html .= '<li class="is-active"><a href="' . add_query_arg('tab', 'profile') . '">资料</a></li>';
					$html .= '<li><a href="' . add_query_arg('tab', 'account') . '">账户</a></li>';
				}
				$html .= '</ul></div>';
				$html .= Wnd_Profile_Form::build();
				break;

			case 'account':
				$html .= '<div class="tabs is-boxed"><ul class="tab">';
				if (wnd_doing_ajax()) {
					if ($ajax_type == 'modal') {
						$html .= '<li><a onclick="wnd_ajax_modal(\'wnd_user_center\',\'tab=profile\');">资料</a></li>';
						$html .= '<li class="is-active"><a onclick="wnd_ajax_modal(\'wnd_user_center\',\'tab=account\');">账户</a></li>';
					} else {
						$html .= '<li><a onclick="wnd_ajax_embed(\'#user-center\',\'wnd_user_center\',\'tab=profile\');">资料</a></li>';
						$html .= '<li class="is-active"><a onclick="wnd_ajax_embed(\'#user-center\',\'wnd_user_center\',\'tab=account\');">账户</a></li>';
					}
				} else {
					$html .= '<li><a href="' . add_query_arg('tab', 'profile') . '">资料</a></li>';
					$html .= '<li class="is-active"><a href="' . add_query_arg('tab', 'account') . '">账户</a></li>';
				}
				$html .= '</ul></div>';
				$html .= Wnd_Account_Form::build();
				break;
			}
		}

		$html .= '</div>';
		return $html;
	}
}
