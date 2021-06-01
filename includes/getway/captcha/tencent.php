<?php
namespace Wnd\Getway\Captcha;

use Exception;
use Wnd\Getway\Wnd_Captcha;
use Wnd\Getway\Wnd_Cloud_API;

/**
 *@since 2020.08.11
 *验证码后端校验
 *
 *@link captcha核验 https://cloud.tencent.com/document/product/1110/36926
 *@link 公共参数  https://cloud.tencent.com/document/api/1110/36920
 */
class Tencent extends Wnd_Captcha {

	/**
	 * 请求服务器验证
	 */
	public function validate() {
		$url  = 'https://captcha.tencentcloudapi.com';
		$args = [
			'headers' => [
				'X-TC-Action'  => 'DescribeCaptchaResult',
				'X-TC-Version' => '2019-07-22',
			],
			'body'    => json_encode([
				'CaptchaType'  => 9,
				'CaptchaAppId' => (int) $this->appid,
				'AppSecretKey' => $this->appkey,
				'Ticket'       => $this->captcha,
				'Randstr'      => $this->captcha_nonce,
				'UserIp'       => $this->user_ip,
			]),
		];

		// 发起请求
		$request = Wnd_Cloud_API::get_instance('Qcloud');
		$result  = $request->request($url, $args);

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
    let button = e.target;
    let form = button.closest("form");

    let device = form.querySelector("input[name=\'_user_user_email\']") || form.querySelector("input[name=\'phone\']");
    let device_value = device.value || "";
    if (!device_value) {
		device.classList.add("is-danger");
        return false;
    }

    button.classList.add("is-loading");

	// 已设置 Captcha：可能为修改后重复请求
	if(button.dataset.' . static::$captcha_name . '){
		wnd_send_code(button);
		return;
	}

    if (typeof TencentCaptcha == "undefined") {
        wnd_load_script("https://ssl.captcha.qq.com/TCaptcha.js", function() {
            captcha_send(button);
        });
    } else {
        captcha_send(button);
    }

     function captcha_send(){
        let captcha = new TencentCaptcha(
            "' . $this->appid . '",
            function(res) {
                if (0 !== res.ret) {
                    button.classList.remove("is-loading");
                    return false;
                }

                button.dataset.' . static::$captcha_name . ' = res.ticket;
                button.dataset.' . static::$captcha_nonce_name . ' = res.randstr;
                wnd_send_code(button, "' . static::$captcha_name . '");
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
    let form = button.closest("form");
    let input =  form.querySelector("[name=\'' . static::$captcha_name . '\']");
    let input_nonce =  form.querySelector("[name=\'' . static::$captcha_nonce_name . '\']");

    button.classList.add("is-loading");

    // 已设置 captcha 可能为前端表单校验导致的修改再次提交（表单完成提交后，无论后端校验如何都应清空 captcha）
    if(input.value){
		captcha_callback();
        return;
    }

    if (typeof TencentCaptcha == "undefined") {
        wnd_load_script("https://ssl.captcha.qq.com/TCaptcha.js", function() {
            captcha_submit();
        });
    } else {
        captcha_submit();
    }

    function captcha_submit(){
        let captcha = new TencentCaptcha(
            "' . $this->appid . '",
            function(res) {
                if (0 !== res.ret) {
                    button.classList.remove("is-loading");
                    return false;
                }

				// 设置表单值
        		input.value = res.ticket;
                input_nonce.value = res.randstr;
				// 设置事件触发 VUE 数据同步
				input.dispatchEvent(new Event("input"));
				input_nonce.dispatchEvent(new Event("input"));

				captcha_callback();
            }
        );
        captcha.show();
    }

     // 设置 captcha 后执行回调函数或再次点击按钮，再次点击按钮应做条件判断以免死循环
    function captcha_callback(){
        if (callback) {
            window[callback](button, input);
        }else{
            button.click();
        }
    }
}

// 非 Vue 表单绑定 Submit 点击事件
var sub_btn = document.querySelectorAll("[type=submit]");
if (sub_btn) {
    sub_btn.forEach(function(btn) {
        btn.addEventListener("click", function(e) {
            let form = btn.closest("form");
            let input =  form.querySelector("[name=\'' . static::$captcha_name . '\']");
            if(input && !input.value){
                wnd_submit_via_captcha(e);
                e.preventDefault();
            }
        });
    });
}
</script>';
		return $script;
	}
}
