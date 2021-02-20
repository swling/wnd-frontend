// 定义菜单数据
var wnd_menus_data = false;

// 菜单
function _wnd_render_menus(el, menus_data) {
	// 优先接收外部传递参数
	wnd_menus_data = menus_data || wnd_menus_data;

	new Vue({
		el: el,
		template: `
		<ul class="menu-list">
		<template v-for="(menu, menu_index) in menus">
		<a v-if="menu.label" v-html="menu.label" @click="expand(menu_index)"></a>
		<li v-show="menu.expand"><ul><li v-for="(item, item_index) in menu.items">
		<a :href="item.href" @click="active(menu_index, item_index)" :class="item.class" v-text="item.title"></a>
		</li></ul></li>
		</template>
		</ul>`,
		data: {
			menus: wnd_menus_data,
		},
		methods: {
			active: function(menu_index, item_index) {
				for (let i = 0; i < this.menus.length; i++) {
					const menu = this.menus[i];
					if (menu_index !== i) {
						menu.expand = false;
						// continue;
					}

					/**
					 * Vue 直接修改数组的值无法触发重新渲染
					 * @link https://cn.vuejs.org/v2/guide/reactivity.html#%E6%A3%80%E6%B5%8B%E5%8F%98%E5%8C%96%E7%9A%84%E6%B3%A8%E6%84%8F%E4%BA%8B%E9%A1%B9
					 */
					for (let j = 0; j < menu.items.length; j++) {
						const item = menu.items[j];
						if (j != item_index || menu_index !== i) {
							Vue.set(item, 'class', '');
						} else {
							Vue.set(item, 'class', 'is-active');
						}
					}
				}
			},
			expand: function(menu_index) {
				for (let i = 0; i < this.menus.length; i++) {
					const menu = this.menus[i];
					if (menu_index !== i) {
						menu.expand = false;
					} else {
						menu.expand = !(menu.expand || false);
					}
				}
			}

		},
		mounted() {
			// 如果尚未定义菜单数据，异步请求数据并赋值
			if (!wnd_menus_data) {
				_this = this;
				axios({
					'method': 'get',
					url: wnd_jsonget_api + '/wnd_menus' + lang_query,
				}).then(function(res) {
					_this.menus = res.data.data;
					wnd_menus_data = res.data.data;
				});
			}

		},
		// 计算
		computed: {},
		// 侦听器
		watch: {},
	});
}