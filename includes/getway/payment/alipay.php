<?php
namespace Wnd\Getway\Payment;

use Exception;
use Wnd\Component\Payment\Alipay\AlipayService;
use Wnd\Component\Payment\Alipay\PayPC;
use Wnd\Component\Payment\Alipay\PayWAP;
use Wnd\Getway\Wnd_Payment;
use Wnd\Model\Wnd_Transaction;

/**
 * 支付宝支付
 * PC支付和wap支付中：product_code 、method 参数有所不同，详情查阅如下
 * 问题排查：
 * - @link https://opendocs.alipay.com/open/common/fr9vsk
 * - @link https://opensupport.alipay.com/support/tools/cloudparse
 *
 * @link https://opendocs.alipay.com/apis/api_1/alipay.trade.page.pay
 * @link https://opendocs.alipay.com/apis/api_1/alipay.trade.wap.pay
 * @since 2020.06.19
 */
class Alipay extends Wnd_Payment {

	/**
	 * 示例代码中采用的是函数调用，为WndWP插件专用务必修改后才能用于其他网站
	 * @since 2019.03.02 请根据注释说明，修改支付宝配置信息，
	 * use Wnd\Component\Payment\Alipay\AlipayCertClient;
	 */
	public static function getConfig() {
		$config = [
			//应用ID,您的APPID。
			'app_id'              => wnd_get_config('alipay_appid'),

			// 支付宝根证书序列号，获取方法：AlipayCertClient::getRootCertSNFromContent($certContent); 对应证书：alipayRootCert.crt
			'alipay_root_cert_sn' => wnd_get_config('alipay_root_cert_sn'),

			// 应用公钥证书序列号，获取方法：AlipayCertClient::getCertSNFromContent($certContent); 对应证书；appCertPublicKey_{xxx}.crt
			'app_cert_sn'         => wnd_get_config('alipay_app_cert_sn'),

			// RSA2 商户私钥 用工具生成的应用私钥
			'app_private_key'     => wnd_get_config('alipay_app_private_key'),

			// 支付宝公钥，获取方法：AlipayCertClient::getPublicKeyFromContent($cert); 对应证书：alipayCertPublicKey_RSA2.crt
			'alipay_public_key'   => wnd_get_config('alipay_public_key'),

			//异步通知地址 *不能带参数否则校验不过 （插件执行页面地址）
			'notify_url'          => wnd_get_endpoint_url('wnd_verify_alipay'),

			//同步跳转 *不能带参数否则校验不过 （插件执行页面地址）
			'return_url'          => wnd_get_endpoint_url('wnd_verify_alipay'),

			//编码格式
			'charset'             => 'utf-8',

			//签名方式
			'sign_type'           => 'RSA2',

			//支付宝网关
			'gateway_url'         => wnd_get_config('payment_sandbox') ? 'https://openapi.alipaydev.com/gateway.do' : 'https://openapi.alipay.com/gateway.do',
		];

		return $config;
	}

	/**
	 * 发起支付
	 *
	 */
	public function build_interface(): string {
		$config = static::getConfig();
		$aliPay = wp_is_mobile() ? new PayWAP($config) : new PayPC($config);
		$aliPay->setTotalAmount($this->total_amount);
		$aliPay->setOutTradeNo($this->out_trade_no);
		$aliPay->setSubject($this->subject);
		$aliPay->generateParams();

		// 生成支付界面
		$payment_interface = $aliPay->buildInterface();
		$payment_id        = $this->transaction->get_transaction_id();
		$title             = '支付宝支付';
		return static::build_payment_interface($payment_id, $payment_interface, $title);
	}

	/**
	 * 根据交易订单解析站内交易ID，并查询记录
	 */
	public static function verify_payment(): Wnd_Transaction {
		static::verify();

		$out_trade_no   = $_REQUEST['out_trade_no'] ?? '';
		$total_amount   = $_REQUEST['total_amount'] ?? 0.00;
		$transaction_id = static::parse_out_trade_no($out_trade_no);
		$transaction    = Wnd_Transaction::get_instance('', $transaction_id);

		/**
		 * 仅在 pending 订单中校验金额，避免错误验证，如：
		 * - 支持退款的支付宝交易会在三个月后执行一次 FINISHED 回调，此时匿名充值订单的交易额可能已经改变
		 */
		if (Wnd_Transaction::$pending_status == $transaction->get_status() and $total_amount != $transaction->get_total_amount()) {
			throw new Exception('金额不匹配');
		}

		return $transaction;
	}

	/**
	 * 验证支付
	 *
	 * @param $this->total_amount
	 */
	private static function verify() {
		/**
		 * 支付平台回调验签
		 *
		 * WordPress 始终开启了魔法引号，因此需要对post 数据做还原处理
		 * @link https://developer.wordpress.org/reference/functions/stripslashes_deep/
		 */
		if ('POST' == $_SERVER['REQUEST_METHOD']) {
			$_POST = stripslashes_deep($_POST);
			if (!static::check_notify()) {
				throw new Exception('异步验签失败');
			}
		} else {
			$_GET = stripslashes_deep($_GET);
			if (!static::check_return()) {
				throw new Exception('同步验签失败');
			}
		}
	}

	/**
	 * 回调验签
	 *
	 */
	private static function check($params): bool {
		$aliPay = new AlipayService(static::getConfig());
		return $aliPay->rsaCheck($params);
	}

	/**
	 * 同步回调通知
	 *
	 */
	private static function check_return(): bool {
		/**
		 * 验签
		 */
		$check = static::check($_GET);
		if (true !== $check) {
			echo ('fail');
		}

		return $check;
	}

	/**
	 * 异步回调通知
	 *
	 * @link https://opendocs.alipay.com/open/203/105286/#%E6%9C%8D%E5%8A%A1%E5%99%A8%E5%BC%82%E6%AD%A5%E9%80%9A%E7%9F%A5%E9%A1%B5%E9%9D%A2%E7%89%B9%E6%80%A7
	 */
	private static function check_notify(): bool {
		/**
		 * 验签
		 */
		if (true !== static::check($_POST)) {
			echo ('fail');
			return false;
		}

		/**
		 * 请在这里加上商户的业务逻辑程序代
		 * 获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
		 *
		 * 如果校验成功必须输出 'success'，页面源码不得包含其他及HTML字符
		 *
		 * # 状态TRADE_SUCCESS：
		 * - 通知触发条件是商户签约的产品支持退款功能的前提下，买家付款成功
		 *
		 * # 交易状态TRADE_FINISHED：
		 * - 知触发条件是商户签约的产品不支持退款功能的前提下，买家付款成功；
		 *   或者，商户签约的产品支持退款功能的前提下，交易已经成功并且已经超过可退款期限。
		 *
		 * 业务处理：
		 * - 判断该笔订单是否在商户网站中已经做过处理
		 * - 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
		 * - 请务必判断请求时的total_amount与通知时获取的total_fee为一致的
		 * - 如果有做过处理，不执行商户的业务程序
		 */
		if ('TRADE_FINISHED' == $_POST['trade_status']) {
			echo ('success');
			return true;
		}

		if ('TRADE_SUCCESS' == $_POST['trade_status']) {
			echo ('success');
			return true;
		}

		/**
		 * 交易关闭
		 * 未付款交易超时关闭，或支付完成后全额退款。
		 * 本系统未针对此操作做订单处理，故直接回报平台响应，并终止
		 */
		if ('TRADE_CLOSED' == $_POST['trade_status']) {
			echo ('success');
			exit;
		}

		/**
		 * 其他未知
		 */
		return false;
	}
}
