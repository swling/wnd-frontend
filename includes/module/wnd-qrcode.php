<?php
namespace Wnd\Module;

/**
 *@since 2019.08.30
 *生成二维码图像
 */
class Wnd_Qrcode extends Wnd_Module {

	protected static function build($string = '') {
		if (!$string) {
			return '';
		}

		require WND_PATH . '/includes/utility/phpqrcode.php';
		// $text, $outfile = false, $level = QR_ECLEVEL_L, $size = 3, $margin = 4, $saveandprint=false
		return \QRcode::png($string, false, 'Q', 7, 2);
	}
}
