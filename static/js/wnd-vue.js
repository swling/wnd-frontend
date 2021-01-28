/**
 *定义API接口
 *
 */
var lang_query = wnd.lang ? '?lang=' + wnd.lang : '';
var wnd_module_api = wnd.rest_url + wnd.module_api;
var wnd_action_api = wnd.rest_url + wnd.action_api;
var wnd_posts_api = wnd.rest_url + wnd.posts_api;
var wnd_users_api = wnd.rest_url + wnd.users_api;
var wnd_jsonget_api = wnd.rest_url + wnd.jsonget_api;
var wnd_endpoint_api = wnd.rest_url + wnd.endpoint_api;

// 根据节点选择器删除
function wnd_remove(el) {
	// 拿到待删除节点:
	var self = document.querySelector(el);
	// 拿到父节点:
	var parent = self.parentElement;
	// 删除:
	var removed = parent.removeChild(self);
	removed === self; // true	
}

// 替换
function wnd_replace(el, html) {
	var self = document.querySelector(el);
	self.innerHTML = html;
}

// 追加
function wnd_append(el, html) {
	var self = document.querySelector(el);
	if (self) {
		self.insertAdjacentHTML('beforeend', html);
	}
}

// 网络请求
// 添加请求拦截器
axios.interceptors.request.use((config) => {
	// console.log("加载前");
	// const token = localStorage.getItem('token')
	// config.headers.Authorization = "Bearer " + token
	config.headers['X-WP-Nonce'] = wnd.rest_nonce;

	return config;
}, function (error) {
	// 对请求错误做些什么
	return Promise.reject(error);
});

// // 添加响应拦截器
axios.interceptors.response.use((response) => {
	// 对响应数据做点什么
	// console.log("加载后");

	return response;
}, function (error) {
	// 对响应错误做点什么
	return Promise.reject(error);
});

/**
 *@since 0.9.25
 *Vue 根据 Json 动态渲染表单
 */
