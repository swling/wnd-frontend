<?php
namespace Wnd\Action\Admin;

use Exception;
use Wnd\Action\Wnd_Action_Root;

/**
 * 更新options
 * @since 2020.06.24
 */
class Wnd_Update_Option extends Wnd_Action_Root {

	private $option_name;
	private $append;
	private $option_data;

	protected function execute(): array {
		if (update_option($this->option_name, $this->option_data, false)) {
			return ['status' => 1, 'msg' => __('更新成功', 'wnd')];
		} else {
			throw new Exception(__('更新失败', 'wnd'));
		}
	}

	protected function parse_data() {
		$this->option_name = $this->data['option_name'];
		$this->append      = (bool) ($this->data['append'] ?? true);
		$this->option_data = $this->request->get_option_data($this->option_name);

		// 更新方式：附加数据，将本次提交数据和数据库数据合并
		if ($this->append) {
			$old_option        = get_option($this->option_name);
			$old_option        = is_array($old_option) ? $old_option : [];
			$this->option_data = array_merge($old_option, $this->option_data);
		}

		// 剔除空值
		$this->option_data = wnd_array_filter($this->option_data);
	}
}
