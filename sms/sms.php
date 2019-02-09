<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 *@since 初始化 引入阿里云 短信SDK
 */
require WNDWP_PATH . 'sms/aliyun-sms/sendSms.php';

/**
 *@since 初始化短信发送表单field
 *参数：$type='reg' 即为注册操作，注册操作会检测手机是否已经注册，反之如果为 lostpassword 则不能发送给未注册用户
 */
function wnd_sms_field($template = '', $type = 'verify') {

	// 注册验证，将手机存入用户字段
	if ($type=='reg') {
		$phone_name = 'sms_phone';
	} else {
		$phone_name = '_meta_phone';
	}
?>
<div class="field is-horizontal">
	<div class="field-body">
		<?php if (!wnd_get_user_phone(get_current_user_id())) {?>
		<div class="field">
			<div class="control has-icons-left">
		    	<input id="sms-phone" class="input" required="required" type="text" name="<?php echo $phone_name; ?>" placeholder="手机号码" />
				<span class="icon is-left"><i class="fa fa-phone-square"></i></span>
		    </div>
		</div>
		<?php }?>
		<div class="field has-addons">
		    <div class="control is-expanded has-icons-left">
		    	<input id="sms-code" required="required" type="text" class="input" name="sms_code" placeholder="验证码"/>
		    	<span class="icon is-left"><i class="fa fa-comment"></i></span>
			</div>
			<div class="control">
		    	<span id="sendSmsBtn" class="button is-primary">获取验证码</span>
			</div>
		</div>
	</div>
</div>
<?php wp_nonce_field('wnd_ajax_send_sms', '_sms_nonce');?>
<input type="hidden" name="sms_type"  value="<?php echo $type; ?>">
<input type="hidden" name="sms_template" value="<?php echo $template; //阿里云短信模板 为设置则调用默认模板   ?>">
<input type="hidden" name="action"  value="wnd_action">
<script>
	var wait = 90; // 获取验证码短信时间间隔 按钮不能恢复 可检查号码
    function countdown() {
        if (wait > 0) {
            $("#sendSmsBtn").text(wait + "秒");
            wait--;
            setTimeout(countdown, 1000);
        } else {
            $("#sendSmsBtn").text("获取验证码").attr("disabled", false).fadeTo("slow", 1);
            wait = 90;
        }
    }

    $("#sendSmsBtn").off("click").on("click", function() {
    	var parent = '#' + $(this).parents('form').attr('id');
        var sms_type = $("input[name=sms_type]").val();
        var sms_template = $("input[name=sms_template]").val();
        var sms_nonce = $("input[name=_sms_nonce]").val();    	

        if ($("#sms-phone").length > 0) {
            var sms_phone = $("#sms-phone").val();
            if (sms_phone == '' || !sms_phone.match(/^(((13[0-9]{1})|(15[0-9]{1})|(17[0-9]{1})|(18[0-9]{1}))+\d{8})$/)) {
                wnd_ajax_msg("手机格式不正确！",'is-danger',parent);
                $("#sms_phone").focus();
                setTimeout(function() {
                    $("#sendSmsBtnErr").slideUp()
                }, 3000);
                return;
            }
        //发送给已知用户，后端获取用户手机号
        }else{
            var sms_phone = '';
        }

        $.ajax({
            type: "post",
            dataType: "json",
            url: ajaxurl,
            data: {
                action: 'wnd_action',
                action_name: "wnd_ajax_send_sms",
                sms_phone: sms_phone,
                sms_type: sms_type,
                sms_template: sms_template,
                _ajax_nonce: sms_nonce
            },
            beforeSend: function() {
            	$("#sendSmsBtn").addClass("is-loading");
            },
            success: function(response) {
                if (response.status === 0) {
                    wnd_ajax_msg(response.msg,"is-danger",parent);
                } else {
                    $("#sendSmsBtn").attr("disabled", true).fadeTo("slow", 0.5);
                    countdown();
                }
                $("#sendSmsBtn").removeClass("is-loading");
            },
            // 错误
            error: function() {
                wnd_ajax_msg("发送失败！","is-danger",parent);
                $("#sendSmsBtn").removeClass("is-loading");
            }
        });

    });
</script>
<?php

}

