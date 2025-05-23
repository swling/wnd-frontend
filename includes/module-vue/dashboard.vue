<style>
	.container {
		max-width: 98% !important;
	}

	.post_form {
		margin: 0 auto;
		max-width: 1024px;
	}

	.side {
		width: 200px;
		background: #FFF;
		overflow-y: auto;
		transition: transform 0.3s ease;
	}

	.side.no-transition {
		transition: none !important;
		position: sticky;
		top: 0;
	}

	.submenu {
		margin-left: 1rem;
	}

	@media (max-width: 1024px) {
		.side-menu {
			padding: 0;
			margin: 0;
		}

		.side {
			position: fixed;
			top: 0;
			bottom: 0;
			left: 0;
			z-index: 100;
			transform: translateX(-100%);
		}

		.side.is-open {
			transform: translateX(0);
		}

		.side-backdrop {
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: rgba(0, 0, 0, 0.3);
			z-index: 90;
		}
	}
</style>
<div id="dashboard-app" class="dashboard columns" style="flex: 1;">
	<div v-if="isMobile && sidebarOpen" class="side-backdrop" @click="sidebarOpen = false"></div>
	<!-- Sidebar -->
	<div class="side-menu column is-narrow" v-if="hasMenuForModule">
		<aside :class="['side', {'is-open':sidebarOpen,'no-transition':!isMobile}]">
			<nav class="menu p-3" v-if="admin_menu.length">
				<p class="menu-label">Admin</p>
				<ul class="menu-list">
					<li v-for="item in admin_menu">
						<a :class="{'is-active': isActive(item) }" @click="toggleMenu(item)">
							<span v-html="item.icon" class="mr-1 icon"></span>{{ item.name }}
							<span v-if="item.children" class="icon is-small is-size-7">
								<i :class="['fas', item.open ? 'fa-chevron-down' : 'fa-chevron-right']"></i>
							</span>
						</a>
						<ul v-if="item.children && item.open" class="submenu">
							<li v-for="child in item.children">
								<a :class="{'is-active': isActive(child) }" @click="navigate(child)">
									<span v-html="child.icon" class="mr-1 icon"></span> {{ child.name }}
								</a>
							</li>
						</ul>
					</li>
				</ul>
			</nav>
			<nav class="menu p-3">
				<p class="menu-label">Dashboard</p>
				<ul class="menu-list">
					<li v-for="item in menu">
						<a :class="{'is-active': isActive(item) }" @click="toggleMenu(item)">
							<span v-html="item.icon" class="mr-1 icon"></span>{{ item.name }}
							<span v-if="item.children" class="icon is-small is-size-7">
								<i :class="['fas', item.open ? 'fa-chevron-down' : 'fa-chevron-right']"></i>
							</span>
						</a>
						<ul v-if="item.children && item.open" class="submenu">
							<li v-for="child in item.children">
								<a :class="{'is-active': isActive(child) }" @click="navigate(child)">
									<span v-html="child.icon" class="mr-1 icon"></span> {{ child.name }}
								</a>
							</li>
						</ul>
					</li>
				</ul>
			</nav>
		</aside>
	</div>

	<!-- Main Content -->
	<div class="main-content column">
		<div v-show="isMobile">
			<div class="level is-mobile mb-1">
				<div class="level-left"><a href="#module=index">Dashboard</a></div>
				<!-- <div class="navbar-burger level-right has-text-right" @click="sidebarOpen = !sidebarOpen">
					<span></span><span></span><span></span>
				</div> -->
				<button class="level-right button is-light side-toggle mb-2" @click="sidebarOpen = !sidebarOpen">
					<i class="fas fa-bars"></i>
				</button>
			</div>
		</div>
		<div id="ajax-module" :class="['box',currentModule]"></div>
	</div>
</div>
<script>
	const vueInstances = [];
	const dashboard = Vue.createApp({
		data() {
			return {
				user_id: wnd_dashboard.user_id,
				query_module: wnd_dashboard.module, // $_GET 请求 module
				// title: 'Home',
				// hash: '',
				default_module: wnd_dashboard.default,

				// ChatGPT
				currentModule: '',
				windowWidth: window.innerWidth,
				sidebarOpen: window.innerWidth <= 1024 ? false : true,
				currentHash: location.hash.slice(1),
				menu: wnd_dashboard.menus,
				admin_menu: wnd_dashboard.admin_menus,
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
			load_module: async function (module, params = {}) {
				await this.destroyAllVueApps();

				if ('post_form' == module) {
					module = 'post/wnd_post_form_vue';
					if ("undefined" == typeof FormComponent) {
						await wnd_load_script(static_path + 'js/form-vue.min.js' + cache_suffix);
					}
				}

				if (!module || "index" == module) {
					module = this.default_module;
				}
				wnd_ajax_embed('#ajax-module', module, params);
			},
			load_module_from_hash() {
				const hashObj = this.parseHash();
				const module = hashObj.module || this.default_module;
				const params = hashObj;
				delete params.module;

				// 阻止点击事件已经触发后的重复请求
				if (module == this.currentModule && 'post_form' != module) {
					return;
				}

				this.currentModule = module;
				this.expandMatchingMenu();
				this.load_module(module, params);
			},
			// ChatGPT
			parseHash() {
				const hash = window.location.hash.slice(1);
				const params = new URLSearchParams(hash);
				const obj = {};
				for (const [key, value] of params.entries()) {
					obj[key] = value;
				}
				return obj;
			},
			toggleMenu(item) {
				if (item.children) {
					item.open = !item.open;
				} else {
					this.navigate(item);
				}
			},
			navigate(item) {
				if (this.isMobile) {
					this.sidebarOpen = false;
				}

				// 重复加载（刷新）
				if (this.currentModule == item.hash) {
					this.load_module(item.hash);
				}

				if (item.hash) {
					const params = new URLSearchParams({ module: item.hash }).toString();
					window.location.hash = decodeURIComponent(params);
				} else {
					window.location.hash = '';
				}
			},
			isActive(item) {
				const currentModule = (this.currentModule == this.default_module) ? 'index' : this.currentModule;
				return item.hash === currentModule;
			},
			expandMatchingMenu() {
				const menu = [...this.menu, ...this.admin_menu];
				for (const item of menu) {
					if (item.children) {
						// item.open = false;
						for (const child of item.children) {
							if (child.hash === this.currentModule) {
								item.open = true;
							}
						}
					}
				}
			},
			handleResize() {
				this.windowWidth = window.innerWidth;
			}
		},
		computed: {
			isMobile() {
				return this.windowWidth <= 1024;
			},
			hasMenuForModule() {
				if (!this.user_id) {
					return false;
				}
				return !this.query_module && 'post_form' != this.currentModule;
			},
		},
		mounted() {
			// url GET 参数优先
			if (this.query_module) {
				wnd_ajax_embed('#ajax-module', this.query_module, wnd_dashboard.query);
				return;
			}

			this.load_module_from_hash();
			window.addEventListener('hashchange', this.load_module_from_hash);
			window.addEventListener('resize', this.handleResize);
		},
		beforeUnmount() {
			window.removeEventListener('hashchange', this.load_module_from_hash);
			window.removeEventListener('resize', this.handleResize);
		},
	});

	dashboard.mount('#dashboard-app');
</script>