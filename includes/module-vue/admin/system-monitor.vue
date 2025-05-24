<style>
	#sys-monitor-app .canvas {
		max-width: 300px;
	}
</style>
<div id="sys-monitor-app" class="container">
	<div class="buttons is-right">
		<button class="button is-small is-warning" @click="confirmClear('clear_opcache')">
			<i class="fas fa-broom"></i>&nbsp;清空 OPcache
		</button>
		<button class="button is-small is-warning" @click="confirmClear('clear_object_cache')">
			<i class="fas fa-trash"></i>&nbsp;清空 object cache
		</button>
		<!-- <button class="button is-small is-danger" @click="confirmClear('clear_redis')">
			<i class="fas fa-trash"></i>&nbsp;清空 Redis
		</button> -->
	</div>

	<div id="charts" class="columns is-multiline">
		<div class="column is-6">
			<div class="box">
				<div class="canvas"><canvas id="opcacheMemoryChart"></canvas></div>
				<table class="table is-fullwidth is-striped is-size-7 is-bordered">
					<tbody>
						<tr v-for="(v, k) in opcacheStats" :key="k">
							<th class="is-narrow">{{ k }}</th>
							<td>{{ v }}</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<div class="column is-6">
			<div class="box">
				<div class="canvas"><canvas id="redisChart"></canvas></div>
				<table class="table is-fullwidth is-striped is-size-7 is-bordered">
					<tbody>
						<tr v-for="(v, k) in redisStats" :key="k">
							<th class="is-narrow">{{ k }}</th>
							<td>{{ v }}</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<div class="column is-4">
			<div class="box">
				<div class="canvas"><canvas id="diskChart"></canvas></div>
				<div class="info">
					<h2><i class="fas fa-tachometer-alt"></i> 系统负载</h2>
					<table class="table is-fullwidth is-striped is-size-7">
						<thead>
							<tr>
								<th>1 分钟</th>
								<th>5 分钟</th>
								<th>15 分钟</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>{{ system.load['1min'].toFixed(2) }}</td>
								<td>{{ system.load['5min'].toFixed(2) }}</td>
								<td>{{ system.load['15min'].toFixed(2) }}</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="column is-4">
			<div class="box">
				<div class="canvas"><canvas id="cpuChart"></canvas></div>
				<div class="info">
					<h2><i class="fas fa-microchip"></i> CPU 信息</h2>
					<table class="table is-fullwidth is-striped is-size-7">
						<thead>
							<tr>
								<th>型号</th>
								<th>核心</th>
								<th>使用率</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>{{ system.cpu.model }}</td>
								<td>{{ system.cpu.cores }}</td>
								<td>{{ system.cpu.usage_percent }}%</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="column is-4">
			<div class="box">
				<div class="canvas"><canvas id="memChart"></canvas></div>
				<div class="info">
					<h2><i class="fas fa-microchip"></i> Mem 信息</h2>
				</div>
			</div>
		</div>
	</div>

