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
<div id="vue-posts-app">
	<div class="wnd-filter-tabs mb-3">
		<!-- 搜索 -->
		<div class="field has-addons has-addons-right mb-2">
			<div class="control is-expanded">
				<input class="input is-small" type="text" v-model.lazy="param.search" placeholder="Search" @keyup.enter="query()">
			</div>
			<div class="control">
				<button class="button is-small" @click="query()">
					<i class="fas fa-search"></i>
				</button>
			</div>
		</div>
		<div v-for="tab in tabs" :key="tab.key" class="columns is-marginless is-vcentered is-mobile is-size-7">
			<div class="column is-narrow">{{tab.label}}</div>
			<div class="column tabs">
				<ul class="tab is-size-7">
					<li v-for="(value, key) in tab.options" :key="key" :class="is_active(tab.key, value)">
						<a @click="update_filter(tab.key, value)">{{key}}</a>
					</li>
				</ul>
			</div>
		</div>
	</div>
	<div id="posts-list">
		<div v-for="(item, index) in data.results" class="box mb-5 post-card item" :class="getItemClass(item)">
			<!-- 第一行：缩略图 + 标题 + 状态 -->
			<div class="is-flex is-align-items-center is-justify-content-space-between pb-2" style="border-bottom: 1px solid #EEE;">
				<div class="is-flex is-align-items-center" style="min-width:0;">
					<figure v-if="item.thumbnail" class="mr-2" style="flex-shrink:0;">
						<p class="image is-48x48">
							<img :src="item.thumbnail" style="object-fit:cover;">
						</p>
					</figure>
					<div v-else class="mr-2 has-background-light has-text-grey is-flex is-align-items-center is-justify-content-center" style="width:48px;height:48px;flex-shrink:0;">
						<i class="fas fa-image"></i>
					</div>
					<div style="min-width:0;">
						<div style="word-break:break-word; overflow-wrap:break-word;">
							<a :href="item.link" target="_blank">{{ item.post_title }}</a>
						</div>
						<div class="is-size-7 has-text-grey">
							<span class="mr-1">
								<time><i class="fas fa-calendar-check"></i></i> {{ item.post_date }}</time>
							</span>
						</div>
					</div>
				</div>
			</div>
			<!-- 第二行：meta 信息 -->
			<div class="is-size-7 has-text-grey mt-2 is-flex is-flex-wrap-wrap">
				<span class="tag is-light mr-1 mb-1" :class="statusClass(item.post_status)">
					{{ item.post_status }}
				</span>
				<span v-for="term in flatTerms(item.terms)" :key="term.id" class="tag is-light mr-1 mb-1" :class="termClass(term.tax)">
					<i class="fas fa-tag mr-1"></i> {{ term.name }}
				</span>
			</div>
			<!-- 第三行：操作按钮 -->
			<div class="mt-1 is-flex is-justify-content-space-between">
				<div class="is-size-7 mr-1">
					<span class="mr-3">
						<i class="fas fa-hashtag"></i> {{ item.ID ?? '-' }}
					</span>
					<span class="mr-3">
						<i class="fas fa-user"></i> {{ item.post_author}}
					</span>
				</div>
				<div class="tags">
					<span class="tag is-info is-light is-clickable mr-1" @click="ajax_modal('post/wnd_post_status_form', { post_id: item.ID })">
						<i class="fas fa-cog"></i>
					</span>
					<a class="tag is-light is-small mr-1" @click="ajax_modal('post/wnd_post_detail', { post_id: item.ID })">
						<i class="fas fa-info-circle"></i>
					</a>
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
					data: { results: [], number: 0 },
					tabs: module_data.tabs,
				};

				const base = super.data();
				return { ...base, ...data };
			}

			// 查询
			async query() {
				let res = await wnd_query("wnd_posts", this.param, {
					Container: "#posts-list"
				});
				wnd_loading("#posts-list", true);

				this.data = res.data || { results: [], number: 0 };
			}
			ajax_modal(module, params) {
				wnd_ajax_modal(module, params);
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
			// 状态样式
			statusClass(status) {
				return {
					'is-success': status === 'publish',
					'is-warning': status === 'draft',
					'is-danger': status === 'trash',
					'is-info': status === 'pending',
					'is-light': !status
				};
			}
			// 是否有 terms
			hasTerms(terms) {
				if (!terms) return false;
				return Object.keys(terms).some(
					key => Array.isArray(terms[key]) && terms[key].length
				);
			}
			// 扁平化 taxonomy
			flatTerms(terms) {
				if (!terms) return [];
				let result = [];
				Object.keys(terms).forEach(tax => {
					if (Array.isArray(terms[tax])) {
						terms[tax].forEach(t => {
							if (t && t.name) {
								result.push({ ...t, tax });
							}
						});
					}
				});

				return result;
			}
			// taxonomy 颜色
			termClass(tax) {
				return {
					'is-info': tax === 'category',
					'is-warning': tax === 'post_tag'
				};
			}
		}

		const container = "#vue-posts-app";
		const custom = new App_Filter(container);
		const vueComponent = custom.toVueComponent();
		const app = Vue.createApp(vueComponent);
		app.mount(container);
		vueInstances.push(app);
	}
</script>