<div id="vue-finance-stats">
	<div class="tabs">
		<ul>
			<li :class="is_active('type','recharge')"><a @click="update_filter('type','recharge')">充值统计</a></li>
			<li :class="is_active('type','order')"><a @click="update_filter('type','order')">消费统计</a></li>
		</ul>
	</div>
	<div class="tabs">
		<ul>
			<li :class="is_active('range','14')"><a @click="update_filter('range',14)">近2周</a></li>
			<li :class="is_active('range','year')"><a @click="update_filter('range','year')">近12个月</a></li>
		</ul>
	</div>
	<canvas id="finance-stats" class="charts" ref="chartCanvas" style="width:100%;max-height:500px;"></canvas>
	<h3 class="has-text-centered is-size-4 mt-1">总计: {{res.total}}</h3>
</div>
<script>
	{
		class App_Filter extends Filter {
			data() {
				const { shallowRef } = Vue;
				const param = Object.assign({
					paged: 1,
					range: 14,
					type: "recharge",
				}, module_data);

				const data = {
					res: {},
					param: param,
					tradeChart: shallowRef(null),
				}

				const base = super.data();
				return { ...base, ...data };
			}
			async query() {
				let res = await wnd_query('admin/wnd_finance_stats', this.param);
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
					if ('undefined' == typeof ChartDataLabels) {
						let plugin_url = static_path + 'js/lib/chartjs-plugin-datalabels.min.js' + cache_suffix;
						await wnd_load_script(plugin_url);
					}
					this.showCharts();
				}
			}
			showCharts() {
				const ctx = this.$refs.chartCanvas.getContext('2d');
				const plugins = [];
				// 非移动端才启用 datalabels 插件
				if (!wnd_is_mobile()) {
					plugins.push(ChartDataLabels);
				}
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
						},
						plugins: {
							datalabels: {
								anchor: 'end',
								align: 'top',
								color: '#EEE',
								font: { weight: 'bold' },
								backgroundColor: '#F14668',
								borderRadius: 3,
								formatter: value => value + ' 元'
							}
						},
					},
					plugins: plugins
				});
			}
			unmounted() {
				this.tradeChart.destroy();
			}
		}
		const container = "#vue-finance-stats";
		const custom = new App_Filter(container);
		const vueComponent = custom.toVueComponent();
		const app = Vue.createApp(vueComponent);
		app.mount(container);
		vueInstances.push(app);
	}
</script>