function wnd_render_form(container, form_json) {
	// 混入对象（有点类似 PHP Class 中的特性）
	var wnd_input_field_mixin = {
		props: ['attr', 'value', "index"],
		template: '<input v-bind="attr" v-model="value" @input="input(value)"/>',
		methods: {
			input: function (value) {
				this.$emit('update:wnd_value', value);
			}
		}
	}

	// 常规 input 组件
	Vue.component('wnd-text', {
		mixins: [wnd_input_field_mixin]
	});

	// 密码 组件
	Vue.component('wnd-password', {
		mixins: [wnd_input_field_mixin]
	});

	// Hidden 组件
	Vue.component('wnd-hidden', {
		mixins: [wnd_input_field_mixin]
	});

	// Html 组件
	Vue.component('wnd-html', {
		props: ['value'],
		template: '<div v-html=" value"></div>',
	});

	// Radio 组件
	Vue.component('wnd-radio', {
		props: ['value'],
		template: '<div v-html=" value"></div>',
	});

	// 定义 Form 输出模板
	var form_template = '<div class="vue-form">';

	// 表单头
	form_template += '<form v-bind="form.attrs">';
	form_template += '<h3 v-bind="form.title.attrs" v-html="form.title.title"></h3>';
	form_template += '<div v-bind="form.message.attrs" v-show="form.message.message"><div class="message-body" v-html="form.message.message"></div></div>';

	// 循环字段
	form_template += '<template v-for="(field, index) in form.fields" :key="index">';
	form_template += '<div :class="get_input_field_class(field)">';
	// label
	form_template += '<label v-html="field.label" class="label"></label>';

	form_template += '<div  v-if="field.addon_left" class="control" v-html="field.addon_left"></div>';

	form_template += '<div :class="get_input_control_class(field)">';
	form_template += '<component :is="\'wnd-\' + field.type" :attr="parse_input_attr(field)" :value="field.value" :wnd_value.sync="field.value"/>'
	form_template += '<span v-if="field.icon_left" class="icon is-left"  v-html="field.icon_left"></span>';
	form_template += '<span v-if="field.icon_right"  class="icon is-right" v-html="field.icon_right"></span>';
	form_template += '</div>';

	form_template += '<div  v-if="field.addon_right" class="control" v-html="field.addon_right"></div>';

	form_template += '</div>';
	form_template += '</template>';

	// 提交按钮
	form_template += '<div class="field is-grouped is-grouped-centered">';
	form_template += '<button v-bind="form.submit.attrs" @click="submit" v-text="form.submit.text"></button>';
	form_template += '</div>';

	// 表尾
	form_template += '</form>';

	// loading 遮罩
	form_template += '</div>';

	new Vue({
		el: container,
		// 模板将替换挂载点内的内容
		template: form_template,
		// 数据
		data: {
			form: Object.assign({
				attrs: [],
				title: [],
				message: [],
				fields: [],
				submit: [],
			}, form_json),
		},

		// 事件处理
		methods: {
			has_input_addon: function (field) {
				return (field.addon_left || field.addon_right);
			},

			has_input_icon: function (field) {
				return field.icon_left || field.icon_right;
			},

			get_input_field_class: function (field) {
				return this.has_input_addon(field) ? 'field has-addons' : 'field';
			},

			get_input_control_class: function (field) {
				var el_class = 'control';
				el_class += this.has_input_addon(field) ? ' is-expanded' : '';
				el_class += field.icon_left ? ' has-icons-left' : '';
				el_class += field.icon_right ? ' has-icons-right' : '';
				return el_class;
			},

			parse_input_attr: function (field) {
				let _field = JSON.parse(JSON.stringify(field));

				Object.keys(_field).forEach(item => {
					if (!_field[item] || '' == _field[item]) {
						delete _field[item]
					};
				});

				_field["class"] = "input " + (_field["class"] || '');
				delete _field['icon_left'];
				delete _field['icon_right'];
				delete _field['addon_left'];
				delete _field['addon_right'];
				// delete _field['value'];

				return _field;
			},

			// 提交
			submit: function () {
				// 按钮
				var submit_class = this.form.submit.attrs.class;
				this.form.submit.attrs.class = submit_class + " is-loading";

				// 表单检查
				var can_submit = true;
				this.form.fields.forEach(function (filed, index) {
					if (filed.required && !filed.value) {
						filed.class = filed.class + " is-danger";
						can_submit = false;
					};

					return false;
				});

				if (!can_submit) {
					this.form.submit.attrs.class = submit_class
					return false;
				}

				// Ajax 请求
				var _this = this;
				var form = document.querySelector('#' + this.form.attrs.id);
				var data = new FormData(form);
				axios.post(this.form.attrs.action, data)
					.then(function (response) {
						_this.form.message.message = response.data.msg;
						_this.form.message.attrs.class = (response.data.status <= 0) ? _this.form.message.attrs.class + ' is-danger' : _this.form.message.attrs.class;
						_this.form.submit.attrs.class = submit_class;

					})
					.catch(function (error) { // 请求失败处理
						console.log(error);
					});
			}
		},
		// 计算
		computed: {},
		// 侦听器
		watch: {},
	});
}

/**
 *@since 2019.1.10  从后端请求ajax内容并填充到指定DOM
 *原理同 wnd_ajax_modal()，区别为，响应方式为嵌入
 *@param 	container 	srting 		指定嵌入的容器选择器
 *@param 	module 		string 		module类名称
 *@param 	param 		json 		传参
 *@param callback 	回调函数
 **/
