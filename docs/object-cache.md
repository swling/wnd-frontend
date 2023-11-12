# object cache

```php
// 分类关联标签
wp_cache_set($cat_id . $tag_taxonomy . $limit, $tags, 'wnd_tags_under_category', 86400);

// 未读邮件统计
wp_cache_set($user_id, $user_mail_count, static::$mail_count_cache_group);

// 存储wnd_auth数据表对象
wp_cache_set($user_id, $user_data, static::$user_cache_group);
wp_cache_set($open_id, $user_id, $cache_group);

// 将文章流量统计：views字段缓存在对象缓存中，降低数据库读写（满10次，写入一次数据库）
wp_cache_set($object_id, $meta_value, 'wnd_views');
```