<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** 
*@see
*自定义一些标准模块以便在页面或ajax请求中快速调用
*函数均以echo直接输出返回
*以_wnd_做前缀的函数可用于ajax请求，无需nonce校验，因此相关模板函数中不应有任何数据库操作，仅作为界面响应输出。
*/

/**
*@since 2019.01.28 用户登录、注册表单、找回密码，账户更新（已登录时）
*@param $action :login/reg/lostpassword/
*/
function _wnd_user_form($action = 'reg'){

	// 已登录用户，显示资料设置
	if(is_user_logged_in()){
		_wnd_profile_form();
		return;
	}

    $action = $_GET['action'] ?? $action;
    $type = $_GET['type'] ?? null;

    //登录
    if($action=='login'){
        _wnd_login_form();

    //找回密码 
    }elseif($action == 'lostpassword'){
    	if($type == 'sms')
     		_wnd_lostpassword_form('sms');
     	else
     		_wnd_lostpassword_form('email');

    //注册 
    }else{
        _wnd_reg_form();
    }
}

/**
*@since 2019.01.13 登录框
*/
function _wnd_login_form(){
	// 已登录
	if(is_user_logged_in()){
		echo '<script>wnd_alert_msg("已登录！")</script>';
		return;
	}
	// 获取来源地址
	$redirect_to = $_SERVER['HTTP_REFERER'] ?? home_url();
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
	<?php wp_nonce_field('wnd_user_login', '_ajax_nonce'); ?>
	<input type="hidden" name="action" value="wnd_action">
	<input type="hidden" name="action_name" value="wnd_user_login">
	<div class="field is-grouped is-grouped-centered">
		<button type="submit" name="submit" class="button is-dark" onclick="wnd_ajax_submit('#user-login')">登录</button>
	</div>
	<div class="field">
		<div class="message is-primary">
			<div class="message-body">
				<?php if(wp_doing_ajax()) { //是否在ajax中  ?>
				没有账户？<a onclick="wnd_ajax_modal('reg_form');">立即注册</a> |
				<a onclick="wnd_ajax_modal('lostpassword_form');">忘记密码？</a>
				<?php }else{ ?>
				没有账户？<a href="<?php echo add_query_arg('action','reg')?>">立即注册</a> |
				<a href="<?php echo add_query_arg('action','lostpassword')?>">忘记密码？</a>
				<?php }?>
			</div>
		</div>
	</div>
</form>
<?php

}

