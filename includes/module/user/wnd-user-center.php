<?php
namespace Wnd\Module\User;

use Wnd\Module\Wnd_Module_Html;

/**
 * do => register / login / reset_password, tab => string :profile / account, type => email / phone
 * @since 2019.02.16 封装：用户中心
 *
 * @param  string or array ：
 * @return $html  .= el
 */
class Wnd_User_Center extends Wnd_Module_Html {

	protected static function build(array $args = []): string{
		$ajax_type         = $_GET['ajax_type'] ?? '';
		$enable_sms        = (1 == wnd_get_config('enable_sms')) ? true : false;
		$disable_email_reg = (1 == wnd_get_config('disable_email_reg')) ? true : false;
		$is_user_logged_in = is_user_logged_in();

		// 默认参数
		$defaults = [
			'do'   => 'register',
			'tab'  => 'profile',
			'type' => $enable_sms ? 'phone' : 'email',
			'wrap' => true,
		];

		/**
		 * 在非ajax环境中，约定了GET参数，实现切换模块切换，故此，需要确保GET参数优先级
		 * @see 2019.08.17
		 */
		$args = wp_parse_args($args, $defaults);
		extract($args);

		/**
		 * 重置密码面板：同时允许已登录及未登录用户
		 */
		if ('reset_password' == $do) {
			$html = $wrap ? '<div id="user-center">' : '';
			$html .= Wnd_Reset_Password_Form::render(['type' => $type]);
			$html .= '<div class="has-text-centered mt-3">';

			if (wnd_is_rest_request()) {
				if ('email' == $type and $enable_sms) {
					$html .= static::build_module_link('do=reset_password&type=phone', __('手机验证找回', 'wnd'), $ajax_type);
				} elseif ('phone' == $type) {
					$html .= static::build_module_link('do=reset_password&type=email', __('邮箱验证找回', 'wnd'), $ajax_type);
				}

				if (!$is_user_logged_in) {
					$html .= $enable_sms ? ' | ' : '';
					$html .= static::build_module_link('do=login', __('登录', 'wnd'), $ajax_type);
				}

			} else {
				if ('email' == $type and $enable_sms) {
					$html .= '<a href="' . add_query_arg('type', 'phone') . '">' . __('手机验证找回', 'wnd') . '</a>';
				} elseif ('phone' == $type) {
					$html .= '<a href="' . add_query_arg('type', 'email') . '">' . __('邮箱验证找回', 'wnd') . '</a>';
				}

				if (!$is_user_logged_in) {
					$html .= $enable_sms ? ' | ' : '';
					$html .= '<a href="' . add_query_arg('do', 'login') . '">' . __('登录', 'wnd') . '</a>';
				}
			}

			$html .= '</div>';
			$html .= $wrap ? '</div>' : '';
			return $html;
		}

		/**
		 * 其他面板
		 *
		 * $wrap作用：在ajax嵌入环境中，首次调用时wrap容器已经存在。
		 * 切换选项时，再次请求此模块，后端响应直接嵌入这个容器，需要剥离外部容器
		 */
		$html = $wrap ? '<div id="user-center">' : '';
		if (!$is_user_logged_in) {
			switch ($do) {
				case 'register':
					$html .= Wnd_Reg_Form::render(['type' => $type]);
					$html .= '<div class="has-text-centered mt-3">';
					if (wnd_is_rest_request()) {
						if ('email' == $type and $enable_sms) {
							$html .= static::build_module_link('do=register&type=phone', __('手机注册', 'wnd'), $ajax_type) . ' | ';
						} elseif ('phone' == $type and !$disable_email_reg) {
							$html .= static::build_module_link('do=register&type=email', __('邮箱注册', 'wnd'), $ajax_type) . ' | ';
						}

						$html .= static::build_module_link('do=login', __('登录', 'wnd'), $ajax_type);

					} else {
						if ('email' == $type and $enable_sms) {
							$html .= '<a href="' . add_query_arg('type', 'phone') . '">' . __('手机注册', 'wnd') . '</a> | ';
						} elseif ('phone' == $type and !$disable_email_reg) {
							$html .= '<a href="' . add_query_arg('type', 'email') . '">' . __('邮箱注册', 'wnd') . '</a> | ';
						}
						$html .= '<a href="' . add_query_arg('do', 'login') . '">' . __('登录', 'wnd') . '</a>';

					}
					$html .= '</div>';
					break;

				default:
				case 'login':
					$html .= Wnd_Login_Form::render();
					$html .= '<div class="has-text-centered mt-3">';
					if (wnd_is_rest_request()) {
						$html .= static::build_module_link('do=register', __('立即注册', 'wnd'), $ajax_type) . ' | ';
						$html .= static::build_module_link('do=reset_password', __('忘记密码', 'wnd'), $ajax_type);
					} else {
						$html .= '<a href="' . add_query_arg('do', 'register') . '">' . __('立即注册', 'wnd') . '</a> | ';
						$html .= '<a href="' . add_query_arg('do', 'reset_password') . '">' . __('忘记密码', 'wnd') . '</a>';
					}
					$html .= '</div>';
					break;
			}

		} else {
			switch ($tab) {
				default:
				case 'profile':
					$html .= '<div class="tabs"><ul class="tab">';
					if (wnd_is_rest_request()) {
						$html .= '<li class="is-active">' . static::build_module_link('tab=profile', __('资料', 'wnd'), $ajax_type) . '</li>';
						$html .= '<li>' . static::build_module_link('tab=account', __('账户', 'wnd'), $ajax_type) . '</li>';
					} else {
						$html .= '<li class="is-active"><a href="' . add_query_arg('tab', 'profile') . '">' . __('资料', 'wnd') . '</a></li>';
						$html .= '<li><a href="' . add_query_arg('tab', 'account') . '">' . __('账户', 'wnd') . '</a></li>';
					}
					$html .= '</ul></div>';
					$html .= Wnd_Profile_Form::render();
					break;

				case 'account':
					$html .= '<div class="tabs"><ul class="tab">';
					if (wnd_is_rest_request()) {
						$html .= '<li>' . static::build_module_link('tab=profile', __('资料', 'wnd'), $ajax_type) . '</li>';
						$html .= '<li class="is-active">' . static::build_module_link('tab=account', __('账户', 'wnd'), $ajax_type) . '</li>';
					} else {
						$html .= '<li><a href="' . add_query_arg('tab', 'profile') . '">' . __('资料', 'wnd') . '</a></li>';
						$html .= '<li class="is-active"><a href="' . add_query_arg('tab', 'account') . '">' . __('账户', 'wnd') . '</a></li>';
					}
					$html .= '</ul></div>';
					$html .= Wnd_Account_Form::render();
					break;
			}
		}

		$html .= $wrap ? '</div>' : '';
		return $html;
	}

	/**
	 * 构建用户中心模块切换链接
	 * $wrap作用：在ajax嵌入环境中，首次调用时wrap容器已经存在。
	 * 切换选项时，再次请求此模块，后端响应直接嵌入这个容器，需要剥离外部容器
	 * @since 2020.04.23
	 */
	public static function build_module_link($args, $text, $ajax_type) {
		$args = wp_parse_args($args, ['wrap' => ('embed' == $ajax_type) ? '0' : '1']);

		if ('embed' == $ajax_type) {
			return wnd_embed_link('#user-center', $text, 'user/wnd_user_center', $args);
		} elseif ('modal' == $ajax_type) {
			return wnd_modal_link($text, 'user/wnd_user_center', $args);
		}
	}
}
