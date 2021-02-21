<?php
namespace Wnd\Module;

use Exception;
use Wnd\JsonGet\Wnd_Menus;
use Wnd\Module\Wnd_User_Center;

/**
 *封装用户中心页面
 *@since 0.9.0
 *
 *Template Name: 用户中心
 *
 * 页面功能：
 * - 根据 URL 参数 $_GET['state'] 处理社交登录（绝大部分社交登录均支持在回调 URL 中添加 $_GET['state']，如有例外后续补充处理）
 * - 根据 URL 参数 $_GET['module'] 呈现对应 UI 模块
 * - 根据 URL 参数 $_GET['action'] = （submit/edit） 调用对应内容发布/编辑表单模块
 * - 默认为用户中心：注册、登录、账户管理，内容管理，财务管理等
 */
class Wnd_User_Page extends Wnd_Module_Html {

	protected static function build(array $args = []): string{
		$defaults = [
			'module'    => '',
			'action'    => '',
			'state'     => '',
			'post_type' => 'post',
			'post_id'   => 0,
		];

		// WP 环境中 $_GET 参数无法直接传递 ['post_type'] 统一为 ['type']
		$args['post_type'] = $args['post_type'] ?? ($args['type'] ?? '');

		// 解析并合并参数
		$args = wp_parse_args($args, $defaults);

		//监听社交登录 可能有跳转，因此需要在header之前
		if ($args['state'] ?? '') {
			$domain       = \Wnd\Utility\Wnd_Login_Social::parse_state($args['state'])['domain'];
			$Login_Social = \Wnd\Utility\Wnd_Login_Social::get_instance($domain);
			$Login_Social->login();
			return '';
		}

		// 加载脚本
		wp_enqueue_script('wnd-menus', WND_URL . 'static/js/wnd-menus.js', ['wnd-vue'], WND_VER);
		wp_enqueue_script('wnd-front-page', WND_URL . 'static/js/wnd-front-page.js', ['wnd-vue', 'wnd-menus'], WND_VER);
		$module = static::handle_module($args) ?: '';
		$module = $module ? ('<div id="ajax-module" class="content box">' . $module . '</div>') : '';

		get_header();
		echo '<script>var wnd_menus_data = ' . json_encode(Wnd_Menus::get()) . ';</script>';
		echo '<main id="user-page-container" class="column">';
		echo $module ?: static::build_user_page();
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
	protected static function handle_module($args): string{
		extract($args);

		if ($module) {
			$params = $_GET;
			unset($params['module']);
			return '<script>wnd_ajax_embed("#ajax-module", "' . $module . '", ' . json_encode($params) . ')</script>';
		}

		// 根据 URL 参数 $_GET['action'] = （submit/edit） 调用对应内容发布/编辑表单模块
		if ('submit' == $action) {
			// 主题定义的表单优先，其次为插件表单
			$module = 'Wndt_Post_Form_' . $post_type;
			$class  = 'Wndt\\Module\\' . $module;
			if (!class_exists($class)) {
				$module = 'Wnd_Post_Form_' . $post_type;
				$class  = 'Wnd\\Module\\' . $module;
			} elseif (!class_exists($class)) {
				throw new Exception($post_type . __('未定义表单', 'wnd'));
			}

			return '<script>wnd_ajax_embed("#ajax-module", "' . $module . '")</script>';
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
			$module = 'Wndt_Post_Form_' . $post_type;
			$class  = 'Wndt\\Module\\' . $module;
			if (!class_exists($class)) {
				$module = 'Wnd_Post_Form_' . $post_type;
				$class  = 'Wnd\\Module\\' . $module;
			} elseif (!class_exists($class)) {
				throw new Exception($post_type . __('未定义表单', 'wnd'));
			}

			return '<script>wnd_ajax_embed("#ajax-module", "' . $module . '", ' . $params . ');</script>';
		}

		return '';
	}

	// 常规用户面板
	protected static function build_user_page(): string {
		if (!is_user_logged_in()) {
			$html = '<div id="user-center" class="columns">';
			$html .= '<div class="column"><div class="box">' . Wnd_User_Center::render() . '</div></div>';
			$html .= '</div>';
			return $html;
		}

		/**
		 *登录用户用户中心默认模块
		 */
		$user_page_default_module = apply_filters('wnd_user_page_default_module', 'wnd_user_overview');

		$html = '
		<div id="user-center" class="columns">
		<div class="column is-narrow is-hidden-mobile">
		<div id="wnd-menus" class="box"><div id="app-menus"></div></div>
		</div>

		<div class="column"><div id="ajax-module" class="box"></div></div>
		</div><script>wnd_render_menus("#app-menus", wnd_menus_data)</script>';

		/**
		 * 默认用户中心：注册、登录、账户管理，内容管理，财务管理等
		 */
		return apply_filters('wnd_user_page', $html);
	}
}
