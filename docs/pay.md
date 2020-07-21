# 付费内容
必须设置价格

## 价格：
文章字段设置 wp_post_meta: price (此处使用独立字段，方便用户对付费和免费内容进行筛选区分)

### 内容：
请在模板中自行设定区分付费内容与免费内容。

## 下载
文章字段：wnd_post_meta: file (存储上传附件的id)
下载计数：wnd_post_meta: download_count ;

## 模板代码演示
```php
/**
 * 在内容页面判断当前用户是否已付费
 * 采用wp editor <!--more--> 标记区分免费部分与付费部分
 */
$with_paid_content = false;
if (wnd_get_post_price($post->ID)) {
	$content           = wnd_explode_post_by_more($post->post_content);
	$with_paid_content = $content[1] ?? false;
	$user_id           = get_current_user_id();
	if (wnd_user_has_paid($user_id, $post->ID) or $post->post_author == $user_id) {
		the_content();
	} else {
		echo $content[0];
	}
} else {
	the_content();
}
// 在内容页放置付费按钮，将自动检测是否包含付费文件
echo wnd_pay_button($post->ID, $with_paid_content);
```