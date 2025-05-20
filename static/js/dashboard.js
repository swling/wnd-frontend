// 子组件：地址编辑器
const TextEditor = {
	name: 'TextEditor',
	props: {
		pre_editing: false,
		modelValue: {
			type: String,
			default: ''
		}
	},
	emits: ['update:modelValue'],
	data() {
		return {
			editing: this.pre_editing
		};
	},
	mounted() {
		this.injectStyles();
	},
	methods: {
		enableEdit() {
			this.editing = true;
			this.$nextTick(() => {
				this.$refs.input.focus();
			});
		},
		finishEdit(e) {
			if (e.target.value) {
				this.editing = false;
			}
		},
		onInput(e) {
			this.$emit('update:modelValue', e.target.value);
		},
		injectStyles() {
			const style = document.createElement('style');
			style.textContent = `
.text-editor {display: flex;align-items: center;gap: 0.5rem;}
.text-display {font-weight:800}
.editing {flex-grow:1;}
.underline-input {border: none;border-bottom: 1px solid #333;padding: 0.25em 0;background: transparent;width: 100%;outline: none;font-size: 1rem;}
.underline-input:focus {border-bottom: 2px solid #3273dc;}
`;
			document.head.appendChild(style);
		}
	},
	template: `
<div class="text-editor">
  <div class="text-display" :class="{'editing':editing}">
      <span v-show="!editing">{{ modelValue }}</span>
      <input v-show="editing" ref="input" :value="modelValue" @input="onInput" @blur="finishEdit" @keydown.enter="finishEdit" class="underline-input" type="text"/>
  </div>
  <span v-show="!editing" class="icon is-clickable" @click="enableEdit">
    <i class="fas fa-edit"></i>
  </span>
</div>
`
};

// 订单物流管理组件
// 注意：绑定方式必须是 array[index] 形式 才能双向绑定：<Order-Ship-Manager v-model:order="orders[index]"></Order-Ship-Manager>
const OrderShipManager = {
	props: {
		order: {
			type: Object,
			required: true
		}
	},
	data() {
		return {
			loading: false
		}
	},
	computed: {
		localOrder: {
			get() {
				return this.order;
			},
			set(val) {
				this.$emit('update:order', val);
			}
		},
		express_no: {
			get() {
				return this.localOrder.props.express_no || '';
			},
			set(val) {
				// const props = { ...this.localOrder.props, express_no: val };
				// this.localOrder = { ...this.localOrder, props };
				this.localOrder.props.express_no = val;
			}
		},
		isShipped() {
			return this.localOrder.status === 'shipped';
		}
	},
	methods: {
		async shipOrder() {
			if (!this.express_no.trim()) {
				alert('请输入物流单号');
				return;
			}
			if (!confirm("确认发货？")) {
				return;
			}

			this.loading = true;
			const data = {
				"id": this.localOrder.ID,
				"status": "shipped",
				"express_no": this.express_no
			};
			const res = await wnd_ajax_action("admin/wnd_update_transaction", data);
			this.loading = false;
			if (res.status > 0) {
				this.localOrder = { ...this.localOrder, ...data };
			}
		},
		async undoShipping() {
			if (!confirm("确认撤销发货？")) {
				return;
			}

			this.loading = true;
			this.express_no = "";
			const data = {
				"id": this.localOrder.ID,
				"status": "paid",
				"express_no": this.express_no
			};
			const res = await wnd_ajax_action("admin/wnd_update_transaction", data);
			this.loading = false;
			if (res.status > 0) {
				this.localOrder = { ...this.localOrder, ...data };
			}
		}
	},
	template: `
<div class="box">
	<p v-if="order.props.receiver">姓名：{{order.props.receiver.name}} 电话：{{order.props.receiver.phone}} 地址：{{order.props.receiver.address}}</p>
	<div class="field is-grouped mt-3">
		<div class="control is-expanded">
			<input class="input" type="text" placeholder="输入物流单号" v-model="express_no" :disabled="isShipped || loading" @keydown.enter="shipOrder">
		</div>
		<div class="control">
			<button class="button is-primary" @click="shipOrder" :disabled="isShipped || loading">
				<span class="icon"><i class="fas fa-truck"></i></span>
				<span v-if="!loading">发货</span>
				<span v-else>提交中...</span>
			</button>
		</div>
		<div class="control" v-if="isShipped">
			<button class="button is-warning" @click="undoShipping" :disabled="loading">
				<span class="icon"><i class="fas fa-undo"></i></span>
				<span v-if="!loading">撤销发货</span>
				<span v-else>处理中...</span>
			</button>
		</div>
	</div>
</div>
`
};

/**
 * const custom = new MyPageEditor("#app-post-form");
 * const vueComponent = custom.toVueComponent();
 * const app = Vue.createApp(vueComponent);
 * 
 * 监听 hash 变动，并将其转为 Vue  data 中的 this.param
 * wach param 变动，并触发 Vue 中的 this.query() 方法
 * methods 方法中的 this 指向 vue 实例；data 中的 this 指向 class
 */
