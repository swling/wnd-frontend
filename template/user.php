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

	echo '<div id="user-center">';

	//1、 未登录用户面板
	if (!is_user_logged_in()) {

		switch ($action) {

		case 'reg':
			_wnd_reg_form();
			break;

		case 'login':
			_wnd_login_form();
			break;

		case 'lostpassword':
			$type = $_GET['type'] ?? null;
			if ($type == 'sms') {
				_wnd_lostpassword_form('sms');
			} else {
				_wnd_lostpassword_form('email');
			}
			break;

		default:
			_wnd_login_form();
			break;

		}

		//2、已登录用户面板
	} else {

		switch ($tab) {

		case 'profile':
			_wnd_profile_form();
			break;

		case 'account':
			_wnd_account_form();
			break;

		default:
			_wnd_profile_form();
			break;

		}

	}

	echo '</div>';

}

/**
 *@since 2019.01.13 登录框
 */
function _wnd_login_form() {
	// 已登录
	if (is_user_logged_in()) {
		echo '<script>wnd_alert_msg("已登录！")</script>';
		return;
	}
	// 获取来源地址
	$redirect_to = $_SERVER['HTTP_REFERER'] ?? home_url();

	// ajax请求类型
	$ajax_type = $_POST['ajax_type'] ?? 'modal';
	?>
<form id="user-login" class="user-form" action="" method="post" onsubmit="return false">
	<div class="field is-grouped is-grouped-centered content">
		<h3><span class="icon"><i class="fa fa-user"></i></span>登录</h3>
	</div>
	<div class="field">
		<div class="ajax-msg"></div>
	</div>
	<div class="field">
		<label class="label">用户名 <span class="required">*</span></label>
		<div class="control has-icons-left">
			<input type="text" class="input" required="required" name="_user_user_login" placeholder="用户名、手机、邮箱">
			<span class="icon is-left">
				<i class="fa fa-user"></i>
			</span>
		</div>
	</div>
	<div class="field">
		<label class="label">密码 <span class="required">*</span></label>
		<div class="control has-icons-left">
			<input type="password" class="input" required="required" name="_user_user_pass" placeholder="密码">
			<span class="icon is-left">
				<i class="fa fa-unlock-alt"></i>
			</span>
		</div>
	</div>
	<?php do_action('_wnd_login_form');?>
	<input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>">
	<?php wp_nonce_field('wnd_login', '_ajax_nonce');?>
	<input type="hidden" name="action" value="wnd_action">
	<input type="hidden" name="action_name" value="wnd_login">
	<div class="field is-grouped is-grouped-centered">
		<button class="button" name="submit" onclick="wnd_ajax_submit('#user-login')">登录</button>
	</div>
	<div class="field">
		<div class="message is-primary">
			<div class="message-body">
	<?php if (wp_doing_ajax()) {
		if ($ajax_type == 'modal') {
			echo '没有账户？<a onclick="wnd_ajax_modal(\'reg_form\');">立即注册</a> | ';
			echo '<a onclick="wnd_ajax_modal(\'lostpassword_form\');">忘记密码？</a>';
		} else {
			echo '没有账户？<a onclick="wnd_ajax_embed(\'#user-center\',\'reg_form\');">立即注册</a> | ';
			echo '<a onclick="wnd_ajax_embed(\'#user-center\',\'lostpassword_form\');">忘记密码</a>';
		}
	} else {
		echo '没有账户？<a href="' . add_query_arg('action', 'reg') . '">立即注册</a> | ';
		echo '<a href="' . add_query_arg('action', 'lostpassword') . '">忘记密码？</a>';
	}?>
			</div>
		</div>
	</div>
</form>
<?php

}

/**
 *@since 2019.01.21 注册表单
 */
