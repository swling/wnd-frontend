<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 初始化短信发送表单field
 *参数：$verity_type='reg' 即为注册操作，注册操作会检测手机是否已经注册，反之如果为 lostpassword 则不能发送给未注册用户
 */
function _wnd_sms_field($verity_type = 'verify', $template = '') {

?>
<div class="field is-horizontal">
	<div class="field-body">
		<?php if (!wnd_get_user_phone(get_current_user_id())) {?>
		<div class="field">
			<div class="control has-icons-left">
				<input id="sms-phone" class="input" required="required" type="text" name="phone" placeholder="手机号码">
				<span class="icon is-left"><i class="fa fa-phone-square"></i></span>
			</div>
		</div>
		<?php }?>
		<div class="field has-addons">
			<div class="control is-expanded has-icons-left">
				<input id="sms-code" required="required" type="text" class="input" name="v_code" placeholder="验证码">
				<span class="icon is-left"><i class="fa fa-comment"></i></span>
			</div>
			<div class="control">
				<button type="button" class="send-code button is-primary" data-verity-type="<?php echo $verity_type; ?>" data-template="<?php echo $template; ?>" data-nonce="<?php echo wp_create_nonce('wnd_ajax_send_code') ?>" data-send-type="sms">获取验证码</button>
			</div>
		</div>
	</div>
</div>
<?php

}

/**
 *@since 2019.02.10 邮箱验证表单字段
 */
function _wnd_mail_field($verity_type = 'v', $template = '') {
	
?>
<div class="field">
	<div class="field">
		<label class="label">邮箱 <span class="required">*</span></label>
		<div class="control has-icons-left">
			<input type="text" class="input" required="required" name="_user_user_email" placeholder="常用电子邮箱">
			<span class="icon is-left">
				<i class="fa fa-at"></i>
			</span>
		</div>
	</div>
	<div class="field has-addons">
		<div class="control is-expanded has-icons-left">
			<input required="required" type="text" class="input" name="v_code" placeholder="邮箱验证码">
			<span class="icon is-left"><i class="fa fa-key"></i></span>
		</div>
		<div class="control">
			<button type="button" class="button is-primary send-code" data-verity-type="<?php echo $verity_type; ?>" data-template="<?php echo $template; ?>" data-nonce="<?php echo wp_create_nonce('wnd_ajax_send_code') ?>" data-send-type="email">发送验证码</button>
		</div>
	</div>
</div>
<?php

}