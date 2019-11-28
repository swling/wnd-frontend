<?php
namespace Wnd\Hook;

use Wnd\hook\Wnd_Add_Action;
use Wnd\hook\Wnd_Add_Action_WP;
use Wnd\hook\Wnd_Add_Filter;
use Wnd\hook\Wnd_Add_Filter_WP;

/**
 *Wnd Default Hook
 */
class Wnd_Hook {

	private static $instance;

	private function __construct() {
		// Wnd Action Hook
		Wnd_Add_Action::instance();

		// WP Action Hook
		Wnd_Add_Action_WP::instance();

		// Wnd Action Hook
		Wnd_Add_Filter::instance();

		// WP Action Hook
		Wnd_Add_Filter_WP::instance();
	}

	/**
	 *单例模式
	 */
	public static function instance() {
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
