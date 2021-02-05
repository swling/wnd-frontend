var wnd_vue_filter = true;

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

	// 数据 合并数据，并进行深拷贝，以保留原生传参 form_json 不随 data 变动
	let filter = JSON.parse(JSON.stringify(filter_json));
	let init_param = JSON.parse(JSON.stringify(filter_json.add_query_vars));
	var param = JSON.parse(JSON.stringify(init_param));

	new Vue({
		el: container,
		template: build_filter_template(filter),
		data: {
			filter: filter,
		},
		methods: {
			tab_class: function(key, value) {
				if ('type' == key) {
					if (value == filter.query_vars.post_type) {
						return 'is-active';
					}

					return '';
				}

				if ('status' == key) {
					if (value == filter.query_vars.post_status) {
						return 'is-active';
					}
					return '';
				}

				if (filter.query_vars.tax_query && key.includes('_term_')) {
					for (const [index, term_query] of Object.entries(filter.query_vars.tax_query)) {
						if (term_query.terms == value) {
							return 'is-active';
						}
					}
				}
			},

			show_tab: function(tab, key) {
				if (key.includes('_term_')) {

					if (!this.filter.taxonomies) {
						return true;
					}

					taxonomy = key.replace('_term_', '');
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
				 *切换类型：恢复初始参数
				 *
				 *  JavaScript 中，如果直接对数组、对象赋值，会导致关联的双方都同步改变
				 *  因此需要做JSON.parse(JSON.stringify(html_data)) 处理，以保留初始参数
				 */
				if ('type' == key) {
					param = JSON.parse(JSON.stringify(init_param));
				}

				// 将当前 Tab 参数合并入请求参数
				param = Object.assign(param, {
					[key]: value
				});

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

					})
					.catch(function(error) { // 请求失败处理
						console.log(error);
					});
			},

		},
		// 计算
		computed: {},
		// 侦听器
		watch: {},
	});

	function build_filter_template() {
		let t = '<div class="filter">';

		t += build_tabs_template();
		t += build_posts_template();

		t += '</div>'

		return t;
	}

	function build_tabs_template() {
		let t = '<div class="wnd-filter-tabs">';
		t += '<template v-for="(tab,key) in filter.tabs">'
		t += '<div class="columns is-marginless is-vcentered" v-show="show_tab(tab, key)">'
		t += '<div class="column is-narrow">{{tab.title}}</div>';

		t += '<div class="column tabs">';
		t += '<ul class="tab">'
		t += '<template v-for="(value,name) in tab.options">'
		t += '<li :class="tab_class(key, value)"><a :data-key="key" :data-value="value" @click="update_filter(key,value)">{{name}}</a></li>'
		t += '</template>'
		t += '</ul>'
		t += '</div>'

		t += '</div>'
		t += '</template>'
		t += '</div>'
		return t;
	}

	function build_posts_template() {
		let t = '';
		t += '<div class="wnd-filter-posts">';
		t += '<ul class="posts-list">'
		t += '<template v-for="(post,index) in filter.posts">'
		t += '<li><a :href="post.guid">{{post.post_title}}</a></li>'
		t += '</template>'
		t += '</ul>'

		t += '</div>'
		return t;
	}
}