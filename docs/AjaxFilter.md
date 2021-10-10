# Ajax Filter 
- Ajax Filter 包含 Wnd_Filter_Ajax 及 Wnd_filter_User
- 常规 Post 筛选请参考：/docs/demo/wnd-filter-demo.php
- Wnd_filter_User 仅支持 Ajax 筛选

## 自定义user 及 post 列表输出模板
Ajax Filter 前端渲染采用 Vue 框架，下面的实例代码简单地演示了如何自定义user 及 post 列表端渲染。
 - 其中参数：filter_json 即为后端响应的 json 数据
 - 模板代码中的 'filter' 字符为固定值，不得修改
 - 'filter' 在本函数中为字符串，当本模板挂载到应用中，对应为 Vue 应用的 data 值，@see static/filter.js
 
 综上即：本函数通过JavaScript的方式，动态生成 vue 模板字符串
```JavaScript
function wnd_filter_list_template(filter_json) {
	// 用户列表
	if (filter_json.users) {
		return `
<table class="table is-fullwidth is-hoverable is-striped">
<thead>
<tr>
<th class="is-narrow is-hidden-mobile">注册日期自定义</th>
<th>用户</th>
<th class="is-narrow has-text-centered">操作自定义</th>
</tr>
</thead>

<tbody>
<tr v-for="(user, index) in filter.users">
<td class="is-narrow is-hidden-mobile">{{user.data.user_registered}}</td>
<td><a :href="user.data.link" target="_blank">{{user.data.display_name}}</a></td>
<a @click='wnd_ajax_modal("wnd_delete_user_form", {"user_id": user.ID} )'><i class="fas fa-trash-alt"></i></a>
<a @click='wnd_ajax_modal("wnd_account_status_form", {"user_id": user.ID} )'><i class="fas fa-cog"></i></a>
</tr>
</tbody>
</table>`;
	}

	// 内容列表
	return `
<ul>
<template v-if="filter.query_vars.post_type =='post'">
<li v-for="(post, index) in filter.posts">文章标题：{{post.post_title}}</li>
</template>
<template v-if="filter.query_vars.post_type =='project'">
<li v-for="(post, index) in filter.posts">项目标题：{{post.post_title}}</li>
</template>
</ul>`;
}
```

## 注意事项：
Vue 模板会首先查询上述对应的模板函数是否已定义，若是则调用用户自定义模板，否则调用内置默认模板。因此，定义自定义模板函数需注意如下事项:
- 函数名称："wnd_filter_list_template" 为固定值，必须严格遵守此命名规定
- 定义模板函数的 JavaScript 代码必须放置在引入渲染脚本之前，否则仍将调用内置默认模板