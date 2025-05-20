<div id="vue-attachments-app">
	<div class="wnd-filter-tabs mb-3 is-hidden">
		<div v-for="tab in tabs" class="columns is-marginless is-vcentered is-mobile">
			<div class="column is-narrow">{{tab.label}}</div>
			<div class="column tabs">
				<ul class="tab">
					<li v-for="(value, key) in tab.options" :class="is_active(tab.key, value)">
						<a @click="update_filter(tab.key, value)">{{key}}</a>
					</li>
				</ul>
			</div>
		</div>
	</div>
	<!-- <input type="checkbox" @change="toggleAll" :checked="allSelected"> -->
	<div v-show="selectedItems.length">{{selectedItems}}</div>
	<div id="lists">
		<div v-for="(item, index) in data.results" class="columns is-multiline is-marginless is-justify-content-space-between" style="border-top: 1px dotted #CCC;">
			<div class="column is-narrow">
				<div class="is-pulled-left mr-3">
					<input type="checkbox" v-model="item.selected" class="mr-1">
					{{item.file_path}}
				</div>
				<span class="is-size-7">{{timeToString(item.created_at)}}</span>
				<span class="is-size-7">&nbsp;-&nbsp;
					<em v-text="0 == item.user_id ? `[Anonymous]`: `[user : ${item.user_id}]`"></em>
					<em v-text="0 == item.post_id ? `0`: `[post : ${item.post_id}]`"></em>
				</span>
			</div>

			<div class="column is-narrow">
				<a class="button is-danger is-small" @click="delete_attachment(item.ID,index)">Delete</a>
				<!-- <a @click="get_detail(item.ID)"><span v-text="item.subject"></span></a> -->
			</div>
			<!-- <div class="column is-full" v-show="details[item.ID] && !details[item.ID].hidden" v-html="show_detail(details[item.ID])"></div> -->
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
				};

				const base = super.data();
				return { ...base, ...data };
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
			async query() {
				let res = await wnd_query("wnd_attachments", this.param, {
					"Container": "#lists"
				});

				wnd_loading("#lists", true);
				this.data = res.data;
			}
			async delete_attachment(id, index) {
				if (!confirm("Are you sure to delete this attachment?" + index)) {
					return;
				}
				let res = await wnd_ajax_action("common/wnd_delete_file", { "file_id": id });
				if (1 == res.status) {
					this.data.results.splice(index, 1);

				}
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
		}

		const container = "#vue-attachments-app";
		const custom = new App_Filter(container);
		const vueComponent = custom.toVueComponent();
		const app = Vue.createApp(vueComponent);
		app.mount(container);
		vueInstances.push(app);
	}
</script>