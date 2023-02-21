<div id="vue-finance-stats">
	<div class="tabs">
		<ul>
			<li :class="is_active('type','order')"><a @click="set_type('order')">消费统计</a></li>
			<li :class="is_active('type','recharge')"><a @click="set_type('recharge')">充值统计</a></li>
		</ul>
	</div>
	<div class="tabs">
		<ul>
			<li :class="is_active('range','week')"><a @click="set_range('week')">本周</a></li>
			<li :class="is_active('range','month')"><a @click="set_range('month')">本月</a></li>
			<li :class="is_active('range','year')"><a @click="set_range('year')">今年</a></li>
		</ul>
	</div>
	<canvas id="finance-stats" class="charts" style="width:100%;min-height:300px;"></canvas>
</div>

<script>
	var option = {
		data() {
			return {
				res: {},
				type: 'order',
				range: 'month',
			}
		},
		methods: {
			is_active: function (key, value) {
				return this[key] == value ? 'is-active' : '';
			},
			set_type: function (type) {
				this.type = type;
				this.query_status();
			},
			set_range: function (range) {
				this.range = range;
				this.query_status();
			},
			query_status: async function () {
				let res = await wnd_query('wnd_site_stats', { 'type': this.type, 'range': this.range });
				this.res = res.data;
				this.showCharts('finance-stats', this.res);
			},
			showCharts: async function (id, data) {
				if ('undefined' == typeof uCharts) {
					let url = static_path + 'js/lib/u-charts.min.js' + cache_suffix;
					await wnd_load_script(url);
				} 
				this._showCharts(id, data);
			},

			_showCharts: function (id, data) {
				let uChartsInstance = {};

				const canvas = document.getElementById(id);
				const ctx = canvas.getContext('2d');
				canvas.width = canvas.offsetWidth;
				canvas.height = canvas.offsetHeight;
				uChartsInstance[id] = new uCharts({
					type: 'line',
					context: ctx,
					width: canvas.width,
					height: canvas.height,
					categories: data.categories,
					series: data.series,
					animation: true,
					background: '#FFFFFF',
					color: ['#1890FF', '#91CB74', '#FAC858', '#EE6666', '#73C0DE', '#3CA272', '#FC8452', '#9A60B4', '#ea7ccc'],
					padding: [15, 10, 0, 15],
					legend: {},
					xAxis: {
						disableGrid: true
					},
					yAxis: {
						gridType: 'dash',
						dashLength: 2,
						data: [{}] // 添加此空白参数后， Y 轴刻度精度问题得以解决，原因不明
					},
					extra: {
						line: {
							type: 'curve',
							width: 2
						}
					}
				});

				canvas.onclick = function (e) {
					uChartsInstance[e.target.id].touchLegend(getH5Offset(e));
					uChartsInstance[e.target.id].showToolTip(getH5Offset(e));
				};
				canvas.onmousemove = function (e) {
					uChartsInstance[e.target.id].showToolTip(getH5Offset(e));
				};
			},
		},

		mounted: function () {
			this.query_status();
		}
	}

	Vue.createApp(option).mount('#vue-finance-stats');
</script>