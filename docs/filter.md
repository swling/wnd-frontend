# filter

```php
// 预先在需要权限校验的地方，设置filter,若status 为 0，表示权限校验不通过，当前钩子所在函数操作会中断，将权限校验数组结果返回
add_filter('wnd_can_insert_post', 'wnd_can_insert_post', 10, 3);
function wnd_can_insert_post($default_return, $post_type, $update_id) {
	if($post_type=='post'){
		return ['status'=>0,'msg'=>'测试权限阻断:不能插入post类型的文章']
	}
}
```
## ajax表单数据
```php
/**
*@since 2019.03.16 Wnd_Request
*过滤前端表单提交的数据，改操作在verify_form_nonce()校验通过后执行
*$this->form_data = apply_filters('wnd_request', $_POST);
*/
apply_filters('wnd_request', $_POST)

/**
*@since 2019.12.22
*根据表单数据控制该表单是否可以提交
*注意：$form_data = apply_filters('wnd_request', $_POST);
*/
apply_filters('wnd_request_controller', ['status' => 1], $form_data);
```

## 文章
```php
### 文章写入(默认直接通过，当$update_id有效，默认根据WordPress判断当前用户是否可以编辑)
apply_filters('wnd_can_insert_post', ['status'=>1,'msg'=>''], $post_type, $update_id);
####返回值过滤
apply_filters('wnd_insert_post_return', $return_array, $post_type, $post_id);

###写入文章时的状态(默pending)
apply_filters('wnd_insert_post_status', 'pending', $post_type, $update_id);

##更新文章状态权限 @since 2019.01.21(默认根据WordPress判断当前用户是否可以编辑文章)
apply_filters('wnd_can_update_post_status', $can_array, $before_post, $after_status );

/**
*@since 2019.02.19 过滤当前用户可以发布管理的 post types
*@param $post_types array 文章类型数组 如移除 post 类型： unset($post_type['post'])
*/
apply_filters( 'wnd_allowed_post_types', $post_types );
```
## 多重筛选
```php
/**
*
* @param $this->tabs 			筛选选项Tabs（HTML）
* @param $this->wp_query_args 	当前多重筛选查询参数（注意：在执行get_tabs()方法之后新增的参数，将无法获取）
*/
apply_filters('wnd_filter_tabs', $this->tabs, $this->wp_query_args);
```

## 文件上传
```php
###文件上传权限控制
apply_filters('wnd_can_upload_file', ['status' => 1, 'msg' => ''], $post_parent, $meta_key);
```

## 用户
```php
###用户注册(默认通过)
apply_filters('wnd_can_reg', ['status'=>1,'msg'=>'']);
###返回值过滤
apply_filters('wnd_reg_return',  ['status' => 3, 'msg' => $redirect_to], $user_id);

##用户登录 @since 2019.01.21
apply_filters('wnd_can_login', ['status'=>1,'msg'=>''], $user);

###用户更新资料
apply_filters('wnd_can_update_profile', ['status'=>1,'msg'=>'']);
####返回值过滤
apply_filters('wnd_update_profile_return', ['status' => 1, 'msg' => '更新成功'], $user_id);

##用户更新账户：邮箱，密码
apply_filters('wnd_can_update_account', ['status'=>1,'msg'=>'']);
####用户更新返回值
apply_filters('wnd_update_account_return', ['status' => 1, 'msg' => '更新成功'], $user_id);

/**
*@since 0.8.64
*删除用户权限补充 Hook
*/
apply_filters('wnd_can_delete_user', ['status' => 1, 'msg' => ''], $user_id);

/**
 *@since 2019.06.10
 *用户面板允许的post types
 */
apply_filters('wnd_user_panel_post_types', $post_types);

```

