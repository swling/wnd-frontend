/**
 *定义API接口
 *
 */
var wnd_module_api = wnd.rest_url + wnd.module_api;
var wnd_action_api = wnd.rest_url + wnd.action_api;
var wnd_posts_api = wnd.rest_url + wnd.posts_api;
var wnd_users_api = wnd.rest_url + wnd.users_api;
var wnd_jsonget_api = wnd.rest_url + wnd.jsonget_api;
var wnd_endpoint_api = wnd.rest_url + wnd.endpoint_api;

// 当前 JS 文件所在 URL 路径
var this_src = document.currentScript.src;
var static_path = this_src.substring(0, this_src.lastIndexOf('/js/') + 1);

// jsdeliver CDN 无效添加 suffix
let cache_suffix = static_path.includes('//cdn.jsdelivr.net/gh') ? '' : '?ver=' + wnd.ver;

// 其他
var trs_time = 160;

// 定义菜单
var menus_side = false;

// 加载中 Element
if ('undefined' == typeof loading_el) {
    var loading_el = `<div class="spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div></div>`;
}

// Axios 全局请求参数
// axios.defaults.headers.Authorization = "Bearer " + token
axios.defaults.withCredentials = true; // 跨域请求允许携带cookie
if (!wnd.disable_rest_nonce || wnd.is_admin) {
    axios.defaults.headers['X-WP-Nonce'] = wnd.rest_nonce;
}
// axios.defaults.headers['X-Requested-With'] = 'XMLHttpRequest';
if (wnd.lang) {
    axios.defaults.params = {}
    axios.defaults.params['lang'] = wnd.lang;
}

/**
 *axios 拦截器 统一设置 WP Rest Nonce
 *@link https://github.com/axios/axios#interceptors
 * - 如果设置了 header:container 则统一设置“loading”效果
 * - 响应数据时，不设置统一清除“loading”效果，原因在于：获取响应数据后，可能需要动态加载 JavaScript 渲染当前数据，这需要耗时。
 *   因此应该在对响应数据进行渲染的具体方法中，设置清除“loading”效果
 */
axios.interceptors.request.use(function(config) {
    if (config.headers.Container || false) {
        wnd_loading(config.headers.Container);
    }
    return config;
});

//判断是否为移动端
function wnd_is_mobile() {
    if (navigator.userAgent.match(/(iPhone|iPad|iPod|Android|ios|App\/)/i)) {
        return true;
    } else {
        return false;
    }
}

/**
 *@since 2019.02.14 搜索引擎爬虫
 */
function wnd_is_spider() {
    let userAgent = navigator.userAgent;
    // 蜘蛛判断
    if (userAgent.match(/(Googlebot|Baiduspider|spider)/i)) {
        return true;
    } else {
        return false;
    }
}

// 根据节点选择器删除
function wnd_remove(el) {
    let self = ('object' == typeof el) ? el : document.querySelector(el);
    if (self) {
        self.outerHTML = '';
    }
}

/**
 *插入 HTML  支持运行 JavaScript
 * */
function wnd_inner_html(el, html) {
    let self = ('object' == typeof el) ? el : document.querySelector(el);
    self.innerHTML = html;
    const scripts = self.querySelectorAll('script');
    for (let script of scripts) {
        runScript(script);
    }

    function runScript(script) {
        // 直接 document.head.appendChild(script) 是不会生效的，需要重新创建一个
        const newScript = document.createElement('script');
        // 获取 inline script
        newScript.innerHTML = script.innerHTML;
        // 存在 src 属性的话
        const src = script.getAttribute('src');
        if (src) newScript.setAttribute('src', src);

        document.body.appendChild(newScript);
        document.body.removeChild(newScript);
    }
}

// 追加
function wnd_append(el, html) {
    /**  
     *beforebegin 在元素之前
     *afterbegin 在元素的第一个子元素之后
     *beforeend 在元素的最后一个子元素之后
     *afterend 在元素之后 
     */
    let self = ('object' == typeof el) ? el : document.querySelector(el);
    if (self) {
        self.insertAdjacentHTML('beforeend', html);
    }
}

