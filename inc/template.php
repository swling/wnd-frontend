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

	// 已登录用户，显示账户设置
	if(is_user_logged_in()){
		_wnd_account_form();
		return;
	}

    $action = $_GET['action'] ?: $action;
    $type = $_GET['type'] ?: null;

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
		retuen;
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
            'thumb_size' => array('width'=>150, 'height'=>150),
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
					<input type="text" class="input" name="_meta_website" value="<?php echo $user->user_url; ?>">
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

/**
*@since 2019.01.20 
*快速编辑文章状态表单
*/
function _wnd_post_status_form($post_id){

	$post_status = get_post_status( $post_id );
	switch ($post_status) {

		case 'publish':
			$status_text = '已发布';
			break;

		case 'pending':
			$status_text = '待审核';
			break;	

		case 'draft':
			$status_text = '草稿';
			break;

		case false:
			$status_text = '已删除';
			break;					

		default:
			$status_text = $post_status;
			break;
	}

?>
<form id="post-status" action="" method="post" onsubmit="return false">
	<div class="field">
		<div class="ajax-msg"></div>
	</div>
	<div class="field text-centered">
		<label class="radio">
			<input type="radio" class="radio" name="post_status" value="publish" <?php if($post_status=='publish' ) echo ' checked="checked" ' ?>>
			发布
		</label>

		<label class="radio">
			<input type="radio" class="radio" name="post_status" value="draft" <?php if($post_status=='draft') echo ' checked="checked" ' ?>>
			草稿
		</label>

		<label class="radio">
			<input type="radio" class="radio" name="post_status" value="delete">
			<span class="is-danger">删除</span>
		</label>
	</div>
	<?php if(wnd_is_manager()) { ?>
	<div class="field">
		<textarea name="remark" class="textarea" placeholder="备注（可选）"></textarea>
	</div>
	<?php } ?>
	<input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
	<?php wp_nonce_field('wnd_update_post_status', '_ajax_nonce'); ?>
	<input type="hidden" name="action" value="wnd_action">
	<input type="hidden" name="action_name" value="wnd_update_post_status">
	<button type="submit" name="submit" class="button is-primary align-center" onclick="wnd_ajax_submit('#post-status')">确认</button>
</form>
<script>
wnd_ajax_msg("<?php echo '当前： '.$status_text;?>", "is-danger", "#post-status")
</script>
<?php

}

