<?php
namespace Wnd\Action;

use Exception;
use Wnd\Utility\Wnd_Form_Data;

/**
 *@since 2020.06.24
 *更新options
 */
class Wnd_Update_Option extends Wnd_Action_Ajax {

	public static function execute(): array{
		$option_name = $_POST['option_name'];

		// 实例化当前提交的表单数据
		try {
			$form_data   = new Wnd_Form_Data();
			$option_data = $form_data->get_option_data($option_name);
		} catch (Exception $e) {
			throw new Exception($e->getMessage());
		}

		if (update_option($option_name, $option_data, false)) {
			return ['status' => 1, 'msg' => __('更新成功', 'wnd')];
		} else {
			return ['status' => 0, 'msg' => __('更新失败', 'wnd')];
		}
	}
}
