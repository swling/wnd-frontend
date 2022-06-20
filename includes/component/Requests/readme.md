## 异步请求示例
```php

use Wnd\Component\Requests\AsyncRequests;

/**
 * 数据接收脚本延迟处理演示代码：
 * - 打开请求后关闭浏览器，并观察日志结果
 * - file_get_contents('php://input') 用于接收 json 数据
 *
 * PHP在发送信息给浏览器时,才能检测连接是否已经中断. 仅使用echo语句不能确保信息已发送,参见flush()函数.
 * flush() 函数不会对服务器或客户端浏览器的缓存模式产生影响。因此，必须同时使用 ob_flush() 和flush() 函数来刷新输出缓冲。
 * @link https://www.laruence.com/2010/04/15/1414.html
 */
$POST_DATA = ($_GET ?: file_get_contents('php://input'));
// $POST_DATA = $_POST;
if ($POST_DATA) {
	ignore_user_abort(true); // 用户关闭客户端后，继续执行
	set_time_limit(0); // 较为耗时的操作，可酌情延长脚本超时时间。0 代表永不超时

	$post = is_string($POST_DATA) ? $POST_DATA : json_encode($POST_DATA);

	$i = 0;
	while ($i < 5) {
		sleep(2);

		$i++;
		@error_log($post . '-' . connection_aborted() . "\n", 3, 'dev.log');

		// 此处用于测试 ignore_user_abort(true);
		ob_flush();
		flush();
		echo $i;
	}

	return;
}

/**
 * 异步请求演示代码
 *
 **/
// $args = ['body' => json_encode(['time' => time()])];
$args = ['body' => ['time' => time()], 'method' => 'GET'];
new AsyncRequests('http://127.0.0.1/demo/dev.php?sex=gril', $args);
echo 'success';

```