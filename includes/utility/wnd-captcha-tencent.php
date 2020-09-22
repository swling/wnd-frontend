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
						_this.data("captcha", res.ticket);
						_this.data("captcha_nonce", res.randstr);
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
	 */
	public function render_submit_form_script(): string{
		$script = '
		<script>
		function wnd_submit_form_via_captcha(form_id){
			var captcha = new TencentCaptcha(
				"' . $this->appid . '",
				function(res) {
					if(0 === res.ret){
						$("#" + form_id + " [name=\'' . static::$captcha_name . '\']").val(res.ticket);
						$("#" + form_id + " [name=\'' . static::$captcha_nonce_name . '\']").val(res.randstr);
						wnd_ajax_submit(form_id);
					}
				}
			)
			captcha.show();
		}

		// 绑定点击事件
		$(function() {
			$("[type=\'submit\'].ajax-submit").click(function() {
				var form_id = $(this).closest("form").attr("id");
				if (typeof TencentCaptcha == "undefined") {
					$.getScript("https://ssl.captcha.qq.com/TCaptcha.js", function() {
						wnd_submit_form_via_captcha(form_id);
					});
				}else{
					wnd_submit_form_via_captcha(form_id);
				}
			});
		});
		</script>';
		return $script;
	}
}