function _wnd_reg_form() {
	// 已登录
	if (is_user_logged_in()) {
		echo '<script>wnd_alert_msg("已登录！")</script>';
		return;
		//已关闭注册
	} elseif (!get_option('users_can_register')) {
		echo '<script>wnd_alert_msg("站点已关闭注册！")</script>';
		return;
	}

	// ajax请求类型
	$ajax_type = $_POST['ajax_type'] ?? 'modal';

	?>
<form id="user-reg" class="user-form" action="" method="post" onsubmit="return false">
	<div class="field is-grouped is-grouped-centered content">
		<h3 class="text-centered"><span class="icon"><i class="fa fa-user"></i></span>注册</h3>
	</div>
	<div class="field">
		<div class="ajax-msg"></div>
	</div>
	<div class="field">
		<label class="label">用户名 <span class="required">*</span></label>
		<div class="control has-icons-left">
			<input type="text" class="input" required="required" name="_user_user_login" placeholder="登录用户名">
			<span class="icon is-left">
				<i class="fa fa-user"></i>
			</span>
		</div>
	</div>
	<?php _wnd_mail_field($type = 'reg', $template = '');?>
	<div class="field">
		<label class="label">密码 <span class="required">*</span></label>
		<div class="control has-icons-left">
			<input type="password" class="input" required="required" name="_user_user_pass" placeholder="登录密码">
			<span class="icon is-left">
				<i class="fa fa-unlock-alt"></i>
			</span>
		</div>
	</div>
	<?php do_action('_wnd_reg_form');?>
	<?php if (wnd_get_option('wndwp', 'wnd_sms_enable') == 1) {
		_wnd_sms_field($type = 'reg', wnd_get_option('wndwp', 'wnd_ali_TemplateCode_R'));
	}
	?>
	<?php wp_nonce_field('wnd_reg', '_ajax_nonce');?>
	<input type="hidden" name="action" value="wnd_action">
	<input type="hidden" name="action_name" value="wnd_reg">
	<?php if (wnd_get_option('wndwp', 'wnd_agreement_url')) {?>
	<div class="field">
		<div class="control">
			<label class="checkbox">
				<input type="checkbox" name="agreement" value="agree" checked="checked" required="required">
				我已阅读并同意注册协议<a href="<?php echo wnd_get_option('wndwp', 'wnd_agreement_url'); ?>" target="_blank">《注册协议》</a>
			</label>
		</div>
	</div>
	<?php }?>
	<div class="field is-grouped is-grouped-centered">
		<button type="button" name="submit" class="button" onclick="wnd_ajax_submit('#user-reg')">注册</button>
	</div>
	<div class="field">
		<div class="message is-primary">
			<div class="message-body">
	<?php if (wp_doing_ajax()) {
		//是否在ajax中
		if ($ajax_type == 'modal') {
			echo '已有账户？<a onclick="wnd_ajax_modal(\'login_form\');">登录</a>';
		} else {
			echo '已有账户？<a onclick="wnd_ajax_embed(\'#user-center\',\'login_form\');">登录</a>';
		}
	} else {
		echo '已有账户？<a href="' . add_query_arg('action', 'login') . '">登录</a>';
	}?>
			</div>
		</div>
	</div>
</form>
<script>
</script>
<?php

}

/**
 *@since 2019.01.28 找回密码
 */
