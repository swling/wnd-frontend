/**
 *@since 0.9.25
 *Vue 根据 Json 动态渲染筛选列表
 */
function _wnd_render_filter(container, filter_json) {
	// 添加请求拦截器
	// axios.interceptors.request.use(function (config) {
	// 	if ('get' == config.method) {
	// 		wnd_loading(container + ' .wnd-filter-posts');
	// 	}

	// 	return config;
	// });
	let parent = document.querySelector(container).parentNode;

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
	var param = JSON.parse(JSON.stringify(init_param));

	new Vue({
		el: container,
		template: build_filter_template(filter),
		data: {
			filter: filter,
		},
		methods: {
			item_class: function(key, value) {
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

				if (this.filter.query_vars.tax_query && key.includes('_term_')) {
					for (const [index, term_query] of Object.entries(this.filter.query_vars.tax_query)) {
						if (term_query.terms == value) {
							return 'is-active';
						}
					}
				}
			},

			show_tab: function(tab) {
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

			update_filter: function(key, value) {
				/**
				 *	切换类型：恢复初始参数
				 *  	JavaScript 中，如果直接对数组、对象赋值，会导致关联的双方都同步改变
				 * 		因此需要做JSON.parse(JSON.stringify(html_data)) 处理，以保留初始参数
				 * 
				 * 非翻页筛选条件变更：删除分页参数
				 */
				if ('type' == key) {
					param = JSON.parse(JSON.stringify(init_param));
				} else if ('paged' != key) {
					delete param['paged'];
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

				let _this = this;
				axios({
						method: 'get',
						url: wnd_posts_api,
						params: param
					})
					.then(function(response) {
						if ('undefined' == typeof response.data.status) {
							console.log(response);
							return false;
						}
						// 合并响应数据
						_this.filter = Object.assign(_this.filter, response.data.data);

						_this.$nextTick(function() {
							funTransitionHeight(parent, trs_time);
						});
					})
					.catch(function(error) { // 请求失败处理
						console.log(error);
					});
			},

		},
		mounted() {
			funTransitionHeight(parent, trs_time);
		},
		// 计算
		computed: {},
		// 侦听器
		watch: {},
	});

	function build_filter_template(filter) {
		let t = '<div class="filter">';
		t += '<div v-if="filter.before_html" v-html="filter.before_html"></div>';
		t += build_tabs_template(filter);
		t += build_posts_template(filter);
		t += build_navigation(filter);
		t += '<div v-if="filter.after_html" v-html="filter.after_html"></div>';
		t += '</div>'
		return t;
	}

	function build_tabs_template(filter) {
		let t = '<div class="wnd-filter-tabs">';
		for (let index = 0; index < filter.tabs.length; index++) {
			// 需要与定义数据匹配
			let tab = filter.tabs[index];

			// 特别注意：此处定义的是 Vue 模板字符串，而非实际数据，Vue 将据此字符串渲染为具体值
			if (is_category_tab(tab, filter)) {
				var tab_vn = 'filter.category_tabs';
			} else if (is_tag_tab(tab, filter)) {
				var tab_vn = 'filter.tags_tabs';
			} else {
				var tab_vn = 'filter.tabs[' + index + ']';
			}

			t += build_tabs(tab_vn);
		}

		t += '</div>'
		return t;
	}

	function build_posts_template(filter) {
		let t = '<div class="wnd-filter-posts">';
		t += build_post_template();
		t += '</div>'
		return t;
	}

	function build_post_template() {
		let t = '<table class="table is-fullwidth is-hoverable is-striped">';
		// 表头
		t += '<thead>';
		t += '<tr>';
		t += '<th class="is-narrow">日期</th>';
		t += '<th>标题</th>';
		t += '<th class="is-narrow has-text-centered">操作</th>';
		t += '</tr>';
		t += '</thead>';

		//列表 
		t += '<tbody>';
		t += '<tr v-for="(post,index) in filter.posts">';
		t += '<td class="is-narrow">{{post.post_date}}</td>';
		t += '<td>{{post.post_title}}</td>';

		t += '<td class="is-narrow has-text-centered">';
		t += 'xxx';
		t += '</td>';
		t += '</tr>';
		t += '</tbody>';
		t += '</table>';

		return t;
	}

	function build_tabs(tabs) {
		let t = '';
		t += '<div v-if="' + tabs + '" class="columns is-marginless is-vcentered" :class="' + tabs + '.key">'
		t += '<div class="column is-narrow">{{' + tabs + '.title}}</div>';

		t += '<div class="column tabs">';
		t += '<ul class="tab">'
		t += '<template v-for="(value,name) in ' + tabs + '.options">'
		t += '<li :class="item_class(' + tabs + '.key, value)"><a :data-key="' + tabs + '.key" :data-value="value" @click="update_filter(' + tabs + '.key ,value)">{{name}}</a></li>'
		t += '</template>'
		t += '</ul>'
		t += '</div>'

		t += '</div>'
		return t;
	}

	function build_navigation(filter) {
		let t = '<nav class="pagination is-centered">';
		t += '<ul class="pagination-list">';
		t += '<li v-if="filter.pagination.paged >= 2"><a class="pagination-previous" @click="update_filter(\'paged\', --filter.pagination.paged)">上一页</a></li>';
		t += '<li v-if="filter.pagination.post_count >= filter.pagination.posts_per_page">';
		t += '<a class="pagination-next" @click="update_filter(\'paged\', ++filter.pagination.paged)">下一页</a>';
		t += '</li>';
		t += '</ul>';
		t += '</nav>';
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
}