/**
 * @since 0.9.25 动态加载脚本后执行回调函数
 * @since 0.9.39 改用 promise 可获取回调函数返回值
 *  let url = static_path + 'js/lib/spark-md5.min.js' + cache_suffix;
 *  md5_str = await wndt_load_script(
 *      url,
 *      function() {
 *          // do something and return value
 *          return 'xxx';
 *      }
 *  );  
 * 
 */
function wnd_load_script(url, callback) {
    return new Promise(function(resolve, reject) {
        let script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = url;
        document.head.appendChild(script);
        script.onload = function() {
            resolve(callback());
        };
    });
}

/**
 * 动态加载CSS
 * @param {string} url 样式地址
 */
function wnd_load_style(url) {
    let link = document.createElement('link');
    link.type = 'text/css';
    link.rel = 'stylesheet';
    link.href = url;
    document.head.appendChild(link);
}

// 指定容器设置加载中效果
function wnd_loading(el, remove = false) {
    let self = ('object' == typeof el) ? el : document.querySelector(el);
    if (!self) {
        return;
    }

    // Modal
    if (self.classList.contains('modal-entry')) {
        if (remove) {
            self.innerHTML = '';
        } else {
            self.innerHTML = loading_el;
        }
        return;
    }

    // Embed
    if (!remove) {
        self.style.position = 'relative';
        wnd_append(self, `<div class="wnd-loading" style="position:absolute;top:0;left:0;right:0;bottom:0;z-index:9;background:#FFF;opacity:0.7">${loading_el}</div>`);
    } else {
        wnd_remove(self.querySelector('.wnd-loading'));
        self.style.removeProperty('position');
    }
}

// 按需加载 wnd-vue-form.js 并渲染表单
function wnd_render_form(container, form_json, add_class) {
    if ('function' != typeof _wnd_render_form) {
        let url = static_path + 'js/form.min.js' + cache_suffix;
        wnd_load_script(url, function() {
            _wnd_render_form(container, form_json, add_class)
        });
    } else {
        _wnd_render_form(container, form_json, add_class);
    }
}

/**
 * @since 0.9.39
 * 文件直传 OSS，并写入附件记录
 **/
async function wnd_upload_to_oss(file, sign_data = {}) {
    return await wnd_upload_to_oss_dynamic(file, '', '', false, sign_data);
}

/**
 * @since 0.9.39
 * 文件直传 OSS
 * 不写入附件记录
 **/
async function wnd_upload_to_oss_direct(file, oss_sp, endpoint, sign_data = {}) {
    return await wnd_upload_to_oss_dynamic(file, oss_sp, endpoint, true, sign_data);
}


/**
 * @since 0.9.39
 * 文件直传 OSS，并写入附件记录
 **/
async function wnd_upload_to_oss_dynamic(file, oss_sp, endpoint, direct = true, sign_data = {}) {
    let upload_res;
    if ('undefined' == typeof _wnd_upload_to_oss) {
        let url = static_path + 'js/file.min.js' + cache_suffix;
        upload_res = await wnd_load_script(
            url,
            function() {
                return _wnd_upload_to_oss(file, oss_sp, endpoint, direct, sign_data);
            }
        );
    } else {
        upload_res = _wnd_upload_to_oss(file, oss_sp, endpoint, direct, sign_data);
    }
    return upload_res;
}

// 按需加载 wnd-vue-form.js 并渲染表达
function wnd_render_filter(container, filter_json, add_class) {
    if ('function' != typeof _wnd_render_filter) {
        let url = static_path + 'js/filter.min.js' + cache_suffix;
        wnd_load_script(url, function() {
            _wnd_render_filter(container, filter_json, add_class);
        });
    } else {
        _wnd_render_filter(container, filter_json, add_class);
    }
}

