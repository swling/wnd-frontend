/**
 *@since 0.9.25
 *Vue 根据 Json 动态渲染筛选列表（含 Posts 及 Users）
 */
function _wnd_render_filter(container, filter_json, add_class) {
	let parent = document.querySelector(container);
	if (add_class) {
		parent.classList.add(add_class);
	}

	// 数据 合并数据，并进行深拷贝，以保留原生传参 form_json 不随 data 变动
	let filter = JSON.parse(JSON.stringify(filter_json));

	/** 
	 *post_type 及 post_status 在常规 URL GET 参数中会触发 WordPress 默认路由
	 *故此在本插件多重筛选中分布重命名为 type 及 status
	 *虽然在 API 请求中 不存在上述问题，但未保持常规筛选及 Ajax 筛选一致性，故此也统一重命名
	 */
	let init_param = JSON.parse(JSON.stringify(filter_json.add_query_vars));
	init_param.type = init_param.post_type;
	init_param.status = init_param.post_status;
	delete init_param.post_type;
	delete init_param.post_status;
	let param = JSON.parse(JSON.stringify(init_param));

	let option = {
		el: container + ' .vue-app',
		template: build_filter_template(filter),
		data() {
			return {
				filter: filter,
			}
		},
		methods: {
			item_class: function (key, value) {
				if ('type' == key) {
					if (value == this.filter.query_vars.post_type) {
						return 'is-active';
					}

					return '';
				}

				if ('status' == key) {
					if (value == this.filter.query_vars.post_status) {
						return 'is-active';
					}
					return '';
				}

				if (key.includes('_term_')) {
					if (!this.filter.query_vars.tax_query.length) {
						return value ? '' : 'is-active';
					}

					for (const [index, term_query] of Object.entries(this.filter.query_vars.tax_query)) {
						if (term_query.terms == value) {
							return 'is-active';
						}
					}
				}

				if (key.includes('_meta_')) {
					// user meta query 可能不存在该值
					if (!this.filter.query_vars.meta_query || !this.filter.query_vars.meta_query.length) {
						return value ? '' : 'is-active';
					}

					for (const [index, meta_query] of Object.entries(this.filter.query_vars.meta_query)) {
						if (meta_query.value == value) {
							return 'is-active';
						}
					}
				}

				if (this.filter.query_vars[key] == value) {
					return 'is-active';
				}
			},

			show_tab: function (tab) {
				if (!tab.options) {
					return false;
				}

				if (tab.key.includes('_term_')) {

					if (!this.filter.taxonomies) {
						return true;
					}

					taxonomy = tab.key.replace('_term_', '');
					if (this.filter.taxonomies.includes(taxonomy)) {
						return true;
					} else {
						return false;
					}
				}
				return true;
			},

			get_container: function () {
				return (parent.id ? '#' + parent.id : '') + ' .wnd-filter-results';
			},

			get_list_component(post_type) {
				if (!post_type) {
					return 'user-list';
				}

				return 'post-list';
			},

			update_filter: function (key, value, remove_args = []) {
				/**
				 *	切换类型：恢复初始参数
				 *  	JavaScript 中，如果直接对数组、对象赋值，会导致关联的双方都同步改变
				 * 		因此需要做JSON.parse(JSON.stringify(html_data)) 处理，以保留初始参数
				 * 
				 * 非翻页筛选条件变更：删除分页参数
				 */
				for (let i = 0; i < remove_args.length; i++) {
					const key = remove_args[i];
					if ('all' == key) {
						param = JSON.parse(JSON.stringify(init_param));
						break;
					} else {
						delete param[key];
					}
				}

				/**
				 *切换主分类
				 *移除相关标签
				 */
				if ('_term_category' == key || key.indexOf('_cat') > 0) {
					delete param['_term_' + this.filter.query_vars.post_type + '_tag'];
				}

				// 将当前 Tab 参数合并入请求参数
				if (!value) {
					delete param[key];
				} else {
					param[key] = value;
				}

				axios({
					method: 'get',
					url: filter.posts ? wnd_posts_api : wnd_users_api,
					params: param,
					headers: {
						'Container': this.get_container(),
					},
				}).then(response => {
					wnd_loading(this.get_container(), true);
					if ('undefined' == typeof response.data.status) {
						console.log(response);
						return false;
					}
					// 合并响应数据
					this.filter = Object.assign(this.filter, response.data.data);
				}).catch(function (error) {
					console.log(error);
				});
			},

		},
		mounted() {
			funTransitionHeight(parent, trs_time);
		},
		updated() {
			funTransitionHeight(parent, trs_time);
		},
		components: {
			'post-list': {
				props: ['posts'],
				template: `
<table class="table is-fullwidth is-hoverable is-striped">
<thead>
<tr>
<th class="is-narrow is-hidden-mobile">日期/Date</th>
<th class="is-narrow">用户/User</th>
<th>标题/Title</th>
<th class="is-narrow has-text-centered">操作/Set</th>
</tr>
</thead>

<tbody>
<tr v-for="(post, index) in posts">
<td class="is-narrow is-hidden-mobile">{{post.post_date}}</td>
<td class="is-narrow"><a :href="post.author.link" target="_blank">{{post.author.name}}</a></td>
<td><a :href="post.link" target="_blank">{{post.post_title}}</a></td>
<td class="is-narrow has-text-centered">
<a @click='ajax_modal("post/wnd_post_detail", {"post_id": post.ID} )'><i class="fas fa-info-circle"></i></a>&nbsp;
<a @click='ajax_modal("post/wnd_post_status_form", {"post_id": post.ID} )'><i class="fas fa-cog"></i></a>
</td>
</tr>
</tbody>
</table>`,
				methods: {
					ajax_modal: function (module, params) {
						wnd_ajax_modal(module, params);
					}
				},
			},

			'user-list': {
				props: ['users'],
				template: `
<table class="table is-fullwidth is-hoverable is-striped">
<thead>
<tr>
<th class="is-narrow is-hidden-mobile">注册日期/Reg Date</th>
<th>用户/User</th>
<th class="is-narrow has-text-centered">操作/Set</th>
</tr>
</thead>

<tbody>
<tr v-for="(user, index) in users">
<td class="is-narrow is-hidden-mobile">{{user.data.user_registered}}</td>
<td><a :href="user.data.link" target="_blank">{{user.data.display_name}}</a></td>
<td class="is-narrow has-text-centered">
<a @click='ajax_modal("admin/wnd_delete_user_form", {"user_id": user.ID} )'><i class="fas fa-trash-alt"></i></a>&nbsp;
<a @click='ajax_modal("admin/wnd_account_status_form", {"user_id": user.ID} )'><i class="fas fa-cog"></i></a>
</td>
</tr>
</tbody>
</table>`,
				methods: {
					ajax_modal: function (module, params) {
						wnd_ajax_modal(module, params);
					}
				},
			},
		},
	};

	Vue.createApp(option).mount(container);

	/******************************************************* 构造 Vue 模板 **********************************************************/
	function build_filter_template(filter) {
		return `
<div class="filter">
<div v-if="filter.before_html" v-html="filter.before_html"></div>
${build_tabs_template(filter)}
<div class="wnd-filter-results mb-3">${build_list_template(filter)}</div>
${build_navigation_template()}
<div v-if="filter.after_html" v-html="filter.after_html"></div>
</div>`;
	}

	function build_tabs_template(filter) {
		let t = '<div class="wnd-filter-tabs">';
		for (let i = 0, n = filter.tabs.length; i < n; i++) {
			// 需要与定义数据匹配
			let tab = filter.tabs[i];
			let tab_vn = 'filter.tabs[' + i + ']';

			// 特别注意：此处定义的是 Vue 模板字符串，而非实际数据，Vue 将据此字符串渲染为具体值
			if (is_category_tab(tab, filter)) {
				tab_vn = 'filter.category_tabs';
			} else if (is_tag_tab(tab, filter)) {
				tab_vn = 'filter.tags_tabs';
			}

			t += _build_tabs_template(tab_vn);
		}

		t += '</div>'
		return t;
	}

	function is_category_tab(tab, filter) {
		if (tab.key.includes('_term_')) {
			taxonomy = tab.key.replace('_term_', '');
			if (taxonomy == filter.category_taxonomy) {
				return true;
			}
		}
		return false;
	}

	function is_tag_tab(tab, filter) {
		if (tab.key.includes('_term_')) {
			taxonomy = tab.key.replace('_term_', '');
			if (taxonomy == (filter.query_vars.post_type + '_tag')) {
				return true;
			}
		}
		return false;
	}

	function _build_tabs_template(tabs) {
		return `
<div v-if="${tabs}" class="columns is-marginless is-vcentered is-mobile" :class="${tabs}.key">
<div class="column is-narrow">{{${tabs}.label}}</div>

<div class="column tabs">
<ul class="tab">
<template v-for="(value, name) in ${tabs}.options">
<li :class="item_class(${tabs}.key, value)"><a :data-key="${tabs}.key" :data-value="value" @click="update_filter(${tabs}.key, value, ${tabs}.remove_args)">{{name}}</a></li>
</template>
</ul>
</div>

</div>`
	}

	function build_list_template(filter) {
		if ('function' == typeof wnd_filter_list_template) {
			return wnd_filter_list_template(filter);
		}
		return `<component :is="get_list_component(filter.query_vars.post_type)" :posts="filter.posts" :users="filter.users"></component>`;
	}


	function build_navigation_template() {
		return `
<nav class="pagination is-centered">
<ul class="pagination-list">
<li v-if="filter.pagination.paged >= 2"><a class="pagination-previous" @click="update_filter('paged', --filter.pagination.paged)">上一页</a></li>
<li v-if="filter.pagination.current_count >= filter.pagination.per_page">
<a class="pagination-next" @click="update_filter('paged', ++filter.pagination.paged)">下一页</a>
</li>
</ul>
</nav>`;
	}
}