/**
*###################################################### 附件上传表单
*@since 2019.01.16
*/
function _wnd_upload_field($args) {

	$defaults = array(
		'id'=>'upload-file',
		'meta_key' => '',
		'is_image'=> 1,
		'post_parent' => 0,
		'save_size'=>array('width'=>0,'height'=>0),
		'thumb_size'=>array('width'=>200,'height'=>200),
		'default_thumb' => WNDWP_URL . '/static/images/default.jpg',
	);
	$args = wp_parse_args($args,$defaults);

	// 根据user type 查找目标文件
	$attachment_id = wnd_get_post_meta( $args['post_parent'], $args['meta_key']);
	$attachment_id = $attachment_id ?: wnd_get_user_meta( get_current_user_id(), $args['meta_key']);
	$attachment_url = wp_get_attachment_url($attachment_id);

	// 如果文件不存在，例如已被后台删除，删除对应meta key
	if(!$attachment_url){
		if($args['post_parent']){
			wnd_delete_post_meta($args['post_parent'],$args['meta_key']);
		}else{
			wnd_delete_user_meta(get_current_user_id(),$args['meta_key']);
		}
	}

	//根据上传类型，设置默认样式 
	if($args['is_image']==1){
		$attachment_url = $attachment_url ?: $args['default_thumb'];
	}else{
		$attachment_url = $attachment_url ?: '……';
	}

	?>
<div id="<?php echo $args['id'];?>" class="upload-field field">
	<div class="ajax-msg"></div>
	<?php if ($args['is_image'] == 1) { // 1、图片类型，缩略图 ?>
	<div class="field">
		<a onclick="wnd_click('input[data-id=\'<?php echo $args['id'];?>\']')"><img class="thumb" src="<?php echo $attachment_url; ?>" height="<?php echo $args['thumb_size']['height']?>" width="<?php echo $args['thumb_size']['width']?>" title="上传图像"></a>
		<button class="delete" data-id="<?php echo $args['id'];?>" data-attachment-id="<?php echo $attachment_id;?>"></button>
	</div>
	<div class="file">
		<input type="file" class="file-input" name="file[]" accept="image/*" data-id="<?php echo $args['id'];?>" />
	</div>
	<!-- 图片信息 -->
	<input type="hidden" name="file_save_width" value="<?php echo $args['save_size']['width']; ?>" />
	<input type="hidden" name="file_save_height" value="<?php echo $args['save_size']['height']; ?>" />
	<input type="hidden" name="file_default_thumb" value="<?php echo $args['default_thumb'] ?>" />
	<?php } else { ///2、文件上传 ?>
	<div class="columns is-mobile">
		<div class="column">
			<div class="file has-name is-fullwidth">
				<label class="file-label">
					<input type="file" class="file-input" name="file[]" data-id="<?php echo $args['id'];?>" />
					<span class="file-cta">
						<span class="file-icon">
							<i class="fa fa-upload"></i>
						</span>
						<span class="file-label">
							选择文件
						</span>
					</span>
					<span class="file-name">
						<?php echo $attachment_url;?>
					</span>
				</label>
			</div>
		</div>
		<div class="column is-narrow">
			<button class="delete" data-id="<?php echo $args['id'];?>" data-attachment-id="<?php echo $attachment_id;?>"></button>
		</div>
	</div>
	<?php } ?>
	<!-- 自定义属性，用于区分上传用途，方便后端区分处理 -->
	<input type="hidden" name="file_post_parent" value="<?php echo $args['post_parent'] ?>" />
	<input type="hidden" name="file_meta_key" value="<?php echo $args['meta_key']; ?>" />
	<input type="hidden" name="file_is_image" value="<?php if($args['is_image']==1) echo '1'; else echo '0'; ?>" />
	<?php wp_nonce_field('wnd_upload_file','file_upload_nonce');?>
	<?php wp_nonce_field('wnd_delete_attachment','file_delete_nonce');?>
</div>
<?php

}

/**
*@since 2019.01.30 上传付费文件表单字段
*/
function _wnd_paid_file_field($post_parent){

    $defaults = array(
        'id'=>'upload-file',
        'meta_key' => 'file',
        'is_image'=> 0,
        'post_parent' => $post_parent,
    );

    $args = wp_parse_args($args,$defaults);
    _wnd_upload_field($args);
?>
<div class="field">
	<div class="control has-icons-left">
		<input type="number" min="0" step="0.01" class="input" required="required" name="_wpmeta_price" placeholder="下载价格">
		<span class="icon is-left">
			<i class="fa fa-money"></i>
		</span>
	</div>
</div>
<?php
}

