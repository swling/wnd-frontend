<?php
namespace Wnd\Action\Admin;

use Exception;
use Wnd\Action\Wnd_Action_Admin;

/**
 * 实体商铺配置信息
 * @since 0.9.87
 */
class Wnd_Update_Store_Settings extends Wnd_Action_Admin {

	protected $verify_sign = false;

	private $option_name = 'wnd_store_settings';
	private $option_data;

	protected function execute(): array {
		if (update_option($this->option_name, $this->option_data, false)) {
			return ['status' => 1, 'msg' => __('更新成功', 'wnd')];
		} else {
			throw new Exception(__('更新失败', 'wnd'));
		}
	}

	protected function parse_data() {
		// 更新方式：附加数据，将本次提交数据和数据库数据合并
		$old_option        = get_option($this->option_name);
		$old_option        = is_array($old_option) ? $old_option : [];
		$this->option_data = array_merge($old_option, $this->data);

		// 剔除空值
		$this->option_data = wnd_array_filter($this->option_data);
	}
}
