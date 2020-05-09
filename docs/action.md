# action
```php
##单上传文件后
do_action('wnd_upload_file', $attachment_id, $post_parent, $meta_key);

##相册上传(多图片上传)
do_action('wnd_upload_gallery', $return_array, $post_parent);

##删除文件后
do_action('wnd_delete_file', $attachment_id, $post_parent, $meta_key);

##更新用户资料后
do_action( 'wnd_update_profile', $user_id);

##@since 2019.03.14 站点清理
do_action('wnd_clean_up');

// 完成统计时附加动作
do_action('wnd_update_views', $post_id);

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