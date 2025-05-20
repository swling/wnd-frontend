<div id="vue-mail-box-app">
	<div class="wnd-filter-tabs mb-3">
		<div v-for="tab in tabs" class="columns is-vcentered is-mobile">
			<div class="column tabs">
				<ul class="tab">
					<li class="ml-2 mr-2"><input type="checkbox" @change="toggleAll" :checked="allSelected"></li>
					<li v-for="(value, key) in tab.options" :class="is_active(tab.key, value)"><a @click="update_filter(tab.key, value)">{{key}}</a></li>
				</ul>
			</div>
		</div>
		<a @click="toggleSelection">反选</a>
		<input type="checkbox" @change="toggleAll" :checked="allSelected">
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
				<a class="pagination-previous" @click="update_filter('paged', +param.paged -1 )">←</a>
			</li>
			<li v-if="data.number >= param.number">
				<a class="pagination-next" @click="update_filter('paged', +param.paged + 1)">→</a>
			</li>
		</ul>
	</nav>
</div>
<script>
	{
		class App_Filter extends Filter {
			data() {
				const data = {
					data: { "results": [] },
					details: {},
					tabs: module_data.tabs,
				}

				const base = super.data();
				return { ...base, ...data };
			}
			async query() {
				let res = await wnd_query("wnd_mails", this.param, {
					"Container": "#lists"
				});

				wnd_loading("#lists", true);
				this.data = res.data;
			}
			async get_detail(id, index) {
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
			}
			show_detail(obj) {
				if (!obj) {
					return '';
				}
				return obj.content;
			}
			get_status_class(obj) {
				return "read" == obj.status ? "has-text-primary" : "";
			}
			timeToString(timestamp) {
				return wnd_time_to_string(timestamp);
			}
			toggleAll(event) {
				this.allSelected = event.target.checked;
			}
			toggleSelection() {
				this.data.results.forEach(item => {
					item.selected = !item.selected;
				});
			}
			computed() {
				return {
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
				}
			}
		}

		const container = "#vue-mail-box-app";
		const custom = new App_Filter(container);
		const vueComponent = custom.toVueComponent();
		const app = Vue.createApp(vueComponent);
		app.mount(container);
		vueInstances.push(app);
	}
</script>