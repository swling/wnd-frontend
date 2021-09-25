<?php
namespace Wnd\Permission;

use Exception;

/**
 * 文章发布管理权限：Post Permission Controller
 *
 * 默认权限如下：
 * - 写入内容：登录用户可发布内容
 * - 编辑权限：WP默认 current_user_can('edit_post', $this->post_id)
 * - 更新权限：WP默认 current_user_can('edit_post', $this->post_id)
 * - 更改状态：仅管理员可设置为 publish
 *
 * @since 0.9.36
 */
class Wnd_PPC {

	protected $post_type;
	protected $post;
	protected $post_id;
	protected $post_status;
	protected $post_title;

	protected $user_id;

	public function __construct(string $post_type) {
		$this->user_id   = get_current_user_id();
		$this->post_type = $post_type;
	}

	/**
	 * 根据post type 实例化
	 */
	public static function get_instance(string $post_type): Wnd_PPC {
		if (!$post_type) {
			throw new Exception($post_type . '未设指定Post Type');
		}

		$instance = new self($post_type);

		/**
		 * 可通过此 filter 指定 PPC 实例化对象
		 * 返回的实例化对象必须为本本类的子类
		 */
		$instance = apply_filters('wnd_ppc_instance', $instance, $post_type);
		if (!$instance) {
			throw new Exception(__('无效的 PPC 实例类型：', 'wnd') . $post_type);
		}

		return $instance;
	}

	/**
	 * 设定Post ID
	 */
	public function set_post_id(int $post_id) {
		$this->post_id = $post_id;
		$this->post    = $this->post_id ? get_post($this->post_id) : false;
		if (!$this->post) {
			throw new Exception('指定ID无效');
		}

		if ($this->post_type != $this->post->post_type) {
			throw new Exception('指定ID 的 Post Type 与实例化 Post Type 不一致');
		}
	}

	/**
	 * 设定Post Status
	 */
	public function set_post_status(string $post_status) {
		$this->post_status = $post_status;
	}

	/**
	 * 设定Post Title
	 */
	public function set_post_title(string $post_title) {
		$this->post_title = $post_title;
	}

	/**
	 * 基础创建权限检查
	 */
	public function check_create() {
		if (!$this->user_id) {
			throw new Exception('请登录');
		}
	}

	/**
	 * 基础写入权限检查
	 */
	public function check_insert() {
		if (!$this->user_id) {
			throw new Exception('请登录');
		}
	}

	/**
	 * 基础编辑权限检查
	 *
	 * - 本权限用于前端控制
	 * - 主要依据现有状态控制是否可以打开编辑界面
	 * - 不对修改更新的数据做检查
	 * - 数据写入权限检测请在 $this->check_update() 中实现
	 *
	 * @since 0.9.37
	 */
	public function check_edit() {
		if (!$this->post) {
			throw new Exception('获取内容失败');
		}

		// 更新权限
		if (!current_user_can('edit_post', $this->post_id)) {
			throw new Exception('权限错误');
		}
	}

	/**
	 * 基础更新权限检查
	 */
	public function check_update() {
		if (!$this->post) {
			throw new Exception('获取内容失败');
		}

		// 更新权限
		if (!current_user_can('edit_post', $this->post_id)) {
			throw new Exception('权限错误');
		}
	}

	/**
	 * 基础更新文章状态权限
	 * - 非管理员不等直接发布公开
	 */
	public function check_status_update() {
		if (wnd_is_manager()) {
			return true;
		}

		if ('publish' == $this->post_status) {
			throw new Exception('权限错误');
		}
	}
}
