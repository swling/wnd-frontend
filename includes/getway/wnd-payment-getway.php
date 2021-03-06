<?php
namespace Wnd\Getway;

/**
 * 支付网关
 *
 * @since 0.9.2
 */
abstract class Wnd_Payment_Getway {
	/**
	 * 根据支付订单ID获取第三方支付平台接口标识
	 */
	public static function get_payment_gateway(int $payment_id): string{
		$payment = $payment_id ? get_post($payment_id) : false;
		if (!$payment) {
			return '';
		}

		return $payment->post_excerpt;
	}

	/**
	 * 构建支付接口名称及标识
	 */
	public static function get_gateway_options(): array{
		$gateway_data['支付宝'] = wnd_get_config('alipay_qrcode') ? 'Alipay_QRCode' : 'Alipay';

		if (wnd_get_config('paypal_clientid')) {
			$gateway_data['PayPal'] = 'PayPal';
		}

		return apply_filters('wnd_payment_gateway_options', $gateway_data);
	}

	/**
	 * 默认支付网关
	 */
	public static function get_default_gateway(): string{
		$default_gateway = wnd_get_config('alipay_qrcode') ? 'Alipay_QRCode' : 'Alipay';
		return apply_filters('wnd_default_payment_gateway', $default_gateway);
	}
}
