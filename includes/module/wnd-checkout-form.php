<?php
namespace Wnd\Module;

/**
 *@since 0.8.73
 *
 *购物车结算表单
 */
class Wnd_Checkout_Form extends Wnd_Module_Form {

	protected static function configure_form(): object{
		$form = new \stdClass();
		return $form;
	}
}
