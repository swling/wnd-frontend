<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@see
 *自定义一些标准模块以便在页面或ajax请求中快速调用
 *函数均以echo直接输出返回
 *以_wnd_做前缀的函数可用于ajax请求，无需nonce校验，因此相关模板函数中不应有任何数据库操作，仅作为界面响应输出。
 */

/**
 *@since 2019.02.16 封装：用户中心
 *@param string or array ：action => reg / login / lostpassword, tab => string :profile / account
 *@return echo el
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

	echo '<div id="user-center">';

	//1、 未登录用户面板
	if (!is_user_logged_in()) {

		switch ($action) {

		case 'reg':

			// 关闭邮箱注册强制短信注册
			$type = wnd_get_option('wndwp', 'wnd_disable_email_reg') == 1 ? 'sms' : ($_GET['type'] ?? $args['type'] ?? 'email');

			_wnd_reg_form($type);

			echo '<div class="user-form"><div class="message is-primary"><div class="message-body">';
			if (wp_doing_ajax()) {
				//是否在ajax中
				if ($ajax_type == 'modal') {

					if ($type == 'email' and wnd_get_option('wndwp', 'wnd_sms_enable') == 1) {
						echo '<a onclick="wnd_ajax_modal(\'user_center\',\'action=reg&type=sms\');">手机注册</a> | ';
					} elseif ($type == 'sms' and wnd_get_option('wndwp', 'wnd_disable_email_reg') != 1) {
						echo '<a onclick="wnd_ajax_modal(\'user_center\',\'action=reg&type=email\');">邮箱注册</a> | ';
					}
					echo '已有账户？<a onclick="wnd_ajax_modal(\'user_center\',\'action=login\');">登录</a>';

				} else {

					if ($type == 'email' and wnd_get_option('wndwp', 'wnd_sms_enable') == 1) {
						echo '<a onclick="wnd_ajax_embed(\'#user-center\',\'user_center\',\'action=reg&type=sms\');">手机注册</a> | ';
					} elseif ($type == 'sms' and wnd_get_option('wndwp', 'wnd_disable_email_reg') != 1) {
						echo '<a onclick="wnd_ajax_embed(\'#user-center\',\'user_center\',\'action=reg&type=email\');">邮箱注册</a> | ';
					}
					echo '已有账户？<a onclick="wnd_ajax_embed(\'#user-center\',\'user_center\',\'action=login\');">登录</a>';

				}

			} else {

				if ($type == 'email' and wnd_get_option('wndwp', 'wnd_sms_enable') == 1) {
					echo '<a href="' . add_query_arg('type', 'sms') . '">手机注册</a> | ';
				} elseif ($type == 'sms' and wnd_get_option('wndwp', 'wnd_disable_email_reg') != 1) {
					echo '<a href="' . add_query_arg('type', 'email') . '">邮箱注册</a> | ';
				}
				echo '已有账户？<a href="' . add_query_arg('action', 'login') . '">登录</a>';

			}
			echo '</div></div></div>';

			break;

		default:case 'login':

			_wnd_login_form();

			echo '<div class="user-form"><div class="message is-primary"><div class="message-body">';
			if (wp_doing_ajax()) {
				if ($ajax_type == 'modal') {
					echo '没有账户？<a onclick="wnd_ajax_modal(\'user_center\',\'action=reg\');">立即注册</a> | ';
					echo '<a onclick="wnd_ajax_modal(\'user_center\',\'action=lostpassword\');">忘记密码？</a>';
				} else {
					echo '没有账户？<a onclick="wnd_ajax_embed(\'#user-center\',\'user_center\',\'action=reg\');">立即注册</a> | ';
					echo '<a onclick="wnd_ajax_embed(\'#user-center\',\'user_center\',\'action=lostpassword\');">忘记密码</a>';
				}
			} else {
				echo '没有账户？<a href="' . add_query_arg('action', 'reg') . '">立即注册</a> | ';
				echo '<a href="' . add_query_arg('action', 'lostpassword') . '">忘记密码？</a>';
			}
			echo '</div></div></div>';

			break;

		case 'lostpassword':

			$type = $_GET['type'] ?? $args['type'] ?? 'email';
			_wnd_lostpassword_form($type);

			echo '<div class="user-form"><div class="message is-primary"><div class="message-body">';
			if (wp_doing_ajax()) {
				if ($ajax_type == 'modal') {

					if ($type == 'email' and wnd_get_option('wndwp', 'wnd_sms_enable') == 1) {
						echo '<a onclick="wnd_ajax_modal(\'user_center\',\'action=lostpassword&type=sms\');">手机验证找回</a> | ';
					} elseif ($type == 'sms') {
						echo '<a onclick="wnd_ajax_modal(\'user_center\',\'action=lostpassword&type=email\');">邮箱验证找回</a> | ';
					}
					echo '<a onclick="wnd_ajax_modal(\'user_center\',\'action=login\');">登录</a>';

				} else {

					if ($type == 'email' and wnd_get_option('wndwp', 'wnd_sms_enable') == 1) {
						echo '<a onclick="wnd_ajax_embed(\'#user-center\',\'user_center\',\'action=lostpassword&type=sms\');">手机验证找回</a> | ';
					} elseif ($type == 'sms') {
						echo '<a onclick="wnd_ajax_embed(\'#user-center\',\'user_center\',\'action=lostpassword&type=email\');">邮箱验证找回</a> | ';
					}

					echo '<a onclick="wnd_ajax_embed(\'#user-center\',\'user_center\',\'action=login\');">登录</a>';
				}
			} else {

				if ($type == 'email' and wnd_get_option('wndwp', 'wnd_sms_enable') == 1) {
					echo '<a href="' . add_query_arg('type', 'sms') . '">手机验证找回</a> | ';
				} elseif ($type == 'sms') {
					echo '<a href="' . add_query_arg('type', 'email') . '">邮箱验证找回</a> | ';
				}
				echo '<a href="' . add_query_arg('action', 'login') . '">登录</a>';

			}
			echo '</div></div></div>';

			break;

		}

		//2、已登录用户面板
	} else {

		switch ($tab) {

		case 'profile':default:

			echo '<div class="tabs is-boxed"><ul class="tab">';
			if (wp_doing_ajax()) {
				if ($ajax_type == 'modal') {
					echo '<li class="is-active"><a onclick="wnd_ajax_modal(\'user_center\',\'tab=profile\');">资料</a></li>';
					echo '<li><a onclick="wnd_ajax_modal(\'user_center\',\'tab=account\');">账户</a></li>';
				} else {
					echo '<li class="is-active"><a onclick="wnd_ajax_embed(\'#user-center\',\'user_center\',\'tab=profile\');">资料</a></li>';
					echo '<li><a onclick="wnd_ajax_embed(\'#user-center\',\'user_center\',\'tab=account\');">账户</a></li>';
				}
			} else {
				echo '<li class="is-active"><a href="' . add_query_arg('tab', 'profile') . '">资料</a></li>';
				echo '<li><a href="' . add_query_arg('tab', 'account') . '">账户</a></li>';
			}
			echo '</ul></div>';

			_wnd_profile_form();

			break;

		case 'account':

			echo '<div class="tabs is-boxed"><ul class="tab">';
			if (wp_doing_ajax()) {
				if ($ajax_type == 'modal') {
					echo '<li><a onclick="wnd_ajax_modal(\'user_center\',\'tab=profile\');">资料</a></li>';
					echo '<li class="is-active"><a onclick="wnd_ajax_modal(\'user_center\',\'tab=account\');">账户</a></li>';
				} else {
					echo '<li><a onclick="wnd_ajax_embed(\'#user-center\',\'user_center\',\'tab=profile\');">资料</a></li>';
					echo '<li class="is-active"><a onclick="wnd_ajax_embed(\'#user-center\',\'user_center\',\'tab=account\');">账户</a></li>';
				}
			} else {
				echo '<li><a href="' . add_query_arg('tab', 'profile') . '">资料</a></li>';
				echo '<li class="is-active"><a href="' . add_query_arg('tab', 'account') . '">账户</a></li>';
			}
			echo '</ul></div>';

			_wnd_account_form();

			break;

		}

	}

	echo '</div>';

}

/**
 *@since 2019.01.13 登录框
 *@since 2019.03.10 Wnd_Ajax_Form
 */
