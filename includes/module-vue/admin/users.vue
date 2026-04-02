<style>
	.item.box {
		border-left: 3px solid #ffe08a;
	}

	.item.is-today.box {
		border-left: 3px solid #f14668;
	}

	.item.is-today time {
		color: #f14668;
	}

	.item.is-yesterday.box {
		border-left: 3px solid #48c78e;
	}

	.item.is-yesterday time {
		color: #48c78e;
	}
</style>
<div id="vue-users-app">
	<div class="wnd-filter-tabs mb-3">
		<div class="field has-addons has-addons-right mb-0">
			<div class="control is-expanded">
				<input class="input is-small" type="text" v-model.lazy="param.s" placeholder="Search" @keyup.enter="query()">
			</div>
			<div class="control"><button class="button is-small" @click="query()"><i class="fas fa-search"></i></button></div>
		</div>
		<div v-for="tab in tabs" class="columns is-marginless is-vcentered is-mobile is-size-7">
			<div class="column is-narrow">{{tab.label}}</div>
			<div class="column tabs">
				<ul class="tab is-size-7">
					<li v-for="(value, key) in tab.options" :class="is_active(tab.key, value)">
						<a @click="update_filter(tab.key, value)">{{key}}</a>
					</li>
				</ul>
			</div>
		</div>
	</div>
	<div id="lists">
		<div v-for="(item, index) in data.results" class="box item mb-5 user-card" :class="getItemClass(item)">
			<!-- 第一行 -->
			<div class="is-flex is-align-items-center is-justify-content-space-between pb-1" style="border-bottom: 1px solid #EEE;">
				<!-- 左：用户 -->
				<div class="is-flex is-align-items-center" style="min-width:0;">
					<figure v-if="item.avatar" class="mr-2" style="flex-shrink:0;">
						<p class="image is-40x40">
							<img :src="item.avatar" class="is-rounded">
						</p>
					</figure>
					<div style="min-width:0;">
						<div class="has-text-weight-semibold" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
							{{ item.data.display_name || 'Unknown User' }}
						</div>
						<div class="is-size-7 has-text-grey">
							<time>{{ item.data.user_registered }}</time>
						</div>
					</div>
				</div>
				<!-- 右：余额 -->
				<div style="flex-shrink:0;">
					<span class="tag is-success is-light">
						<i class="fas fa-wallet mr-1"></i> {{ formatBalance(item.data.wnd_user.balance) }}</span>
				</div>
			</div>
			<!-- 第二行：信息 -->
			<div class="is-size-7 has-text-grey mt-1 is-flex is-flex-wrap-wrap">
				<span class="mr-3">
					<i class="fas fa-clock mr-1"></i> {{ formatTime(item.data.wnd_user.last_login, 'Never') }}
				</span>
				<span class="mr-3">
					<i class="fas fa-sign-in-alt mr-1"></i> {{ formatCount(item.data.wnd_user.login_count) }} logins
				</span>
				<span class="mr-3">
					<i class="fas fa-network-wired mr-1"></i> {{ formatIP(item.data.wnd_user.client_ip) }}
				</span>
			</div>

			<!-- 第三行：操作 -->
			<div class="mt-1 is-flex is-justify-content-space-between">
				<div class="is-size-7 mr-1">
					<a @click="get_user_info(item.ID)"><b>ID: {{ item.ID }}</b></a>
					<!-- &nbsp;<i class="fas fa-user-plus"></i>&nbsp;{{ item.data.user_registered }} -->
					<!-- &nbsp;<i class="fas fa-envelope mr-1"></i>&nbsp;{{ item.data.user_email || `……` }} -->
				</div>
				<div class="tags">
					<span class="tag is-info is-light is-clickable mr-1" @click="update_user_status(item.ID)">
						<i class="fas fa-cog"></i>
					</span>
					<span class="tag is-danger is-light is-clickable" @click="delete_user(item.ID, index)">
						<i class="fas fa-trash-alt"></i>
					</span>
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
					data: { "results": [] },
					tabs: module_data.tabs,
				}

				const base = super.data();
				return { ...base, ...data };
			}
			async query() {
				let res = await wnd_query("wnd_users", this.param, {
					"Container": "#lists"
				});

				wnd_loading("#lists", true);
				this.data = res.data;
			}
			async get_user_info(user_id) {
				const res = await wnd_query("wnd_get_profile", { "user_id": user_id });
				if (res.status <= 0) {
					alert(res.msg);
					return;
				}
				let html = '<table class="table is-bordered is-striped is-fullwidth"><tbody>';
				for (const key in res.data) {
					if (res.data.hasOwnProperty(key)) {
						if ("avatar_url" == key) {
							continue;
						}
						html += `<tr><th>${key}</th><td>${res.data[key]}</td></tr>`;
					}
				}
				html += '</tbody></table>'
				wnd_alert_modal(html);
			}
			delete_user(user_id) {
				wnd_ajax_modal("admin/wnd_delete_user_form", { "user_id": user_id });
			}
			update_user_status(user_id) {
				wnd_ajax_modal("admin/wnd_account_status_form", { "user_id": user_id });
			}
			formatTime(val, fallback = '-') {
				if (!val) return fallback;
				return this.timeToString(val);
			}
			formatCount(val) {
				// ⚠️ 0 是合法值
				return val === 0 ? 0 : (val || 0);
			}
			formatIP(val) {
				if (!val) return 'N/A';
				return val;
			}
			formatBalance(val) {
				if (val === 0 || !val) return '0.00';
				return val;
			}
			getItemClass(item) {
				const date = new Date(item.post_date);
				const now = new Date();

				const isSameDate = (d1, d2) =>
					d1.getFullYear() === d2.getFullYear() &&
					d1.getMonth() === d2.getMonth() &&
					d1.getDate() === d2.getDate();

				const yesterday = new Date();
				yesterday.setDate(now.getDate() - 1);

				if (isSameDate(date, now)) {
					return 'is-today';
				} else if (isSameDate(date, yesterday)) {
					return 'is-yesterday';
				} else {
					return '';
				}
			}
		}

		const container = "#vue-users-app";
		const custom = new App_Filter(container);
		const vueComponent = custom.toVueComponent();
		const app = Vue.createApp(vueComponent);
		app.mount(container);
		vueInstances.push(app);
	}
</script>