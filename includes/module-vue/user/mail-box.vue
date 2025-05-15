<div id="vue-mail-list-app">
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
				<div class="is-pulled-left mr-3" :class="get_status_class(item)">
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
	{
		const parent = document.querySelector("#vue-mail-list-app").parentNode;
		const param = Object.assign({
			number: 20,
			paged: 1,
			status: "any",
		}, module_data.param);
		const option = {
			data() {
				return {
					param: param,
					data: { "results": [] },
					details: {},
					tabs: module_data.tabs,
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

					wnd_update_url_hash(this.param, ['ajax_type']);
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
				timeToString: function (timestamp) {
					return wnd_time_to_string(timestamp);
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

		const app = Vue.createApp(option);
		app.mount('#vue-mail-list-app');
		vueInstances.push(app);
	}
</script>