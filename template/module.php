<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 2019.01.21 充值表单
 */
function _wnd_recharge_form() {

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
<form id="recharge" action="<?php echo wnd_get_do_url(); ?>?action=payment" method="post">
	<div class="field">
		<div class="ajax-msg"></div>
	</div>
	<div class="radio-toolbar field level content">
		<div class="level-item">
			<input id="radio1" required="required" name="money" type="radio" value="0.01" checked="checked">
			<label for="radio1">¥0.01</label>
		</div>

		<div class="level-item">
			<input id="radio2" required="required" name="money" type="radio" value="10">
			<label for="radio2">¥10</label>
		</div>

		<div class="level-item">
			<input id="radio3" required="required" name="money" type="radio" value="100">
			<label for="radio3">¥100</label>
		</div>

		<div class="level-item">
			<input id="radio4" required="required" name="money" type="radio" value="500">
			<label for="radio4">¥500</label>
		</div>

		<div class="level-item">
			<input id="radio5" required="required" name="money" type="radio" value="1000">
			<label for="radio5">¥1000</label>
		</div>
	</div>
	<div class="paytype field level is-mobile">
		<div class="level-item">
			<label for="paytype1"><img src="https://t.alipayobjects.com/images/T1HHFgXXVeXXXXXXXX.png"></label>
			<input type="radio" name="paytype" value="alipay" checked="checked" />
		</div>
	</div>
	<?php do_action('_wnd_recharge_form');?>
	<?php wp_nonce_field('wnd_payment');?>
	<div class="field is-grouped is-grouped-centered">
		<button type="submit" name="submit" class="button">确认充值</button>
	</div>
</form>
<?php

}

/**
 *@since 初始化短信发送表单field
 *参数：$type='reg' 即为注册操作，注册操作会检测手机是否已经注册，反之如果为 lostpassword 则不能发送给未注册用户
 */
function _wnd_sms_field($type = 'verify', $template = '') {

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
				<button type="button" class="send-code button is-primary" data-type="<?php echo $type; ?>" data-template="<?php echo $template; ?>" data-nonce="<?php echo wp_create_nonce('wnd_send_code') ?>">获取验证码</button>
			</div>
		</div>
	</div>
</div>
<?php

}

/**
 *@since 2019.02.10 邮箱验证表单字段
 */
function _wnd_mail_field($type = 'v', $template = '') {
	?>
<div class="field">
	<label class="label">Email <span class="required">*</span></label>
	<div class="control has-icons-left">
		<input type="text" class="input" required="required" name="_user_user_email" placeholder="注册邮箱">
		<span class="icon is-left">
			<i class="fa fa-at"></i>
		</span>
	</div>
</div>
<?php
// 如果开启了短信验证，在注册时则验证短信，不重复验证邮箱以简化流程
if ($type != 'reg' or wnd_get_option('wndwp', 'wnd_sms_enable') != 1) {
?>
<div class="field has-addons">
	<div class="control is-expanded has-icons-left">
		<input required="required" type="text" class="input" name="v_code" placeholder="邮箱验证码">
		<span class="icon is-left"><i class="fa fa-key"></i></span>
	</div>
	<div class="control">
		<button type="button" class="button is-primary send-code" data-type="<?php echo $type; ?>" data-template="<?php echo $template; ?>" data-nonce="<?php echo wp_create_nonce('wnd_send_code') ?>">发送验证码</button>
	</div>
</div>
<?php }?>
<?php

}
