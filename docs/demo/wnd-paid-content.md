```php
/**
 *##############################################################
 *付费阅读
 *付费阅读提交订单后，会刷新页面
 */

// 设置价格：字段存储于wp post meta：price
update_post_meta($post_id, 'price', $price);

// 在内容页面判断当前用户是否已付费
if (wnd_user_has_paid($user_id, $post_id)) {
	echo "仅对付费用户展示的内容";
} else {
	echo "仅对未付费用户展示的内容";
}

// 在内容页放置按钮
echo wnd_pay_button($post_id, $with_paid_content = true);

/**
 *##############################################################
 *付费下载
 *付费下载不会刷新页面
 */

// 设置价格：字段存储于wp post meta：price
update_post_meta($post_id, 'price', $price);

/**
 *上传文件并存储attachment id至 wnd post meta：file
 *建议通过本插件Wnd\View\Wnd_Form_Post类中的 add_post_paid_file_upload 方法实现前端上传
 *
 */
wnd_update_post_meta($post_id, 'file', $attachment_id);

// 在内容页放置按钮
echo wnd_pay_button($post_id, $with_paid_content = false);

/**
 *##############################################################
 *同时包含付费阅读及付费下载
 *
 *按前面步骤设置付费内容及付费文件
 *
 *未支付前，采用付费阅读提交支付并刷新页面
 *支付后，采用付费下载方法，下载文件（下载文件时，不会重复扣费）@see Wnd\Action\Wnd_Pay_For_Downloads
 */

// 在内容页放置按钮
echo wnd_pay_button($post_id, $with_paid_content = true);
```