/**
*@since 2019.01.21 注册表单
*/
function _wnd_reg_form(){
	// 已登录
	if(is_user_logged_in()){
		echo '<script>wnd_alert_msg("已登录！")</script>';
		return;
		//已关闭注册 
	} elseif(!get_option( 'users_can_register')){
		echo '<script>wnd_alert_msg("站点已关闭注册！")</script>';
		return;
	}
?>
<form id="user-reg" class="user-form" action="" method="post" onsubmit="return false">
	<div class="field is-grouped is-grouped-centered content">
		<h3 class="text-centered"><span class="icon"><i class="fa fa-user"></i></span>注册</h3>
	</div>
	<div class="field">
		<div class="ajax-msg"></div>
	</div>
	<?php if(!function_exists('wnd_sms_field')) { ?>
	<div class="field">
		<label class="label">用户名 <span class="required">*</span></label>
		<div class="control has-icons-left">
			<input type="text" class="input" required="required" name="_user_user_login" placeholder="登录用户名">
			<span class="icon is-left">
				<i class="fa fa-user"></i>
			</span>
		</div>
	</div>
	<?php } ?>
	<div class="field">
		<label class="label">Email <span class="required">*</span></label>
		<div class="control has-icons-left">
			<input type="text" class="input" required="required" name="_user_user_email" placeholder="常用的电子邮箱">
			<span class="icon is-left">
				<i class="fa fa-at"></i>
			</span>
		</div>
	</div>
	<div class="field">
		<label class="label">密码 <span class="required">*</span></label>
		<div class="control has-icons-left">
			<input type="password" class="input" required="required" name="_user_user_pass" placeholder="登录密码">
			<span class="icon is-left">
				<i class="fa fa-unlock-alt"></i>
			</span>
		</div>
	</div>
	<div class="field">
		<label class="label">确认密码 <span class="required">*</span></label>
		<div class="control has-icons-left">
			<input type="password" class="input" required="required" name="_user_user_pass_repeat" placeholder="确认密码">
			<span class="icon is-left">
				<i class="fa fa-unlock-alt"></i>
			</span>
		</div>
	</div>
	<?php do_action('_wnd_reg_form');?>
	<?php if(function_exists('wnd_sms_field')) wnd_sms_field(wnd_get_option('wndwp','wnd_ali_TemplateCode_R'), $type='reg'); ?>
	<?php wp_nonce_field('wnd_insert_user', '_ajax_nonce'); ?>
	<input type="hidden" name="action" value="wnd_action">
	<input type="hidden" name="action_name" value="wnd_insert_user">
	<div class="field is-grouped is-grouped-centered">
		<button type="submit" name="submit" class="button is-dark" onclick="wnd_ajax_submit('#user-reg')">注册</button>
	</div>
	<div class="field">
		<div class="message is-primary">
			<div class="message-body">
				<?php if(wp_doing_ajax()) { //是否在ajax中  ?>
				已有账户？<a onclick="wnd_ajax_modal('login_form');">马上登录</a>
				<?php }else{ ?>
				已有账户？<a href="<?php echo add_query_arg('action','login')?>">马上登录</a>
				<?php }?>
			</div>
		</div>
	</div>
</form>
<?php

}

/**
*@since 2019.01.23 用户更新账户表单
*/
function _wnd_account_form(){
    if(!is_user_logged_in()){
        echo '<script>wnd_alert_msg(\'请登录\')</script>';
        return;
    }    
	$user= wp_get_current_user();
?>
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
	<?php if(function_exists('wnd_sms_field')) wnd_sms_field(wnd_get_option('wndwp','wnd_ali_TemplateCode_V')); ?>
	<?php wp_nonce_field('wnd_update_account', '_ajax_nonce'); ?>
	<input type="hidden" name="action" value="wnd_action">
	<input type="hidden" name="action_name" value="wnd_update_account">
	<div class="field is-grouped is-grouped-centered">
		<button name="submit" class="button is-dark" onclick="wnd_ajax_submit('#user-account')">保存</button>
	</div>
</form>
<?php	
}

