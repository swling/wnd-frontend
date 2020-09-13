<?php
namespace Wnd\Action;

use Exception;
use Wnd\Utility\Wnd_Form_Data;

/**
 *@since 2020.06.24
 *更新options
 */
class Wnd_Update_Option extends Wnd_Action_Ajax_Root {

	/**
	 *本操作需要分类解析表单数据，故移除通用表单解析数据
	 */
	protected $parse_data = false;

	public function execute(): array{
		$option_name = $_POST['option_name'];
		$append      = (bool) $_POST['append'];

		// 实例化当前提交的表单数据
		$form_data   = new Wnd_Form_Data();
		$option_data = $form_data->get_option_data($option_name);

		// 更新方式：附加数据，将本次提交数据和数据库数据合并
		if ($append) {
			$old_option  = get_option($option_name);
			$old_option  = is_array($old_option) ? $old_option : [];
			$option_data = array_merge(get_option($option_name), $option_data);
		}

		if (update_option($option_name, $option_data, false)) {
			return ['status' => 1, 'msg' => __('更新成功', 'wnd')];
		} else {
			throw new Exception(__('更新失败', 'wnd'));
		}
	}
}