// 按需加载 wnd-vue-form.js 并渲染表达
function wnd_render_menus(container, wnd_menus_data, in_side = false) {
    let parent = document.querySelector(container);
    wnd_inner_html(parent, '<div class="vue-app"></div>');

    new Vue({
        el: container + ' .vue-app',
        template: `
<aside class="menu">
<template v-for="(menu, menu_index) in menus">
<ul class="menu-list">
<a v-if="menu.label" v-html="build_label(menu)" @click="expand(menu_index)"></a>
<li v-show="menu.expand"><ul><li v-for="(item, item_index) in menu.items">
<a :href="item.href" @click="active(menu_index, item_index)" :class="item.class" v-html="item.title"></a></li>
</ul></li>
</ul>
</template>
</aside>`,
        data: {
            menus: wnd_menus_data,
        },
        methods: {
            build_label: function(menu) {
                return menu.label + '&nbsp;' + (menu.expand ? '<i class="fas fa-angle-up"></i>' : '<i class="fas fa-angle-down"></i>');
            },
            active: function(menu_index, item_index) {
                for (let i = 0; i < this.menus.length; i++) {
                    const menu = this.menus[i];

                    /**
                     * Vue 直接修改数组的值无法触发重新渲染
                     * @link https://cn.vuejs.org/v2/guide/reactivity.html#%E6%A3%80%E6%B5%8B%E5%8F%98%E5%8C%96%E7%9A%84%E6%B3%A8%E6%84%8F%E4%BA%8B%E9%A1%B9
                     */
                    for (let j = 0; j < menu.items.length; j++) {
                        const item = menu.items[j];
                        if (j != item_index || menu_index !== i) {
                            Vue.set(item, 'class', '');
                        } else {
                            Vue.set(item, 'class', 'is-active');
                        }
                    }
                }

                // 侧边栏菜单：点击后关闭侧边栏
                if (in_side) {
                    wnd_menus_side_toggle(true);
                }
            },
            expand: function(menu_index) {
                for (let i = 0; i < this.menus.length; i++) {
                    const menu = this.menus[i];
                    if (menu_index !== i) {
                        menu.expand = false;
                    } else {
                        menu.expand = !(menu.expand || false);
                    }
                }
            },
            get_container: function() {
                return parent.id ? '#' + parent.id : '';
            },
        },
        mounted() {
            // 如果尚未定义菜单数据，异步请求数据并赋值
            if (!wnd_menus_data) {
                axios({
                    'method': 'get',
                    url: wnd_jsonget_api + '/wnd_menus',
                    params: {
                        "in_side": in_side
                    },
                    headers: {
                        'Container': this.get_container(),
                    },
                }).then(res => {
                    this.menus = res.data.data;
                    wnd_menus_data = res.data.data;
                    wnd_loading(this.get_container(), true);
                });
            }
        },
    });
}

/**
 *@since 2020.07.21
 *ajax 获取 json数据
 *@param jsonget	对应 JsonGet 类名称
 *@param param 		对应传参
 *@param callback 	回调函数
 */
function wnd_get_json(jsonget, param, callback = '') {
    axios({
        'method': 'get',
        url: wnd_jsonget_api + '/' + jsonget,
        params: param,
    }).then(function(response) {
        if (callback) {
            if ('function' == typeof callback) {
                callback(response.data);
            } else {
                window[callback](response.data);
            }
        }
    });
}

/**
 *@since 2019.1.10  从后端请求ajax内容并填充到指定DOM
 *原理同 wnd_ajax_modal()，区别为，响应方式为嵌入
 *@param 	container 	srting 		指定嵌入的容器选择器
 *@param 	module 		string 		module类名称
 *@param 	param 		json 		传参
 *@param 	callback 	回调函数
 **/
