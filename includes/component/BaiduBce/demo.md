# 百度云平台 签名并请求
下列代码以请求百度自然语言处理，标签分析为例：
注意：
- 请求依赖 wp_remote_request 函数，因此需要在 wordpress 环境中使用，如需在独立环境中使用，请重写 request 方法
- 请将 accessKey、及 secretKey 替换为实际值，并确保以开通此项服务，或者根据你需要测试的其他百度云产品，自行修改对应参数
```php
use Wnd\Getway\Wnd_Cloud_API;
$sign    = Wnd_Cloud_API::get_instance('BaiduBce');
$url     = 'https://aip.baidubce.com/rpc/2.0/nlp/v1/keyword?charset=UTF-8';
$headers = ['Content-type' => 'application/json'];
$params  = json_encode(
	[
		'title'   => 'iphone手机出现“白苹果”原因及解决办法，用苹果手机的可以看下',
		'content' => '如果下面的方法还是没有解决你的问题建议来我们门店看下成都市锦江区红星路三段99号银石广场24层01室。在通电的情况下掉进清水，这种情况一不需要拆机处理。尽快断电。用力甩干，但别把机器甩掉，主意要把屏幕内的水甩出来。如果屏幕残留有水滴，干后会有痕迹。^H3 放在台灯，射灯等轻微热源下让水分慢慢散去。',
	], JSON_UNESCAPED_UNICODE
);
$request = $sign->request($url, ['body' => $params, 'headers' => $headers]);
print_r($request);
```