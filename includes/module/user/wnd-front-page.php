<?php
namespace Wnd\Module\User;

use Exception;
use Wnd\Module\Wnd_Module_Html;
use Wnd\Query\Wnd_Menus;

/**
 * 封装前端中心页面
 * Template Name: 用户中心
 *
 * 页面功能：
 * - 根据 URL 参数 $_GET['module'] 呈现对应 UI 模块
 * - 根据 URL 参数 $_GET['action'] = （submit/edit） 调用对应内容发布/编辑表单模块
 * - 默认为用户中心：注册、登录、账户管理，内容管理，财务管理等
 *
 * @since 0.9.0
 */
class Wnd_Front_Page extends Wnd_Module_Html {

	protected static function build(array $args = []): string {
		$defaults = [
			'module'    => '',
			'action'    => '',
			'post_type' => 'post',
			'post_id'   => 0,
		];

		// 解析并合并参数：WP 环境中 $_GET 参数无法直接传递 ['post_type'] 统一为 ['type']
		$args['post_type'] = $args['post_type'] ?? ($args['type'] ?? '');
		$args              = wp_parse_args($args, $defaults);

		// 解析 Module 并捕获可能抛出的异常
		$default_module = apply_filters('wnd_user_page_default_module', 'user/wnd_user_overview');
		try {
			$module = static::handle_module($args) ?: '';
		} catch (Exception $e) {
			$module = wnd_notification($e->getMessage(), 'is-danger');
		}
		$module = $module ? ('<div id="ajax-module" class="content box">' . $module . '</div>') : '';

		// 构造页面 HTML
		get_header();
		echo '<script>var wnd_menus_data = ' . json_encode(Wnd_Menus::get()) . '; var default_module = "' . $default_module . '";</script>';
		echo '<main id="user-page-container" class="column">';

		if ($module) {
			echo $module;
		} else {
			$html = file_get_contents(WND_PATH . '/includes/module-vue/user/dashboard.vue');
			$html = apply_filters('wnd_user_page', $html);
			echo $html;
		}

		echo '</main>';
		get_footer();

		return '';
	}

	/**
	 * - 根据参数获取 Module 模块
	 * - Action：Submit 及 Edit
	 * - 默认返回用户中心
	 *
	 * 主题可拓展或修改内容表单：\Wndt\Module\Wndt_Post_Form_{$post_type}
	 * 主题可拓展或修改用户中心：\Wndt\Module\Wndt_User_Center;
	 */
	private static function handle_module(array $args): string {
		extract($args);
		unset($args['module']);
		unset($args['action']);

		if ($module) {
			return '<script>wnd_ajax_embed("#ajax-module", "' . $module . '", ' . json_encode($args) . ')</script>';
		}

		// 根据 URL 参数 $_GET['action'] = （submit/edit） 调用对应内容发布/编辑表单模块
		if ('submit' == $action) {
			$module = static::get_post_form_module($post_type);
			return '<script>wnd_ajax_embed("#ajax-module", "' . $module . '", ' . json_encode($args) . ');</script>';
		}

		// 根据 URL 参数 $_GET['action'] = （submit/edit） 调用对应内容发布/编辑表单模块
		if ('edit' == $action) {
			$edit_post = $post_id ? get_post($post_id) : false;
			if (!$edit_post) {
				throw new Exception(__('ID 无效', 'wnd'));
			}

			$post_type = $edit_post->post_type;
			$params    = json_encode([
				'post_id'       => $post_id,
				'attachment_id' => $post_id,
				'post_parent'   => $edit_post->post_parent,
				'is_free'       => 0,
			]);

			// 主题定义的表单优先，其次为插件表单
			$module = static::get_post_form_module($post_type, $edit_post);
			return '<script>wnd_ajax_embed("#ajax-module", "' . $module . '", ' . $params . ');</script>';
		}

		// 未登录用户返回用户中心
		if (!is_user_logged_in()) {
			return '<script>wnd_ajax_embed("#ajax-module", "User/Wnd_User_Center", ' . json_encode($args) . ');</script>';
		}

		return '';
	}

	/**
	 * 根据 Post Type 查找对应表单模块
	 * @since 0.9.39
	 */
	private static function get_post_form_module(string $post_type, $edit_post=null): string {
		// 主题定义的表单优先，其次为插件表单
		if(wnd_is_revision($edit_post)){
			$post_type = get_post_type($edit_post->post_parent);
		}
		$module = 'Wndt_Post_Form_' . $post_type;
		$class  = 'Wndt\\Module\\Post\\' . $module;
		if (!class_exists($class)) {
			$module = 'Wnd_Post_Form_' . $post_type;
			$class  = 'Wnd\\Module\\Post\\' . $module;
		}

		if (!class_exists($class)) {
			throw new Exception(__('未定义表单', 'wnd') . ' : ' . $class);
		}

		return 'Post/' . $module;
	}

}
