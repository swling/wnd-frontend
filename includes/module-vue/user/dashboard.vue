<div id="dashboard-app">
	<div class="is-hidden-desktop is-hidden-tablet mb-5" v-show="!query_module">
		<div class="box is-size-5" v-show="hash">
			<a href="#" @click="go_home()" class="mr-5"><i class="fas fa-arrow-left"></i></a>
			<span v-text="title"></span>
		</div>
		<div class="columns is-multiline is-full is-mobile box is-marginless" v-show="!hash">
			<div class="column is-4" v-for="menu in menus">
				<div class="menu-box box has-text-centered">
					<a @click="load_module(menu.href)">{{menu.title}}</a>
				</div>
			</div>
		</div>
	</div>
	<div class="columns">
		<div class="column is-narrow is-hidden-mobile" v-show="show_menus">
			<div id="wnd-menus" class="box menu">
				<ul class="menu-list">
					<!-- <a>Menus</a> -->
					<li>
						<ul>
							<li v-for="menu in menus" :key="menu.href">
								<a v-html="menu.title" @click="load_module(menu.href)" :class="{ 'is-active': is_active(menu.href) }"></a>
							</li>
						</ul>
					</li>
				</ul>
			</div>
		</div>
		<div class="column">
			<div id="ajax-module" class="box"></div>
		</div>
	</div>
</div>
<script>
	const vueInstances = [];
	const dashboard = Vue.createApp({
		data() {
			return {
				query_module: wnd_dashboard.module, // $_GET 请求 module
				title: 'Home',
				hash: '',
				default_module: wnd_dashboard.default,
				menus: wnd_dashboard.menus[0].items || [],
				current_module: '',
				current_props: {}
			};
		},
		methods: {
			destroyAllVueApps() {
				return new Promise((resolve) => {
					vueInstances.forEach(app => {
						app.unmount();
						app = null;
						console.log("销毁 Module app");
					});
					vueInstances.length = 0;

					// 等 DOM 更新或微任务执行完成再继续
					requestAnimationFrame(() => {
						resolve();
					});
				});
			},
			go_home: function () {
				this.title = 'Home';
				this.load_module(this.default_module);
			},
			load_module: async function (module_name, props = {}) {
				await this.destroyAllVueApps();

				module_name = module_name || this.default_module;
				this.current_module = module_name;
				this.current_props = props;

				const hash = new URLSearchParams(props).toString();
				if (this.default_module == module_name) {
					window.location.hash = hash ? hash : '';
				} else {
					let _hash = hash ? `module=${module_name}&${hash}` : `module=${module_name}`;
					window.location.hash = _hash;
				}
				this.hash = window.location.hash;

				const menu = this.menus.find(menu => menu.href === module_name);
				if (menu) {
					this.title = menu.title;
				}

				if ('post_form' == module_name) {
					module_name = 'post/wnd_post_form_vue';
					if ("undefined" == typeof FormComponent) {
						await wnd_load_script(static_path + 'js/form-vue.js' + cache_suffix);
					}
				}

				wnd_ajax_embed('#ajax-module', module_name, props);
			},
			load_module_from_hash() {
				const hash = window.location.hash.slice(1);
				const params = new URLSearchParams(hash);

				const module = params.get('module') || this.default_module;
				const props = {};
				for (const [key, value] of params.entries()) {
					if (key !== 'module') {
						props[key] = value;
					}
				}

				// 阻止点击事件已经触发后的重复请求
				if (module == this.current_module && JSON.stringify(props) == JSON.stringify(this.current_props)) {
					return;
				}

				if (module) {
					this.load_module(module, props);
				} else {
					this.load_module(this.default_module);
				}
			},
			is_active(module_name) {
				return this.current_module.toLowerCase() === module_name.toLowerCase();
			}
		},
		computed: {
			show_menus() {
				if (!wnd_dashboard.user_id) {
					return false;
				}

				return `post_form` != this.current_module && !this.query_module;
			}
		},
		mounted() {
			// url GET 参数优先
			if (this.query_module) {
				wnd_ajax_embed('#ajax-module', this.query_module, wnd_dashboard.query);
				return;
			}

			this.load_module_from_hash();
			window.addEventListener('hashchange', this.load_module_from_hash);
		},
		beforeUnmount() {
			window.removeEventListener('hashchange', this.load_module_from_hash);
		},
	});

	dashboard.mount('#dashboard-app');
</script>