function _wnd_login_form() {

	// 已登录
	if (is_user_logged_in()) {
		echo '<script>wnd_alert_msg("已登录！")</script>';
		return;
	}

	$form = new Wnd_Ajax_Form();

	$form->set_form_title('<span class="icon"><i class="fa fa-user"></i></span>登录');
	$form->set_form_attr('id="user-login" class="user-form"');

	$form->add_text(
		array(
			'name' => '_user_user_login',
			'value' => '',
			'placeholder' => '用户名、手机、邮箱',
			'label' => '用户名 <span class="required">*</span>',
			'has_icons' => 'left', //icon position "left" orf "right"
			'icon' => '<i class="fas fa-user"></i>', // icon html @link https://fontawesome.com/
			'autofocus' => 'autofocus',
			'required' => true,
		)
	);

	$form->add_password(
		array(
			'name' => '_user_user_pass',
			'value' => '',
			'label' => '密码 <span class="required">*</span>',
			'placeholder' => '密码',
			'has_icons' => 'left',
			'icon' => '<i class="fas fa-unlock-alt"></i>',
			'required' => true,
		)
	);

	$form->add_checkbox(
		array(

			'name' => 'remember',
			'value' => array('保持登录' => '1'),
			'label' => 'checkbox',
			'checked' => '1', //default checked value
		)
	);

	$form->add_hidden('redirect_to', $_SERVER['HTTP_REFERER'] ?? home_url());

	// 与该表单数据匹配的后端处理函数
	$form->set_action('wnd_login');

	$form->set_submit_button('登录', 'is-primary');

	// 以当前函数名设置filter hook
	$form->set_filter(__FUNCTION__);

	// 构造表单
	$form->build();

	// 输出表单
	echo $form->html;


}