function wnd_ajax_embed(container, module, param = {}, callback = '') {
    // 初始高度：设置嵌入高度变化动画之用
    let el = document.querySelector(container);
    funTransitionHeight(el);

    // GET request for remote image in node.js
    axios({
        method: 'get',
        url: wnd_module_api + '/' + module,
        params: Object.assign({
            'ajax_type': 'embed'
        }, param),
        headers: {
            'Container': container
        },
    }).then(function(response) {
        if ('undefined' == typeof response.data.status) {
            console.log(response);
            return false;
        }

        if (response.data.status <= 0) {
            wnd_inner_html(el, '<div class="message is-danger"><div class="message-body">' + response.data.msg + '</div></div>');
            funTransitionHeight(el, trs_time);
            return false;
        }

        if ('form' == response.data.data.type) {
            wnd_render_form(container, response.data.data.structure);
        } else if ('filter' == response.data.data.type) {
            wnd_render_filter(container, response.data.data.structure);
        } else {
            wnd_inner_html(el, response.data.data.structure);
            funTransitionHeight(el, trs_time);
        }

        if (callback) {
            if ('function' == typeof callback) {
                callback(response.data);
            } else {
                window[callback](response.data);
            }
        }
    });
}


/**
 * @since 2019.1
*######################## ajax动态内容请求
*使用本函数主要用以返回更复杂的结果如：表单，页面以弹窗或嵌入指定DOM的形式呈现，以供用户操作。表单提交则通常返回较为简单的结果
	允许携带一个参数

	@since 2019.01.26
	若需要传递参数值超过一个，可将参数定义为GET参数形式如：'post_id=1&user_id=2'，后端采用:wp_parse_args() 解析参数

	实例：
		前端
		wnd_ajax_modal('wnd_xxx','post_id=1&user_id=2');

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
    wnd_alert_modal(loading_el, false);

    axios({
        method: 'get',
        url: wnd_module_api + '/' + module,
        params: Object.assign({
            'ajax_type': 'modal'
        }, param),
    }).then(function(response) {
        if ('undefined' == typeof response.data.status) {
            console.log(response);
            return false;
        }

        if (response.data.status <= 0) {
            wnd_alert_modal('<div class="message is-danger"><div class="message-body">' + response.data.msg + '</div></div>');
            return false;
        }

        if ('form' == response.data.data.type) {
            wnd_render_form('#modal .modal-entry', response.data.data.structure, 'box');
        } else if ('filter' == response.data.data.type) {
            wnd_render_filter('#modal .modal-entry', response.data.data.structure, 'box');
        } else {
            wnd_alert_modal(response.data.data.structure);
        }

        if (callback) {
            if ('function' == typeof callback) {
                callback(response.data);
            } else {
                window[callback](response.data);
            }
        }
    });
}

/**
 * @since 0.9.35.6
 * 发送 ajax Action
 **/
function wnd_ajax_action(action, param = {}, callback = '') {
    axios({
        method: 'POST',
        url: wnd_action_api + '/' + action,
        data: param,
    }).then(function(response) {
        if ('undefined' == typeof response.data.status) {
            console.log(response);
            return false;
        }

        if (callback) {
            if ('function' == typeof callback) {
                callback(response.data);
            } else {
                window[callback](response.data);
            }
        }
    });
}

//弹出bulma对话框
function wnd_alert_modal(content, box = true) {
    wnd_reset_modal();
    let modal = document.querySelector('#modal'); // assuming you have only 1
    let modal_entry = modal.querySelector('.modal-entry');
    modal.classList.add('is-active');
    if (box) {
        modal_entry.classList.add('box');
    }

    // 对于复杂的输入，不能直接使用 innerHTML
    wnd_inner_html(modal_entry, content);

    funTransitionHeight(modal_entry, trs_time);
}

// 直接弹出消息
function wnd_alert_msg(msg, time = 0) {
    wnd_alert_modal(msg, false);

    // 定时关闭
    if (time > 0) {
        let timer = null;
        timer = setInterval(function() {
            clearInterval(timer);
            wnd_reset_modal();
        }, time * 1000);
    }
}

