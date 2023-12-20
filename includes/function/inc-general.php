<?php

function wnd_remote_get(string $url, array $args = []) {
	$args['method'] = 'GET';
	return wnd_remote_request($url, $args);
}

function wnd_remote_post(string $url, array $args = []) {
	$args['method'] = 'POST';
	return wnd_remote_request($url, $args);
}

function wnd_remote_head($url, array $args = []) {
	$args['method'] = 'HEAD';
	return wnd_remote_request($url, $args);
}

function wnd_remote_request(string $url, array $args) {
	$request = new Wnd\Component\Requests\Requests;

	$ua                 = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36';
	$ua                 = apply_filters('http_headers_useragent', $ua);
	$args['user_agent'] = $ua;
	$response           = $request->request($url, $args);

	return $response;
}

/**
 * 保留语言参数的 home_url()
 * @since 0.9.59.2
 */
function wnd_home_url(string $path = '', string $scheme = null) {
	$home_url = get_home_url(null, $path, $scheme);
	return Wnd\Utility\Wnd_language::filter_link($home_url);
}

/**
 * 获取 Router PHP 文件绝对网址，用于不支持伪静态或其他应急场景
 * - 本路由地址整合了 Wnd_Controller Json API 并定义了部分应急操作
 * @since 2019.01.21
 *
 * @return string 	url
 */
function wnd_get_router_url(): string {
	return WND_URL . 'router.php';
}

/**
 * 绝对 Rest API 路由 URL
 * @since 0.9.22
 *
 * @return string url
 */
function wnd_get_route_url(string $route, string $endpoint = ''): string {
	return Wnd\Controller\Wnd_Controller::get_route_url($route, $endpoint);
}

/**
 * 获取指定 Endpoint 绝对路由 URL，处理与第三方平台的通讯
 * @since 0.9.22
 *
 * @return string url
 */
function wnd_get_endpoint_url(string $endpoint = ''): string {
	return Wnd\Controller\Wnd_Controller::get_route_url('endpoint', $endpoint);
}

/**
 * 获取配置选项
 * @since 2020.4.13
 */
function wnd_get_config($config_key) {
	return Wnd\Utility\Wnd_Config::get($config_key);
}

/**
 * 获取用户中心页面 URL
 * @since 0.9.0
 *
 * @param bool $remove_language 是否移除语言参数
 */
function wnd_get_front_page_url($remove_language = false): string {
	$front_page_url = get_permalink(wnd_get_config('front_page'));
	return $remove_language ? remove_query_arg(WND_LANG_KEY, $front_page_url) : $front_page_url;
}

/**
 * 是否在 Rest 请求环境中
 * @since 0.9.26
 */
function wnd_is_rest_request(): bool {
	$current_url = wnd_get_current_url();
	$rest_url    = rest_url();

	return defined('REST_REQUEST') ? REST_REQUEST : str_starts_with($current_url, $rest_url);
}

/**
 * 获取 Json 请求数据
 * @since 0.9.37
 */
function wnd_get_json_request(): array {
	if (!wp_is_json_request()) {
		return [];
	}

	$json = file_get_contents('php://input') ?: '{}';
	return json_decode($json, true);
}

/**
 * 获取用户ip
 * @since 初始化
 *
 * @param  	bool   	$hidden 	是否隐藏IP部分字段
 * @return 	string 	IP address
 *
 * @link https://learnku.com/laravel/t/3905/do-you-really-know-ip-how-do-php-get-the-real-user-ip
 * @link https://stackoverflow.com/questions/3003145/how-to-get-the-client-ip-address-in-php
 * @link https://www.php.net/manual/zh/reserved.variables.server.php
 */
function wnd_get_user_ip(bool $hidden = false): string {
	if (isset($_SERVER)) {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';
	} else {
		$ip = getenv('REMOTE_ADDR') ?? '';
	}
	$ip = $ip ?: '';

	if ($hidden) {
		return preg_replace('/(\d+)\.(\d+)\.(\d+)\.(\d+)/is', '$1.$2.$3.*', $ip);
	} else {
		return $ip;
	}
}

