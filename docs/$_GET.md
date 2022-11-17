# $_GET 参数

本插件内置了多重筛选功能，在 URL 中使用以下 $_GET 参数时将触发 WP Query Action ：pre_get_posts，可能引发冲突

- ?type			类型查询
- ?status	  	状态查询
- ?search  		搜索
- ?page  		分页
- ?orderby  	排序
- ?order  		排序
- ?tax_query  	tax query
- ?meta_query  	meta query
- ?date_query  	date query
- ?_meta_xxx    meta 字段查询
- ?_term_xxx    term 分类查询
- ?_post_xxx    post 文章栏查询

### 作用范围
在 WP 后台、Ajax 请求、内页（is_singular ）环境中不执行。
上述环境下如需创建多重筛选功能，应该单独执行独立的 WP Query

### 移除
```php
// 完全移除
remove_action('pre_get_posts', ['Wnd\View\Wnd_Filter_Query', 'action_on_pre_get_posts']);

// 选择性移除示例：当请求参数中包含 action 时移除
if(isset($_GET['action'])){
	remove_action('pre_get_posts', ['Wnd\View\Wnd_Filter_Query', 'action_on_pre_get_posts']);
}
```

### 参考
@see Wnd\View\Wnd_filter::parse_query_vars();
@see Wnd\View\Wnd_Filter_Query::action_on_pre_get_posts(); 