function wnd_alert_notification(content, add_class = '') {
    add_class = add_class ? ('notification ' + add_class) : 'notification';
    wnd_alert_modal('<div class="notification is-light is-danger">' + content + '</div>', false);
}

// 初始化对话框
function wnd_reset_modal() {
    let modal = document.querySelector('#modal');
    if (modal) {
        let modal_entry = modal.querySelector('#modal .modal-entry');

        modal_entry.innerHTML = '';
        modal.classList.remove('is-active', 'wnd-gallery');
        modal_entry.classList.remove('box');
        // 动画效果需要
        modal_entry.style.height = '0';
    } else {
        wnd_append('body', `
<div id="modal" class="modal">
<div class="modal-background"></div>
<div class="modal-content">
<div class="modal-entry content"></div>
</div>
<button class="modal-close is-large" aria-label="close"></button>
</div>`);
    }
}

/**
 * 常规HTML表单提交:绑定在submit button
 * 常规表单不具备文件上传能力，主要用户构建前端各类简单表单按钮，以避免在常规页面中 Vue 动态加载造成的页面抖动 
 * */
function wnd_ajax_submit(button, captcha_input = false) {
    let form = button.closest('form');
    let route = form.getAttribute('route');

    form.addEventListener('submit', function() {
        button.classList.add('is-loading');
    });

    // 未设置 route 表示为常规 HTML 提交，而非 API 请求
    if (!route) {
        return false;
    }

    // 表单有效性检查
    for (let i = 0, n = form.elements.length; i < n; i++) {
        if (!form.elements[i].hasAttribute('required') || form.elements[i].hasAttribute('disabled')) {
            continue;
        }

        let required_error = false;
        if (['radio', 'checkbox'].includes(form.elements[i].type)) {
            let name = form.elements[i].name;
            let checked = form.querySelector('[name="' + name + '"]:checked');
            if (!checked) {
                required_error = true;
            }
        } else if ('select' == form.elements[i].type && !form.elements[i].selected) {
            required_error = true;
        } else if (!form.elements[i].value) {
            required_error = true;
        }

        if (required_error) {
            form.elements[i].classList.add('is-danger');
            wnd_form_msg(form, wnd.msg.required, 'is-danger');
            button.classList.remove('is-loading');
            return false;
        }
    }

    // Ajax 请求
    let data = new FormData(form);

    // GET 将表单序列化
    let params = {};
    if ('get' == form.method) {
        for (const key of data.keys()) {
            params[key] = data.get(key);
        }
    }
    axios({
        method: form.method,
        url: form.action,
        // POST
        data: data,
        // GET
        params: params,
    }).then(function(response) {
        form_info = wnd_handle_response(response.data, route, form.parentNode);
        if (form_info.msg) {
            wnd_form_msg(form, form_info.msg, form_info.msg_class);
        }
        if (![3, 4, 6].includes(response.data.status)) {
            button.classList.remove('is-loading');
        }

        // 提交后清空无论后端响应如何都应清空 Captcha，因为本次 captcha 行为验证已完成
        if (captcha_input) {
            captcha_input.value = '';
        }
    });
}

function wnd_form_msg(form, msg, msg_class) {
    let el = form.querySelector('.form-message');
    if (!el) {
        return false;
    }

    el.style.display = '';
    el.className = `form-message message ${msg_class}`;
    let msg_body = el.querySelector('.message-body');

    /**
     * @since 0.9.36
     * 当表单已包含完整的 message element（如 Vue 渲染的表单），仅替换内容不破坏原有的元素结构
     * 防止可能因破坏原有结构导致消息无法呈现
     **/
    if (msg_body) {
        msg_body.innerHTML = msg;
    } else {
        el.innerHTML = '<div class="message-body">' + msg + '</div>';
    }

    // 调整高度
    modal_entry = form.closest('#modal .modal-entry');
    if (modal_entry) {
        funTransitionHeight(modal_entry, trs_time);
    } else {
        funTransitionHeight(form.parentNode, trs_time);
    }
}

