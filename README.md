#万能的WordPress前端开发框架概述
##授权声明
使用本插件需遵循：署名-非商业性使用-相同方式共享 2.5。
以下情况中使用本插件需支付授权费用：①用户主体为商业公司，盈利性组织。②个人用户基于本插件二次开发，且以付费形式出售的产品。情节严重者，保留追究法律责任的权利。
###联系方式：QQ：245484493  网站：https://wndwp.com

##核心原理：
1、通过前端表单name前缀自动归类提交的数据对应到WordPress文章，文章字段，用户字段等，从而实现可拓展的前端表单提交
2、通过生成表单的同时，根据表单字段name值生成wp nonce，以防止表单字段被前端篡改
3、前端上传图片，并按meta_key做存储在用户字段，或文章字段（以对应meta_key生成wp nonce，以实现meta_key校验）
4、用户即文章的增删改最终底层实现均采用WordPress原生函数，因此对应操作中WordPress原生的action 及filter均有效
5、相关ajax操作中，设置array filter以实现权限控制
*如未特别说明，字段均指WndWP自定义数组字段，而非wp原生字段*

##功能列表
0、基于bulma框架，ajax表单提交，ajax弹窗模块，ajax嵌入
1、WordPress前端文章增删改 （含权限控制filter）
2、WordPress前端注册用户增删改（含权限控制filter）
3、WordPress订单系统，预设文章付费阅读，付费下载（含权限控制filter）
4、支付，短信模块
5、前端文件、图片上传
6、数组形式合并存储多个user_meta、post_meta、option
7、基于bulma的表单生成类：Wnd_Form、Wnd_Ajax_Form、Wnd_Post_Form、Wnd_User_Form。可快速生成各类表单

#注意事项
##用户角色
普通注册用户的角色：author
editor及以上角色定义为管理员 wnd_is_manager()
##分类与标签关联，需要自定义taxonomy，并遵循以下规则：
```php
$post_type.'_cat';//分类taxonomy
$post_type.'_tag';//标签taxonomy
```
#add_filter priority: 10
10 为WordPress默认值，该值越大，表示filter越靠后执行，可覆盖前面filter已设置的值，也可理解为值越大，当前add_filter的权重越高

#自定义文章类型
*以下 post_type 并未均为私有属性（'public' => false），因此在WordPress后台无法查看到*
##充值：recharge
##消费、订单：order
##站内信：mail
##整站月度财务统计：stats-re（充值）、stats-ex（消费）

#自定义文章状态
##success
用于功能型post、（如：充值，订单等） wp_insert_post 可直接写入未经注册的 post_status，但未经注册的post_status无法通过wp_query进行筛选，故此注册

#文章自定义字段
wp_post_meta: price (价格)
wnd_meta: file (存储上传附件的id)
wnd_meta: download_count (下载统计)

#用户自定义字段
wnd_meta: money：余额
wnd_meta: expense：消费
wnd_meta: commission：佣金
wnd_meta: avatar：头像文件id
wnd_meta: avatar_url：头像外链
wnd_meta: phone：用户手机号码

#ajax交互概述：
```php
/**
 *自定义api：wp-json/wnd/rest-api Allow: GET, POST, PUT, PATCH, DELETE
 *
 *提交的数据中必须包含，$_POST['action'] = $action_name 通过该值，将当前数据交由对应的后端 $action_name() 处理
 *提交的数据中，必须包含$_POST['_ajax_nonce'] = $wp_nonce nonce生成方式：$wp_nonce = wnd_create_nonce($action_name )
 *@see Wnd_Ajax_Form->set_action
 *
 *以"_wnd"为前缀的函数，定义为UI响应函数，无需安全校验
 *后端函数接收$_POST数据并处理后，返回数组值：array('status'=>'状态值','msg'=>'消息');通过统一将结果转为json格式，输出交付前端处理
 *权限控制中通过WordPress add_filters 实现
*/

// 预先在需要权限校验的地方，设置filter,若status 为 0，表示权限校验不通过，当前钩子所在函数操作会中断，将权限校验数组结果返回
add_filter('wnd_can_insert_post', 'wnd_can_insert_post', 10, 3);
function wnd_can_insert_post($default_return, $post_type, $update_id) {
	if($post_type=='post'){
		return array('status'=>0,'msg'=>'测试权限阻断:不能插入post类型的文章')
	}
}
```

#filter

##ajax表单数据
```php
/**
*@since 2019.03.16 Wnd_Form_Data
*过滤前端表单提交的数据，改操作在verify_form_nonce()校验通过后执行
*$this->form_data = apply_filters('wnd_form_data', $_POST);
*/
apply_filters('wnd_form_data', $_POST)
```