class Filter extends VueClass {

	// dom APP 挂载点的父节点（动态调整高度需要）
	parent_node = null;

	// string 组件模板：留空则使用挂载点内部 dom
	template = ``;

	// 初始参数
	init_param = {};

	constructor(container) {
		super(container);
		this.parent_node = document.querySelector(container).parentNode;
		// 初始参数
		this.init_param = Object.assign({
			number: 20,
			paged: 1,
		}, module_data.param);
	}

	// 定义 vue 数据
	data() {
		return {
			init_param: this.init_param,
			param: structuredClone(this.init_param),
			parent_node: this.parent_node,
		}
	}

	// 生命周期钩子
	created() { }
	components() { }
	computed() { }

	mounted() {
		this.query();
		window.addEventListener("hashchange", this.parseHash);
	}

	beforeUnmount() {
		console.log("触发销毁：vue class");
		window.removeEventListener("hashchange", this.parseHash);
	}

	watch() {
		return {
			param: {
				handler(newVal, oldVal) {
					this.query();
				},
				deep: true // 启用深度监听
			}
		};
	}
	updated() {
		this.$nextTick(() => {
			funTransitionHeight(this.parent_node, trs_time);
		});
	}

	// 原始方法（业务逻辑）这里的 this 为 Vue 实例。将 methods 中的方法抽离出来的原因是，方便子类针对性重写
	parseHash() {
		const hash = window.location.hash.slice(1);
		const params = new URLSearchParams(hash);
		const obj = {};
		for (const [key, value] of params.entries()) {
			obj[key] = value;
		}
		delete obj.module;

		this.param = Object.keys(obj).length ? obj : this.init_param;
	}

	update_filter(key, value, remove_args = []) {
		const param = JSON.parse(JSON.stringify(this.param));
		if (value) {
			param[key] = value;
		} else {
			delete param[key];
		}
		if (remove_args) {
			remove_args.forEach((key) => {
				delete param[key];
			});
		}
		// 非 翻页的其他查询，则重置页面
		if ("paged" != key && param.paged) {
			param.paged = "1";
		}
		// 参数重复时，无法触发 hash 变化，但仍然应该响应用户的点击（此时：点击 = 刷新）
		if (JSON.stringify(param) === JSON.stringify(this.param)) {
			return this.query();
		}
		wnd_update_url_hash(param, ['ajax_type']);
	}

	is_active(key, value) {
		if (!this.param[key]) {
			return 'any' == value ? "is-active" : "";
		}
		return this.param[key] == value ? "is-active" : "";
	}

	async query() { }
}


/**
 *@since 0.9.88
 *通过参数更新 url hash
 */
function wnd_update_url_hash(obj, ignore = []) {
	// 解析当前 hash（去掉 #，转为 URLSearchParams）
	const currentParams = new URLSearchParams(location.hash.slice(1));

	// 将 obj 的 key-value 合并进去
	for (const key in obj) {
		if (ignore.length && ignore.includes(key)) {
			continue;
		}

		if (obj[key] === null || obj[key] === undefined) {
			currentParams.delete(key); // 可选逻辑：允许通过 null/undefined 删除字段
		} else {
			currentParams.set(key, obj[key]);
		}
	}

	// 构建新的 hash 字符串
	window.location.hash = decodeURIComponent(currentParams.toString());
}

function wnd_time_to_date(timestamp) {
	// 创建一个新的Date对象，使用时间戳作为参数（以毫秒为单位） 
	const date = new Date(timestamp * 1000);
	const year = date.getFullYear();
	const month = (date.getMonth() + 1).toString().padStart(2, '0');
	const day = date.getDate().toString().padStart(2, '0');
	const hours = date.getHours().toString().padStart(2, '0');
	const minutes = date.getMinutes().toString().padStart(2, '0');
	const seconds = date.getSeconds().toString().padStart(2, '0');

	let now = new Date();
	if (date.getFullYear() === now.getFullYear()) {
		return `${month}-${day} ${hours}:${minutes}:${seconds}`;
	} else {
		return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
	}
}
function wnd_time_to_string(timestamp) {
	let date = new Date(timestamp * 1000),
		now = new Date(), diff = now - date, minutes = Math.floor(diff / 60000), hours = Math.floor(minutes / 60), days = Math.floor(hours / 24);

	if (date.getFullYear() === now.getFullYear() && date.getMonth() === now.getMonth() && date.getDate() === now.getDate()) {
		if (hours === 0) {
			return minutes + " minutes ago";
		} else if (hours < 24) {
			return hours + " hours ago";
		} else {
			return date.toDateString();
		}
	} else {
		return wnd_time_to_date(timestamp);
	}
}