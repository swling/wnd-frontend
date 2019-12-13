<?php
namespace Wnd\Hook;

use Wnd\view\Wnd_Form_WP;

/**
 *WP Filter
 */
class Wnd_Add_Filter_WP {

	private static $instance;

	private function __construct() {
		add_filter('wp_handle_upload_prefilter', [__CLASS__, 'filter_limit_upload']);
		add_filter('get_edit_post_link', [__CLASS__, 'filter_edit_post_link'], 10, 3);
		add_filter('wp_insert_post_data', [__CLASS__, 'filter_wp_insert_post_data'], 10, 1);
		add_filter('wp_insert_attachment_data', [__CLASS__, 'filter_wp_insert_attachment_data'], 10, 2);
		add_filter('the_content', [__CLASS__, 'filter_the_content'], 10, 1);
		add_filter('get_comment_author_url', [__CLASS__, 'filter_comment_author_url'], 1, 3);
		add_filter('get_avatar', [__CLASS__, 'filter_avatar'], 1, 5);
	}

	/**
	 *单例模式
	 */
	public static function instance() {
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 *@since 2019.01.16
	 * 限制wp editor上传附件
	 */
	public static function filter_limit_upload($file) {
		// 上传体积限制
		$image_size = $file['size'] / 1024;
		$limit      = wnd_get_option('wnd', 'wnd_max_upload_size') ?: 2048;

		if ($image_size > $limit) {
			$file['error'] = '上传文件不得超过' . $limit . 'KB';
			return $file;
		}

		// 文件信息
		$info = pathinfo($file['name']);
		$ext  = isset($info['extension']) ? '.' . $info['extension'] : null;
		if (!$ext) {
			$file['error'] = '未能获取到文件拓展名';
			return $file;
		}

		// 重命名文件名为随机码：用于美化附件slug，同时实现基本的文件路径加密
		$file['name'] = uniqid('file') . $ext;

		return $file;
	}

	/**
	 *@since 2019.01.31 重写WordPress原生编辑链接到指定的页面
	 */
	public static function filter_edit_post_link($link, $post_id, $context) {
		if (is_admin()) {
			return $link;
		}

		$edit_page = (int) wnd_get_option('wnd', 'wnd_edit_page');
		if ($edit_page) {
			return get_permalink($edit_page) . '?post_id=' . $post_id;
		}
		return $link;
	}

	/**
	 *@since 2019.04.03
	 *apply_filters( 'wp_insert_post_data', $data, $postarr )
	 *防止插入相同标题文章时（功能型post），反复查询post name，故此设置为随机值
	 */
	public static function filter_wp_insert_post_data($data) {
		if (empty($data['post_name'])) {
			$data['post_name'] = uniqid();
		}

		return $data;
	}

	/**
	 *@since 2019.07.18
	 *$data = apply_filters( 'wp_insert_attachment_data', $data, $postarr );
	 *自动给上传的附件依次设置 menu_order
	 *
	 *menu order值为当前附属的post上传附件总次数
	 *@see wnd_action_add_attachment
	 */
	public static function filter_wp_insert_attachment_data($data, $postarr) {
		// 如果已经指定了menu order
		if ($data['menu_order']) {
			return $data;
		}

		$menu_order         = wnd_get_post_meta($data['post_parent'], 'attachment_records') ?: 0;
		$data['menu_order'] = ++$menu_order;

		return $data;
	}

	/**
	 * 付费内容
	 *@since 2018.09.17
	 *设置 文章自定义字段 price
	 *使用WordPress经典编辑器插入 more标签 或者编辑源码插入 <!--more--> 以区分免费内容和付费内容
	 */
	public static function filter_the_content($content) {
		global $post;
		if (!$post) {
			return;
		}

		$user_id       = get_current_user_id();
		$price         = wnd_get_post_price($post->ID);
		$user_money    = wnd_get_user_money($user_id);
		$user_has_paid = wnd_user_has_paid($user_id, $post->ID);
		$primary_color = 'is-' . wnd_get_option('wnd', 'wnd_primary_color');
		$second_color  = 'is-' . wnd_get_option('wnd', 'wnd_second_color');

		$file = wnd_get_post_meta($post->ID, 'file');
		// 价格为空且没有文件，免费文章
		if (!$price and !$file) {
			return $content;
		}

		/**
		 *付费下载
		 */
		if ($file) {

			// 未登录用户
			if (!$user_id) {
				$content .= $price ? '<div class="message ' . $second_color . '"><div class="message-body">付费下载：¥' . $price . '</div></div>' : '';
				$button_text = '请登录后下载';
				$button      = '<div class="field is-grouped is-grouped-centered"><button class="button is-warning" onclick="wnd_ajax_modal(\'wnd_user_center\',\'do=login\')">' . $button_text . '</button></div>';
				$content .= $button;
				return $content;
			}

			if ($user_has_paid) {
				$button_text = '您已购买点击下载';

			} elseif ($user_id == $post->post_author) {
				$button_text = '您发布的下载文件';

			} elseif ($price > 0) {
				$button_text = '付费下载 ¥' . $price;

			} else {
				$button_text = '免费下载';
			}

			// 判断支付
			if ($price > $user_money and !$user_has_paid) {
				$msg = '<div class="message ' . $second_color . ' has-text-centered"><div class="message-body">';
				$msg .= '<p>¥ ' . $price . ' （可用余额：¥ ' . $user_money . '）</p>';
				if (wnd_get_option('wnd', 'wnd_alipay_appid')) {
					$msg .= '<a class="button ' . $primary_color . '" href="' . wnd_order_link($post->ID) . '">在线支付</a>';
					$msg .= '&nbsp;&nbsp;';
					$msg .= '<a class="button ' . $primary_color . ' is-outlined" onclick="wnd_ajax_modal(\'wnd_user_recharge_form\')">余额充值</a>';
				} else {
					$msg .= '余额不足';
				}
				$msg .= '</div></div>';

				$content .= $msg;

				// 无论是否已支付，均需要提交下载请求，是否扣费将在wnd_ajax_pay_for_download内部判断
			} else {
				$form = new Wnd_Form_WP();
				$form->add_hidden('post_id', $post->ID);
				$form->set_action('wnd_pay_for_download');
				$form->set_submit_button($button_text);
				$form->build();

				$content .= $form->html;
			}

			return $content;
		}

		/**
		 *付费阅读
		 */
		//查找是否有more标签，否则免费部分为空（全文付费）
		$content_array = explode('<!--more-->', $post->post_content, 2);
		if (count($content_array) == 1) {
			$content_array = ['', $post->post_content];
		}
		list($free_content, $paid_content) = $content_array;

		// 未登录用户
		if (!$user_id) {
			$content = '<div class="free-content">' . $free_content . '</div>';
			$content .= '<div class="paid-content"><div class="message ' . $second_color . '"><div class="message-body">付费内容：¥' . $price . '</div></div></div>';
			$button = '<div class="field is-grouped is-grouped-centered"><button class="button is-warning" onclick="wnd_ajax_modal(\'wnd_user_center\',\'do=login\')">请登录</button></div>';
			$content .= $button;
			return $content;
		}

		// 已支付
		if ($user_has_paid) {
			$content = '<div class="free-content">' . $free_content . '</div>';
			$content .= '<div class="paid-content">' . $paid_content . '</div>';
			$content .= '<div class="message ' . $second_color . '"><div class="message-body">您已付费：¥' . $price . '</div></div>';
			return $content;
		}

		// 作者本人
		if ($user_id == $post->post_author) {
			$content = '<div class="free-content">' . $free_content . '</div>';
			$content .= '<div class="paid-content">' . $paid_content . '</div>';
			$content .= '<div class="message ' . $second_color . '"><div class="message-body">您的付费文章：¥' . $price . '</div></div>';
			return $content;
		}

		// 已登录未支付
		$content = '<div class="free-content">' . $free_content . '</div>';
		$content .= '<div class="paid-content"><p class="ajax-message">以下为付费内容</p></div>';
		$button_text = '付费阅读： ¥' . wnd_get_post_price($post->ID);

		if ($price > $user_money) {
			$msg = '<div class="message ' . $second_color . ' has-text-centered"><div class="message-body">';
			$msg .= '<p>¥ ' . $price . ' （可用余额：¥ ' . $user_money . '）</p>';
			if (wnd_get_option('wnd', 'wnd_alipay_appid')) {
				$msg .= '<a class="button ' . $primary_color . '" href="' . wnd_order_link($post->ID) . '">在线支付</a>';
				$msg .= '&nbsp;&nbsp;';
				$msg .= '<a class="button ' . $primary_color . ' is-outlined" onclick="wnd_ajax_modal(\'wnd_user_recharge_form\')">余额充值</a>';
			} else {
				$msg .= '余额不足';
			}
			$msg .= '</div></div>';

			$content .= $msg;
		} else {
			$form = new Wnd_Form_WP();
			$form->add_hidden('post_id', $post->ID);
			$form->set_action('wnd_pay_for_reading');
			$form->set_submit_button($button_text);
			$form->build();

			$content .= $form->html;
		}

		return $content;
	}

	/**
	 *@since 2019.01.16
	 *注册用户的评论链接到作者页面
	 */
	public static function filter_comment_author_url($url, $id, $comment) {
		if ($comment->user_id) {
			return get_author_posts_url($comment->user_id);
		}
		return $url;
	}

	/**
	 *@since 初始化
	 * 调用用户字段 avatar存储的图像id，或者avatar_url存储的图像地址做自定义头像，并添加用户主页链接
	 */
	public static function filter_avatar($avatar, $id_or_email, $size, $default, $alt) {

		// 默认头像
		$avatar_url = wnd_get_option('wnd', 'wnd_default_avatar_url') ?: WND_URL . 'static/images/avatar.jpg';

		// 获取用户 ID
		if (is_numeric($id_or_email)) {
			$user_id = (int) $id_or_email;
			//评论获取
		} elseif (is_object($id_or_email)) {
			$user_id = (int) $id_or_email->user_id ?? 0;
			// 邮箱获取
		} else {
			$user    = get_user_by('email', $id_or_email);
			$user_id = $user ? $user->ID : 0;
		}
		$user_id = $user_id ?? 0;

		//已登录用户调用字段头像
		if ($user_id) {
			if (wnd_get_user_meta($user_id, 'avatar')) {
				$avatar_id  = wnd_get_user_meta($user_id, 'avatar');
				$avatar_url = wp_get_attachment_url($avatar_id) ?: $avatar_url;
				/**
				 *@since 2019.07.23
				 * 统一按阿里云oss裁剪缩略图
				 */
				$avatar_url = wnd_get_thumbnail_url($avatar_url, $size, $size);

			} elseif (wnd_get_user_meta($user_id, 'avatar_url')) {
				$avatar_url = wnd_get_user_meta($user_id, 'avatar_url') ?: $avatar_url;

			}

		}

		//头像
		$avatar = "<img alt='{$alt}' src='$avatar_url' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";

		//注册用户，添加链接
		if ($user_id and !is_admin()) {
			$author_url = get_author_posts_url($user_id);
			$avatar     = sprintf(
				'<a href="%s" rel="external nofollow" class="url">%s</a>',
				$author_url,
				$avatar
			);
		}

		return $avatar;
	}
}
