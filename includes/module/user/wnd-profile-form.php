<?php
namespace Wnd\Module\User;

use Wnd\Module\Wnd_Module_Form;
use Wnd\View\Wnd_Form_User;

/**
 * 用户常规资料表单
 * @since 2019.01.29
 */
class Wnd_Profile_Form extends Wnd_Module_Form {

	protected static function configure_form(): object{
		$form = new Wnd_Form_User(true);
		// profile表单可能有较为复杂的编辑界面，阻止回车提交
		$form->add_form_attr('onkeydown', 'if(event.keyCode==13){return false;}');

		/*头像上传*/
		$form->add_user_avatar();

		$form->add_user_display_name(__('名称', 'wnd'), __('名称', 'wnd'));
		$form->add_user_url(__('网站', 'wnd'), __('网站', 'wnd'));

		$form->add_user_description(__('简介', 'wnd'), __('简介', 'wnd'));
		$form->set_route('action', 'user/wnd_update_profile');
		$form->set_submit_button(__('保存', 'wnd'));

		$form->set_filter(__CLASS__);
		return $form;
	}
}
