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

var loading_el = '<div class="spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div></div>';

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

// 指定容器设置加载中效果
function wnd_loading(el) {
    /**  
     * 
     *beforebegin 在元素之前
     *afterbegin 在元素的第一个子元素之后
     *beforeend 在元素的最后一个子元素之后
     *afterend 在元素之后 
     */
    var el = document.querySelector(el);
    if (el) {
        el.innerHTML = loading_el;
    }
}

// 按需加载 wnd-vue-form.js 并渲染表达
function wnd_render_form(container, form_json) {
    if ('undefined' == typeof wnd_vue_form) {
        var script = document.createElement("script");
        script.type = "text/javascript";
        script.src = wnd.plugin_url + '/static/js/wnd-vue-form.js?ver=' + wnd.ver;
        script.onload = function() {
            _wnd_render_form(container, form_json);
        };
        document.body.appendChild(script);
    } else {
        _wnd_render_form(container, form_json);
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
    }, function(error) {
        // Do something with request error
        return Promise.reject(error);
    });

    axios.get(wnd_module_api + "/" + module + lang_query, {
            params: Object.assign({
                "ajax_type": "embed"
            }, param)
        })
        .then(function(response) {
            if ('form' == response.data.type) {
                return wnd_render_form(container, response.data.data);
            } else {
                wnd_replace(container, response.data.data);
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
    // 添加请求拦截器
    // 添加请求拦截器
    axios.interceptors.request.use(function(config) {
        if ('get' == config.method && 'modal' == (config.params.ajax_type || false)) {
            wnd_alert_msg(loading_el);
        }

        return config;
    }, function(error) {
        // Do something with request error
        return Promise.reject(error);
    });

    axios.get(
            wnd_module_api + "/" + module + lang_query, {
                params: Object.assign({
                    "ajax_type": "modal"
                }, param)
            }
        )
        .then(function(response) {
            if ('form' == response.data.type) {
                wnd_alert_modal('<div id="vue-app"></div>');
                wnd_render_form("#vue-app", response.data.data);
            } else {
                wnd_alert_modal(response.data.data);
            }

            if (callback) {
                window[callback](response);
            }
        })
        .catch(function(error) { // 请求失败处理
            console.log(error);
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
    var modal_entry = document.querySelector('#modal .modal-entry');

    if (modal) {
        modal.classList.remove("is-active", "wnd-gallery");
        modal_entry.classList.remove('box');
        modal_entry.innerHTML = '';
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