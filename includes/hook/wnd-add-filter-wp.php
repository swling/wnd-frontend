<?php
namespace Wnd\Hook;

use Wnd\Utility\Wnd_Singleton_Trait;

/**
 * WP Filter
 */
class Wnd_Add_Filter_WP {

	use Wnd_Singleton_Trait;

	private function __construct() {
		add_filter('auth_cookie_expiration', [__CLASS__, 'filter_auth_cookie_expiration'], 10, 3);
		add_filter('wp_handle_upload_prefilter', [__CLASS__, 'filter_limit_upload']);
		add_filter('get_edit_post_link', [__CLASS__, 'filter_edit_post_link'], 10, 3);
		add_filter('post_type_link', [__CLASS__, 'filter_post_type_link'], 10, 2);
		add_filter('wp_insert_post_data', [__CLASS__, 'filter_wp_insert_post_data'], 10, 2);
		add_filter('wp_insert_attachment_data', [__CLASS__, 'filter_wp_insert_attachment_data'], 10, 2);
		add_filter('get_comment_author_url', [__CLASS__, 'filter_comment_author_url'], 10, 3);
		add_filter('get_avatar', [__CLASS__, 'filter_avatar'], 10, 5);
		add_filter('get_comment_author', [__CLASS__, 'filter_comment_author'], 10, 3);
		add_filter('posts_join', [__CLASS__, 'join_posts_analyses_table'], 10, 2);
		add_filter('posts_orderby', [__CLASS__, 'order_by_posts_analyses'], 10, 2);
	}

	/**
	 * apply_filters( 'auth_cookie_expiration', 14 * DAY_IN_SECONDS, $user_id, $remember );
	 * @since 0.9.71
	 * 账号 cookie 有效时间
	 */
	public static function filter_auth_cookie_expiration($seconds, $user_id, $remember): int {
		return $remember ? 180 * DAY_IN_SECONDS : $seconds;
	}

	/**
	 * 限制wp editor上传附件
	 * @since 2019.01.16
	 */
	public static function filter_limit_upload($file) {
		// 排除后台
		if (is_admin()) {
			return $file;
		}

		// 上传体积限制
		$image_size = $file['size'] / 1024;
		$limit      = wnd_get_config('max_upload_size') ?: 2048;

		if ($image_size > $limit) {
			$file['error'] = '上传文件不得超过' . $limit . 'KB';
			return $file;
		}

		// 文件信息
		$info = pathinfo($file['name']);
		$ext  = isset($info['extension']) ? '.' . $info['extension'] : '';
		if (!$ext) {
			$file['error'] = '未能获取到文件拓展名';
			return $file;
		}

		// 重命名文件名为随机码：用于美化附件slug，同时实现基本的文件路径加密
		$file['name'] = uniqid('file') . $ext;

		return $file;
	}

	/**
	 * 重写WordPress原生编辑链接到指定的页面
	 * @since 2019.01.31
	 */
	public static function filter_edit_post_link($link, $post_id, $context) {
		if (is_admin()) {
			return $link;
		}

		$front_page_url = wnd_get_front_page_url();
		if ($front_page_url) {
			return add_query_arg(['action' => 'edit', 'post_id' => $post_id], $front_page_url);
		}
		return $link;
	}

	/**
	 * 重写插件定义的功能型非公开型 POST 链接，以通过 Module 展示相关详情
	 * @since 0.9.0
	 */
	public static function filter_post_type_link($link, $post) {
		if (is_admin()) {
			return $link;
		}

		if (get_post_type_object($post->post_type)->public) {
			return $link;
		}

		$url = wnd_get_front_page_url() ?: wnd_get_router_url();
		return add_query_arg(['module' => 'post/wnd_post_detail', 'post_id' => $post->ID], $url);
	}

	/**
	 * apply_filters( 'wp_insert_post_data', $data, $postarr )
	 * - 防止插入相同标题文章时（功能型post），反复查询post name，故此设置为随机值
	 * @since 2019.04.03
	 */
	public static function filter_wp_insert_post_data($data, $postarr) {
		if (empty($data['post_name'])) {
			$data['post_name'] = uniqid();
		}

		return $data;
	}