/**
 * 搜索引擎判断
 * @since 初始化
 *
 * @return bool 	是否是搜索引擎
 */
function wnd_is_robot() {
	return (
		isset($_SERVER['HTTP_USER_AGENT']) and preg_match('/bot|crawl|slurp|spider|mediapartners/i', $_SERVER['HTTP_USER_AGENT'])
	);
}

/**
 * 获取随机大小写字母和数字组合字符串
 * @since 2019.01.30
 *
 * @param  	int    	$length        	随机字符串长度
 * @return 	string 	随机字符
 */
function wnd_random($length) {
	$chars = '123456789abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ';
	$hash  = '';
	$max   = strlen($chars) - 1;
	for ($i = 0; $i < $length; $i++) {
		$hash .= $chars[mt_rand(0, $max)];
	}
	return $hash;
}

/**
 * 生成N位随机数字
 * @since 初始化
 *
 * @param  	int    	$length        	随机字符串长度
 * @param  	false  	$positive      	是否为正数
 * @return 	string 	随机字符
 */
function wnd_random_code($length = 6, $positive = false) {
	$No = '';
	for ($i = 0; $i < $length; $i++) {
		$No .= (0 == $i and $positive) ? mt_rand(1, 9) : mt_rand(0, 9);
	}
	return $No;
}

/**
 * 生成包含当前日期信息的高强度的唯一性ID
 * @since 2019.03.04
 *
 * @return 	string 	随机字符
 */
function wnd_generate_order_NO() {
	$today = wnd_date('Ymd');
	$rand  = substr(hash('sha256', uniqid(rand(), TRUE)), 0, 10);
	return $today . $rand;
}

/**
 * @since 2019.02.09  验证是否为手机号
 *
 * @0.9.69.2
 * 简化判断：仅判断字符串是否为纯数字
 *
 * @param  	string 	$phone  需要验证的手机号
 * @return 	bool   	是否为合法的手机号码格式
 */
function wnd_is_mobile($phone): bool {
	return ctype_digit($phone);
}

/**
 * 复制taxonomy term数据到 另一个 taxonomy下
 * @since 2019.04.30
 *
 * @param 	string 	$old_taxonomy	需要被复制的taxonomy
 * @param 	string 	$new_taxonomy	需要创建的taxonomy
 */
function wnd_copy_taxonomy($old_taxonomy, $new_taxonomy) {
	$terms = get_terms($old_taxonomy, 'hide_empty=0');

	if (!empty($terms) and !is_wp_error($terms)) {
		foreach ($terms as $term) {
			wp_insert_term($term->name, $new_taxonomy);
		}
		unset($term);
	}
}

/**
 * @since 2019.02.19 在当前位置自动生成一个容器，以供ajax嵌入模板
 *
 * @param  $template 	string    				被调用Module
 * @param  $args     	array     or string 	传递给被调用Module的参数
 * @return string    JavaScript 脚本
 */
function wnd_ajax_embed($template, $args = []) {
	$div_id    = 'wnd-embed-' . uniqid();
	$ajax_args = json_encode(wp_parse_args($args));

	$html = '<div id="' . $div_id . '">';
	$html .= '<script>wnd_ajax_embed(\'#' . $div_id . '\', \'' . $template . '\', ' . $ajax_args . ')</script>';
	$html .= '</div>';

	return $html;
}

/**
 * 快速生成 ajax action 请求脚本
 * @since 0.9.35
 *
 * @param  $action 	string    				被调用Action
 * @param  $args   array      or string 	传递给被调用Action的参数
 * @return string  JavaScript 脚本
 */
function wnd_ajax_action(string $action, array $args = []): string {
	$args = Wnd\Controller\Wnd_Request::sign_request($args);
	$args = json_encode(wp_parse_args($args));
	return '<script>wnd_ajax_action(\'' . $action . '\', ' . $args . ')</script>';
}

/**
 * 获取当前页面URL
 * @since 2020.01.14
 */