## 配置Wnd Config
所有的配置选项如果通过 Wnd\Utility\Wnd_Config::get($config_key)获取，均自动加载一个与其 $config_key 名称对应的 filter 匹配形式如下
```php
$filter_name = 'wnd_option_' . $config_key;

/**
*@since 2020.04.11
*注册后跳转地址
*/
apply_filters('wnd_option_reg_redirect_url', $redirect_url);

/**
*@since 2020.04.12
*支付成功后跳转链接（包含订单或充值）
*/
apply_filters('wnd_option_pay_return_url', $return_url);

```
## 订单
```php
// 用户订单权限
apply_filters('wnd_can_create_order', ['status'=>1,'msg'=>''], $post_id, $sku_id, $quantity);

/**
 *订单创建成功返回信息
 *@since 0.8.71
 */
$return_array = ['status' => 4, 'msg' => __('支付成功', 'wnd'), 'data' => ['waiting' => 5]];
return apply_filters('wnd_create_order_return', $return_array, $order_post);

/**
*@since 2019.02.12 付费内容，作者收益提成，默认为文章价格* 后台比例设置
*/
apply_filters('wnd_get_order_commission', $commission, $order_id);

/**
*@since 2019.02.13 post价格
*
*@since 0.8.76
*新增 $sku_id
*/
apply_filters('wnd_get_post_price', $price, $post_id, $sku_id);

```
## 支付
### UI端拓展支付接口
将新增的支付接口以数组形式['接口名称'=>'接口标识']写入
```php
$payment_gateway = apply_filters('wnd_payment_gateway_options', [__('支付宝', 'wnd') => 'Alipay']);

// 实例：新增微信支付UI
add_filter('wnd_payment_gateway_options', function ($data) {
	return array_merge($data, ['微信支付' => 'Tenpay']);
});

// 默认支付网关
apply_filters('wnd_default_payment_gateway', $default_gateway);
```
### 接口后端拓展支付接口
接上，可以通过 wnd_payment_handler 过滤器，返回一个完整的可执行的类名称（若存在，需包含命名空间）来实现对微信支付的接口拓展
通常来讲，该类应该是继承 Wnd\Model\Wnd_Payment 的子类，否则您需要在该类中以同样的方法名，完整实现相关业务逻辑
实现代码可参考本插件已内置的支付宝接口：Wnd\Model\Wnd_Payment_Alipay
```php
$class_name = apply_filters('wnd_payment_handler', $class_name, $payment_gateway);

// 实例，通过插件的方式添加微信支付处理类
// apply_filters('wnd_payment_handler', $class_name, $payment_gateway)
add_filter('wnd_payment_handler', function ($class_name, $payment_gateway) {
	if ('Tenpay' != $payment_gateway) {
		return $class_name;
	}

	return 'Wnd_plugin\\Wndt_Payment_Gateway\\Wndt_Payment_Tenpay';
}, $priority = 10, $accepted_args = 2);

```

### 充值表单金额选项
```php
/**
*@param array $defaults
*/
apply_filters('wnd_recharge_amount_options', $defaults);

// 实例
add_filter('wnd_recharge_amount_options', function(){
	return ['一毛' => 0.1];
}, 12, 1);
```

## 表单
```php
/**
*@since 2019.10.03
*表单模板以全面实现oop，filter为对应的类名
*注意区分大小写
**/
##注册表单@since 2019.03.10
apply_filters('Wnd\Module\Wnd_Login_Form', $input_values);

##登录表单@since 2019.03.10
apply_filters('Wnd\Module\Wnd_Reg_Form', $input_values);

##用户资料表单@since 2019.03.10
apply_filters('Wnd\Module\Wnd_Profile_Form', $input_values);

##文章发布编辑表单 @since 2019.03.11
apply_filters('Wnd\Module\Wnd_Default_Post_Form', $input_values);

##找回密码表单
apply_filters('Wnd\Module\Wnd_Reset_Password_Form', $input_values);

##账户表单
apply_filters('Wnd\Module\Wnd_Account_Form', $input_values);
```
## 面包屑
```php
apply_filters('wnd_breadcrumb_right', $breadcrumb_right);
```

## 缩略图
```php
apply_filters('wnd_post_thumbnail', $html, $post_id, $width, $height);
```
## wnd_safe_action
```php
/**
*Wnd_Safe_Action
*前端可直接向rest api发起：wnd_safe_action操作，以执行一些请求或非敏感类操作
*由于do_action 没有返回值，无法对响应的操作返回消息给前端，故此用filter替代操作
*WP中filter与action的底层实质相同
*
*@since 2020.04.18
*@see Wnd\Action\Wnd_Safe_Action
*/
apply_filters('wnd_safe_action_return');
```

## 发送短信或邮件验证码权限
```php
$can_send_code = apply_filters('wnd_can_send_code', ['status' => 1, 'msg' => '']);
```

## 人机校验
```php
/**
 *@since 0.9.0
 *
 * Wnd\View\Wnd_Form_Post 内容表单提交是否启启用人机校验
 * 默认匿名用户提交需验证
 */
apply_filters('enable_post_form_captcha', !is_user_logged_in(), $post_type, $post_id);
```

## 产品
```php
/**
 *过滤产品 SKU 字段
*/
apply_filters('wnd_sku_keys', $sku_keys, $post_type);
```
