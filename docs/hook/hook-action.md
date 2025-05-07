# action
本插件所有用户注册，及post写入更新等基础操作，底层均采用wp原生功能，因此相关原生Hook均可用。
除此之外，插件自定义了一些功能，以及与之配套的Hook。

## 文章
```php
/**
 * 完成文章写入后
 * @since 0.9.37
 */
do_action('wnd_insert_post', $this->post_id, $this->data);

```

## 上传文件
```php
##单上传文件后
do_action('wnd_upload_file', $attachment_id, $post_parent, $meta_key);

##删除文件后
do_action('wnd_delete_file', $attachment_id, $post_parent, $meta_key);

```

## 用户
```php
/**
 * 注册完成
 * - 由于注册采用了 json 数据，故此设置，以传递数据
 * @since 0.9.37
 */
do_action('wnd_user_register', $user_id, $this->data);

##更新用户资料后 
// 已废弃 请采用 WordPress官方 Hook : do_action( 'profile_update', $user_id, $old_user_data );
// do_action('wnd_update_profile', $user_id);

##管理员封禁账户后执行
do_action('wnd_ban_account', $user_id);

##已封禁账户恢复正常
do_action('wnd_restore_account', $user_id);

/**
 *@since 0.8.61
 *登录失败（密码错误）
 *@param object WP_User
*/
do_action('wnd_login_failed', $user);

/**
 *@since 0.8.61
 *登录成功
 *@param object WP_User
 */
do_action('wnd_login', $user);
```

## 交易及支付
```php
/**
 * 交易完成
 * @since 0.9.37 
 * - 动态钩子 'wnd_' . $this->transaction_type . '_completed'
 * - 新增统一钩子 'wnd_transaction_completed'
 */
do_action('wnd_' . $this->transaction_type . '_completed', $this->transaction_id, $this->transaction);

do_action('wnd_transaction_completed', $this->transaction_id, $this->transaction_type, $this->transaction);

/**
 * 基于上述动态钩子，可以得知，插件内置了如下两个钩子
 * - 订单完成
 * - 充值完成
 * 
 * 其他拓展交易类，亦自动添加与之 Type 匹配的钩子
 */
do_action('wnd_order_completed', $order_id, $order_object);
do_action('wnd_recharge_completed', $recharge_id, $recharge_object);
```

## 产品
```php
/**
 * 获取产品属性时之前的 Action
 * - 如释放符号条件的订单等
 * @since 0.9.38
 */
do_action('wnd_pre_get_product_props', $object_id);
```

## 其他
```php
##@since 2019.03.14 站点清理
do_action('wnd_clean_up');

// 完成统计时附加动作
do_action('wnd_update_views', $post_id);
```
