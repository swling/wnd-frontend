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
function wnd_send_code_via_captcha(e) {
    var button = e.target;
    var form = button.closest("form");
    var device = form.querySelector("input[name=\'_user_user_email\']") || form.querySelector("input[name=\'phone\']");
    var device_value = device.value || "";
    if (!device_value) {
        wnd_form_msg(form, wnd.msg.required, "is-warning");
        return false;
    }
    button.classList.add("is-loading");
    if (typeof TencentCaptcha == "undefined") {
        wnd_load_script("https://ssl.captcha.qq.com/TCaptcha.js", function() {
            captcha_send(button);
        });
    } else {
        captcha_send(button);
    }

     function captcha_send(button){
        var captcha = new TencentCaptcha(
            "' . $this->appid . '",
            function(res) {
                if (0 !== res.ret) {
                    button.classList.remove("is-loading");
                    return false;
                }

                button.dataset.' . static::$captcha_name . ' = res.ticket;
                button.dataset.' . static::$captcha_nonce_name . ' = res.randstr;
                wnd_send_code(button);
            }
        );
        captcha.show();
    }
}

// 绑定点击事件
var sd_btn = document.querySelectorAll("button.send-code");
if (sd_btn) {
    sd_btn.forEach(function(btn) {
        btn.addEventListener("click", function(e) {
            wnd_send_code_via_captcha(e);
        });
    });
}
</script>';
		return $script;
	}

	/**
	 *表单提交人机验证
	 *@since 0.8.64
	 *JavaScript 函数 [wnd_submit_via_captcha] 将会在前端渲染中被引用，因此函数名称及传参必须保持一致
	 */
	public function render_submit_form_script(): string{
		$script = '
<script>
function wnd_submit_via_captcha(e, callback = false) {
	let button = e.target;
    button.classList.add("is-loading");
    if (typeof TencentCaptcha == "undefined") {
        wnd_load_script("https://ssl.captcha.qq.com/TCaptcha.js", function() {
            captcha_submit(button, callback);
        });
    } else {
        captcha_submit(button, callback);
    }

    function captcha_submit(button, callback){
        var captcha = new TencentCaptcha(
            "' . $this->appid . '",
            function(res) {
                if (0 !== res.ret) {
                    button.classList.remove("is-loading");
                    return false;
                }

                let form = button.closest("form");
				let captcha =  form.querySelector("[name=\'' . static::$captcha_name . '\']");
                let captcha_nonce =  form.querySelector("[name=\'' . static::$captcha_nonce_name . '\']");
				// 设置表单值
        		captcha.value = res.ticket;
                captcha_nonce.value = res.randstr;
				// 设置事件触发 VUE 数据同步
				captcha.dispatchEvent(new Event("input"));
				captcha_nonce.dispatchEvent(new Event("input"));

                // 设置 captcha 后执行回调函数或再次点击按钮，再次点击按钮应做条件判断以免死循环
                if (callback) {
                    window[callback](button);
                }else{
					button.click();
				}
            }
        );
        captcha.show();
    }
}

// 非 Vue 表单绑定 Submit 点击事件
var sub_btn = document.querySelectorAll("[type=submit]");
if (sub_btn) {
    sub_btn.forEach(function(btn) {
        btn.addEventListener("click", function(e) {
            wnd_submit_via_captcha(e, "wnd_ajax_submit");
			e.preventDefault();
        });
    });
}
</script>';
		return $script;
	}
}
