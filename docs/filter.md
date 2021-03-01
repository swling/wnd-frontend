# Ajax Filter 
- Ajax Filter 包含 Wnd_Filter_Ajax 及 Wnd_filter_User
- 常规 Post 筛选请参考：/docs/demo/wnd-filter-demo.php
- Wnd_filter_User 仅支持 Ajax 筛选

## 自定义文章列表输出模板
Ajax Filter 前端渲染采用 Vue 框架，下面的实例代码简单地演示了如何根据 Post Type 执行不同的前端渲染。<br/>
其中 Vue 变量：filter 即为后端响应的 json 数据。
```JavaScript
	function wnd_posts_template(){
		return `
		<ul>
		<template v-if="filter.query_vars.post_type =='post'">
		<li v-for="(post, index) in filter.posts">文章标题：{{post.post_title}}</li>
		</template>
		<template v-if="filter.query_vars.post_type =='project'">
		<li v-for="(post, index) in filter.posts">项目标题：{{post.post_title}}</li>
		</template>
		</ul>
		`;
	}
```

## 自定义用户列表输出模板
同上：
```JavaScript
	function wnd_users_template() {
		return `
		<ul>
		<template v-for="(user, index) in filter.users">
		<li>{{user.data.display_name}}</li>
		</ul>
		`;
	}
```

## 注意事项：
Vue 模板会首先查询上述对应的模板函数是否已定义，若是则调用用户自定义模板，否则调用内置默认模板。因此，定义自定义模板函数需注意如下事项:
- 函数名称："wnd_posts_template" 及 "wnd_users_template" 为固定值，必须严格遵守此命名规定
- 定义模板函数的 JavaScript 代码必须放置在引入渲染脚本之前，否则仍将调用内置默认模板