/**
*@since 2019.01.31 发布文章通用模板
*/
function _wnd_post_form($args=array()){

	$defaults = array(
		'post_id'	=> 0,
		'post_type' => 'post',
		'is_free'	=> 1,
		'has_excerpt' => 1,
	);
	$args = wp_parse_args($args,$defaults);
	$post_id = $args['post_id'];

	// 未指定id，新建文章，否则为编辑
	if(!$post_id){
		$post_type = $args['post_type'];
    	$post_id = wnd_get_draft_post($post_type = $post_type, $create_new = true, $only_current_user = false, $interval_time = 3600*24 )['msg'];
    }

    // 定义文章数据
    $post = get_post($post_id);
    $post_type = $post->post_type;
    $post = get_post($post_id);

/**
*@since 2019.02.01 
*获取指定 post type的所有注册taxonomy
*当一个taxonomy 关联多个 post type 时，通过指定post type无法获取（应该为WordPress bug）。
*解决办法全部获取，然后遍历taxonomy数据中的 object_type 是否包含当前指定 post type（好在taxonomy通常只有几个或十来个）
*/
$cat_taxonomies = array();
$tag_taxonomies = array();
$taxonomies = get_taxonomies(array('public'=> true), 'object', 'and' );

if ( $taxonomies ) {
  foreach ( $taxonomies  as $taxonomy ) {
  	// 未关联当前分类
  	if(!in_array($post_type,$taxonomy->object_type)){
  		continue;
  	}
  	// 根据是否具有层级，推入分类数组或标签数组
    if( is_taxonomy_hierarchical($taxonomy->name) ){
        array_push($cat_taxonomies, $taxonomy->name);
    }else{
        array_push($tag_taxonomies, $taxonomy->name);
    }
  }unset($taxonomy);
}

?>
<form id="new-post-<?php echo $post_id;?>" name="new_post" method="post" action="" onsubmit="return false;" onkeydown="if(event.keyCode==13){return false;}">
	<div class="field content">
		<h3><span class="icon"><i class="fa fa-edit"></i></span> 发布[ID]: <?php echo $post_id;?></h3>
	</div>	
	<div class="ajax-msg"></div>
	<div class="field">
		<label class="label">标题<span class="required">*</span></label>
		<div class="control">
			<input type="text" class="input" name="_post_post_title" required="required" value="<?php if($post->post_title!=='Auto-draft') echo $post->post_title;?>" placeholder="标题">
		</div>
	</div>

<?php
if($cat_taxonomies){
echo '<div class="field is-horizontal"><div class="field-body">'.PHP_EOL;
	 //遍历分类 
	foreach ($cat_taxonomies as $cat_taxonomy ) {
		$cat = get_taxonomy($cat_taxonomy);
    	// 获取当前文章已选择分类ID
    	$current_cat = get_the_terms($post_id, $cat_taxonomy);
    	$current_cat = $current_cat ? reset($current_cat) : 0;
    	$current_cat_id = $current_cat ? $current_cat->term_id : 0;
?>
	<div class="field">
		<label for="cat" class="label"><?php echo $cat->labels->name;?><span class="required">*</span></label>
		<div class="select">
			<?php wp_dropdown_categories('show_option_none=—选择'.$cat->labels->name.' * —&required=true&name=_term_'.$cat_taxonomy.'&taxonomy='.$cat_taxonomy.'&orderby=name&hide_empty=0&hierarchical=1&selected=' . $current_cat_id );?>
		</div>
	</div>
<?php

	}unset($cat_taxonomy);
echo '</div></div>'.PHP_EOL;
}
?>

<?php
	// 遍历标签
	foreach ($tag_taxonomies as $tag_taxonomy ) {
		// 排除WordPress原生 文章格式类型
		if($tag_taxonomy == 'post_format'){
			continue;
		}
		$tag = get_taxonomy($tag_taxonomy);
?>
	<div class="field">
		<label class="label"><?php echo $tag->labels->name;?></label>
		<div class="control">
			<input type="text" id="tags" class="input" name="_term_<?php echo $tag_taxonomy; ?>" value="<?php wnd_post_terms_text($post_id, $tag_taxonomy);?>" >
		</div>
	</div>
<?php 	
	}unset($tag_taxonomy); 
?>	

<?php if($args['has_excerpt']==1) { //摘要 ?>
	<div class="field">
		<label class="label">摘要</label>
		<div class="control">
			<textarea name="_post_post_excerpt" class="textarea" placeholder="摘要"><?php echo $post->post_excerpt;?></textarea>
		</div>
	</div>
<?php } ?>

<?php if($args['is_free']!=1 and !wp_doing_ajax()) { //付费内容 
		echo '<label class="label">付费内容</label>';
		_wnd_paid_file_field($post_id);
	}
?>
<?php do_action( 'wnd_post_form', $post_id,$post_type,$post ); ?>

	<div class="field">
<?php 
	// 正文详情
	if(wp_doing_ajax()){

		echo '<textarea class="textarea" name="_post_post_content" placeholder="详情"></textarea>';

	}else {

        if(isset($post)){
        	$post = $post;
        	wp_editor( $post->post_content, '_post_post_content','media_buttons=1');
        }else{
        	wp_editor( $post->post_content, '_post_post_content','media_buttons=0');
        }
	}
?>
	</div>

	<input type="hidden" name="_post_post_type" value="<?php echo $post_type;?>">
	<?php if($post_id) { ?>
	<input type="hidden" name="_post_post_id" value="<?php echo $post_id; ?>">
	<?php }?>
	<input type="hidden" name="action" value="wnd_action">
	<input type="hidden" name="action_name" value="wnd_insert_post">
	<?php wp_nonce_field('wnd_insert_post', '_ajax_nonce'); ?>
	<div class="field is-grouped is-grouped-centered">
		<button name="submit" class="button is-dark" onclick="wnd_ajax_submit('#new-post-<?php echo $post_id?>')">提交</button>
	</div>	
</form>
<?php

}