##文章
```php
### 文章写入（默认直接通过，当$update_id有效，默认根据WordPress判断当前用户是否可以编辑）
apply_filters('wnd_can_insert_post', array('status'=>1,'msg'=>'默认通过'), $post_type, $update_id);
####返回值过滤
apply_filters('wnd_insert_post_return', $return_array, $post_type, $post_id);

###写入文章时的状态（默pending）
apply_filters('wnd_insert_post_status', 'pending', $post_type, $update_id);

##更新文章状态权限 @since 2019.01.21（默认根据WordPress判断当前用户是否可以编辑文章）
apply_filters('wnd_can_update_post_status', $can_array, $before_post, $after_status );

/**
*@since 2019.02.19 过滤当前用户可以发布管理的 post types
*@param $post_types array 文章类型数组 如移除 post 类型： unset($post_type['post'])
*/
apply_filters( 'wnd_allowed_post_types', $post_types );
```
##多重筛选
```php
/**
*@since 2019.02.25 文章列表输出模板 $post数据过滤
*/
apply_filters('_wnd_table_list_data', $post);

/**
*@since 2019.04.18 多重筛选，文章输出模板
*/
apply_filters('_wnd_post_list_tpl', $list, $post);

/**
*@since 2019.04.27 多重筛选，边栏，默认没有边栏内容（返回html数据）
*/
$sidebar = apply_filters('_wnd_posts_filter_sidebar', '', $args);
```

##文件上传
```php
###文件上传权限控制
apply_filters('wnd_can_upload_file', array('status' => 1, 'msg' => '默认通过'), $post_parent, $meta_key);
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
apply_filters('wnd_can_create_order', array('status'=>1,'msg'=>'默认通过'), $post_id);

/**
*@since 2019.02.12 付费内容，作者收益提成，默认为文章价格* 后台比例设置
*/
apply_filters( 'wnd_get_post_commission', $commission, $post_id )

/**
*@since 2019.02.13 post价格
*/
apply_filters('wnd_get_post_price', $price, $post_id);

```
##表单
```php
##注册表单@since 2019.03.10
apply_filters( '_wnd_login_form', $input_values )

##登录表单@since 2019.03.10
apply_filters( '_wnd_reg_form', $input_values )

##用户资料表单@since 2019.03.10
apply_filters( '_wnd_profile_form', $input_values )

##文章发布编辑表单 @since 2019.03.11
apply_filters( '_wnd_post_form_{$post_type}', $input_values )
```

#action
```php
##单上传文件后
do_action('wnd_upload_file', $attachment_id,$post_parent, $meta_key);

##相册上传（多图片上传）
do_action('wnd_upload_gallery', $return_array, $post_parent);

##删除文件后
do_action('wnd_delete_file', $attachment_id, $post_parent, $meta_key);

##更新用户资料后
do_action( 'wnd_update_profile', $user_id );

##@since 2019.03.14 站点清理
do_action('wnd_clean_up');

##充值表单 @since 2019.01.21
do_action('_wnd_recharge_form')

// 完成统计时附加动作
do_action('wnd_ajax_update_views', $post_id);

```
#前端Form name规则

##前端表单遵循以下规则定义的name，后台获取后自动提取，并更新到数据库
	文章：_post_{field}

	文章字段：
	_meta_{key} （*自定义数组字段）
	_wpmeta_{key} （*WordPress原生字段）

	用户：_user_{field}

	用户字段：
	_usermeta_{key} （*自定义数组字段）
	_wpusermeta_{key} （*WordPress原生字段）

##手机验证表单：
	phone
	v_code

#充值、消费(自定义文章类型)
金额：post_content
关联：post_parent
标题：post_title
状态：post_status: pengding / success
类型：post_type：recharge / order

#付费内容
必须设置价格
优先检测文件，如果设置了付费文件，则文章内容将全文输出

##价格：
文章字段设置 wp_post_meta: price (此处使用独立字段，方便用户对付费和免费内容进行筛选区分)

###内容：
用WordPress经典编辑器的more标签分割免费内容和付费内容（<!--more-->）
如不含more标签，则全文付费后可见

##下载
文章字段：wnd_post_meta: file (存储上传附件的id)
下载计数：wnd_post_meta: download_count ;

#action filter函数命名规则：
wnd_action_xxx
wnd_filter_xxx

#数据库
如果需要用户昵称唯一：建议对users->display_name 新增索引

#站内信功能
post_type => mail
post_status => 未读：pengding 已读: private