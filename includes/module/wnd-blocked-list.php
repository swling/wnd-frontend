<?php
namespace Wnd\Module;

use Wnd\Utility\Wnd_Defender;

/**
 *@since 0.8.62
 *列出对象缓存IP屏蔽信息
 *
 */
class Wnd_Blocked_List extends Wnd_Module_Root {

	protected static function build(): string{
		$defender = Wnd_Defender::get_instance(0, 0, 0);
		$logs     = $defender->get_block_logs();

		$html = '<ul>';
		foreach ($logs as $ip => $request) {
			$html .= '<li><h3 class="is-size-5">' . $ip . '</h3><p>' . json_encode($request) . '</p></li>';
		}unset($ip, $request);
		$html .= '</ul>';

		return $html;
	}
}
