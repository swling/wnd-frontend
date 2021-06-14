# 主流云平台 API 统一请求
注意：
- 请求依赖 wp_remote_request 函数，因此需要在 wordpress 环境中使用，如需在独立环境中使用，请重写 request 方法
- 请在后台填写对应平台的 accessKey、及 secretKey ，并确保以开通此项服务，或者根据你需要测试的其他百度云产品，自行修改对应参数
- 具体请求 body 及 headers 需要根据对应的产品文档对应配置填写
- 各云平台签名助手默认已配置默认公共参数，因此绝大部分情况，无需填写公共参数。

### 百度云自然语言处理：文章标签
```php
/**
 * @link https://cloud.baidu.com/doc/NLP/s/7k6z52ggx
 **/
use Wnd\Getway\Wnd_Cloud_API;
$request    = Wnd_Cloud_API::get_instance('BaiduBce');
$url     = 'https://aip.baidubce.com/rpc/2.0/nlp/v1/keyword?charset=UTF-8';
$params  = json_encode(
	[
		'title'   => 'iphone手机出现“白苹果”原因及解决办法，用苹果手机的可以看下',
		'content' => '如果下面的方法还是没有解决你的问题建议来我们门店看下成都市锦江区红星路三段99号银石广场24层01室。在通电的情况下掉进清水，这种情况一不需要拆机处理。尽快断电。用力甩干，但别把机器甩掉，主意要把屏幕内的水甩出来。如果屏幕残留有水滴，干后会有痕迹。^H3 放在台灯，射灯等轻微热源下让水分慢慢散去。',
	], JSON_UNESCAPED_UNICODE
);
$result = $request->request($url, ['body' => $params]);
print_r($result);
```

### 腾讯云分词
```php
/**
 *@link https://cloud.tencent.com/document/product/271/35494
 **/
use Wnd\Getway\Wnd_Cloud_API;
$request = Wnd_Cloud_API::get_instance('Qcloud');
$url    = 'https://nlp.tencentcloudapi.com';
$params = [
	'Text' => '腾讯云是个好平台，但是签名v3搞起来实在有点太复杂了，感觉没必要',
];
$result  = $request->request(
	$url,
	[
		'method'  => 'POST',
		'body'    => json_encode($params, JSON_UNESCAPED_UNICODE),
		'headers' => [
			'X-TC-Action'  => 'LexicalAnalysis',
			'X-TC-Version' => '2019-04-08',
			'X-TC-Region'  => 'ap-guangzhou',
		],
	]
);

print_r($result);
```

### 阿里云抠图
```php
/**
 *阿里云 AI 抠图
 *@link https://help.aliyun.com/document_detail/146443.html
 *
 */
use Wnd\Getway\Wnd_Cloud_API;
$image_url = 'oss.jpg';
$request = Wnd_Cloud_API::get_instance('Aliyun');
$result  = $request->request(
	'https://imageseg.cn-shanghai.aliyuncs.com',
	[
		'body' => [
			'Action'   => 'SegmentCommonImage',
			'RegionId' => 'cn-shanghai',
			'Version'  => '2019-12-30',
			'ImageURL' => $image_url,
		],
	]
);
```