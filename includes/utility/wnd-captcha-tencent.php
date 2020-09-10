<?php
namespace Wnd\Utility;
use Exception;

/**
 *@since 2020.08.11
 *验证码后端校验
 *
 *@link captcha核验 https://cloud.tencent.com/document/product/1110/36926
 *@link 公共参数  https://cloud.tencent.com/document/api/1110/36920
 */
class Wnd_Captcha_Tencent extends Wnd_Captcha {

	// 腾讯云API Secret ID
	protected $secret_id;

	// 腾讯云API Secret Key
	protected $secret_key;

	public function __construct() {
		$this->url        = 'https://captcha.tencentcloudapi.com';
		$this->secret_id  = wnd_get_config('tencent_secretid');
		$this->secret_key = wnd_get_config('tencent_secretkey');

		parent::__construct();
	}

	/**
	 * 请求服务器验证
	 */
	public function validate() {
		$params = [
			// 公共参数
			'Action'       => 'DescribeCaptchaResult',
			'Timestamp'    => time(),
			'Nonce'        => wnd_random_code(6, true),
			'Version'      => '2019-07-22',

			/**
			 *在 云API密钥 上申请的标识身份的 SecretId，一个 SecretId 对应唯一的 SecretKey ，而 SecretKey 会用来生成请求签名 Signature。
			 *@link https://console.cloud.tencent.com/capi
			 */
			'SecretId'     => $this->secret_id,

			// 验证码参数
			'CaptchaType'  => 9,
			'CaptchaAppId' => $this->appid,
			'AppSecretKey' => $this->appkey,
			'Ticket'       => $this->captcha,
			'Randstr'      => $this->captcha_nonce,
			'UserIp'       => $this->user_ip,
		];

		// 参数签名
		$params['Signature'] = $this->sign($params);

		//获取响应报文
		$Response = wp_remote_post($this->url, ['body' => $params]);
		if (is_wp_error($Response)) {
			throw new Exception($Response->get_error_message());
		}

		// 提取校验结果
		$result = json_decode($Response['body'], true);
		if ($result['Response']['Error'] ?? false) {
			throw new Exception($result['Response']['Error']['Code'] . ':' . $result['Response']['Error']['Message']);
		}

		if (1 != $result['Response']['CaptchaCode']) {
			throw new Exception($result['Response']['CaptchaCode'] . ':' . $result['Response']['CaptchaMsg']);
		}
	}

	/**
	 *@link https://cloud.tencent.com/document/api/1110/36922
	 *签名
	 */
	protected function sign(array $param): string{
		ksort($param);

		$signStr = $_SERVER['REQUEST_METHOD'] . 'captcha.tencentcloudapi.com/?';
		foreach ($param as $key => $value) {
			$signStr = $signStr . $key . '=' . $value . '&';
		}
		$signStr = substr($signStr, 0, -1);

		return base64_encode(hash_hmac('sha1', $signStr, $this->secret_key, true));
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
						$("#" + form_id + " [name=\'captcha\']").val(res.ticket);
						$("#" + form_id + " [name=\'captcha_nonce\']").val(res.randstr);
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
