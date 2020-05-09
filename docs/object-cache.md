# object cache

```php
wp_cache_set($user_id . '-' . $object_id, $user_has_paid, 'wnd_has_paid');

wp_cache_set($cat_id . $tag_taxonomy . $limit, $tags, 'wnd_tags_under_category', 86400);

wp_cache_set($user_id, $user_mail_count, 'wnd_mail_count');

// 存储wnd_user数据表对象
wp_cache_set($user_id, $user, 'wnd_users');

// 存储手机与用户id的对应关系
wp_cache_set($phone, $user_id, 'wnd_phone');

// 存储openID与用户id的对应关系
wp_cache_set($openid, $user_id, 'wnd_openid');

// 将文章流量统计：views字段缓存在对象缓存中，降低数据库读写（满10次，写入一次数据库）
wp_cache_set($object_id, $meta_value, 'wnd_views');
```