/**
 *@since 初始化
 *通过ajax发送短信
 *点击发送按钮，通过js获取表单填写的手机号，检测并发送短信
 */
function wnd_ajax_send_sms() {

	// 此处通过 sms.js单独提取wnd_sms_field数据，非整体提交表单，故命名规则取决于sms.js ajax定义
	$type = $_POST['sms_type'];
	$phone = trim($_POST['sms_phone']);
	$template = $_POST['sms_template'] ?: wnd_get_option('wndwp', 'wnd_ali_TemplateCode');

	// 给指定用户发送短信
	if ( wnd_get_user_phone(get_current_user_id()) ) {
		return wnd_send_sms_to_user($template, $type);
	}

	// 未登录、未验证手机的用户
	return wnd_send_sms($phone, $template, $type);
}

/**
 *@since 初始化
 *通过给当前用户发送短信
 *点击发送按钮，通过js获取表单填写的手机号，检测并发送短信
 */
function wnd_send_sms_to_user($template, $type) {

	// 获取当前用户的手机号码
	$user_id = get_current_user_id();
	$phone = wnd_get_user_phone($user_id);
	if (!$phone) {
		return array('status' => 0, 'msg' => '未能获取到手机号码！');
	}

	return wnd_send_sms($phone, $template, $type);
}

/**
 *@since 初始化
 *发送手机短信
 **/
function wnd_send_sms($phone, $template, $type) {

	// 权限检测
	$wnd_can_send_sms = wnd_can_send_sms($phone, $type);
	if ($wnd_can_send_sms['status'] === 0) {
		return $wnd_can_send_sms;
	}

	// 通过检测，发送短信
	$code = generateCode();
	// 写入手机记录
	if (wnd_insert_sms($phone, $code)) {

		$send_status = sendSms($phone, $code, $template);
		if ($send_status->Code == "OK") {
			$value = array('status' => 1, 'msg' => '发送成功！');
		} else {
			$value = array('status' => 0, 'msg' => '系统错误，请联系客服处理！');
		}

	} else {
		$value = array('status' => 0, 'msg' => '数据库写入失败！');
	}

	return $value;

}

// ###################################################################### 检测
function wnd_can_send_sms($phone, $type) {

	$send_time = wnd_get_sms_sendtime($phone); //获取改号码上一次发送时间

	//发送前检测
	if (!isPhone($phone)) {
		return array('status' => 0, 'msg' => '手机号码不正确！');
	}

	// 检测是否为注册类型，注册类型去重
	if ($type=='reg' and wnd_get_user_by_phone($phone)) {
		return array('status' => 0, 'msg' => '该号码已注册过！');
	}

	// 找回密码
	if ($type == 'lostpassword' and !wnd_get_user_by_phone($phone)) {
	    return array('status' => 0, 'msg' => '该号码尚未注册过！');
	}
	

	// 上次发送短信的时间，防止短信攻击
	if ($send_time and (time() - $send_time < 90)) {
		return array('status' => 0, 'msg' => '操作太频繁，请稍后！');
	}

}

/**
 *校验短信验证
 *@since 初始化
 *@return array
 */
function wnd_verify_sms($phone, $code, $type) {
	global $wpdb;
	$errors = false;

	if (empty($code)) {
		$errors = "校验失败：请填写短信验证码！";
	}

	if (empty($phone)) {
		$errors = "校验失败：请填写手机号！";

		// 在 wnd_send_sms() 前端初步验证的基础上，再次检验手机号码，防止客户通过匿名方式获取验证码，绕道注册
	} elseif ($type=='reg' && wnd_get_user_by_phone($phone)) {

		$errors = "校验失败：手机号码已注册过！";

	} else {
		// 清空该号码十分钟前的code
		wnd_clear_sms($phone);
		$sys_code = $wpdb->get_var($wpdb->prepare("SELECT code FROM $wpdb->wnd_user WHERE phone = %s;", $phone));

		if (empty($sys_code)) {
			$errors = "校验失败：请先获取短信验证码！";
		} elseif ($code != $sys_code) {
			$errors = "校验失败：短信验证码不正确！";
		}

	}

	if ($errors) {

		$value = array('status' => 0, 'msg' => $errors);

	} else {

		$value = array('status' => 1, 'msg' => '验证通过！');

		/**
		 *@since 2019.01.22 非注册类校验完成，清空当前手机验证码
		 */
		if ($type!=='reg') {
			wnd_reset_sms($phone, $reg_user_id = 0);
		}

	}

	return $value;

}

