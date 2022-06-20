## 手动设定 action 层请求数据
```php
$data = ['_post_post_title' => '测试', '_post_post_content' => '测试内容', '_post_post_type' => 'post'];
$data = Wnd\Controller\Wnd_Request::sign_request($data);

$wp_rest_request = new WP_REST_Request('POST');
$wp_rest_request->set_body_params($data);

$action = new Wnd\Action\Post\Wnd_Insert_Post($wp_rest_request);
$result =  $action->do_action();
print_r($result);
```