/**
 *@since 2019.01.21 注册表单
 */
function _wnd_reg_form($type = 'email') {

	// 已登录
	if (is_user_logged_in()) {
		echo '<script>wnd_alert_msg("已登录！")</script>';
		return;

		//已关闭注册
	} elseif (!get_option('users_can_register')) {
		echo '<script>wnd_alert_msg("站点已关闭注册！")</script>';
		return;

		// 关闭了邮箱注册（强制手机验证）
	} elseif ($type == 'email' and wnd_get_option('wndwp', 'wnd_disable_email_reg') == 1) {

		echo "<script>wnd_alert_msg('当前设置禁止邮箱注册！')</script>";
		return;

		//为开启手机验证
	} elseif ($type == 'sms' and wnd_get_option('wndwp', 'wnd_sms_enable') != 1) {

		echo "<script>wnd_alert_msg('当前未配置短信验证！')</script>";
		return;

	}

	$form = new Wnd_Ajax_Form();

	$form->set_form_title('<span class="icon"><i class="fa fa-user"></i></span>注册');
	$form->set_form_attr('id="user-reg" class="user-form"');

	$form->add_text(
		array(
			'name' => '_user_user_login',
			'has_icons' => 'left',
			'icon' => '<i class="fa fa-user"></i>',
			'required' => true,
			'placeholder' => '用户名',
		)
	);

	$form->add_password(
		array(
			'name' => '_user_user_pass',
			'value' => '',
			'label' => '密码 <span class="required">*</span>',
			'placeholder' => '密码',
			'has_icons' => 'left',
			'icon' => '<i class="fas fa-unlock-alt"></i>',
			'required' => true,
		)
	);

	if ($type == 'sms') {
		$form->add_sms_verify($verify_type = 'reg', wnd_get_option('wndwp', 'wnd_ali_TemplateCode_R'));
	} else {
		$form->add_email_verify($verify_type = 'reg', $template = '');
	}
	if (wnd_get_option('wndwp', 'wnd_agreement_url')) {

		$form->add_html('
		<div class="field">
			<div class="control">
				<label class="checkbox">
					<input type="checkbox" name="agreement" value="agree" checked="checked" required="required">
					我已阅读并同意注册协议<a href="' . wnd_get_option('wndwp', 'wnd_agreement_url') . '" target="_blank">《注册协议》</a>
				</label>
			</div>
		</div>');
	}

	$form->set_action('wnd_reg');
	$form->set_submit_button('注册');

	// 以当前函数名设置filter hook
	$form->set_filter(__FUNCTION__);

	$form->build();

	echo $form->html;


}

/**
 *@since 2019.01.28 找回密码
 */
function _wnd_lostpassword_form($type = 'email') {

	if ($type == 'sms') {
		//1、验证短信重置密码
		if (wnd_get_option('wndwp', 'wnd_sms_enable') != 1) {
			echo '<script type="text/javascript">wnd_alert_msg(\'短信验证功能未启用！\')</script>';
			return;
		}
	}

	$form = new Wnd_Ajax_Form();

	if ($type == 'sms') {
		$form->set_form_title('<span class="icon"><i class="fa fa-phone-square"></i></span>手机验证');
	} else {
		$form->set_form_title('<span class="icon"><i class="fa fa-at"></i></span>邮箱验证</h3>');
	}

	$form->set_form_attr('id="user-lost-password" class="user-form"');

	$form->add_password(
		array(
			'name' => '_user_new_pass',
			'value' => '',
			'label' => '新密码 <span class="required">*</span>',
			'placeholder' => '新密码',
			'has_icons' => 'left',
			'icon' => '<i class="fas fa-unlock-alt"></i>',
			'required' => true,
		)
	);

	$form->add_password(
		array(
			'name' => '_user_new_pass_repeat',
			'value' => '',
			'label' => '确认新密码 <span class="required">*</span>',
			'placeholder' => '确认新密码',
			'has_icons' => 'left',
			'icon' => '<i class="fas fa-unlock-alt"></i>',
			'required' => true,
		)
	);

	if ($type == 'sms') {
		$form->add_sms_verify($verify_type = 'reset-pass', wnd_get_option('wndwp', 'wnd_ali_TemplateCode_R'));
	} else {
		$form->add_email_verify($verify_type = 'reset-pass', $template = '');
	}

	$form->set_action('wnd_reset_password');
	$form->set_submit_button('重置密码');
	$form->build();

	echo $form->html;

}

/*
########################################################################## part2： 已登录用户
 */

/**
 *@since 2019.01.29 用户常规资料表单
 */
function _wnd_profile_form($args = array()) {

	if (!is_user_logged_in()) {
		echo '<script>wnd_alert_msg(\'请登录\')</script>';
		return;
	}
	$user = wp_get_current_user();

	$form = new Wnd_Ajax_Form();

	$form->set_form_attr('id="user-profile"');

	/*头像上传*/
	$defaults = array(
		'id' => 'user-avatar',
		'thumbnail_size' => array('width' => 150, 'height' => 150),
		'thumbnail' => WNDWP_URL . '/static/images/default.jpg',
		'data' => array(
			'meta_key' => 'avatar',
			'save_width' => 200,
			'savve_height' => 200,
		),
	);
	$args = wp_parse_args($args, $defaults);
	$form->add_image_upload($args);

	$form->add_text(
		array(
			'name' => '_user_display_name',
			'value' => $user->display_name,
			'label' => '名称 <span class="required">*</span>',
			'placeholder' => '用户名称',
			'has_icons' => 'left',
			'icon' => '<i class="fas fa-unlock-alt"></i>',
			'required' => true,
		)
	);

	$form->add_text(
		array(
			'name' => '_user_user_url',
			'value' => $user->user_url,
			'label' => '网站',
			'placeholder' => '网站链接',
			'has_icons' => 'left',
			'icon' => '<i class="fas fa-unlock-alt"></i>',
			'required' => false,
		)
	);

	// textarea
	$form->add_textarea(
		array(
			'name' => '_wpusermeta_description',
			'label' => '简介',
			'placeholder' => '简介资料',
			'value' => $user->description,
		)
	);

	$form->set_action('wnd_update_profile');
	$form->set_submit_button('保存');

	// 以当前函数名设置filter hook
	$form->set_filter(__FUNCTION__);
	$form->build();

	echo $form->html;

}

/**
 *@since 2019.01.23 用户更新账户表单
 */
function _wnd_account_form() {
	if (!is_user_logged_in()) {
		echo '<script>wnd_alert_msg(\'请登录\')</script>';
		return;
	}

	$form = new Wnd_Ajax_Form();
	$form->set_form_title('<span class="icon"><i class="fa fa-user"></i></span>账户安全');
	$form->set_form_attr('id="user-account" class="user-form"');

	$form->add_password(
		array(
			'name' => '_user_user_pass',
			'value' => '',
			'label' => '当前密码 <span class="required">*</span>',
			'placeholder' => '当前密码',
			'has_icons' => 'left',
			'icon' => '<i class="fas fa-unlock-alt"></i>',
			'required' => true,
		)
	);

	$form->add_password(
		array(
			'name' => '_user_new_pass',
			'value' => '',
			'label' => '新密码 <span class="required">*</span>',
			'placeholder' => '新密码',
			'has_icons' => 'left',
			'icon' => '<i class="fas fa-unlock-alt"></i>',
			'required' => true,
		)
	);

	$form->add_password(
		array(
			'name' => '_user_new_pass_repeat',
			'value' => '',
			'label' => '确认新密码 <span class="required">*</span>',
			'placeholder' => '确认新密码',
			'has_icons' => 'left',
			'icon' => '<i class="fas fa-unlock-alt"></i>',
			'required' => true,
		)
	);

	if (wnd_get_option('wndwp', 'wnd_sms_enable') == 1) {
		$form->add_sms_verify($verify_type = 'v', wnd_get_option('wndwp', 'wnd_ali_TemplateCode_R'));
	} else {
		$form->add_email_verify($verify_type = 'v', $template = '');
	}

	$form->set_action('wnd_reset_password');
	$form->set_submit_button('保存');
	$form->build();

	echo $form->html;

}
