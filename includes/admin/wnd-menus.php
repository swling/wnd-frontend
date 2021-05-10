<?php
namespace Wnd\Admin;

use Wnd\View\Wnd_Form_Option;

/**
 * @since 0.8.62
 * WP后台选项配置菜单
 */
class Wnd_Menus {
	// 菜单基本属性
	protected $page_title = 'Wnd Frontend Setting';
	protected $menu_title = 'Wnd Frontend';
	protected $capability = 'administrator';
	protected $menu_slug  = 'wnd-frontend';

	// 主菜单 slug
	protected static $main_slug = 'wnd-frontend';

	// 数据存储 option name
	protected $option_name = 'wnd';

	// 当前表单数据是否合并到已有数据，否则将以此表单数据存储为 option
	protected $append = true;

	// 当前实例是否为子菜单
	protected $is_submenu = false;

	/**
	 *定义子菜单
	 * - 将依次循环拼接类名：Wnd_Admin_Menu_ {$slug} 并实例化
	 */
	protected $sub_menus = ['Accesskey', 'Transaction', 'Alipay', 'Sms', 'Captcha', 'Social_Login', 'OSS'];

	/**
	 *构造
	 *
	 */
	public function __construct() {
		/**
		 *判断当前实例是否为继承本类的子类
		 */
		$this->is_submenu = is_subclass_of($this, __CLASS__);

		/**
		 * - 注册菜单
		 * - 加载静态资源
		 */
		add_action('admin_menu', [$this, 'add_menu']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);

		/**
		 *根据配置实例化子菜单
		 */
		if (!$this->is_submenu) {
			foreach ($this->sub_menus as $slug) {
				$class_name = __NAMESPACE__ . '\\' . 'Wnd_Menu_' . $slug;
				new $class_name();
			}
		}
	}

	/**
	 *仅在定义的菜单页面加载静态资源
	 */
	public function enqueue_scripts($hook_suffix) {
		if ('toplevel_page_' . $this->menu_slug != $hook_suffix and 0 !== stripos($hook_suffix, 'wnd-frontend')) {
			return;
		}

		wnd_enqueue_scripts();
	}

	/**
	 *注册菜单
	 */
	public function add_menu() {
		if ($this->is_submenu) {
			add_submenu_page(static::$main_slug, $this->page_title, $this->menu_title, $this->capability, $this->menu_slug, [$this, 'build_page']);
		} else {
			add_menu_page($this->page_title, $this->menu_title, $this->capability, $this->menu_slug, [$this, 'build_page']);
		}
	}

	/**
	 *构建选项页面
	 */
	public function build_page() {
		echo '<div class="wrap">' . $this->build_form() . '</div>';
	}

	/**
	 *构造选项表单
	 */
	public function build_form() {
		$form = new Wnd_Form_Option($this->option_name, $this->append);

		$form->add_radio(
			[
				'name'     => 'static_host',
				'options'  => ['本地' => 'local', 'jsdeliver' => 'jsdeliver', '关闭' => 'close'],
				'required' => 'required',
				'class'    => 'is-checkradio is-danger',
				'label'    => '静态资源',
			]
		);

		$form->add_page_select('front_page', '前端页面', true);

		$form->add_text(
			[
				'name'        => 'agreement_url',
				'label'       => '注册协议',
				'required'    => false,
				'placeholder' => '新用户注册协议页面',
			]
		);

		$form->add_url(
			[
				'name'        => 'reg_redirect_url',
				'label'       => '注册跳转',
				'required'    => false,
				'placeholder' => '新用户注册后跳转地址',
			]
		);

		$form->add_url(
			[
				'name'        => 'default_avatar_url',
				'label'       => '默认头像',
				'required'    => false,
				'placeholder' => '默认用户头像地址',
			]
		);

		$form->add_number(
			[
				'name'        => 'max_upload_size',
				'placeholder' => '前端文件最大上传限制（默认2048KB，不得大于服务器设置）',
				'label'       => '前端最大上传（KB）',
				'min'         => 1,
				'step'        => 1,
			]
		);

		$form->add_number(
			[
				'name'        => 'max_stick_posts',
				'placeholder' => '限制置顶文章数量，按新旧顺序保留（非WordPress原生置顶功能）',
				'label'       => '最大置顶文章数量',
				'min'         => 1,
				'step'        => 1,
			]
		);

		$form->add_radio(
			[
				'name'    => 'disable_locale',
				'options' => ['启用' => 0, '禁用' => 1],
				'label'   => '禁用语言包',
				'class'   => 'is-checkradio is-danger',
			]
		);

		$form->add_select(
			[
				'name'     => 'primary_color',
				'options'  => [
					'primary' => 'primary',
					'success' => 'success',
					'info'    => 'info',
					'link'    => 'link',
					'warning' => 'warning',
					'danger'  => 'danger',
					'dark'    => 'dark',
					'black'   => 'black',
					'light'   => 'light',
				],
				'label'    => '主色调',
				'required' => false,
			]
		);

		$form->add_select(
			[
				'name'     => 'second_color',
				'options'  => [
					'primary' => 'primary',
					'success' => 'success',
					'info'    => 'info',
					'link'    => 'link',
					'warning' => 'warning',
					'danger'  => 'danger',
					'dark'    => 'dark',
					'black'   => 'black',
					'light'   => 'light',
				],
				'label'    => '辅色调',
				'required' => false,
			]
		);

		$form->add_radio(
			[
				'name'    => 'disable_rest_nonce',
				'options' => ['启用' => 0, '禁用' => 1],
				'label'   => 'Rest Nonce',
				'help'    => ['text' => '是否禁用 Rest Nonce（当您采用其他身份校验如 Token 或执行跨域类操作的时候，可能需要禁用 WP Rest Nonce）'],
				'class'   => 'is-checkradio is-danger',
			]
		);

		$form->set_submit_button('保存', 'is-danger');
		$form->build();

		return $form->html;
	}
}