function _wnd_lostpassword_form($type = 'email') {

	// ajax请求类型
	$ajax_type = $_POST['ajax_type'] ?? 'modal';

	if ($type == 'sms') {
		//1、验证短信重置密码
		if (wnd_get_option('wndwp', 'wnd_sms_enable') != 1) {
			echo '<script type="text/javascript">wnd_alert_msg(\'短信验证功能未启用！\')</script>';
			return;
		}
		?>
<form id="sms-reset-pass" class="user-form" action="" method="post" onsubmit="return false">
	<div class="field content">
		<h3><span class="icon"><i class="fa fa-phone"></i></span>手机验证</h3>
	</div>
	<div class="field">
		<div class="ajax-msg"></div>
	</div>
	<div class="field">
		<label class="label">新密码 <span class="required">*</span></label>
		<div class="control has-icons-left">
			<input type="password" class="input" required="required" name="_user_new_pass" placeholder="新密码">
			<span class="icon is-left">
				<i class="fa fa-unlock-alt"></i>
			</span>
		</div>
	</div>
	<div class="field">
		<label class="label">确认新密码 <span class="required">*</span></label>
		<div class="control has-icons-left">
			<input type="password" class="input" required="required" name="_user_new_pass_repeat" placeholder="确认新密码">
			<span class="icon is-left">
				<i class="fa fa-unlock-alt"></i>
			</span>
		</div>
	</div>
	<?php _wnd_sms_field($type = 'reset-pass', wnd_get_option('wndwp', 'wnd_ali_TemplateCode_V'));?>
	<?php wp_nonce_field('wnd_reset_password', '_ajax_nonce');?>
	<input type="hidden" name="action" value="wnd_action">
	<input type="hidden" name="action_name" value="wnd_reset_password">
	<div class="field is-grouped is-grouped-centered">
		<button type="button" name="submit" class="button" onclick="wnd_ajax_submit('#sms-reset-pass')">重置密码</button>
	</div>
	<div class="field">
		<div class="message is-primary">
			<div class="message-body">
		<?php if (wp_doing_ajax()) {
			if ($ajax_type == 'modal') {
				echo '<a onclick="wnd_ajax_modal(\'lostpassword_form\');">邮箱验证找回</a> | ';
				echo '<a onclick="wnd_ajax_modal(\'login_form\');">登录</a>';
			} else {
				echo '<a onclick="wnd_ajax_embed(\'#user-center\',\'lostpassword_form\');">邮箱验证找回</a> | ';
				echo '<a onclick="wnd_ajax_embed(\'#user-center\',\'login_form\');">马上登陆</a>';
			}
		} else {
			echo '<a href="' . add_query_arg('type', 'email') . '">邮箱验证找回</a> | ';
			echo '<a href="' . add_query_arg('action', 'login') . '">登录</a>';
		}?>
			</div>
		</div>
	</div>
</form>
<?php } else {
		//2、验证邮箱重置密码 ?>
<form id="email-reset-pass" class="user-form" action="" method="post" onsubmit="return false">
	<div class="field content">
		<h3><span class="icon"><i class="fa fa-at"></i></span>邮箱验证</h3>
	</div>
	<div class="field">
		<div class="ajax-msg"></div>
	</div>
	<?php _wnd_mail_field($type = 'reset-pass', $template = '');?>
	<div class="field">
		<label class="label">新密码 <span class="required">*</span></label>
		<div class="control has-icons-left">
			<input type="password" class="input" required="required" name="_user_new_pass" placeholder="新密码">
			<span class="icon is-left">
				<i class="fa fa-unlock-alt"></i>
			</span>
		</div>
	</div>
	<div class="field">
		<label class="label">确认新密码 <span class="required">*</span></label>
		<div class="control has-icons-left">
			<input type="password" class="input" required="required" name="_user_new_pass_repeat" placeholder="确认新密码">
			<span class="icon is-left">
				<i class="fa fa-unlock-alt"></i>
			</span>
		</div>
	</div>
	<?php wp_nonce_field('wnd_reset_password', '_ajax_nonce');?>
	<input type="hidden" name="action" value="wnd_action">
	<input type="hidden" name="action_name" value="wnd_reset_password">
	<div class="field is-grouped is-grouped-centered">
		<button type="button" name="submit" class="button" onclick="wnd_ajax_submit('#email-reset-pass')">重置密码</button>
	</div>
	<div class="field">
		<div class="message is-primary">
			<div class="message-body">
		<?php if (wp_doing_ajax()) {
			if ($ajax_type == 'modal') {
				if (wnd_get_option('wndwp', 'wnd_sms_enable') == 1) {
					echo '<a onclick="wnd_ajax_modal(\'lostpassword_form\',\'sms\');">手机验证找回</a> | ';
				}
				echo '<a onclick="wnd_ajax_modal(\'login_form\');">马上登录</a>';
			} else {
				if (wnd_get_option('wndwp', 'wnd_sms_enable') == 1) {
					echo '<a onclick="wnd_ajax_embed(\'#user-center\',\'lostpassword_form\',\'sms\');">手机验证找回</a> | ';
				}
				echo '<a onclick="wnd_ajax_embed(\'#user-center\',\'login_form\');">登录</a>';
			}
		} else {
			if (wnd_get_option('wndwp', 'wnd_sms_enable') == 1) {
				echo '<a href="' . add_query_arg('type', 'sms') . '">手机验证找回</a> | ';
			}
			echo '<a href="' . add_query_arg('action', 'login') . '">登录</a>';
		}?>
			</div>
		</div>
	</div>
</form>
<?php }?>
<?php

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

	// ajax请求类型
	$ajax_type = $_POST['ajax_type'] ?? 'modal';

	?>
<div class="tabs">
	<ul class="tab">
	<?php if (wp_doing_ajax()) {
		if ($ajax_type == 'modal') {
			echo '<li class="is-active"><a onclick="wnd_ajax_modal(\'profile_form\');">资料</a></li>';
			echo '<li><a onclick="wnd_ajax_modal(\'account_form\');">账户</a></li>';
		} else {
			echo '<li class="is-active"><a onclick="wnd_ajax_embed(\'#user-center\',\'profile_form\',\'sms\');">资料</a></li>';
			echo '<li><a onclick="wnd_ajax_embed(\'#user-center\',\'account_form\',\'sms\');">账户</a></li>';
		}
	} else {
		echo '<li class="is-active"><a href="' . add_query_arg('tab', 'profile') . '">资料</a></li>';
		echo '<li><a href="' . add_query_arg('tab', 'account') . '">账户</a></li>';
	}?>
	</ul>
</div>
<form id="profile-form" action="" method="post" onsubmit="return false" onkeydown="if(event.keyCode==13){return false;}">

	<div class="field">
		<div class="ajax-msg"></div>
	</div>
	<div class="field">
	<?php
/*头像上传*/
	$defaults = array(
		'id' => 'user-avatar',
		'is_image' => 1,
		'meta_key' => 'avatar',
		'post_parent' => 0,
		'save_size' => array('width' => 200, 'height' => 200),
		'thumbnail_size' => array('width' => 130, 'height' => 130),
		'default_thumbnail' => WNDWP_URL . '/static/images/default.jpg',
	);
	$args = wp_parse_args($args, $defaults);
	_wnd_upload_field($args);
	?>
	</div>
	<div class="field is-horizontal">
		<div class="field-body">
			<div class="field">
				<label for="name" class="label">名称<span class="required">*</span></label>
				<div class="control">
					<input type="text" class="input" required="required" name="_user_display_name"	value="<?php echo $user->display_name; ?>">
				</div>
			</div>
			<div class="field">
				<label for="website" class="label">网站</label>
				<div class="control">
					<input type="text" class="input" name="_user_user_url" value="<?php echo $user->user_url; ?>">
				</div>
			</div>
		</div>
	</div>
	<?php do_action('_wnd_profile_form', $user);?>
	<div class="field">
		<label for="description" class="label">简介</label>
		<div class="control">
			<textarea class="textarea" name="_wpusermeta_description" rows="5" cols="60"><?php echo esc_html($user->description); ?></textarea>
		</div>
	</div>
	<?php wp_nonce_field('wnd_update_profile', '_ajax_nonce');?>
	<input type="hidden" name="action" value="wnd_action">
	<input type="hidden" name="action_name" value="wnd_update_profile">
	<div class="field is-grouped is-grouped-centered">
		<button type="button" name="submit" class="button" onclick="wnd_ajax_submit('#profile-form')">保存</button>
	</div>

</form>
<?php
}

