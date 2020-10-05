<?php
namespace Wnd\Utility;

use Exception;
use Wnd\Component\Qcloud\SignatureTrait;

/**
 *@since 2020.08.11
 *验证码后端校验
 *
 *@link captcha核验 https://cloud.tencent.com/document/product/1110/36926
 *@link 公共参数  https://cloud.tencent.com/document/api/1110/36920
 */
class Wnd_Captcha_Tencent extends Wnd_Captcha {

	// 引入腾讯云 API 签名及请求特性
	use SignatureTrait;

	public function __construct() {
		$this->endpoint   = 'captcha.tencentcloudapi.com';
		$this->secret_id  = wnd_get_config('tencent_secretid');
		$this->secret_key = wnd_get_config('tencent_secretkey');

		parent::__construct();
	}

	/**
	 * 请求服务器验证
	 */
	public function validate() {
		$this->params = [
			// 公共参数
			'Action'       => 'DescribeCaptchaResult',
			'Timestamp'    => time(),
			'Nonce'        => wnd_random_code(6, true),
			'Version'      => '2019-07-22',
			'SecretId'     => $this->secret_id,

			// 验证码参数
			'CaptchaType'  => 9,
			'CaptchaAppId' => $this->appid,
			'AppSecretKey' => $this->appkey,
			'Ticket'       => $this->captcha,
			'Randstr'      => $this->captcha_nonce,
			'UserIp'       => $this->user_ip,
		];

		// 发起请求
		$result = $this->request();

		// 核查响应
		if ($result['Response']['Error'] ?? false) {
			throw new Exception($result['Response']['Error']['Code'] . ':' . $result['Response']['Error']['Message']);
		}

		if (1 != $result['Response']['CaptchaCode']) {
			throw new Exception($result['Response']['CaptchaCode'] . ':' . $result['Response']['CaptchaMsg']);
		}
	}

	/**
	 *验证码人机验证脚本
	 */
	public function render_send_code_script(): string{
		$script = '
		<script>
		function wnd_send_code_via_captcha(_this){
			var captcha = new TencentCaptcha(
				"' . $this->appid . '",
				function(res) {
					if(0 === res.ret){
						_this.data("' . static::$captcha_name . '", res.ticket);
						_this.data("' . static::$captcha_nonce_name . '", res.randstr);
						wnd_send_code(_this);
					}
				}
			)
			captcha.show();
		}

		// 绑定点击事件
		$(function() {
			$(".send-code").click(function() {
				var _this = $(this);
				var form_id = _this.closest("form").attr("id");
				var email = _this.closest(".validate-field-wrap").find("input[name=\'_user_user_email\']").val();
				var phone = _this.closest(".validate-field-wrap").find("input[name=\'phone\']").val();
				if (!email && !phone) {
					wnd_ajax_msg(wnd.msg.required, "is-warning", "#" + form_id);
					return false;
				}

				if (typeof TencentCaptcha == "undefined") {
					$.getScript("https://ssl.captcha.qq.com/TCaptcha.js", function() {
						wnd_send_code_via_captcha(_this);
					});
				}else{
					wnd_send_code_via_captcha(_this);
				}
			});
		});
		</script>';
		return $script;
	}

	/**
	 *表单提交人机验证
	 *@since 0.8.64
	 *
	 *@since 0.8.73
	 *必须指定 form[captcha='1'] 选择器，否则页面请求一次后JavaScript已在当前页面生效，会影响其他表单
	 */
	public function render_submit_form_script(): string{
		$script = '
		<script>
		function wnd_submit_form_via_captcha(form_id, ajax_submit){
			var captcha = new TencentCaptcha(
				"' . $this->appid . '",
				function(res) {
					if(0 !== res.ret){
						return false;
					}

					$("#" + form_id + " [name=\'' . static::$captcha_name . '\']").val(res.ticket);
					$("#" + form_id + " [name=\'' . static::$captcha_nonce_name . '\']").val(res.randstr);

					if (ajax_submit){
						wnd_ajax_submit(form_id);
					} else {
						$("#" + form_id).submit();
					}
				}
			);

			captcha.show();
		}

		// 绑定点击事件
		$(function() {
			$("form [type=\'submit\'].captcha, form#commentform [type=\'submit\']").click(function() {
				// 当 button 的 id 或 name 为 "submit" 时，JavaScript submit() 将无法提交表单
				$(this).prop("id","");
				var form_id = $(this).closest("form").attr("id");
				var ajax_submit = (-1 != $(this).prop("class").indexOf("ajax-submit"));

				if ("undefined" == typeof TencentCaptcha) {
					$.getScript("https://ssl.captcha.qq.com/TCaptcha.js", function() {
						wnd_submit_form_via_captcha(form_id, ajax_submit);
					});
				}else{
					wnd_submit_form_via_captcha(form_id, ajax_submit);
				}

				return false;
			});
		});
		</script>';
		return $script;
	}
}
