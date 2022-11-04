# 主流云平台 API 统一请求
注意：
- 请在后台填写对应平台的 accessKey、及 secretKey ，并确保以开通此项服务，或者根据你需要测试的其他百度云产品，自行修改对应参数
- 具体请求 body 及 headers 需要根据对应的产品文档对应配置填写
- 各云平台签名助手默认已配置默认公共参数，因此绝大部分情况，无需填写公共参数。

### Filter
```php
// 用于对特定云服务商的特定产品，配置特定的密匙，数据格式： ['secret_id'  => 'xxx', 'secret_key' => 'xxx']
$access_info = apply_filters('wnd_cloud_client_access_info', [], $service_provider, $product);
```

### 百度云自然语言处理：文章标签
```php
/**
 * @link https://cloud.baidu.com/doc/NLP/s/7k6z52ggx
 **/
use Wnd\Getway\Wnd_Cloud_Client;
$request    = Wnd_Cloud_Client::get_instance('BaiduBce');
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
 * @link https://cloud.tencent.com/document/product/271/35494
 * 腾讯云公共参数均在 headers 中以 X-TC-Xxx 形式添加。通常包含：'X-TC-Action', 'X-TC-Version'，'X-TC-Region'
 **/
use Wnd\Getway\Wnd_Cloud_Client;
$request = Wnd_Cloud_Client::get_instance('Qcloud');
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

阿里云接口区分 RPC 及 ROA 调用，需要根据具体产品调用不同方法

### 阿里云 RPC
```php
/**
 *阿里云 AI 抠图
 *@link https://help.aliyun.com/document_detail/146443.html
 *
 */
use Wnd\Getway\Wnd_Cloud_Client;
$image_url = 'oss.jpg';
$request = Wnd_Cloud_Client::get_instance('Aliyun');
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

### 阿里云 ROA
```php
/**
 * 内容安全
 * @link https://help.aliyun.com/document_detail/53413.html
 * 
 */
use Wnd\Getway\Wnd_Cloud_Client;
$request = Wnd_Cloud_Client::get_instance('AliyunROA');
$url     = 'https://green.cn-shanghai.aliyuncs.com/green/image/scan';
$headers = [
	'x-acs-signature-version' => '1.0',
	'x-acs-version'           => '2018-05-09',
];
$params =
	[
	'scenes' => ['porn', 'terrorism'],
	'tasks'  => [
		[
			'url' => 'https://uploads.fenbu.net/chuangtu/2019/10/file5db7b01649128.png?x-oss-process=image/resize,m_fill,w_100,h_100',
		],
	],
];
$result = $request->request($url, ['headers' => $headers, 'body' => json_encode($params, JSON_UNESCAPED_UNICODE)]);
print_r($result);
```