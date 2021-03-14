<?php

/**
 *@since 2019.01.21
 *获取 Router PHP 文件绝对网址，用于不支持伪静态或其他应急场景
 * - 本路由地址整合了 Wnd_Controller Json API 并定义了部分应急操作
 *
 *@return string 	url
 */
function wnd_get_router_url(): string {
	return WND_URL . 'router.php';
}

/**
 *@since 0.9.22
 *绝对 Rest API 路由 URL
 *
 *@return string url
 */
function wnd_get_route_url(string $route, string $endpoint = ''): string {
	return Wnd\Controller\Wnd_Controller::get_route_url($route, $endpoint);
}

/**
 *@since 0.9.22
 *获取指定 Endpoint 绝对路由 URL，处理与第三方平台的通讯
 *
 *@return string url
 */
function wnd_get_endpoint_url(string $endpoint = ''): string {
	return Wnd\Controller\Wnd_Controller::get_route_url('endpoint', $endpoint);
}

/**
 *@since 2020.4.13
 *获取配置选项
 */
function wnd_get_config($config_key) {
	return Wnd\Utility\Wnd_Config::get($config_key);
}

/**
 *@since 0.9.0
 *获取用户中心页面 URL
 *@param bool $remove_language 是否移除语言参数
 */
function wnd_get_front_page_url($remove_language = false): string{
	$front_page_url = get_permalink(wnd_get_config('front_page'));
	return $remove_language ? remove_query_arg(WND_LANG_KEY, $front_page_url) : $front_page_url;
}

/**
 *@since 2019.04.07
 */
function wnd_doing_ajax() {
	if ('XMLHttpRequest' == ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? false)) {
		return true;
	}

	if (wp_doing_ajax()) {
		return true;
	}

	return false;
}

/**
 *@since 初始化
 *获取用户ip
 *@param 	bool 	$hidden 	是否隐藏IP部分字段
 *@return 	string 	IP address
 */
function wnd_get_user_ip($hidden = false) {
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	if ($hidden) {
		return preg_replace('/(\d+)\.(\d+)\.(\d+)\.(\d+)/is', "$1.$2.$3.*", $ip);
	} else {
		return $ip;
	}
}

/**
 *@since 初始化
 *搜索引擎判断
 *@return bool 	是否是搜索引擎
 */
function wnd_is_robot() {
	return (
		isset($_SERVER['HTTP_USER_AGENT']) and preg_match('/bot|crawl|slurp|spider|mediapartners/i', $_SERVER['HTTP_USER_AGENT'])
	);
}

/**
 *@since 2019.01.30
 *获取随机大小写字母和数字组合字符串
 *
 *@param 	int 	$length 	随机字符串长度
 *@return 	string 	随机字符
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
 *@since 初始化
 *生成N位随机数字
 *@param 	int 	$length 	随机字符串长度
 *@param 	false 	$positive 	是否为正数
 *@return 	string 	随机字符
 */
function wnd_random_code($length = 6, $positive = false) {
	$No = '';
	for ($i = 0; $i < $length; $i++) {
		$No .= (0 == $i and $positive) ? mt_rand(1, 9) : mt_rand(0, 9);
	}
	return $No;
}

/**
 *@since 2019.03.04
 *生成包含当前日期信息的高强度的唯一性ID
 *@return 	string 	随机字符
 */
function wnd_generate_order_NO() {
	$today = date('Ymd');
	$rand  = substr(hash('sha256', uniqid(rand(), TRUE)), 0, 10);
	return $today . $rand;
}

/**
 *@since 2019.02.09  验证是否为手机号
 *
 *@param 	string 	$phone 	需要验证的手机号
 *@return 	bool 	是否为合法的手机号码格式
 */
function wnd_is_mobile($phone) {
	if ((empty($phone) or !preg_match("/^(((13[0-9]{1})|(15[0-9]{1})|(17[0-9]{1})|(18[0-9]{1})|(19[0-9]{1}))+\d{8})$/", $phone))) {
		return false;
	} else {
		return true;
	}
}

/**
 *复制taxonomy term数据到 另一个 taxonomy下
 *@since 2019.04.30
 *@param 	string 	$old_taxonomy	需要被复制的taxonomy
 *@param 	string 	$new_taxonomy	需要创建的taxonomy
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
 *@since 2019.02.19 在当前位置自动生成一个容器，以供ajax嵌入模板
 *@param $template 	string  			被调用函数(必须以 _wnd为前缀)
 *@param $args 		array or string 	传递给被调用模板函数的参数
 */
function wnd_ajax_embed($template, $args = []) {
	$div_id    = 'wnd-embed-' . uniqid();
	$ajax_args = json_encode(wp_parse_args($args));

	$html = '<div id="' . $div_id . '">';
	$html .= '<script>wnd_ajax_embed(\'#' . $div_id . '\',\'' . $template . '\',' . $ajax_args . ')</script>';
	$html .= '</div>';

	return $html;
}

/**
 *@since 2020.01.14
 *
 *获取当前页面URL
 */
function wnd_get_current_url() {
	return ((isset($_SERVER['HTTPS']) and 'on' == $_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 *生成二维码图像
 *@param string 需要转换的字符信息
 *
 *@return string 二维码图像地址
 */
function wnd_generate_qrcode(string $string): string {
	return wnd_get_endpoint_url('wnd_qrcode') . '?string=' . $string;
}

/**
 *按more标签，切割内容
 *字符串处理代码取自wp官方函数：get_the_content
 *@see get_the_content
 */
function wnd_explode_post_by_more(string $content): array{
	if (preg_match('/<!--more(.*?)?-->/', $content, $matches)) {
		if (has_block('more', $content)) {
			// Remove the core/more block delimiters. They will be left over after $content is split up.
			$content = preg_replace('/<!-- \/?wp:more(.*?) -->/', '', $content);
		}

		$content = explode($matches[0], $content, 2);
	} else {
		$content = array($content);
	}

	return $content;
}

/**
 *@since 2019.07.17
 *设置默认的异常处理函数
 */
set_exception_handler('wnd_exception_handler');
function wnd_exception_handler($exception) {
	$html = '<article class="column message is-danger">';
	$html .= '<div class="message-header">';
	$html .= '<p>异常</p>';
	$html .= '</div>';
	$html .= '<div class="message-body">' . $exception->getMessage() . '</div>';
	$html .= '</article>';

	echo $html;
}
