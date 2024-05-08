<div id="user-dashboard">
	<div id="dashboard-template">
		<div class="is-hidden-desktop is-hidden-tablet mb-5">
			<div class="box is-size-5">
				<a v-show="hash" href="#" @click="handle_home()" class="mr-5"><i class="fas fa-arrow-left"></i></a>
				<span>{{title}}</span>
			</div>
			<div class="columns is-multiline is-full is-mobile box is-marginless" v-show="!hash">
				<div class="column is-4" v-for="menu in menus">
					<div class="menu-box box has-text-centered">
						<a :href="menu.href" @click="sync_menu(menu)">{{menu.title}}</a>
					</div>
				</div>
			</div>
		</div>
		<div class="columns">
			<div class="column is-narrow is-hidden-mobile">
				<div id="wnd-menus" class="box">
					<div id="app-menus"></div>
				</div>
			</div>
			<div class="column">
				<div id="ajax-module" class="box"></div>
			</div>
		</div>
	</div>
</div>
<script>
	let dashboard_option = {
		data() {
			return {
				menus: wnd_menus_data[0].items,
				hash: '',
				title: 'Home',
			}
		},

		methods: {
			sync_menu: function (menu) {
				this.hash = menu.href;
				this.title = menu.title;
			},
			handle_home: function () {
				this.hash = '';
				this.title = 'Home';
			}
		},

		mounted: function () {
			this.hash = location.hash;
		},
	}

	Vue.createApp(dashboard_option).mount('#user-dashboard');

	// hash render
	function handle_hash() {
		let module = location.hash.replace('#', '');
		if (!module) {
			wnd_ajax_embed('#ajax-module', default_module);
			return;
		}

		wnd_ajax_embed('#ajax-module', module);
	}

	window.onload = window.onhashchange = handle_hash;
	wnd_render_menus('#app-menus', wnd_menus_data);
</script>