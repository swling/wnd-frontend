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

// 加载中 Element
if ('undefined' == typeof loading_el) {
    var loading_el = '<div class="spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div></div>';
}

/**
 *axios 拦截器 统一设置 WP Rest Nonce
 *@link https://github.com/axios/axios#interceptors
 */
// Add a request interceptor
axios.interceptors.request.use(function(config) {
    // config.headers.Authorization = "Bearer " + token
    config.headers['X-WP-Nonce'] = wnd.rest_nonce;
    config.headers['X-Requested-With'] = 'XMLHttpRequest';
    return config;
}, function(error) {
    // Do something with request error
    return Promise.reject(error);
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
    var userAgent = navigator.userAgent;
    // 蜘蛛判断
    if (userAgent.match(/(Googlebot|Baiduspider|spider)/i)) {
        return true;
    } else {
        return false;
    }
}

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

// 嵌入 HTML
function wnd_inner_html(el, html) {
    var self = document.querySelector(el);
    self.innerHTML = html;
}

// 追加
function wnd_append(el, html) {
    /**  
     *beforebegin 在元素之前
     *afterbegin 在元素的第一个子元素之后
     *beforeend 在元素的最后一个子元素之后
     *afterend 在元素之后 
     */
    var self = document.querySelector(el);
    if (self) {
        self.insertAdjacentHTML('beforeend', html);
    }
}

// 指定容器设置加载中效果
function wnd_loading(el, remove = false) {
    var el = document.querySelector(el);
    if (el && !remove) {
        el.innerHTML = loading_el;
    } else {
        el.innerHTML = '';
    }
}

// 按需加载 wnd-vue-form.js 并渲染表达
function wnd_render_form(container, form_json) {
    if ('undefined' == typeof wnd_vue_form) {
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = wnd.plugin_url + '/static/js/wnd-vue-form.js?ver=' + wnd.ver;
        script.onload = function() {
            _wnd_render_form(container, form_json);
        };
        document.head.appendChild(script);
    } else {
        _wnd_render_form(container, form_json);
    }
}

// 按需加载 wnd-vue-form.js 并渲染表达
function wnd_render_filter(container, filter_json) {
    if ('undefined' == typeof wnd_vue_filter) {
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = wnd.plugin_url + '/static/js/wnd-vue-filter.js?ver=' + wnd.ver;
        script.onload = function() {
            _wnd_render_filter(container, filter_json);
        };
        document.head.appendChild(script);
    } else {
        _wnd_render_filter(container, filter_json);
    }
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
    // 添加请求拦截器
    axios.interceptors.request.use(function(config) {
        if ('get' == config.method && 'embed' == (config.params.ajax_type || false)) {
            wnd_loading(container);
        }

        return config;
    });

    // GET request for remote image in node.js
    axios({
            method: 'get',
            url: wnd_module_api + '/' + module + lang_query,
            params: Object.assign({
                'ajax_type': 'embed'
            }, param),
        })
        .then(function(response) {
            if ('undefined' == typeof response.data.status) {
                console.log(response);
                return false;
            }

            if (response.data.status <= 0) {
                wnd_inner_html(container, '<div class="message is-danger"><div class="message-body">' + response.data.msg + '</div></div>');
                return false;
            }

            if ('form' == response.data.data.type) {
                wnd_inner_html(container, '<div class="vue-app"></div>');
                wnd_render_form(container + ' .vue-app', response.data.data.structure);
            } else if ('filter' == response.data.data.type) {
                wnd_inner_html(container, '<div class="vue-app"></div>');
                wnd_render_filter(container + ' .vue-app', response.data.data.structure);
            } else {
                wnd_inner_html(container, response.data.data.structure);
            }

            if (callback) {
                window[callback](response);
            }
        })
        .catch(function(error) { // 请求失败处理
            console.log(error);
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
    // 添加请求拦截器
    axios.interceptors.request.use(function(config) {
        if ('get' == config.method && 'modal' == (config.params.ajax_type || false)) {
            wnd_alert_msg(loading_el);
        }

        return config;
    });

    axios({
            method: 'get',
            url: wnd_module_api + '/' + module + lang_query,
            params: Object.assign({
                'ajax_type': 'modal'
            }, param),
        })
        .then(function(response) {
            if ('undefined' == typeof response.data.status) {
                console.log(response);
                return false;
            }

            if (response.data.status <= 0) {
                wnd_alert_modal('<div class="message is-danger"><div class="message-body">' + response.data.msg + '</div></div>');
                return false;
            }

            if ('form' == response.data.data.type) {
                wnd_alert_modal('<div id="vue-app"></div>');
                wnd_render_form('#vue-app', response.data.data.structure);
            } else {
                wnd_alert_modal(response.data.data.structure);
            }

            if (callback) {
                window[callback](response);
            }
        })
        .catch(function(error) { // 请求失败处理
            wnd_alert_modal(wnd.msg.system_error);
        });
}


//弹出bulma对话框
function wnd_alert_modal(content, is_gallery = false) {
    wnd_reset_modal();
    var modal = document.querySelector('#modal'); // assuming you have only 1
    var modal_entry = modal.querySelector('.modal-entry');
    if (is_gallery) {
        modal.classList.add('is-active', 'wnd-gallery');
    } else {
        modal.classList.add('is-active');
    }
    modal_entry.classList.add('box');
    // 对于复杂的输入，不能直接使用 innerHTML
    wnd_append('#modal .modal-entry', content);
}

// 直接弹出消息
function wnd_alert_msg(msg, time = 0) {
    wnd_reset_modal();
    var modal = document.querySelector('#modal'); // assuming you have only 1
    var modal_entry = modal.querySelector('.modal-entry');
    modal.classList.add('is-active');
    modal_entry.classList.remove('box');
    modal_entry.innerHTML = msg;

    // 定时关闭
    if (time > 0) {
        var timer = null;
        timer = setInterval(function() {
            clearInterval(timer);
            wnd_reset_modal();
        }, time * 1000);
    }
}

// 初始化对话框
function wnd_reset_modal() {
    var modal = document.querySelector('#modal');
    if (modal) {
        var modal_entry = modal.querySelector('#modal .modal-entry');

        modal_entry.innerHTML = '';
        modal.classList.remove('is-active', 'wnd-gallery');
        modal_entry.classList.remove('box');
    } else {
        wnd_append('body',
            '<div id="modal" class="modal">' +
            '<div class="modal-background" onclick="wnd_reset_modal()"></div>' +
            '<div class="modal-content">' +
            '<div class="modal-entry content"></div>' +
            '</div>' +
            '<button class="modal-close is-large" aria-label="close" onclick="wnd_reset_modal()"></button>' +
            '</div>'
        );
    }
}

// 常规HTML表单提交:绑定在submit button
function wnd_ajax_submit(button) {
    let form = button.closest('form');
    let route = form.getAttribute('route');

    form.addEventListener('submit', function() {
        button.classList.add('is-loading');
    });

    // 为设置 route 表示为常规 HTML 提交，而非 API 请求
    if (!route) {
        return false;
    }

    // Ajax 请求
    let data = new FormData(form);

    // GET 将表单序列化
    let params = {};
    if ('get' == form.method) {
        for (var key of data.keys()) {
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
        })
        .then(function(response) {
            button.classList.remove('is-loading');
            form_info = handle_response(response.data, route);
            console.log(form_info);
        });
}

// 统一处理表单响应
function handle_response(response, route) {
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
            if ('form' == response.data.type) {
                wnd_alert_modal('<div id="vue-app"></div>');
                wnd_render_form('#vue-app', response.data.structure);
            } else {
                wnd_alert_modal(response.data);
            }
        } else {
            wnd_alert_modal(response.msg);
        }
        // this.form.submit.attrs.class = form_json.submit.attrs.class;
        return form_info;
    }

    // 根据后端响应处理
    form_info.msg = response.msg;
    form_info.msg_class = response.status <= 0 ? 'is-danger' : 'is-success';
    switch (response.status) {

        // 常规类，展示后端提示信息，状态 8 表示禁用提交按钮 
        case 1:
        case 8:
            // form_info.msg =  response.data.msg;
            break;

            //更新类
        case 2:
            form_info.msg = wnd.msg.submit_successfully + '<a href="' + response.data.url + '" target="_blank">&nbsp;' + wnd.msg.view + '</a>';
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
            var timer = null;
            var time = response.data.waiting;

            submit_button.removeClass("is-loading");
            submit_button.text(wnd.msg.waiting + " " + time);
            timer = setInterval(function() {
                if (time <= 0) {
                    clearInterval(timer);
                    wnd_reset_modal();
                    window.location.reload();
                } else {
                    submit_button.text(wnd.msg.waiting + " " + time);
                    time--;
                }
            }, 1000);
            break;

            // 弹出信息并自动消失
        case 5:
            // wnd_alert_msg(response.msg, 1);
            break;

            // 下载类
        case 6:
            form_info.msg = wnd.msg.downloading;
            window.location = response.data.redirect_to;
            break;

            // 以响应数据替换当前表单
        case 7:
            // $("#" + form_id).replaceWith(response.data);
            break;

            // 默认
        default:
            // wnd_ajax_form_msg(form_id, response.msg, style);
            break;
    }

    return form_info;
}

/**
 *@since 0.9.25
 *文档加载完成后执行
 */
window.onload = function() {
    /**
     *@since 0.9.5
     *常规表单(非 Vue 渲染表单)提交
     */
    // document.querySelector('.ajax-submit').addEventListener('click', function(e) {

    // });

}