	/**
	 * $data = apply_filters( 'wp_insert_attachment_data', $data, $postarr );
	 * 自动给上传的附件依次设置 menu_order
	 * menu order值为当前附属的post上传附件总次数
	 * @see wnd_action_add_attachment
	 * @since 2019.07.18
	 */
	public static function filter_wp_insert_attachment_data($data, $postarr) {
		// 如果已经指定了menu order或者附件并未附属到post
		if ($data['menu_order'] or !$data['post_parent']) {
			return $data;
		}

		$menu_order         = wnd_get_post_meta($data['post_parent'], 'attachment_records') ?: 0;
		$data['menu_order'] = ++$menu_order;

		return $data;
	}

	/**
	 * 注册用户的评论链接到作者页面
	 * @time 2020.08.16
	 * 新增检测用户是否存在，避免已删除用户的评论产生无效链接
	 * @since 2019.01.16
	 */
	public static function filter_comment_author_url($url, $id, $comment) {
		if ($comment->user_id and get_user_by('id', $comment->user_id)) {
			return get_author_posts_url($comment->user_id);
		}
		return $url;
	}

	/**
	 * 调用用户字段 avatar存储的图像id，或者avatar_url存储的图像地址做自定义头像，并添加用户主页链接
	 * @since 初始化
	 */
	public static function filter_avatar($avatar, $id_or_email, $size, $default, $alt) {
		// 获取用户 ID
		$user_id = 0;
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
		$user_id = ($user_id and get_user_by('id', $user_id)) ? $user_id : 0;

		//头像
		$avatar_url = wnd_get_avatar_url($user_id, $size);
		$avatar     = "<img alt='{$alt}' src='$avatar_url' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";

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

	/**
	 * 登录用户的评论名称同步显示为昵称
	 * @since 0.9.11
	 */
	public static function filter_comment_author($author, $comment_ID, $comment) {
		if (!$comment->user_id) {
			return $author;
		}

		$user = get_userdata($comment->user_id);
		if (!$user) {
			return $author;
		}

		return $user->display_name ?: $author;
	}

	/**
	 * posts 独立分析表排序
	 * @since 0.9.74
	 *
	 */
	public static function join_posts_analyses_table($join, $query) {
		global $wpdb;
		if (in_array($query->query_vars['orderby'], ['today_views', 'week_views', 'month_views', 'total_views', 'favorites_count', 'rating_score'])) {
			$join .= " LEFT JOIN {$wpdb->wnd_analyses} pa ON {$wpdb->posts}.ID = pa.post_id";
		}

		return $join;
	}

	public static function order_by_posts_analyses($orderby, $query) {
		/**
		 * WP Query 限定了 orderby 参数，因此不能使用 $query->get('orderby') 获取
		 * @see WP_Query::parse_orderby()
		 */
		$orderby_var = $query->query_vars['orderby'];
		if (!$orderby_var) {
			return $orderby;
		}

		$current_date   = wnd_date('Y-m-d');
		$start_of_week  = wnd_date('Y-m-d', strtotime('monday this week'));
		$start_of_month = wnd_date('Y-m-01');

		if ($orderby_var == 'favorites_count') {
			$orderby = 'pa.favorites_count ' . $query->get('order');
		} elseif ($orderby_var == 'rating_score') {
			$orderby = 'pa.rating_score ' . $query->get('order');
		} elseif ($orderby_var == 'today_views') {
			$orderby = "CASE
                        WHEN pa.last_viewed_date = '{$current_date}' THEN pa.today_views
                        ELSE 0
                    END " . $query->get('order');
		} elseif ($orderby_var == 'week_views') {
			$orderby = "CASE
                        WHEN pa.last_viewed_date >= '{$start_of_week}' THEN pa.week_views
                        ELSE 0
                    END " . $query->get('order');
		} elseif ($orderby_var == 'month_views') {
			$orderby = "CASE
                        WHEN pa.last_viewed_date >= '{$start_of_month}' THEN pa.month_views
                        ELSE 0
                    END " . $query->get('order');
		} elseif ($orderby_var == 'total_views') {
			$orderby = 'pa.total_views ' . $query->get('order');
		}

		return $orderby;
	}

}