/**
*@since ≈2018.07
*###################################################### 表单设置：标签编辑器
*/
function wnd_tags_editor($maxTags=3, $maxLength=10, $placeholder='标签', $taxonomy='' ,$initialTags='' ){

?>
<!--jquery标签编辑器 Begin-->
<script src="<?php echo WNDWP_URL.'static/js/jquery.tag-editor.js' ?>"></script>
<script src="<?php echo WNDWP_URL.'static/js/jquery.caret.min.js' ?>"></script>
<link rel="stylesheet" href="<?php echo WNDWP_URL.'static/css/jquery.tag-editor.css' ?>">
<script>
jQuery(document).ready(function($) {
	$('#tags').tagEditor({
		//自动提示 
		autocomplete: {
			delay: 0,
			position: {
				collision: 'flip'
			},
			source: [<?php if($taxonomy) wnd_terms_text($taxonomy,100);?>]
			// source: ['ActionScript', 'AppleScript', 'Asp', 'BASIC']  //demo
		},
		forceLowercase: false,
		placeholder: '<?php echo $placeholder; ?>',
		maxTags: '<?php echo $maxTags; ?>', //最多标签个数
		maxLength: '<?php echo $maxLength; ?>', //单个标签最长字数
		onChange: function(field, editor, tags) {
			// alert("变了");
		},
		// 预设标签
		initialTags: [<?php if($initialTags) echo $initialTags; ?>],
		// initialTags: ['ActionScript', 'AppleScript', 'Asp', 'BASIC'], //demo
	});
});
</script>
<?php    
    
}

// ###################################################################################
// 以文本方式输出当前文章标签、分类名称 主要用于前端编辑器输出形式： tag1, tag2, tag3
function wnd_post_terms_text($post_id, $taxonomy) {
	$terms = wp_get_object_terms($post_id, $taxonomy );
	if (!empty($terms)) {
		if (!is_wp_error($terms)) {
		    $terms_list = '';
			foreach ($terms as $term) {
				$terms_list .= $term->name . ',';
			}

			// 移除末尾的逗号
			echo rtrim( $terms_list,",");
		}
	}
}

//###################################################################################
 // 以文本方式列出热门标签，分类名称 用于标签编辑器，自动提示文字： 'tag1', 'tag2', 'tag3'
function wnd_terms_text($taxonomy,$number){

	$terms = get_terms( $taxonomy, 'orderby=count&order=DESC&hide_empty=0&number='.$number );
	if (!empty($terms)) {
		if (!is_wp_error($terms)) {
		    $terms_list = '';
			foreach ($terms as $term) {
				$terms_list .= '\''.$term->name . '\',';
			}

			// 移除末尾的逗号
			echo rtrim( $terms_list,",");
		}
	}	

}