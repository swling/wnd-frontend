<?php

namespace Wnd\Utility;

class Wnd_Excel {

	/**
	 * 将数组作为csv导出
	 * @param $data
	 * @param $filename
	 */
	public static function array_to_csv(array $data, string $filename = '') {
		$filename = $filename ?: date('Y-m-d:h-i-s', time());
		if (!$data) {
			return;
		}

		header('Content-Type: application/vnd.ms-execl');
		header('Content-Disposition: attachment;filename="' . $filename . '.csv"');
		header('Pragma: no-cache');
		header('Expires: 0');

		//以写入追加的方式打开
		$fp = fopen('php://output', 'a');

		$i = 0;
		foreach ($data as $k => $v) {
			fputcsv($fp, $v);
			$i++;

			//读取一部分数据刷新下输出buffer
			if ($i > 1000) {
				ob_flush();
				flush();
				$i = 0;
			}
		}
		unset($k, $v);

		fclose($fp);
	}
}