/**
 *@since 初始化 用户注册后
 *校验完成后，重置验证码数据
 */
add_action('user_register', 'wnd_reset_reg_sms');
function wnd_reset_reg_sms($user_id) {

	// 注册类，将注册用户id写入对应数据表
	if (isset($_POST['sms_phone'])) {
		$phone = $_POST['sms_phone'];
		wnd_reset_sms($phone, $user_id);
	}
}

/**
 *##############################################################################  函数封装
*
*/

/** 
 *@since 初始化
 *生成6位随机数字
 */
function generateCode() {

	$No='';
	for ($i = 0; $i<6; $i++) 
	{
	    $No .= mt_rand(0,9);
	}
	return $No;

}

// 验证是否为手机号
function isPhone($phone) {
	if ((empty($phone) || !preg_match("/^(((13[0-9]{1})|(15[0-9]{1})|(17[0-9]{1})|(18[0-9]{1}))+\d{8})$/", $phone))) {
		return 0;
	} else {
		return 1;
	}

}

function wnd_insert_sms($phone, $code) {
	global $wpdb;
	$ID = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->wnd_user WHERE phone = %s", $phone));

	if ($ID) {
		$db = $wpdb->update($wpdb->wnd_user, array('code' => $code, 'time' => time()), array('phone' => $phone), array('%s', '%d'), array('%s'));
	} else {
		$db = $wpdb->insert($wpdb->wnd_user, array('phone' => $phone, 'code' => $code, 'time' => time()), array('%s', '%s', '%d'));
	}

	if ($db) {
		return true;
	}

}

function wnd_get_sms_sendtime($phone) {
	global $wpdb;
	$time = $wpdb->get_var($wpdb->prepare("SELECT time FROM $wpdb->wnd_user WHERE phone = %s;", $phone));
	if ($time) {
		return $time;
	} else {
		return 0;
	}

}

/**
*@since 2019.01.26 根据用户id获取号码
*/
function wnd_get_user_phone($user_id) {

	if(!$user_id){
		return false;
	}

	global $wpdb;
	$phone = $wpdb->get_var($wpdb->prepare("SELECT phone FROM $wpdb->wnd_user WHERE user_id = %d;", $user_id));
	if ($phone) {
		return $phone;
	} else {
		return false;
	}

}

/**
*@since 2019.01.28 根据号码查询用户
*/
function wnd_get_user_by_phone($phone) {
	global $wpdb;
	$user_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $wpdb->wnd_user WHERE phone = %s;", $phone));
	if ($user_id) {
		return $user_id;
	} else {
		return false;
	}

}

// 清空号码十分钟前的code
function wnd_clear_sms($phone) {
	global $wpdb;
	$wpdb->query($wpdb->prepare("update $wpdb->wnd_user  SET code='' WHERE phone=%s AND time < %s ", $phone, (time() - 600)));
}

// 号码已完成对应验证后
function wnd_reset_sms($phone, $reg_user_id) {
	global $wpdb;
	// 手机注册用户
	if ($reg_user_id) {
		$wpdb->update(
			$wpdb->wnd_user,
			array('code' => '', 'time' => time(), 'user_id' => $reg_user_id),
			array('phone' => $phone),
			array('%s', '%d', '%d'),
			array('%s')
		);
		//其他操作
	} else {
		$wpdb->update(
			$wpdb->wnd_user,
			array('code' => '', 'time' => time()),
			array('phone' => $phone),
			array('%s', '%d'),
			array('%s')
		);
	}

}