// 统一处理表单响应
function wnd_handle_response(response, route, parent) {
    let form_info = {
        'msg': '',
        'msg_class': ''
    };

    /**
     *@since 0.8.73
     *GET 提交弹出 UI 模块
     */
    if ('module' == route) {
        if (response.status >= 1) {
            wnd_alert_modal(loading_el, false);
            if ('form' == response.data.type) {
                wnd_render_form('#modal .modal-entry', response.data.structure, 'box');
            } else if ('filter' == response.data.type) {
                wnd_render_filter('#modal .modal-entry', response.data.structure, 'box');
            } else {
                wnd_alert_modal(response.data.structure);
            }
        } else {
            wnd_alert_modal(response.msg);
        }
        // this.form.submit.attrs.class = form_json.submit.attrs.class;
        return form_info;
    }

    // 根据后端响应处理
    form_info.msg = response.msg;
    form_info.msg_class = (response.status <= 0) ? 'is-danger' : 'is-success';
    switch (response.status) {

        // 常规类，展示后端提示信息，状态 8 表示禁用提交按钮 
        case 1:
        case 8:
            // form_info.msg =  response.data.msg;
            break;

            //更新类
        case 2:
            form_info.msg = response.msg + '<a href="' + response.data.url + '" target="_blank">&nbsp;' + wnd.msg.view + '</a>';
            break;

            // 跳转类
        case 3:
            form_info.msg = wnd.msg.waiting;
            window.location = response.data.redirect_to;
            break;

            // 刷新当前页面
        case 4:
            if ("undefined" == typeof response.data || "undefined" == typeof response.data.waiting) {
                window.location.reload();
            }

            // 延迟刷新
            // var timer = null;
            // var time = response.data.waiting;

            // submit_button.removeClass("is-loading");
            // submit_button.text(wnd.msg.waiting + " " + time);
            // timer = setInterval(function() {
            //     if (time <= 0) {
            //         clearInterval(timer);
            //         wnd_reset_modal();
            //         window.location.reload();
            //     } else {
            //         submit_button.text(wnd.msg.waiting + " " + time);
            //         time--;
            //     }
            // }, 1000);
            break;

            // 弹出信息并自动消失
        case 5:
            wnd_alert_msg('<div class="has-text-centered"><h5 class="has-text-white">' + response.msg + '</h5></div>', 1);
            break;

            // 下载类
        case 6:
            form_info.msg = wnd.msg.downloading;
            window.location = response.data.redirect_to;
            break;

            // 以响应数据替换当前表单
        case 7:
            wnd_inner_html(parent, response.data);
            break;

            // 默认
        default:
            // wnd_ajax_form_msg(form_id, response.msg, style);
            break;
    }

    parent.scrollIntoView({
        behavior: 'smooth'
    });

    return form_info;
}

/**
 *@since 2019.02.09 发送手机或邮箱验证码
 *@param object button element
 *@param string captcha data key
 */
function wnd_send_code(button, captcha_data_key = '') {
    let form = button.closest('form');
    let device = form.querySelector('input[name=\'_user_user_email\']') || form.querySelector('input[name=\'phone\']');
    let device_value = device.value || '';
    if (!device_value) {
        device.classList.add('is-danger');
        return false;
    }

    button.classList.add('is-loading');

    let data = button.dataset;
    data.device = device_value;
    let style = 'is-success';

    axios({
        url: wnd_action_api + '/' + data.action,
        method: 'POST',
        data: data,
    }).then(function(response) {
        if (response.data.status <= 0) {
            style = 'is-danger';
        } else {
            button.disabled = true;
            button.textContent = wnd.msg.send_successfully;

            // 定时器
            let time = data.interval;
            let timer = null;
            timer = setInterval(function() {
                if (time <= 1) {
                    clearInterval(timer);
                    button.textContent = wnd.msg.try_again;
                    button.disabled = false;
                } else {
                    time--;
                    button.textContent = time;
                }
            }, 1000);
        }

        wnd_form_msg(form, response.data.msg, style);
        button.classList.remove('is-loading');
        // 清空 captcha
        if (captcha_data_key) {
            button.dataset[captcha_data_key] = '';
        }
    });
};