</div>
<script>
	{
		const parent = document.querySelector('#sys-monitor-app').parentNode;
		const app = Vue.createApp({
			data() {
				return {
					opcache: {},
					redis: {},
					system: {
						disk: { total: 0, used: 0, free: 0 },
						cpu: { model: '', cores: 0, usage_percent: 0 },
						load: { '1min': 0, '5min': 0, '15min': 0 }
					},
					charts: {},
					intervalId: null
				};
			},
			computed: {
				opcacheStats() {
					const info = this.opcache.opcache_statistics || {};
					return {
						'Cached scripts': info.num_cached_scripts,
						'Max keys': info.max_cached_keys,
						'Cached keys': info.num_cached_keys,
						'Hit rate': info.opcache_hit_rate,
						'Hits': `${info.hits / 1000} K`,
					};
				},
				redisStats() {
					const info = this.redis.info || {};
					return {
						'Hits': `${info.keyspace_hits / 1000} K`,
						'Misses': info.keyspace_misses,
						'Clients': info.connected_clients,
						'QPS': info.instantaneous_ops_per_sec,
						'Policy': info.maxmemory_policy,
					};
				}
			},
			methods: {
				async fetchData() {
					let res = await wnd_query("admin/wnd_system_info");
					if (res.satus < 1) {
						return;
					}
					this.opcache = res.data.opcache || {};
					this.redis = res.data.redis || {};
					this.renderCharts();

					this.system = res.data.system;
					this.renderDiskChart();
					this.renderCPUChart();
					this.renderMemChart();
				},
				renderCharts() {
					// OPcache 内存占用图表
					const ctx3 = document.getElementById('opcacheMemoryChart');
					const memoryUsed = (this.opcache.memory_usage?.used_memory || 0) / (1024 * 1024); // 已使用内存 (MB)
					const memoryFree = (this.opcache.memory_usage?.free_memory || 0) / (1024 * 1024); // 空闲内存 (MB)
					const memoryWasted = (this.opcache.memory_usage?.wasted_memory || 0) / (1024 * 1024); // 浪费内存 (MB)

					if (this.charts.opcacheMemory) this.charts.opcacheMemory.destroy();
					this.charts.opcacheMemory = new Chart(ctx3, {
						type: 'doughnut',
						data: {
							labels: [
								`已使用`,
								`空闲`,
								`浪费`
							],
							datasets: [{
								label: '内存 (MB)',
								data: [memoryUsed, memoryFree, memoryWasted],
								backgroundColor: ['#ff851b', '#23d160', '#f14668']
							}]
						},
						options: {
							plugins: {
								title: {
									display: true,
									text: 'OPcache 内存占用 (MB)'
								}
							},
						}
					});

					// Redis 内存使用图表
					const ctx2 = document.getElementById('redisChart');
					const used = (this.redis.info?.used_memory || 0) / (1024 * 1024); // Redis 已使用内存 (MB)
					const max = (this.redis.maxmemory || 0) / (1024 * 1024); // Redis 最大内存 (MB)
					const free = (max > 0) ? Math.max(0, max - used) : 0; // Redis 剩余内存 (MB)

					if (this.charts.redis) this.charts.redis.destroy();
					this.charts.redis = new Chart(ctx2, {
						type: 'doughnut',
						data: {
							labels: [
								`已使用: ${used.toFixed(2)} MB`,
								`剩余: ${free.toFixed(2)} MB`
							],
							datasets: [{
								label: '内存 (MB)',
								data: [used, free],
								backgroundColor: ['#ff851b', '#23d160']
							}]
						},
						options: {
							plugins: {
								title: {
									display: true,
									text: max ? `Redis 限制: ${max.toFixed(0)} MB` : 'Redis 未设置内存限制'
								}
							},
						}
					});
				},
				renderDiskChart() {
					const ctx = document.getElementById('diskChart');
					const { used, free, total } = this.system.disk;
					const label = `磁盘总容量：${(total).toFixed(2)} GB`;

					if (this.charts.disk) this.charts.disk.destroy();
					this.charts.disk = new Chart(ctx, {
						type: 'doughnut',
						data: {
							labels: ['已用', '剩余'],
							datasets: [{
								label: '磁盘 (GB)',
								data: [used, free],
								backgroundColor: ['#ff3860', '#00d1b2']
							}]
						},
						options: {
							plugins: {
								title: {
									display: true,
									text: label
								}
							}
						}
					});
				},
				renderCPUChart() {
					const ctx = document.getElementById('cpuChart');
					const { usage_percent } = this.system.cpu;
					const idle = 100 - usage_percent;

					if (this.charts.cpu) this.charts.cpu.destroy();
					this.charts.cpu = new Chart(ctx, {
						type: 'doughnut',
						data: {
							labels: ['使用率', '空闲'],
							datasets: [{
								data: [usage_percent, idle],
								backgroundColor: ['#3273dc', '#dbdbdb']
							}]
						},
						options: {
							plugins: {
								title: {
									display: true,
									text: `CPU 当前使用率：${usage_percent}%`
								}
							}
						}
					});
				},
				renderMemChart() {
					const mem = this.system.mem;
					const ctx = document.getElementById('memChart');
					if (this.charts.mem) this.charts.mem.destroy();
					this.charts.mem = new Chart(ctx, {
						type: 'doughnut',
						data: {
							labels: ['Used', 'Free', 'Cached', 'Buffers'],
							datasets: [{
								label: '内存 (GB)',
								data: [
									mem.used_gb,
									mem.free_gb,
									mem.cached_gb,
									mem.buffers_gb
								],
								backgroundColor: ['#f14668', '#48c774', '#209cee', '#ffdd57'],
							}]
						},
						options: {
							plugins: {
								title: {
									display: true,
									text: `Total Memory: ${mem.total_gb} GB`,
									font: { size: 18 }
								},
								legend: { position: 'bottom' }
							}
						}
					});
				},
				async confirmClear(type) {
					if (!confirm(`确定要清空 ${type.toUpperCase()} 吗？`)) {
						return;
					}
					let res = await wnd_ajax_action("admin/wnd_update_system_settings", { type });
					if (res.status > 0) {
						this.fetchData();
					} else {
						alert(res.msg);
					}
				}
			},
			async mounted() {
				this.fetchData();
				if ('undefined' == typeof Chart) {
					let url = static_path + 'js/lib/chart.umd.js' + cache_suffix;
					await wnd_load_script(url);
				}
			},
			unmounted() {
				// 自动销毁所有图表实例
				for (const key in this.charts) {
					if (this.charts[key]) {
						this.charts[key].destroy();
						this.charts[key] = null;
					}
				}
			},
			updated() {
				this.$nextTick(() => {
					funTransitionHeight(parent, trs_time);
				});
			}
		});
		app.mount('#sys-monitor-app');
		vueInstances.push(app);
	}
</script>