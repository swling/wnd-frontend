<div id="dashboard-app">
	<div class="is-hidden-desktop is-hidden-tablet mb-5">
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
	const app = Vue.createApp({
		data() {
			return {
				title: 'Home',
				hash: '',
				default_module: wnd_dashboard.default,
				menus: wnd_dashboard.menus[0].items,
				current_module: '',
				current_props: {}
			};
		},
		methods: {
			go_home: function () {
				this.hash = '';
				this.title = 'Home';
			},
			load_module: async function (module_name, props = {}) {
				this.current_module = module_name;
				this.current_props = props;

				const hash = new URLSearchParams(props).toString();
				if (this.default_module == module_name) {
					if (hash) {
						window.location.hash = hash;
					}
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
					this.load_post_form(props);
				} else {
					wnd_ajax_embed('#ajax-module', module_name, props);
				}
			},
			load_post_form: async function (props) {
				let res = await wnd_query('wnd_query_post_form', props);
				if (res.status <= 0) {
					wnd_inner_html('#ajax-module', '<div class="notification is-danger is-light">' + res.msg + '</div>');
					return false;
				}
				wnd_render_form('#ajax-module', res.data.structure, '', res.data.request_url);
			},
			load_module_from_hash() {
				const hash = window.location.hash.slice(1);
				const params = new URLSearchParams(hash);

				const module = params.get('module');
				const props = {};
				for (const [key, value] of params.entries()) {
					if (key !== 'module') {
						props[key] = value;
					}
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

				return `post_form` != this.current_module && !wnd_dashboard.module;
			}
		},
		mounted() {
			// url GET 参数优先
			if (wnd_dashboard.module) {
				wnd_ajax_embed('#ajax-module', wnd_dashboard.module, wnd_dashboard.query);
				return;
			}

			this.load_module_from_hash();
			window.addEventListener('hashchange', this.load_module_from_hash);
		},
		beforeUnmount() {
			window.removeEventListener('hashchange', this.load_module_from_hash);
		},
	});

	app.mount('#dashboard-app');
</script>