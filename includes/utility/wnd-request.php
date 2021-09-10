<?php
namespace Wnd\Utility;

use Exception;
use Wnd\Getway\Wnd_Captcha;
use Wnd\Utility\Wnd_Validator;
use WP_REST_Request;

/**
 * 前端请求遵循以下规则定义的name，后台获取后自动提取，并更新到数据库
 * 	文章：_post_{$field}
 *
 * 	文章字段：
 * 	_meta_{$key} (*自定义数组字段)
 * 	_wpmeta_{$key} (*WordPress原生字段)
 *
 * 	Term:
 * 	_term_{$taxonomy}(*taxonomy)
 *
 * 	用户：_user_{$field}
 *
 * 	用户字段：
 * 	_usermeta_{$key} (*自定义数组字段)
 * 	_wpusermeta_{$key} (*WordPress原生字段)
 *
 * option：
 * 存储在 Wnd option中 : _option_{$option_name}_{$option_key}
 *
 * @since 2019.03.04
 *
 * @param $wp_rest_request  WP_REST_Request WP_REST_Request 实例
 * @param $verify_sign      bool            是否校验签名
 * @param $validate_captcha bool            是否进行人机验证（如果存在）
 */
class Wnd_Request {

	/**
	 * 请求数据
	 */
	protected $request;

	/**
	 * WP_REST_Request 实例
	 * @since 0.9.36
	 */
	protected $wp_rest_request;

	/**
	 * 签名请求 name
	 */
	public static $sign_name = '_wnd_sign';

	/**
	 * 解析请求数据时，是否验证请求签名
	 */
	protected $verify_sign = true;

	/**
	 * 解析请求数据时，是否进行人机验证（如果存在）
	 */
	protected $validate_captcha = true;

	/**
	 * Construct
	 */
	public function __construct(WP_REST_Request $wp_rest_request, bool $verify_sign = true, bool $validate_captcha = true) {
		$this->wp_rest_request  = $wp_rest_request;
		$this->verify_sign      = $verify_sign;
		$this->validate_captcha = $validate_captcha;

		$this->validate_request();
	}

	/**
	 * 解析请求数据
	 * 	与原请求数据相比，此时获取的请求提交数据，执行了 wnd_request_filter，并进行了签名验证及人机验证（数据验证根据设置可取消）
	 * 	请勿重复调用本方法
	 * @since 0.8.64
	 *
	 * @return array 返回解析后的请求提交数据
	 */
	protected function validate_request(): array{
		$method = $this->wp_rest_request->get_method();
		$route  = $this->wp_rest_request->get_route();

		if ('GET' == $method) {
			$request = $this->wp_rest_request->get_query_params();
		} else {
			$request = $this->wp_rest_request->get_json_params() ?: $this->wp_rest_request->get_body_params();
		}
		if (empty($request)) {
			return [];
		}

		/**
		 * apply_filters('wnd_request', $request) 操作可能会直接修改$request
		 * 因而校验请求操作应该在filter应用之前执行
		 * 通过filter添加的数据，自动视为被允许提交的数据
		 * @since 2019.05.10
		 */
		if ($this->verify_sign and !static::verify_sign($request)) {
			throw new Exception(__('数据已被篡改', 'wnd'));
		}

		/**
		 * 人机验证：由于请求字段设置了字段名称一致性校验，前端无法更改字段，因此可用是否设置了 captcha 字段来判断当前请求是否需要人机验证
		 * @since 0.8.64
		 */
		if ($this->validate_captcha and isset($request[Wnd_Captcha::$captcha_name])) {
			Wnd_Validator::validate_captcha();
		}

		// 允许修改请求提交数据
		$request = apply_filters('wnd_request', $request);

		/**
		 * 根据请求数据控制请求提交
		 * @since 2019.12.22
		 */
		$can_array = apply_filters('wnd_request_controller', ['status' => 1], $request, $route);
		if (0 === $can_array['status']) {
			throw new Exception($can_array['msg']);
		}

		$this->request = $request;

		return $this->request;
	}

	/**
	 * 获取请求数据
	 *
	 * @since 0.8.73
	 */
	public function get_request(): array{
		return $this->request ?: $this->validate_request();
	}

	/**
	 * 根据前缀提取指定请求数据
	 *
	 * @since 2020.01.04
	 */
	protected function get_data_by_prefix($prefix): array{
		$request = [];
		foreach ($this->request as $key => $value) {
			if (0 === strpos($key, $prefix)) {
				$key           = str_replace($prefix, '', $key);
				$request[$key] = $value;
			}
		}unset($key, $value);

		return $request;
	}

	// 获取WordPress user数据数组
	public function get_user_data(): array{
		return $this->get_data_by_prefix('_user_');
	}

	// 获取WordPress原生use meta数据数组
	public function get_wp_user_meta_data(): array{
		return $this->get_data_by_prefix('_wpusermeta_');
	}

	// 获取自定义WndWP user meta数据数组
	public function get_user_meta_data(): array{
		return $this->get_data_by_prefix('_usermeta_');
	}

	// 获取WordPress原生post meta数据数组
	public function get_post_data(): array{
		return $this->get_data_by_prefix('_post_');
	}

	// 获取WordPress原生post meta数据数组
	public function get_wp_post_meta_data(): array{
		return $this->get_data_by_prefix('_wpmeta_');
	}

	// 获取WndWP post meta数据数组
	public function get_post_meta_data(): array{
		return $this->get_data_by_prefix('_meta_');
	}

	// 获取WordPress分类：term数组
	public function get_terms_data(): array{
		return $this->get_data_by_prefix('_term_');
	}

	// 获取指定 option_name 数组数据
	public function get_option_data($option_name): array{
		return $this->get_data_by_prefix('_option_' . $option_name . '_');
	}

	/**
	 * 生成包含签名数据的完整请求数据数组
	 * @since 0.9.35.6
	 */
	public static function sign_request(array $request): array{
		$sign                        = static::sign(array_keys($request));
		$request[static::$sign_name] = $sign;
		return $request;
	}

	/**
	 * 构建请求签名
	 * @since 2019.10.27
	 *
	 * @param array 	$request_names 请求所有字段name数组
	 */
	public static function sign(array $request_names): string{
		// nonce 自身字段也需要包含在内，生成请求标识
		$request_names[] = static::$sign_name;
		$identifier      = static::generate_request_identifier($request_names);

		return wp_create_nonce($identifier);
	}

	/**
	 * @see static::sign
	 * @since 2019.05.09 校验请求字段是否被篡改
	 */
	public static function verify_sign(array $request): bool {
		if (!isset($request[static::$sign_name])) {
			return false;
		}

		// 提取请求数据数组键值，生成请求标识
		$request_names = array_merge(array_keys($request), array_keys($_FILES));
		$identifier    = static::generate_request_identifier($request_names);

		// 校验数组键值签名
		return wp_verify_nonce($request[static::$sign_name], $identifier);
	}

	/**
	 * 构建请求签名标识符
	 * @since 0.8.66
	 *
	 * @param  array    $request_names 请求字段数组
	 * @return string
	 */
	protected static function generate_request_identifier(array $request_names): string{
		$request_names = array_unique($request_names);
		sort($request_names);

		return md5(implode('', $request_names) . AUTH_KEY);
	}
}
