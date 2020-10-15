<?php
namespace Wnd\Module;

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
class Wnd_User_Page extends Wnd_Module {

	public static function build($args = []): string{
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

		get_header();
		echo '<main id="user-page-container" class="column">';
		echo '<div class="main box">';
		echo static::build_page_module($args);
		echo '</div>';
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
	protected static function build_page_module($args) {
		extract($args);

		if ($module) {
			$class = \Wnd\Controller\Wnd_API::parse_class($module, 'Module');
			return $class::render();
		}

		// 根据 URL 参数 $_GET['action'] = （submit/edit） 调用对应内容发布/编辑表单模块
		if ('submit' == $action) {
			// 主题定义的表单
			$class = '\Wndt\Module\\Wndt_Post_Form_' . $post_type;
			if (class_exists($class)) {
				return $class::render();
			}

			// 插件默认表单
			$class = '\Wnd\Module\\Wnd_Post_Form_' . $post_type;
			if (class_exists($class)) {
				return $class::render();
			}

			// 未找到匹配表单
		}

		// 根据 URL 参数 $_GET['action'] = （submit/edit） 调用对应内容发布/编辑表单模块
		if ('edit' == $action) {
			$edit_post = $post_id ? get_post($post_id) : false;
			if (!$edit_post) {
				return static::build_error_message(__('ID 无效', 'wnd'));
			}

			// 主题定义的表单
			$class = '\Wndt\Module\\Wndt_Post_Form_' . $edit_post->post_type;
			if (class_exists($class)) {
				return $class::render(['post_id' => $post_id]);
			}

			// 附件编辑表单
			if ('attachment' == $edit_post->post_type) {
				return \Wnd\Module\Wnd_Post_Form_Attachment::render(['attachment_id' => $post_id]);
			}

			// 插件默认表单
			$class = '\Wnd\Module\\Wnd_Post_Form_' . $edit_post->post_type;
			if (class_exists($class)) {
				return $class::render(
					[
						'post_id'     => $post_id,
						'post_parent' => $edit_post->post_parent,
						'is_free'     => false,
					]
				);
			}
		}

		// 默认用户中心：注册、登录、账户管理，内容管理，财务管理等
		if (class_exists('\Wndt\Module\Wndt_User_Center')) {
			return \Wndt\Module\Wndt_User_Center::render();
		} else {
			return \Wnd\Module\Wnd_User_Center::render();
		}
	}
}
