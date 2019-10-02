<?php
/**
 *增强版nonce校验，在nonce校验中加入秘钥
 *@since 2019.05.12
 *@param 	string 	$action
 *@return 	string 	nonce
 **/
function wnd_create_nonce($action) {
	$secret_key = wnd_get_option('wnd', 'wnd_secret_key');
	return wp_create_nonce(md5($action . $secret_key));
}

/**
 *校验nonce
 *@since 2019.05.12
 *
 *@param 	string 	$anone
 *@param 	string 	$action
 *
 *@return 	bool
 **/
function wnd_verify_nonce($nonce, $action) {
	$secret_key = wnd_get_option('wnd', 'wnd_secret_key');
	return wp_verify_nonce($nonce, md5($action . $secret_key));
}

/**
 *@since 2019.01.21 获取do page地址
 *一个没有空白的WordPress环境，接收或执行一些操作
 *
 *@return string 	url
 */
function wnd_get_do_url() {
	return WND_URL . 'do.php';
}

/**
 *@since 2019.04.07
 */
function wnd_doing_ajax() {
	if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
		return true;
	} else {
		return false;
	}
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
 *@return 	string 	随机字符
 */
function wnd_random_code($length = 6) {
	$No = '';
	for ($i = 0; $i < $length; $i++) {
		$No .= mt_rand(0, 9);
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
function wnd_is_phone($phone) {
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
 * @since 2019.06.12
 * 获取当前页面查询类型基本信息
 * 通常用于在ajax请求中传递请求页面信息以供后端判断请求来源
 * */
function wnd_get_queried_type() {
	if (is_single()) {
		return array('type' => 'single', 'ID' => get_queried_object()->ID);

	} elseif (is_page()) {
		return array('type' => 'page', 'ID' => get_queried_object()->ID);

	} elseif (is_tax()) {
		return array('type' => 'tax', 'ID' => get_queried_object()->term_id);

	} elseif (is_home()) {
		return array('type' => 'home');

	} else {
		return array('type' => 'ajax');
	}
}

/**
 *@since 2019.02.19 在当前位置自动生成一个容器，以供ajax嵌入模板
 *@param $template 	string  			被调用函数(必须以 _wnd为前缀)
 *@param $args 		array or string 	传递给被调用模板函数的参数
 */
function _wnd_ajax_embed($template, $args = '') {
	$div_id    = 'wnd-embed-' . uniqid();
	$args      = wp_parse_args($args);
	$ajax_args = http_build_query($args);

	echo '<div id="' . $div_id . '">';
	echo '<script>wnd_ajax_embed(\'#' . $div_id . '\',\'' . $template . '\',\'' . $ajax_args . '\')</script>';
	echo '</div>';
}

/**
 *@since 初始化 ajax标题去重
 *@param $_POST['post_title']
 *@param $_POST['post_id']
 *@param $_POST['post_type']
 */
function _wnd_ajax_is_title_duplicated() {
	$title      = $_POST['post_title'];
	$exclude_id = $_POST['post_id'];
	$post_type  = $_POST['post_type'];

	if (wnd_is_title_duplicated($title, $exclude_id, $post_type)) {
		return array('status' => 1, 'msg' => '标题重复！');
	} else {
		return array('status' => 0, 'msg' => '标题唯一！');
	}
}

/**
 *@since 2019.01.16
 *@param $_GET['post_id']
 *@param $_GET['useragent']
 */
function _wnd_ajax_update_views() {
	$post_id = (int) $_GET['param'];
	if (!$post_id) {
		return;
	}
	$useragent    = $_GET['useragent'];
	$should_count = true;

	// 根据 useragent 排除搜索引擎
	$bots = array(
		'Google Bot' => 'google'
		, 'MSN' => 'msnbot'
		, 'Alex' => 'ia_archiver'
		, 'Lycos' => 'lycos'
		, 'Ask Jeeves' => 'jeeves'
		, 'Altavista' => 'scooter'
		, 'AllTheWeb' => 'fast-webcrawler'
		, 'Inktomi' => 'slurp@inktomi'
		, 'Turnitin.com' => 'turnitinbot'
		, 'Technorati' => 'technorati'
		, 'Yahoo' => 'yahoo'
		, 'Findexa' => 'findexa'
		, 'NextLinks' => 'findlinks'
		, 'Gais' => 'gaisbo'
		, 'WiseNut' => 'zyborg'
		, 'WhoisSource' => 'surveybot'
		, 'Bloglines' => 'bloglines'
		, 'BlogSearch' => 'blogsearch'
		, 'PubSub' => 'pubsub'
		, 'Syndic8' => 'syndic8'
		, 'RadioUserland' => 'userland'
		, 'Gigabot' => 'gigabot'
		, 'Become.com' => 'become.com'
		, 'Baidu' => 'baiduspider'
		, 'so.com' => '360spider'
		, 'Sogou' => 'spider'
		, 'soso.com' => 'sosospider'
		, 'Yandex' => 'yandex',
	);

	foreach ($bots as $name => $lookfor) {
		if (!empty($useragent) and (stristr($useragent, $lookfor) !== false)) {
			$should_count = false;
			break;
		}
	}

	// 统计
	if ($should_count) {
		if (wnd_inc_post_meta($post_id, 'views', 1)) {
			// 完成统计时附加动作
			do_action('wnd_ajax_update_views', $post_id);
			// 统计更新成功
			return array('status' => 1, 'msg' => time());

			//字段写入失败，清除对象缓存
		} else {
			wp_cache_delete($post_id, 'post_meta');
		}

	} else {
		// 未更新
		return array('status' => 0, 'msg' => time());
	}
}

/**
 *@since 2019.05.09  测试函数
 */
function wnd_ajax_test() {
	return array(
		'status' => 0,
		'msg'    => '测试函数触发成功!',
		'data'   => $_REQUEST,
	);
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
