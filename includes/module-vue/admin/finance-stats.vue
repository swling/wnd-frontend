<div id="vue-finance-stats">
	<div class="tabs">
		<ul>
			<li :class="is_active('type','recharge')"><a @click="update_filter('type','recharge')">充值统计</a></li>
			<li :class="is_active('type','order')"><a @click="update_filter('type','order')">消费统计</a></li>
		</ul>
	</div>
	<div class="tabs">
		<ul>
			<li :class="is_active('range','15')"><a @click="update_filter('range',15)">近15天</a></li>
			<li :class="is_active('range','year')"><a @click="update_filter('range','year')">近12个月</a></li>
		</ul>
	</div>
	<canvas id="finance-stats" class="charts" ref="chartCanvas" style="width:100%;max-height:500px;"></canvas>
	<h3 class="has-text-centered is-size-4 mt-1">总计: {{res.total}}</h3>
</div>
<script>
	{
		const { shallowRef } = Vue;
		const param = Object.assign({
			paged: 1,
			range: '15',
			type: "recharge",
		}, module_data);

		const option = {
			data() {
				return {
					res: {},
					param: param,
					tradeChart: shallowRef(null),
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
				is_active: function (key, value) {
					return this.param[key] == value ? 'is-active' : '';
				},
				query: async function () {
					let res = await wnd_query('wnd_site_stats', this.param);
					this.res = res.data;
					// 更新数据
					if (this.tradeChart) {
						this.tradeChart.data.labels = this.res.categories;
						this.tradeChart.data.datasets[0].data = this.res.data;
						this.tradeChart.update();
						return;
					} else {
						if ('undefined' == typeof Chart) {
							let url = static_path + 'js/lib/chart.umd.js' + cache_suffix;
							await wnd_load_script(url);
						}
						this.showCharts();
					}
				},
				showCharts: function () {
					const ctx = this.$refs.chartCanvas.getContext('2d');
					this.tradeChart = new Chart(ctx, {
						type: 'line',
						data: {
							labels: this.res.categories,
							datasets: [{
								label: '交易金额（元）',
								data: this.res.data,
								borderColor: 'rgba(75, 192, 192, 1)',
								fill: false,
								tension: 0.3
							}]
						},
						options: {
							responsive: true,
							scales: {
								x: { display: true, title: { display: true, text: '日期' } },
								y: { display: true, title: { display: true, text: '金额（元）' } }
							}
						}
					});
				},
			},
			mounted: function () {
				this.query();
			}
		}
		const app = Vue.createApp(option);
		app.mount('#vue-finance-stats');
		vueInstances.push(app);
	}
</script>