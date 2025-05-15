<style>
	.order-card,
	.recharge-card {
		margin-bottom: 1.5rem;
	}

	.product-item {
		border-top: 1px solid #ddd;
		padding: .5rem 0;
	}

	.product-image {
		max-width: 80px;
		height: auto;
	}
</style>
<div id="vue-finance-list-app">
	<div v-if="panel" v-html="panel"></div>
	<div class="wnd-filter-tabs mb-3">
		<div v-for="tab in tabs" class="columns is-marginless is-vcentered is-mobile">
			<div class="column is-narrow">{{tab.label}}</div>
			<div class="column tabs">
				<ul class="tab">
					<li v-for="(value, key) in tab.options" :class="is_active(tab.key, value)"><a @click="update_filter(tab.key, value)">{{key}}</a></li>
				</ul>
			</div>
		</div>
	</div>
	<!-- <input type="checkbox" @change="toggleAll" :checked="allSelected"> -->
	<div v-show="selectedItems.length">{{selectedItems}}</div>
	<div id="lists">
		<div v-for="(item, index) in data.results" class="order-item">
			<!-- 另一个商品订单 -->
			<div v-if="item.type ==`order`" class="order-card box">
				<div class="level is-mobile mb-1">
					<div class="level-left">
						<i class="fas fa-receipt mr-1"></i><strong>订单号：</strong>&nbsp;{{item.ID}}
					</div>
					<div class="level-right tags">
						<!-- <span class="tag is-info is-light"><i class="fas fa-clock mr-1"></i>待支付</span> -->
						<!-- <span class="tag is-light"><i class="fas fa-check-circle"></i>已完成</span> -->
						<span class="tag is-success is-light"><i class="fas fa-truck mr-1"></i>已发货</span>
						<span class="tag is-danger is-light is-clickable"><i class="fas fa-trash-alt"></i>删除</span>
					</div>
				</div>
				<div class="is-size-7 has-text-grey mb-1">
					<i class="fas fa-clock mr-1"></i>时间：{{timeToString(item.time)}}
					<i class="fas fa-money-check-alt mr-1 ml-2"></i>{{item.payment_gateway}}
					<i class="fas fa-user mr-1 ml-2"></i> {{item.user_id}}
				</div>

				<div class="product-item media">
					<figure class="media-left">
						<p class="image is-64x64">
							<img class="product-image" src="" alt="商品图">
						</p>
					</figure>
					<div class="media-content">
						<p><i class="fas fa-stopwatch mr-1"></i><strong>{{item.subject}}</strong></p>
						<p class="is-size-7 has-text-grey">规格：标准版 / 黑色</p>
						<p class="is-size-7">数量：1　小计：<i class="fas fa-yen-sign"></i>{{item.total_amount}}</p>
					</div>
				</div>

				<div class="has-text-right mt-1">
					<strong><i class="fas fa-money-bill-wave mr-1"></i>总计：<i class="fas fa-yen-sign"></i>{{item.total_amount}}</strong>
				</div>
			</div>
			<!-- 另一条充值记录 -->
			<div v-else class="box recharge-card">
				<div class="level is-mobile mb-1">
					<div class="level-left">
						<i class="fas fa-file-invoice-dollar mr-1"></i>
						<strong>订单号：</strong>&nbsp;{{item.ID}}
					</div>
					<div class="level-right tags">
						<span class="tag is-light"><i class="fas fa-check-circle"></i>已完成</span>
						<span class="tag is-danger is-light is-clickable"><i class="fas fa-trash-alt"></i>删除</span>
					</div>
				</div>

				<div class="is-size-7 has-text-grey">
					<i class="fas fa-clock mr-1"></i>时间：{{timeToString(item.time)}}
					<i class="fas fa-money-check-alt mr-1 ml-2"></i>{{item.payment_gateway}}
					<i class="fas fa-user mr-1 ml-2"></i> {{item.user_id}}
					<p>{{item.subject}}</p>
				</div>

				<div class="mt-1">
					<p><i class="fas fa-coins"></i>金额：<b class="has-text-danger">{{item.total_amount}}</b></p>
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
		const parent = document.querySelector("#vue-finance-list-app").parentNode;
		const param = Object.assign({
			number: 20,
			paged: 1,
			type: "order",
		}, module_data.param);
		const option = {
			data() {
				return {
					panel: module_data.panel || null,
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
					let res = await wnd_query("wnd_transactions", this.param, {
						"Container": "#lists"
					});

					wnd_loading("#lists", true);
					this.data = res.data;
				},
				get_detail: async function (id) {
					if (this.details[id]) {
						this.details[id].hidden = !this.details[id].hidden;
						return;
					}
					this.details[id] = {};
				},
				show_detail: function (obj) {
					if (!obj) {
						return '';
					}
					let str = '';
					// str += `<button class="button is-small" onclick='wnd_ajax_modal("admin/wnd_account_status_form", {"user_id": ${obj.ID}} )'><i class="fas fa-info-circle"></i></button>`;
					str += `<button class="button is-small" onclick='wnd_ajax_modal("admin/wnd_refund_form", {"transaction_id": ${obj.ID}} )'><i class="fas fa-coins"></i></button>`;
					str += '<table class="table is-fullwidth  is-size-7 is-bordered">';
					str += Object.entries(obj).map(([key, value]) => `<tr><td style="white-space:nowrap;width:1px;">${key}</td><td>${value}</td></tr>`).join('\n');
					str += '</table>';
					return str;
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
			},
		}

		const app = Vue.createApp(option);
		app.mount('#vue-finance-list-app');
		vueInstances.push(app);
	}
</script>