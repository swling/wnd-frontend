<?php
namespace Wnd\Getway;

use Wnd\Model\Wnd_Transaction;

/**
 * 支付网关
 *
 * @since 0.9.2
 */
abstract class Wnd_Payment_Getway {

	public static $internal_getway = 'internal';

	/**
	 * 根据支付订单ID获取第三方支付平台接口标识
	 */
	public static function get_payment_gateway(int $payment_id): string {
		$transaction = Wnd_Transaction::get_instance('', $payment_id);
		return $transaction->get_payment_gateway() ?: 'Internal';
	}

	/**
	 * 构建支付接口名称及标识
	 */
	public static function get_gateway_options(): array {
		$payments = [];

		if (wnd_get_config('alipay_appid')) {
			$payments[] = [
				'label' => '支付宝',
				'value' => wnd_get_config('alipay_qrcode') ? 'Alipay_QRCode' : 'Alipay',
				'icon'  => '<i class="fab fa-alipay"></i>',
			];
		}

		if (wnd_get_config('wechat_mchid')) {
			$payments[] = [
				'label' => '微信',
				'value' => wp_is_mobile() ? 'WeChat_H5' : 'WeChat_Native',
				'icon'  => '<i class="fab fa-weixin"></i>',
			];
		}

		if (wnd_get_config('paypal_clientid')) {
			$payments[] = [
				'label' => 'PayPal',
				'value' => 'PayPal',
				'icon'  => '<i class="fab fa-cc-paypal"></i>',
			];
		}

		return apply_filters('wnd_payment_gateway_options', $payments);
	}

	/**
	 * 默认支付网关
	 */
	public static function get_default_gateway(): string {
		$default_gateway = wnd_get_config('alipay_qrcode') ? 'Alipay_QRCode' : 'Alipay';
		return apply_filters('wnd_default_payment_gateway', $default_gateway);
	}

	/**
	 * 判断是否为站内支付网关标识
	 *
	 * @since 0.9.37
	 */
	public static function is_internal_getway(string $getway): bool {
		return static::$internal_getway == strtolower($getway);
	}

	/**
	 * 判断是否为站内支付订单
	 *
	 * @since 0.9.37
	 */
	public static function is_internal_payment(int $payment_id): bool {
		$getway = static::get_payment_gateway($payment_id);
		return static::is_internal_getway($getway);
	}
}