function wnd_ajax_embed(container, module, param = {}, callback = '') {
	// 网络请求
	// 添加请求拦截器
	axios.interceptors.request.use((config) => {

		/**  
		 * 
		 *beforebegin 在元素之前
		 *afterbegin 在元素的第一个子元素之后
		 *beforeend 在元素的最后一个子元素之后
		 *afterend 在元素之后 
		 */
		var el = document.querySelector(container);
		if (el) {
			el.insertAdjacentHTML('afterbegin', '<div class="spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div></div>');
		}

		return config;
	}, function (error) {
		// 对请求错误做些什么
		return Promise.reject(error);
	});

	// // 添加响应拦截器
	axios.interceptors.response.use((response) => {
		// 对响应数据做点什么
		// console.log("加载后");

		return response;
	}, function (error) {
		// 对响应错误做点什么
		return Promise.reject(error);
	});

	axios.get(wnd_module_api + "/" + module + lang_query, Object.assign({
		"ajax_type": "modal"
	}, param))
		.then(function (response) {
			if ('form' == response.data.type) {
				return wnd_render_form(container, response.data.data);
			} else {
				wnd_replace(container, response.data.data);
			}
		})
		.catch(function (error) { // 请求失败处理
			console.log(error);
		});

}


/**
 * @since 2019.1
*######################## ajax动态内容请求
*使用本函数主要用以返回更复杂的结果如：表单，页面以弹窗或嵌入指定DOM的形式呈现，以供用户操作。表单提交则通常返回较为简单的结果
	允许携带一个参数

	@since 2019.01.26
	若需要传递参数值超过一个，可将参数定义为GET参数形式如："post_id=1&user_id=2"，后端采用:wp_parse_args() 解析参数

	实例：
		前端
		wnd_ajax_modal("wnd_xxx","post_id=1&user_id=2");

		后端
		namespace Wnd\Module;
		class wnd_xxx($args){
			$args = wp_parse_args($args)
			return($args);
		}
		弹窗将输出
		Array ( [post_id] => 1 [user_id] =>2)

*典型用途：  击弹出登录框、点击弹出建议发布文章框
*@param     module      string      module类
*@param     param       json        传参
*@param     callback    回调函数
*/
// ajax 从后端请求内容，并以弹窗形式展现
function wnd_ajax_modal(module, param = {}, callback = '') {
	html =
		'<div id="modal" class="modal is-active">' +
		'<div class="modal-background"></div>' +
		'<div class="modal-content">' +
		'<div class="modal-entry content box"><div class="vue-app"></div></div>' +
		'</div>' +
		'<button class="modal-close is-large" aria-label="close"></button>' +
		'</div>';
	wnd_append("body", html);
	wnd_is_loading('body .vue-app');

	axios.get(wnd_module_api + "/" + module + lang_query, +module, Object.assign({
		"ajax_type": "modal"
	}, param))
		.then(function (response) {
			if ('form' == response.data.type) {
				wnd_render_form("#modal .vue-app", response.data.data);
			} else {
				wnd_replace("#modal .modal-entry", response.data.data);
			}
		})
		.catch(function (error) { // 请求失败处理
			console.log(error);
		});
}

function wnd_is_loading(el) {
	/**  
	 * 
	 *beforebegin 在元素之前
	 *afterbegin 在元素的第一个子元素之后
	 *beforeend 在元素的最后一个子元素之后
	 *afterend 在元素之后 
	 */
	var el = document.querySelector(el);
	if (el) {
		el.insertAdjacentHTML('afterbegin', '<div class="spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div></div>');
	}
}

//弹出bulma对话框
function wnd_alert_modal(html, is_gallery = false) {
	wnd_reset_modal();
	if (is_gallery) {
		$("#modal.modal").addClass("is-active wnd-gallery");
	} else {
		$("#modal.modal").addClass("is-active");
		$("#modal .modal-entry").addClass("box");
	}
	$("#modal .modal-entry").html(html);
}

// 直接弹出消息
function wnd_alert_msg(msg, time = 0) {
	wnd_reset_modal();
	$("#modal.modal").addClass("is-active");
	$("#modal .modal-entry").removeClass("box");
	$("#modal .modal-entry").html('<div class="alert-message has-text-white has-text-centered">' + msg + '</div>');
	// 定时关闭
	if (time > 0) {
		var timer = null;
		timer = setInterval(function () {
			clearInterval(timer);
			wnd_reset_modal();
		}, time * 1000);
	}
}