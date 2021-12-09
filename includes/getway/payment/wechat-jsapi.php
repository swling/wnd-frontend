<?php
namespace Wnd\Getway\Payment;

use Exception;
use Wnd\Component\Payment\WeChat\JSAPI;
use Wnd\Endpoint\Wnd_Issue_Token_WeChat;

/**
 * 微信 JSAPI 支付（微信环境中支付：小程序，公众号内支付）
 * @link https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_1_1.shtml
 * @since 0.9.56.6
 */
class WeChat_JSAPI extends WeChat_Native {

	/**
	 * 此响应将交付给微信应用造支付参数，唤起微信支付
	 */
	public function build_interface(): string {
		// JSAPI 支付环境为微信内部：公众号、小程序等，对应 appid 应从微信中传参而来
		if (!$this->app_id) {
			throw new Exception('JSAPI 支付必须指定 app_id');
		}

		extract(static::get_config());
		$pay    = new JSAPI($mchid, $this->app_id, $apicert_sn, $private_key);
		$openid = Wnd_Issue_Token_WeChat::get_current_user_openid($this->app_id);
		if (!$openid) {
			throw new Exception('获取当前账户微信 openid 失败');
		}

		$pay->setTotalAmount($this->total_amount);
		$pay->setOutTradeNo($this->out_trade_no);
		$pay->setSubject($this->subject);
		$pay->setPayer(['openid' => $openid]);
		$pay->setNotifyUrl(wnd_get_endpoint_url('wnd_verify_wechat'));
		$pay->generateParams();
		return $pay->buildInterface();
	}
}
