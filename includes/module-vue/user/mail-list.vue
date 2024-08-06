<div id="vue-list-app">
	<div class="wnd-filter-tabs mb-3">
		<div v-for="tab in tabs" class="columns is-vcentered is-mobile">
			<div class="column tabs">
				<ul class="tab">
					<li class="ml-2 mr-2"><input type="checkbox" @change="toggleAll" :checked="allSelected"></li>
					<li v-for="(value, key) in tab.options" :class="is_active(tab.key, value)"><a @click="update_filter(tab.key, value)">{{key}}</a></li>
				</ul>
			</div>
		</div>
		<!-- <a @click="toggleSelection">反选</a> -->
		<span v-show="selectedItems.length">{{selectedItems}}</span>
	</div>
	<div id="lists">
		<div v-for="(item, index) in data.results" class="columns is-multiline is-justify-content-space-between" style="border-top: 1px dotted #CCC;">
			<div class="column is-narrow">
				<div class="is-pulled-left" :class="get_status_class(item)" style="min-width: 80px;">
					<input type="checkbox" v-model="item.selected" class="mr-1">
					<a @click="get_detail(item.ID, index)">
						<b v-if="`unread` == item.status">{{item.subject}}</b>
						<span v-else>{{item.subject}}</span>
					</a>
				</div>
			</div>

			<div class="column is-narrow">
				<a @click="get_detail(item.ID)"><span>{{timeToString(item.sent_at)}}</span></a>
			</div>
			<div class="column is-full" v-show="details[item.ID] && !details[item.ID].hidden">
				<div class="message is-primary is-light">
					<div class="message-body">{{show_detail(details[item.ID])}}</div>
				</div>
			</div>
		</div>
	</div>
	<nav class="pagination is-centered">
		<ul class="pagination-list">
			<li v-if="param.paged >= 2">
				<a class="pagination-previous" @click="update_filter('paged', --param.paged)">←</a>
			</li>
			<li v-if="data.number >= param.number">
				<a class="pagination-next" @click="update_filter('paged', ++param.paged)">→</a>
			</li>
		</ul>
	</nav>
</div>
<script>
	var parent = document.querySelector("#vue-list-app").parentNode;
	vue_param = Object.assign({
		number: 20,
		paged: 1,
		status: "any",
	}, vue_param);
	var option = {
		data() {
			return {
				param: vue_param,
				data: {},
				details: {},
				tabs: vue_tabs,
			}
		},
		methods: {
			update_filter: function (key, value, remove_args = []) {
				if (value) {
					this.param[key] = value;
				} else {
					delete this.param[key];
				}
				if (remove_args) {
					remove_args.forEach((key) => {
						delete this.param[key];
					});
				}
				// 非 翻页的其他查询，则重置页面
				if ("paged" != key) {
					this.param.paged = 1;
				}

				this.query();
			},
			query: async function () {
				let res = await wnd_query("wnd_mails", this.param, {
					"Container": "#lists"
				});

				wnd_loading("#lists", true);
				this.data = res.data;
			},
			get_detail: async function (id, index) {
				if (this.details[id]) {
					this.details[id].hidden = !this.details[id].hidden;
					return;
				}

				let res = await wnd_query("wnd_get_mail", { "id": id });
				if (1 == res.status) {
					this.details[id] = res.data;
					// 标记为已读
					this.data.results[index].status = "read";
				}
			},
			show_detail: function (obj) {
				if (!obj) {
					return '';
				}
				return obj.content;
			},
			is_active: function (key, value) {
				return this.param[key] == value ? "is-active" : "";
			},
			get_status_class: function (obj) {
				return "completed" == obj.status ? "has-text-primary" : "";
			},
			get_date: function (timestamp) {
				// 创建一个新的Date对象，使用时间戳作为参数（以毫秒为单位） 
				const date = new Date(timestamp * 1000);
				const year = date.getFullYear();
				const month = (date.getMonth() + 1).toString().padStart(2, '0');
				const day = date.getDate().toString().padStart(2, '0');
				const hours = date.getHours().toString().padStart(2, '0');
				const minutes = date.getMinutes().toString().padStart(2, '0');
				const seconds = date.getSeconds().toString().padStart(2, '0');

				let now = new Date();
				if (date.getFullYear() === now.getFullYear()) {
					return `${month}-${day} ${hours}:${minutes}:${seconds}`;
				} else {
					return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
				}
			},
			timeToString: function (timestamp) {
				let date = new Date(timestamp * 1000),
					now = new Date(), diff = now - date, minutes = Math.floor(diff / 60000), hours = Math.floor(minutes / 60), days = Math.floor(hours / 24);

				if (date.getFullYear() === now.getFullYear() && date.getMonth() === now.getMonth() && date.getDate() === now.getDate()) {
					if (hours === 0) {
						return minutes + " minutes ago";
					} else if (hours < 24) {
						return hours + " hours ago";
					} else {
						return date.toDateString();
					}
				} else {
					return this.get_date(timestamp);
				}
			},
			toggleAll(event) {
				this.allSelected = event.target.checked;
			},
			toggleSelection() {
				this.data.results.forEach(item => {
					item.selected = !item.selected;
				});
			}
		},
		computed: {
			allSelected: {
				get() {
					return this.data.results.every(item => item.selected);
				},
				set(value) {
					this.data.results.forEach(item => {
						item.selected = value;
					});
				}
			},
			selectedItems() {
				return this.data.results.filter(item => item.selected).map(item => item.ID);
			}
		},
		mounted() {
			this.query();
		},
		updated() {
			funTransitionHeight(parent, trs_time);
		}
	}
	Vue.createApp(option).mount('#vue-list-app');
</script>