<?php
/**
 *付费阅读
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
echo wnd_paid_reading_button($post_id);

/**
 *##############################################################
 *付费下载
 *
 */
// 设置价格：字段存储于wp post meta：price
update_post_meta($post_id, 'price', $price);

/**
 *上传文件并存储attachment id至 wnd post meta：file
 *建议通过本插件Wnd\View\Wnd_Form_Post类中的 add_post_paid_file_upload 方法实现前端上传
 *
 */

// 在内容页放置按钮
echo wnd_paid_download_button($post_id);
