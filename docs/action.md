# action
本插件所有用户注册，及post写入更新等基础操作，底层均采用wp原生功能，因此相关原生Hook均可用。
除此之外，插件自定义了一些功能，以及与之配套的Hook。

## 上传文件
```php
##单上传文件后
do_action('wnd_upload_file', $attachment_id, $post_parent, $meta_key);

##相册上传(多图片上传)
do_action('wnd_upload_gallery', $return_array, $post_parent);

##删除文件后
do_action('wnd_delete_file', $attachment_id, $post_parent, $meta_key);

```

## 用户
```php
##更新用户资料后
do_action('wnd_update_profile', $user_id);

##管理员封禁用户后执行
do_action('wnd_ban_user', $user_id);

##已封禁账户恢复正常
do_action('wnd_restore_user', $user_id);
```

## 支付
```php
/**
 * @since 2019.06.30
 *成功完成付款后
 *$order_or_recherche_id 订单或充值记录ID
 */
do_action('wnd_payment_verified', $order_or_recherche_id);

/**
 * @since 2019.07.14
 *订单完成
 */
do_action('wnd_order_completed', $order_id);

/**
 *@since 2019.08.12
 *充值完成
 */
do_action('wnd_recharge_completed', $recharge_id);

```

## 其他
```php
##@since 2019.03.14 站点清理
do_action('wnd_clean_up');

// 完成统计时附加动作
do_action('wnd_update_views', $post_id);
```
