<style>
	.product-item {
		border-top: 1px solid #ddd;
		padding: .5rem 0;
	}

	.product-image {
		max-width: 80px;
		height: auto;
	}

	.order-item>.box {
		border-left: 3px solid #ffe08a;
	}

	.order-item.is-today>.box {
		border-left: 3px solid #f14668;
	}

	.order-item.is-today time {
		color: #f14668;
	}

	.order-item.is-yesterday>.box {
		border-left: 3px solid #48c78e;
	}

	.order-item.is-yesterday time {
		color: #48c78e;
	}
</style>
<div id="vue-orders-app">
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
	<div id="lists">
		<div v-for="(item, index) in data.results" class="order-item" :class="getItemClass(item)">
			<!-- 另一个商品订单 -->
			<div v-if="item.type ==`order`" class="order-card box mb-5">
				<div class="level is-mobile mb-1">
					<div class="level-left">
						<i class="fas fa-receipt mr-1"></i><strong>订单号：</strong>&nbsp;{{item.ID}}
					</div>
					<div class="level-right tags">
						<span v-html="get_status_icon(item)" class="mr-1"></span>
						<span @click="delete_transaction(item.ID, index)">
							<span class="tag is-danger is-light is-clickable"><i class="fas fa-trash-alt"></i></span>
						</span>
					</div>
				</div>
				<div class="is-size-7 has-text-grey">
					<time><i class="fas fa-clock mr-1"></i>{{timeToString(item.time)}}</time>
					<i class="fas fa-money-check-alt mr-1 ml-2"></i>{{item.payment_gateway}}
					<i class="fas fa-user mr-1 ml-2"></i>{{item.user_id}}
				</div>

				<div class="product-item media mt-1">
					<figure class="media-left" v-if="item.thumbnail">
						<p class="image is-64x64">
							<img class="product-image" :src="item.thumbnail" alt="商品图">
						</p>
					</figure>
					<div class="media-content is-size-7 has-text-grey">
						<p><i class="fas fa-stopwatch mr-1"></i><a :href="item.object_url" target="_blank"><strong>{{item.subject}}</strong></a></p>
						<p v-if="item.props.sku">规格：{{item.props.sku}}</p>
						<p>数量：{{item.props.quantity}} 小计：<i class="fas fa-yen-sign"></i>{{item.total_amount}}</p>
						<p v-if="is_manager">网络：{{item.props.ip}}</p>
					</div>
				</div>
				<div class="level is-mobile">
					<div class="level-left tags">
						<!-- 用户查看物流 -->
						<template v-if="item.props.is_virtual < 1">
							<i class="fas fa-truck mr-1"></i>运单：<span class="tag">{{item.props.express_no || `……`}}</span>
							<span class="tag is-small is-warning is-clickable" @click="confirmReceipt(item,index)" v-if="`shipped`==item.status">
								确认收货
							</span>
							<span v-if="`completed` == item.status" class="tag is-small is-success">已收货</span>
						</template>
					</div>
					<div class="level-right">
						<strong><i class="fas fa-shopping-bag mr-1"></i>总计：<i class="fas fa-yen-sign"></i>{{item.total_amount}}</strong>
					</div>
				</div>
				<!-- 管理员发货 -->
				<div class="Order-Ship-Manager" v-if="is_manager && (`paid`==item.status || `shipped`==item.status)">
					<Order-Ship-Manager v-model:order="data.results[index]"></Order-Ship-Manager>
				</div>
			</div>
			<!-- 另一条充值记录 -->
			<div v-else class="box recharge-card mb-5">
				<div class="level is-mobile mb-1">
					<div class="level-left">
						<i class="fas fa-file-invoice-dollar mr-1"></i>
						<strong>订单号：</strong>&nbsp;{{item.ID}}
					</div>
					<div class="level-right tags">
						<span v-html="get_status_icon(item)" class="mr-1"></span>
						<span @click="delete_transaction(item.ID, index)">
							<span class="tag is-danger is-light is-clickable"><i class="fas fa-trash-alt"></i></span>
						</span>
					</div>
				</div>
				<div class="is-size-7 has-text-grey">
					<p>
						<time><i class="fas fa-clock mr-1"></i>{{timeToString(item.time)}}</time>
						<i class="fas fa-money-check-alt mr-1 ml-2"></i>{{item.payment_gateway}}
						<i class="fas fa-user mr-1 ml-2"></i> {{item.user_id}}
					</p>
				</div>
				<div class="product-item mt-1 is-size-7 has-text-grey">
					<p v-if="is_manager"><i class="fas fa-map-marker-alt mr-1"></i>IP：{{item.props.ip}}</p>
				</div>
				<div class="level is-mobile">
					<div class="level-left is-size-7">
						<i class="fas fa-stopwatch mr-1"></i><strong>{{item.subject}}</strong>
					</div>
					<div class="level-right">
						<i class="fas fa-coins mr-1"></i><b class="has-text-danger">{{item.total_amount}}</b>
					</div>
				</div>
			</div>
		</div>
	</div>
	<nav class="pagination is-centered">
		<ul class="pagination-list">
			<li v-if="param.paged >= 2">
				<a class="pagination-previous" @click="update_filter('paged', +param.paged - 1)">←</a>
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
					panel: module_data.panel || null,
					data: { "results": [] },
					details: {},
					tabs: module_data.tabs,
					is_manager: module_data.is_manager || false,
				}

				const base = super.data();
				return { ...base, ...data };
			}

			components() {
				return { OrderShipManager };
			}

			async query() {
				let res = await wnd_query("wnd_transactions", this.param, {
					"Container": "#lists"
				});

				wnd_loading("#lists", true);
				this.data = res.data;
			}
			async confirmReceipt(item, index) {
				if (!confirm("确认收货？")) {
					return;
				}
				const res = await wnd_ajax_action("common/wnd_confirm_receipt", { "id": item.ID });
				if (res.status > 0) {
					this.data.results[index].status = res.data.status;
				} else {
					wnd_alert_modal(`<div class="notification is-danger is-light">${res.msg}</div>`);
				}
			}
			async delete_transaction(id, index) {
				if (!confirm("确认删除？")) {
					return;
				}
				const res = await wnd_ajax_action("common/wnd_delete_transaction", { "id": id });
				if (res.status > 0) {
					this.data.results.splice(index, 1); // 删除1个元素
				} else {
					wnd_alert_modal(`<div class="notification is-danger is-light">${res.msg}</div>`);
				}
			}
			getItemClass(item) {
				const _class = item.status;
				const date = new Date(item.time * 1000);
				const now = new Date();

				const isSameDate = (d1, d2) =>
					d1.getFullYear() === d2.getFullYear() &&
					d1.getMonth() === d2.getMonth() &&
					d1.getDate() === d2.getDate();

				const yesterday = new Date();
				yesterday.setDate(now.getDate() - 1);

				if (isSameDate(date, now)) {
					return _class + ' is-today';
				} else if (isSameDate(date, yesterday)) {
					return _class + ' is-yesterday';
				} else {
					return _class;
				}
			}
			get_status_icon(item) {
				switch (item.status) {
					case 'pending':
						// 待支付：强调等待付款
						return `<span class="tag is-warning is-light"><i class="fas fa-hourglass-start mr-1"></i>待支付</span>`;
					case 'paid':
						// 待发货：用户已付款，等待商家操作
						return `<span class="tag is-info is-light"><i class="fas fa-box-open mr-1"></i>待发货</span>`;
					case 'shipped':
						// 已发货：商品在运输中
						return `<span class="tag is-primary is-light"><i class="fas fa-truck mr-1"></i>已发货</span>`;
					case 'completed':
						// 已完成：交易成功
						return `<span class="tag is-success is-light"><i class="fas fa-check-circle mr-1"></i>已完成</span>`;
					case 'refunded':
						// 已退款：用户收到退款
						return `<span class="tag is-danger is-light"><i class="fas fa-undo-alt mr-1"></i>已退款</span>`;
					case 'closed':
						// 已关闭：订单作废或取消
						return `<span class="tag is-dark is-light"><i class="fas fa-times-circle mr-1"></i>已关闭</span>`;
					default:
						// 未知状态
						return `<span class="tag is-danger"><i class="fas fa-question-circle mr-1"></i>未知：${item.status}</span>`;
				}
			}
		}

		const container = "#vue-orders-app";
		const custom = new App_Filter(container);
		const vueComponent = custom.toVueComponent();
		const app = Vue.createApp(vueComponent);
		app.mount(container);
		vueInstances.push(app);
	}
</script>