/**
 *@since 2019.01.23 用户更新账户表单
 */
function _wnd_account_form() {
	if (!is_user_logged_in()) {
		echo '<script>wnd_alert_msg(\'请登录\')</script>';
		return;
	}
	$user = wp_get_current_user();

	// ajax请求类型
	$ajax_type = $_POST['ajax_type'] ?? 'modal';
	?>
<div class="tabs">
	<ul class="tab">
	<?php if (wp_doing_ajax()) {
		if ($ajax_type == 'modal') {
			echo '<li><a onclick="wnd_ajax_modal(\'profile_form\');">资料</a></li>';
			echo '<li class="is-active"><a onclick="wnd_ajax_modal(\'account_form\');">账户</a></li>';
		} else {
			echo '<li><a onclick="wnd_ajax_embed(\'#user-center\',\'profile_form\',\'sms\');">资料</a></li>';
			echo '<li class="is-active"><a onclick="wnd_ajax_embed(\'#user-center\',\'account_form\',\'sms\');">账户</a></li>';
		}
	} else {
		echo '<li><a href="' . add_query_arg('tab', 'profile') . '">资料</a></li>';
		echo '<li class="is-active"><a href="' . add_query_arg('tab', 'account') . '">账户</a></li>';
	}?>
	</ul>
</div>
<form id="user-account" class="user-form" action="" method="post" onsubmit="return false">
	<div class="field is-grouped is-grouped-centered content">
		<h3><span class="icon"><i class="fa fa-user"></i></span>账户安全</h3>
	</div>
	<div class="field">
		<div class="ajax-msg"></div>
	</div>
	<div class="field">
		<label class="label">当前密码<span class="required">*</span></label>
		<div class="control has-icons-left">
            <input type="password" class="input" name="_user_user_pass" required="required">
            <span class="icon is-left">
                <i class="fa fa-unlock-alt"></i>
            </span>
        </div>
	</div>
	<div class="field">
		<label class="label">新密码 <span class="required">*</span></label>
		<div class="control has-icons-left">
			<input type="password" class="input" required="required" name="_user_new_pass" placeholder="登录密码">
			<span class="icon is-left">
				<i class="fa fa-unlock-alt"></i>
			</span>
		</div>
	</div>
	<div class="field">
		<label class="label">确认新密码 <span class="required">*</span></label>
		<div class="control has-icons-left">
			<input type="password" class="input" required="required" name="_user_new_pass_repeat" placeholder="确认密码">
			<span class="icon is-left">
				<i class="fa fa-unlock-alt"></i>
			</span>
		</div>
	</div>
	<div class="field">
		<label class="label">电子邮件<span class="required">*</span></label>
		<div class="control has-icons-left">
			<input type="text" class="input" required="required" name="_user_user_email" value="<?php echo $user->user_email; ?>">
            <span class="icon is-left">
                <i class="fa fa-at"></i>
            </span>
		</div>
	</div>
	<?php if (wnd_get_option('wndwp', 'wnd_sms_enable') == 1) {
		_wnd_sms_field($type = 'v', wnd_get_option('wndwp', 'wnd_ali_TemplateCode_V'));
	}
	?>
	<?php wp_nonce_field('wnd_update_account', '_ajax_nonce');?>
	<input type="hidden" name="action" value="wnd_action">
	<input type="hidden" name="action_name" value="wnd_update_account">
	<div class="field is-grouped is-grouped-centered">
		<button type="button" name="submit" class="button" onclick="wnd_ajax_submit('#user-account')">保存</button>
	</div>
</form>
<?php
}