/**
 * 流量统计
 */
function wnd_update_views(post_id, interval = 3600) {
    if (wnd_is_spider()) {
        return;
    }

    let timestamp = Date.parse(new Date()) / 1000;
    let wnd_views = localStorage.getItem('wnd_views') ? JSON.parse(localStorage.getItem('wnd_views')) : [];
    let max_length = 10;
    let is_new = true;

    // 数据处理
    for (let i = 0, n = wnd_views.length; i < n; i++) {
        if (wnd_views[i].post_id == post_id) {
            // 存在记录中：且时间过期
            if (wnd_views[i].timestamp < timestamp - interval) {
                wnd_views[i].timestamp = timestamp;
                is_new = true;
            } else {
                is_new = false;
            }
            break;
        }
    }

    // 新浏览
    if (is_new) {
        let new_view = {
            'post_id': post_id,
            'timestamp': timestamp
        };
        wnd_views.unshift(new_view);
    }

    // 删除超过长度的元素
    if (wnd_views.length > max_length) {
        wnd_views.length = max_length;
    }

    // 更新服务器数据
    if (is_new) {
        axios({
            url: wnd_action_api + '/wnd_update_views',
            method: 'POST',
            data: {
                'post_id': post_id
            },
        }).then(function(response) {
            if (1 == response.data.status) {
                localStorage.setItem('wnd_views', JSON.stringify(wnd_views));
            }
        });
    }
}

/**
 *@since 2019.07.02 非表单形式发送ajax请求
 */
var can_click_ajax_link = true;

function wnd_ajax_click(link) {
    // 是否在弹窗中操作
    let in_modal = link.closest('.modal.is-active') ? true : false;

    // 点击频率控制
    if (!can_click_ajax_link) {
        return;
    }
    can_click_ajax_link = false;
    setTimeout(function() {
        can_click_ajax_link = true;
    }, 1000);

    if (1 == link.dataset.disabled) {
        wnd_alert_msg(wnd.msg.waiting, 1);
        return;
    }

    // 判断当前操作是否为取消
    let is_cancel = 0 != link.dataset.is_cancel;
    let action = is_cancel ? link.dataset.cancel : link.dataset.action;
    let args = JSON.parse(link.dataset.args);
    args._wnd_sign = link.dataset.sign;

    axios({
        url: wnd_action_api + '/' + action,
        method: 'POST',
        data: args,
    }).then(function(response) {
        // 正向操作成功
        if (response.data.status != 0 && action == link.dataset.action) {
            // 设置了逆向操作
            if (link.dataset.cancel) {
                link.dataset.is_cancel = 1;
                // 未设置逆向操作，禁止重复点击事件
            } else {
                link.dataset.disabled = 1;
            }

        } else {
            link.dataset.is_cancel = 0;
        }

        // 根据后端响应处理
        switch (response.data.status) {
            // 常规类，展示后端提示信息
            case 1:
                link.innerHTML = response.data.data;
                break;

                // 弹出消息
            case 2:
                if (response.data.data) {
                    link.innerHTML = response.data.data;
                }
                if (!in_modal) {
                    wnd_alert_msg('<div class="has-text-centered"><h5 class="has-text-white">' + response.data.msg + '</h5></div>', 1);
                }
                break;

                // 跳转类
            case 3:
                wnd_alert_msg(wnd.msg.waiting);
                window.location.href = response.data.data.redirect_to;
                break;

                // 刷新当前页面
            case 4:
                wnd_reset_modal();
                window.location.reload();
                break;
        }
    });
}


