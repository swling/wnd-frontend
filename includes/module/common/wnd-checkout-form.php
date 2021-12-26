<?php
namespace Wnd\Module\Common;

use Wnd\Module\Wnd_Module_Form;

/**
 * 购物车结算表单
 * @since 0.8.73
 */
class Wnd_Checkout_Form extends Wnd_Module_Form {

	protected static function configure_form(): object{
		$form = new \stdClass();
		return $form;
	}
}
