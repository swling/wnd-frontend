<?php
namespace Wnd\Action\Admin;

use Exception;
use Wnd\Action\Wnd_Action_Root;

/**
 * 更新options
 * @since 2020.06.24
 */
class Wnd_Update_Option extends Wnd_Action_Root {

	protected function execute(): array{
		$option_name = $this->data['option_name'];
		$append      = (bool) $this->data['append'];
		$option_data = $this->request->get_option_data($option_name);

		// 更新方式：附加数据，将本次提交数据和数据库数据合并
		if ($append) {
			$old_option  = get_option($option_name);
			$old_option  = is_array($old_option) ? $old_option : [];
			$option_data = array_merge(get_option($option_name), $option_data);
		}

		// 剔除空值
		$option_data = wnd_array_filter($option_data);

		// Update
		if (update_option($option_name, $option_data, false)) {
			return ['status' => 1, 'msg' => __('更新成功', 'wnd')];
		} else {
			throw new Exception(__('更新失败', 'wnd'));
		}
	}
}