function wnd_get_current_url() {
	return ((isset($_SERVER['HTTPS']) and 'on' == $_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * 按more标签，切割内容
 * 字符串处理代码取自wp官方函数：get_the_content
 * @see get_the_content
 */
function wnd_explode_post_by_more(string $content): array {
	if (preg_match('/<!--more(.*?)?-->/', $content, $matches)) {
		if (has_block('more', $content)) {
			// Remove the core/more block delimiters. They will be left over after $content is split up.
			$content = preg_replace('/<!-- \/?wp:more(.*?) -->/', '', $content);
		}

		$content = explode($matches[0], $content, 2);
	} else {
		$content = [$content];
	}

	return $content;
}

/**
 * 封装字符串截断
 * wp_trim_words 依赖语言包，如果前端禁止语言包，则中文失效
 * @since 0.9.57.3
 */
function wnd_trim_words(string $text, int $num_words = 55, string $more = '……'): string {
	$text = mb_substr(wp_strip_all_tags($text), 0, $num_words, 'utf-8');
	return $text . $more;
}

/**
 * 定义如何过滤数组数据
 * 本插件定义：过滤空值，但保留0
 * @since 0.9.38
 */
function wnd_array_filter(array $arr): array {
	return array_filter($arr, function ($value) {
		return $value or is_numeric($value);
	});
}

/**
 * 记录常规错误日志
 * @since 0.9.38
 */
function wnd_error_log(string $msg, string $file_name = 'wnd_error') {
	Wnd\Utility\Wnd_Error_Handler::write_log($msg, $file_name);
}

/**
 * 记录支付错误日志
 * @since 0.9.38
 */
function wnd_error_payment_log(string $msg) {
	$msg = $msg . ' Request from ' . wnd_get_user_ip() . '. @' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	wnd_error_log($msg, 'wnd_payment_error');
}

/**
 * @since 0.9.58.3
 *
 * wp_mail 为可覆盖函数，此处直接使用阿里云邮件 API 推送取代之
 *
 * 注意：不支持附件
 *
 * @link https://help.aliyun.com/document_detail/29444.html
 */
if (wnd_get_config('aliyun_dm_account')) {
	function wp_mail(string $to, string $subject, string $message) {
		$client = Wnd\Getway\Wnd_Cloud_Client::get_instance('Aliyun', 'DM');
		$client->request(
			'https://dm.aliyuncs.com',
			[
				'body' => [
					'RegionId'       => 'cn-hangzhou',
					'Action'         => 'SingleSendMail',
					'Format'         => 'JSON',
					'Version'        => '2015-11-23',
					'AccountName'    => wnd_get_config('aliyun_dm_account'),
					'AddressType'    => 1,
					'ReplyToAddress' => 'true',

					'FromAlias'      => get_bloginfo('name'),
					'Subject'        => $subject,
					'ToAddress'      => $to,
					'HtmlBody'       => $message,
				],
			]
		);
	}
}

/**
 * @since 0.9.59.2
 * 获取本地时间戳
 * - WP 默认设置为 UTC 时间，并通过后台配置时区来实现偏移
 */
function wnd_local_time(): int {
	return wnd_time_to_local(time());
}

function wnd_time_to_local(int $timestamp): int {
	return $timestamp + (int) get_option('gmt_offset') * HOUR_IN_SECONDS;
}

/**
 * @since 0.9.59.2
 * 获取本地日期时间
 * - WP 默认设置为 UTC 时间，并通过后台配置时区来实现偏移
 * - 本函数用于取代较为复杂的 wp_date() 函数
 */
function wnd_date(string $format, $time = 0): string {
	$time = $time ?: time();
	return date($format, wnd_time_to_local($time));
}

/**
 * @since 0.9.59.2
 * 获取本地日期时间
 * - WP 默认设置为 UTC 时间，并通过后台配置时区来实现偏移
 * - 本函数用于自动给 php 函数 getdate() 添加时区信息
 */
function wnd_getdate(): array {
	return getdate(wnd_local_time());
}