/**
 * 高度无缝动画方法
 * @link https://www.zhangxinxu.com/wordpress/2015/01/content-loading-height-change-css3-transition-better-experience/
 */
var funTransitionHeight = function(element, time) { // time, 数值，可缺省
    if (typeof window.getComputedStyle == 'undefined') return;

    let height = window.getComputedStyle(element).height;
    element.style.transition = 'none'; // 本行2015-05-20新增，mac Safari下，貌似auto也会触发transition, 故要none下~
    element.style.height = 'auto';
    let targetHeight = window.getComputedStyle(element).height;
    element.style.height = height;
    element.offsetWidth = element.offsetWidth;
    if (time) element.style.transition = 'height ' + time + 'ms';
    element.style.height = targetHeight;
    element.style.overflow = 'hidden';
};

/**
 *@since 0.9.13
 *Ajax 加载侧边栏
 */
function wnd_load_menus_side() {
    if (!menus_side) {
        wnd_append('body', `
<div id="wnd-side-container"></div>
<div id="wnd-side-background" class="modal" style="z-index:31;">
<div class="modal-background"></div>
</div>`);
        wnd_ajax_embed('#wnd-side-container', 'wnd_menus_side', {}, 'wnd_menus_side_toggle');
    } else {
        wnd_menus_side_toggle();
    }
}

/**
 *@since 0.9.13
 *展开或关闭侧边栏
 */
function wnd_menus_side_toggle(close = false) {
    // 按钮及遮罩 Toggle
    document.querySelectorAll('.wnd-side-burger').forEach(function(burger) {
        burger.classList.toggle('is-active');
    });
    document.querySelector('#wnd-side-background').classList.toggle('is-active');

    // Menus
    menus_side = menus_side || document.querySelector('#wnd-side-container').firstChild;
    menus_side.style.transition = 'all ' + trs_time * 2 + 'ms';

    // close
    if (true == close) {
        menus_side.style.left = '-' + menus_side.offsetWidth + 'px';
        return;
    }

    // 初次加载动画
    if (!menus_side.style.left) {
        menus_side.style.left = '-' + menus_side.offsetWidth + 'px';
        setTimeout(() => {
            menus_side.style.left = '0px';
        }, trs_time - 50);
    } else {
        menus_side.style.left = '0px';
    }
}

/**
 *@since 0.9.25
 *监听点击事件
 */
document.addEventListener('click', function(e) {
    // 关闭 Modal
    if (e.target.classList.contains('modal-close')) {
        wnd_reset_modal();
        return;
    }

    // Ajax link
    let a = e.target.closest('a');
    if (a && a.classList.contains('ajax-link')) {
        wnd_ajax_click(a);
        return;
    }

    // DIV
    let div = e.target.closest('div');
    if (!div) {
        return;
    }

    /**
     *@since 0.9.13 从主题中移植
     *移动导航点击展开侧边栏
     */
    if (div.classList.contains('wnd-side-burger')) {
        if (div.classList.contains('is-active')) {
            wnd_menus_side_toggle(true);
        } else {
            wnd_load_menus_side();
        }

        return;
    }

    // 点击Side Menus遮罩，关闭侧栏
    if (div.parentElement.id == 'wnd-side-background') {
        wnd_menus_side_toggle(true);
        return;
    }

    // Modal 遮罩
    if (div.classList.contains('modal-background')) {
        wnd_reset_modal();
        return;
    }

    // 密码输入字段可视切换
    if (div.classList.contains('hide-pw')) {
        // icon
        let i = div.querySelector('i');
        let add = i.classList.contains('fa-eye') ? 'fa-eye-slash' : 'fa-eye';
        let remove = 'fa-eye' == add ? 'fa-eye-slash' : 'fa-eye';
        i.classList.remove(remove);
        i.classList.add(add);

        // input type
        let input = div.closest('div.control').querySelector('input');
        input.type = 'fa-eye' == add ? 'password' : 'text';

        return;
    }
});