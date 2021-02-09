<?php
namespace Wnd\Getway\Captcha;

use Exception;
use Wnd\Component\Qcloud\SignatureTrait;
use Wnd\Utility\Wnd_Captcha;

/**
 *@since 2020.08.11
 *验证码后端校验
 *
 *@link captcha核验 https://cloud.tencent.com/document/product/1110/36926
 *@link 公共参数  https://cloud.tencent.com/document/api/1110/36920
 */
class Tencent extends Wnd_Captcha {

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
function wnd_send_code_via_captcha(_this) {
    var captcha = new TencentCaptcha(
        "' . $this->appid . '",
        function(res) {
            if (0 !== res.ret) {
                _this.classList.remove("is-loading");
                return false;
            }

            _this.dataset.' . static::$captcha_name . ' = res.ticket;
            _this.dataset.' . static::$captcha_nonce_name . ' = res.randstr;
            wnd_send_code(_this);
        }
    )
    captcha.show();
}

// 绑定点击事件
document.addEventListener("click", function(e) {
    if (e.target.getAttribute("class").includes("send-code")) {
        var _this = e.target;
        var form = _this.closest("form");
        var device = form.querySelector("input[name=\'_user_user_email\']") || form.querySelector("input[name=\'phone\']");
        var device_value = device.value || "";
        if (!device_value) {
            wnd_form_msg(form, wnd.msg.required, "is-warning");
            return false;
        }

        _this.classList.add("is-loading");
        if (typeof TencentCaptcha == "undefined") {
            wnd_load_script("https://ssl.captcha.qq.com/TCaptcha.js", function() {
                wnd_send_code_via_captcha(_this);
            });
        } else {
            wnd_send_code_via_captcha(_this);
        }
    }
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
function wnd_submit_via_captcha(_this) {
    var captcha = new TencentCaptcha(
        "' . $this->appid . '",
        function(res) {
            if (0 !== res.ret) {
                _this.classList.remove("is-loading");
                return false;
            }

            let form = _this.closest("form");
            form.querySelector("[name=\'' . static::$captcha_name . '\']").value = res.ticket;
            form.querySelector("[name=\'' . static::$captcha_nonce_name . '\']").value = res.randstr;

            wnd_ajax_submit(_this);
        }
    )
    captcha.show();
}

// 绑定点击事件
document.addEventListener("click", function(e) {
    if ("submit" == e.target.getAttribute("type")) {
        var _this = e.target;
        _this.classList.add("is-loading");
        if (typeof TencentCaptcha == "undefined") {
            wnd_load_script("https://ssl.captcha.qq.com/TCaptcha.js", function() {
                wnd_submit_via_captcha(_this);
            });
        } else {
            wnd_submit_via_captcha(_this);
        }
    }
});
		</script>';
		return $script;
	}
}
