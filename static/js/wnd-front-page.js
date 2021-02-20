/**
 *@since 0.9.25
 *前端用户中心
 */
let container = '#user-center';

function user_center_hash() {
	let hash = location.hash;
	if (!hash) {
		wnd_ajax_embed("#ajax-module", "wnd_user_overview");
		return;
	}

	var module = hash.replace("#", "")
	wnd_ajax_embed("#ajax-module", module);
}

// 用户中心Tabs
user_center_hash();
window.onhashchange = user_center_hash;


// let parent = document.querySelector(container).parentNode;
new Vue({
	el: container,
	// template: build_filter_template(filter),
	data: {
		menus: menus,
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
	mounted() {},
	// 计算
	computed: {},
	// 侦听器
	watch: {},
});
// }