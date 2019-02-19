#万能的WordPress前端开发框架
##授权声明
使用本插件需遵循：署名-非商业性使用-相同方式共享 2.5。
以下情况中使用本插件需支付授权费用：①用户主体为商业公司，盈利性组织。②个人用户基于本插件二次开发，且以付费形式出售的产品。情节严重者，保留追究法律责任的权利。
###联系方式：QQ：245484493  网站：https://wndwp.com

##核心原理：
通过前端表单name前缀自动归类提交的数据对应到WordPress文章，文章字段，用户字段等，从而实现可拓展的前端表单提交。
通过白名单设置允许提交的字段，实现安全过滤
前端上传图片，并按用途做处理，并按指定尺寸裁剪
用户注册，用户更新，文章发布，文章编辑，附件上传，附件删除等功能，最终底层实现均采用WordPress原生函数，因此对应操作中WordPress原生的action 及filter均有效

##功能列表
0.基于bulma框架，ajax表单提交，ajax弹窗模块
1.WordPress前端发布文章，更新文章
2.WordPress前端注册用户，更新资料
3.WordPress文章付费阅读，付费下载
4.支付，短信模块
5.前端文件、图片上传
6.数组形式合并存储多个user_meta、post_meta、option

#ajax交互概述：
```php
// 提交的数据中必须包含，$_POST['action_name'] 并通过该值，判断将当前数据交由对应的后端 action_name() 函数处理
// 后端函数接收$_POST数据并处理后，返回数组值：array('status'=>'状态值','msg'=>'消息');通过统一将结果转为json格式，输出交付前端处理
// 权限控制中通过WordPress add_filters 实现

// 预先在需要权限校验的地方，设置filter,若status 为 0，表示权限校验不通过，当前钩子所在函数操作会中断，将权限校验数组结果返回
add_filter('wnd_can_insert_post', 'wnd_can_insert_post', 10, 3);
function wnd_can_insert_post($default_return, $post_type, $update_id) {
	if($post_type=='post'){
		return array('status'=>0,'msg'=>'测试权限阻断:不能插入post类型的文章')
	}
}
```
##匹配
表单数据必须包含
	action(name) : wnd_action（value）如 <input type="hidden" name="action" value="wnd_action">
才能匹配到本插件的ajax请求	

##校验
form input type hidden 
	（action_name 与WordPress nonce filed校验名称 、及对应的功能函数名称统一）
	action_name = wp_nonce_field('action_name', '_ajax_nonce')  = funcrion action_name()
	以"_wnd_" 开头的函数 不进行 wp nonce校验，用于一些非敏感ajax操作，如弹窗，界面请求等

#钩子

##文章
```php
### 文章写入（默认直接通过，当$update_id有效，默认根据WordPress判断当前用户是否可以编辑）
apply_filters('wnd_can_insert_post', array('status'=>1,'msg'=>'默认通过'), $post_type, $update_id);
####返回值过滤
apply_filters('wnd_insert_post_return', $return_array, $post_type, $post_id);

###写入文章时的状态（默pending）
apply_filters('wnd_post_status', 'pending', $post_type, $update_id);

##更新文章状态权限 @since 2019.01.21（默认根据WordPress判断当前用户是否可以编辑文章）
apply_filters('wnd_can_update_post_status', $can_array, $before_post, $after_status );
```

##用户
```php
###用户注册（默认通过）
apply_filters('wnd_can_reg', array('status'=>1,'msg'=>'默认通过'));
###返回值过滤
apply_filters('wnd_reg_return',  array('status' => 3, 'msg' => $redirect_to), $user_id);


##用户登录 @since 2019.01.21
apply_filters('wnd_can_login', array('status'=>1,'msg'=>'默认通过'));

###用户更新资料
apply_filters('wnd_can_update_profile', array('status'=>1,'msg'=>'默认通过'));
####返回值过滤
apply_filters('wnd_update_profile_return', array('status' => 1, 'msg' => '更新成功！'), $user_id);

##用户更新账户：邮箱，密码
apply_filters('wnd_can_update_account', array('status'=>1,'msg'=>'默认通过'));
####用户更新返回值
apply_filters('wnd_update_account_return', array('status' => 1, 'msg' => '更新成功'), $user_id);

```
##订单
```php
// 用户订单权限
apply_filters('wnd_can_insert_order', array('status'=>1,'msg'=>'默认通过'), $post_id);

/**
*@since 2019.02.12 付费内容，作者收益提成，默认为文章价格
*/
apply_filters( 'wnd_commission', $price, $post_id )

/**
*@since 2019.02.13 post价格
*/
apply_filters('wnd_post_price', $price, $post_id);
```



#动作
```php
##上传文件后
do_action('wnd_upload_file', $attachment_id,$post_parent, $meta_key);

##删除文件后
do_action('wnd_delete_attachment', $attach_id, $post_parent, $meta_key);

##更新用户资料后
do_action( 'wnd_update_profile', $user_id );

##注册表单@since 2019.01.21
do_action('_wnd_reg_form')

##登录表单@since 2019.01.21
do_action('_wnd_login_form');

##用户资料表单@since 2019.01.29
do_action('_wnd_profile_form',$user)

##充值表单 @since 2019.01.21
do_action('_wnd_recharge_form')

##文章发布编辑表单 @since 2019.01.31
do_action( '_wnd_post_form', $post_id,$post_type,$post )

/**
*@since 2019.02.22
*自定义object数据，写入
*/
do_action('wnd_insert_object', $object_id, $type);

/**
*@since 2019.02.22
*自定义object数据，更新
*/
do_action('wnd_update_object', $object_id);

```
#前端Form name规则 
前端表单遵循以下规则定义的name，后台获取后自动提取，并更新到数据库
	文章：_post_{field}

	文章字段：
	_meta_{key} （*自定义数组字段）
	_wpmeta_{key} （*WordPress原生字段）

	用户：_user_{field}

	用户字段：
	_usermeta_{key} （*自定义数组字段）
	_wpusermeta_{key} （*WordPress原生字段）

	<!-- php后台返回msg 不带标点 js返回msg带标点 -->

#支付模块：
自定义:objects数据表
	现金充值type：payment
	站内消费type：expense

#付费内容
必须设置价格
优先检测文件，如果设置了付费文件，则文章内容将全文输出
##价格：
文章字段设置 wp post meta: price
###内容：
用more标签分割免费内容和付费内容
如不含more标签，则全文付费后可见
##下载
文章字段：file 存储上传附件的id

#action filter函数命名规则：
wnd_action_xxx
wnd_filter_xxx

#数据库
如果需要用户昵称唯一：建议对users->display_name 新增索引