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
*@since 2019.03.16 Wnd_Form_Data
*过滤前端表单提交的数据，改操作在verify_form_nonce()校验通过后执行
*$this->form_data = apply_filters('wnd_form_data', $_POST);
*/
apply_filters('wnd_form_data', $_POST)

/**
*@since 2019.12.22
*根据表单数据控制该表单是否可以提交
*注意：$form_data = apply_filters('wnd_form_data', $_POST);
*/
apply_filters('wnd_can_submit_form', ['status' => 1], $form_data);
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
apply_filters('wnd_can_login', ['status'=>1,'msg'=>'']);

###用户更新资料
apply_filters('wnd_can_update_profile', ['status'=>1,'msg'=>'']);
####返回值过滤
apply_filters('wnd_update_profile_return', ['status' => 1, 'msg' => '更新成功'], $user_id);

##用户更新账户：邮箱，密码
apply_filters('wnd_can_update_account', ['status'=>1,'msg'=>'']);
####用户更新返回值
apply_filters('wnd_update_account_return', ['status' => 1, 'msg' => '更新成功'], $user_id);

/**
 *@since 2019.06.10
 *用户面板允许的post types
 */
apply_filters('wnd_user_panel_post_types', $post_types);

```

## 配置Wnd Config
所有的配置选项如果通过 Wnd\Utility\Wnd_Config::get($option)获取，均自动加载一个与其$option名称对应的filter
*注意：$option可以省略wnd_前缀，但filter不会改变，以下代码为其中一个配置的实例*
```php
/**
*@since 2020.04.11
*注册后跳转地址
*/
apply_filters('wnd_reg_redirect_url', $redirect_url);

/**
*@since 2020.04.12
*支付成功后跳转链接（包含订单或充值）
*/
apply_filters('wnd_pay_return_url', $return_url);

```
## 订单
```php
// 用户订单权限
apply_filters('wnd_can_create_order', ['status'=>1,'msg'=>''], $post_id);

/**
*@since 2019.02.12 付费内容，作者收益提成，默认为文章价格* 后台比例设置
*/
apply_filters('wnd_get_post_commission', $commission, $post_id);

/**
*@since 2019.02.13 post价格
*/
apply_filters('wnd_get_post_price', $price, $post_id);

```
## 支付接口
@time 2020.07.12
如需通过插件，拓展或重写支付接口，可以通过 wnd_payment_handler 过滤器，返回一个完整的可执行的类名称（若存在，需包含命名空间）
通常来讲，该类应该是继承 Wnd\Model\Wnd_Payment 的子类，否则您需要在该类中以同样的方法名，完整实现相关业务逻辑
实现代码可参考本插件已内置的支付宝接口：Wnd\Model\Wnd_Payment_Alipay
```php
$class_name = apply_filters('wnd_payment_handler', $class_name, $payment_gateway);
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