/**
*@since 2019.01.28 邮箱找回密码
*/
function _wnd_lostpassword_form($type='email'){
?>
<?php if($type=='sms'){ //1、验证短信重置密码 ?>
<?php if (!function_exists('wnd_sms_field')) { 
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
	<?php wnd_sms_field(wnd_get_option('wndwp','wnd_ali_TemplateCode_V'), 'lostpassword');?>
	<?php wp_nonce_field('wnd_reset_password_by_sms', '_ajax_nonce'); ?>
	<input type="hidden" name="action" value="wnd_action">
	<input type="hidden" name="action_name" value="wnd_reset_password_by_sms">
	<div class="field is-grouped is-grouped-centered">
		<button name="submit" class="button is-dark" onclick="wnd_ajax_submit('#sms-reset-pass')">重置密码</button>
	</div>
	<div class="field">
		<div class="message is-primary">
			<div class="message-body">
				<?php if(wp_doing_ajax()) { //是否在ajax中  ?>
				<a onclick="wnd_ajax_modal('lostpassword_form');">邮箱验证找回</a> | 
				<a onclick="wnd_ajax_modal('login_form');">马上登录</a>
				<?php } else {?>
				<a href="<?php echo add_query_arg('type','email')?>">邮箱验证找回</a> | 
				<a href="<?php echo add_query_arg('action','login',remove_query_arg('type'))?>">马上登录</a>
				<?php } ?>
			</div>
		</div>
	</div>
</form>
<?php } else {  //2、验证邮箱重置密码 ?>
<form id="email-reset-pass" class="user-form" action="" method="post" onsubmit="return false">
	<div class="field content">
		<h3><span class="icon"><i class="fa fa-at"></i></span>邮箱验证</h3>
	</div>
	<div class="field">
		<div class="ajax-msg"></div>
	</div>
	<div class="field">
		<label class="label">Email <span class="required">*</span></label>
		<div class="control has-icons-left">
			<input type="text" class="input" required="required" name="_user_user_email" placeholder="注册邮箱">
			<span class="icon is-left">
				<i class="fa fa-at"></i>
			</span>
		</div>
	</div>
	<div class="field has-addons">
		<div class="control is-expanded has-icons-left">
			<input required="required" type="text" class="input" name="_user_reset_key" placeholder="邮箱验证秘钥">
			<span class="icon is-left"><i class="fa fa-key"></i></span>
		</div>
		<div class="control">
			<span id="sendMailBtn" class="button is-danger">获取秘钥</span>
		</div>
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
	<?php wp_nonce_field('wnd_reset_password_by_email', '_ajax_nonce'); ?>
	<input type="hidden" name="action" value="wnd_action">
	<input type="hidden" name="action_name" value="wnd_reset_password_by_email">
	<div class="field is-grouped is-grouped-centered">
		<button name="submit" class="button is-dark" onclick="wnd_ajax_submit('#email-reset-pass')">重置密码</button>
	</div>
	<div class="field">
		<div class="message is-primary">
			<div class="message-body">
				<?php if(wp_doing_ajax()) { //是否在ajax中  ?>
				<?php if(function_exists('wnd_sms_field')){echo '<a onclick="wnd_ajax_modal(\'lostpassword_form\',\'sms\');">手机验证找回</a> | ';}?>
				<a onclick="wnd_ajax_modal('login_form');">登录</a>
				<?php } else {?>
				<?php if(function_exists('wnd_sms_field')){echo '<a href="'.add_query_arg('type','sms').'">手机验证找回</a> | ';}?>
				<a href="<?php echo add_query_arg('action','login',remove_query_arg('type'))?>">马上登录</a>
				<?php } ?>
			</div>
		</div>
	</div>
</form>
<script>
var wait = 120; // 获取验证码短信时间间隔 按钮不能恢复 可检查号码
function countdown() {
	if (wait > 0) {
		$("#sendMailBtn").text(wait + "秒");
		wait--;
		setTimeout(countdown, 1000);
	} else {
		$("#sendMailBtn").attr("disabled", false).fadeTo("slow", 1);
		wait = 120;
	}
}

$("#sendMailBtn").off("click").on("click", function() {
	var email = $("#email-reset-pass input[name=_user_user_email]").val();
	if (email == '') {
		wnd_ajax_msg("未能获取到email地址！", "is-danger", "#email-reset-pass");
		return false;
	}

	$.ajax({
		type: "post",
		dataType: "json",
		url: ajaxurl,
		data: {
			action: 'wnd_action',
			action_name: "_wnd_get_password_reset_key",
			email: email
		},
		beforeSend: function() {
			$("#sendMailBtn").addClass("is-loading");
		},
		success: function(response) {
			if (response.status === 0) {
				wnd_ajax_msg(response.msg, "is-danger", "#email-reset-pass");
			} else {
				$("#sendMailBtn").attr("disabled", true).fadeTo("slow", 0.5);
				countdown();
			}
			$("#sendMailBtn").removeClass("is-loading");
		},
		// 错误
		error: function() {
			wnd_ajax_msg("发送失败！", "is-danger", "#email-reset-pass");
			$("#sendMailBtn").removeClass("is-loading");
		}
	});

});
</script>
<?php } ?>
<?php

}

/**
*@since 2019.01.29 用户常规资料表单
*/
function _wnd_profile_form($args=array()){
    if(!is_user_logged_in()){
        echo '<script>wnd_alert_msg(\'请登录\')</script>';
        return;
    }
    $user = wp_get_current_user();
?>
<form id="profile-form" action="" method="post" onsubmit="return false" onkeydown="if(event.keyCode==13){return false;}">
	<div class="field">
		<div class="ajax-msg"></div>
	</div>
	<div class="field">
		<?php
        /*头像上传*/
        $defaults = array(
            'id' => 'user-avatar',
            'is_image'=> 1,
            'meta_key' => 'avatar',
            'post_parent' => 0,
            'save_size' => array('width'=>200, 'height'=>200),
            'thumb_size' => array('width'=>130, 'height'=>130),
            'default_thumb' => WNDWP_URL . '/static/images/default.jpg',
        );
        $args = wp_parse_args( $args, $defaults);
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
	<?php do_action('_wnd_profile_form',$user);?>
	<div class="field">
		<label for="description" class="label">简介</label>
		<div class="control">
			<textarea class="textarea" name="_wpusermeta_description" rows="5" cols="60"><?php echo esc_html($user->description); ?></textarea>
		</div>
	</div>
	<?php wp_nonce_field('wnd_update_profile', '_ajax_nonce'); ?>
	<input type="hidden" name="action" value="wnd_action">
	<input type="hidden" name="action_name" value="wnd_update_profile">
	<div class="field is-grouped is-grouped-centered">
		<button name="submit" class="button is-dark" onclick="wnd_ajax_submit('#profile-form')">保存</button>
	</div>

</form>
<?php 
}


/**
*@since 2019.01.21 充值表单
*/
function _wnd_recharge_form(){
?>
<style>
/*单选样式优化*/
.radio-toolbar,
.paytype {
	display: flex;
	align-items: center;
	justify-content: center;
}

.radio-toolbar input[type="radio"] {
	display: none;
}

.radio-toolbar label {
	display: inline-block;
	cursor: pointer;
	border-radius: 3px;
	background: #f5f5f5;
	text-align: center;
}

.radio-toolbar label {
	font-size: 18px;
	padding: 10px 20px;
	margin: 1.5%;
	min-width: 80px;
}

.radio-toolbar input[type="radio"]:checked+label {
	background-color: #00d1b2;
	color: #FFF;
}
</style>
<form id="recharge" action="<?php echo wnd_get_do_url(); ?>?action=recharge" method="post">
	<div class="field">
		<div class="ajax-msg"></div>
	</div>
	<div class="radio-toolbar field recharge">
		<input id="radio1" required="required" name="money" type="radio" value="0.01" checked="checked" />
		<label for="radio1">¥0.01</label>

		<input id="radio2" required="required" name="money" type="radio" value="10">
		<label for="radio2">¥10</label>

		<input id="radio3" required="required" name="money" type="radio" value="100">
		<label for="radio3">¥100</label>

		<input id="radio4" required="required" name="money" type="radio" value="500">
		<label for="radio4">¥500</label>

		<input id="radio5" required="required" name="money" type="radio" value="1000">
		<label for="radio5">¥1000</label>
	</div>
	<div class="paytype field">
		<label for="paytype1"><img src="https://t.alipayobjects.com/images/T1HHFgXXVeXXXXXXXX.png"></label>
		<input type="radio" name="paytype" value="alipay" checked="checked" />
	</div>
	<?php do_action('_wnd_recharge_form');?>
	<?php wp_nonce_field('wnd_recharge'); ?>
	<div class="field is-grouped is-grouped-centered">
		<button type="submit" name="submit" class="button is-dark">确认充值</button>
	</div>
</form>
<?php

}
