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

/**
 * 其他定义
 *
 **/
var trs_time = 160;
var menus_side = false;

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

/**
 *插入 HTML  支持运行 JavaScript
 * */
function wnd_inner_html(container, html) {
    let el = document.querySelector(container);
    el.innerHTML = html;
    const scripts = el.querySelectorAll('script');
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
    var self = document.querySelector(el);
    if (self) {
        self.insertAdjacentHTML('beforeend', html);
    }
}

/**
 *@since 0.9.25 动态加载脚本后执行回调函数
 * 
 */
function wnd_load_script(url, callback) {
    var script = document.createElement("script");
    script.type = "text/javascript";
    script.src = url;
    document.head.appendChild(script);
    script.onload = callback;
}

// 指定容器设置加载中效果
function wnd_loading(el, remove = false) {
    var container = document.querySelector(el);
    if (!remove) {
        container.style.position = 'relative';
        wnd_append(el, '<div class="wnd-loading" style="position:absolute;top:0;left:0;right:0;bottom:0;z-index:999;background:#FFF;opacity:0.7">' + loading_el + '</div>');
    } else {
        wnd_remove(el + ' .wnd-loading');
        container.style.position = 'initial';
    }
}

/**
 * Json 对象转 FormData
 **/
function object_to_formdata(data) {
    let formData = new FormData();
    for (var key in data) {
        formData.append(key, data[key]);
    }

    return formData;
}

// 按需加载 wnd-vue-form.js 并渲染表达
function wnd_render_form(container, form_json) {
    if ('function' != typeof _wnd_render_form) {
        let url = wnd.plugin_url + '/static/js/wnd-vue-form.js?ver=' + wnd.ver;
        wnd_load_script(url, function() {
            _wnd_render_form(container, form_json)
        });
    } else {
        _wnd_render_form(container, form_json);
    }
}

// 按需加载 wnd-vue-form.js 并渲染表达
function wnd_render_filter(container, filter_json) {
    if ('function' != typeof _wnd_render_filter) {
        let url = wnd.plugin_url + '/static/js/wnd-vue-filter.js?ver=' + wnd.ver;
        wnd_load_script(url, function() {
            _wnd_render_filter(container, filter_json);
        });
    } else {
        _wnd_render_filter(container, filter_json);
    }
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
        url: wnd_jsonget_api + '/' + jsonget + lang_query,
        params: param,
    }).then(function(response) {
        if (callback) {
            window[callback](response.data);
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
    container_el = document.querySelector(container);
    funTransitionHeight(container_el);

    wnd_loading(container);

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

            wnd_loading(container, true);

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
                funTransitionHeight(container_el, trs_time);
            }

            if (callback) {
                window[callback](response.data);
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
    // 添加请求拦截器
    wnd_alert_msg('');

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
                window[callback](response.data);
            }
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
    wnd_inner_html('#modal .modal-entry', content);

    funTransitionHeight(document.querySelector('#modal .modal-entry'), trs_time);
}

// 直接弹出消息
function wnd_alert_msg(msg, time = 0) {
    wnd_reset_modal();

    // 移除动画效果
    funTransitionHeight(document.querySelector('#modal .modal-entry'));

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
        // 动画效果需要
        modal_entry.style.height = '0';
    } else {
        wnd_append('body',
            `<div id="modal" class="modal">
            <div class="modal-background"></div>
            <div class="modal-content">
            <div class="modal-entry content"></div>
            </div>
            <button class="modal-close is-large" aria-label="close"></button>
            </div>`
        );
    }
}

/**
 * 常规HTML表单提交:绑定在submit button
 * 常规表单不具备文件上传能力，主要用户构建前端各类简单表单按钮，以避免在常规页面中 Vue 动态加载造成的页面抖动 
 * */
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

    for (var i = 0; i < form.elements.length; i++) {
        if (form.elements[i].value === '' && form.elements[i].hasAttribute('required')) {
            form.elements[i].classList.add('is-danger');
            wnd_form_msg(form, wnd.msg.required, 'is-danger');
            return false;
        }
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
            form_info = handle_response(response.data, route);
            wnd_form_msg(form, form_info.msg, form_info.msg_class);
            if (response.data.status != 3 && response.data.status != 4 && response.data.status != 6) {
                button.classList.remove('is-loading');
            }
        });
}

function wnd_form_msg(form, msg, msg_class) {
    let el = form.querySelector('.form-message');
    el.style.display = '';
    el.classList.add('message', msg_class);
    el.innerHTML = '<div class="message-body">' + msg + '</div>';

    modal_entry = document.querySelector('#modal .modal-entry');
    if (modal_entry) {
        funTransitionHeight(modal_entry, trs_time);
    }
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
            wnd_alert_msg(response.msg, 1);
            break;

            // 下载类
        case 6:
            form_info.msg = wnd.msg.downloading;
            window.location = response.data.redirect_to;
            break;

            // 以响应数据替换当前表单
        case 7:
            wnd_alert_modal(response.data);
            break;

            // 默认
        default:
            // wnd_ajax_form_msg(form_id, response.msg, style);
            break;
    }

    return form_info;
}


/**
 *@since 2019.02.09 发送手机或邮箱验证码
 *@param object button jquery object
 */
function wnd_send_code(button) {
    button.classList.add('is-loading');
    let form = button.closest('form');
    let device = form.querySelector('input[name=\'_user_user_email\']') || form.querySelector('input[name=\'phone\']');
    let device_value = device.value || '';
    if (!device_value) {
        button.classList.remove('is-loading');
        wnd_form_msg(form, wnd.msg.required, 'is-warning');
        return false;
    }

    let data = button.dataset;
    data.device = device_value;
    formData = object_to_formdata(data);

    axios({
        url: wnd_action_api + "/" + data.action + lang_query,
        method: 'POST',
        data: formData,
    }).then(function(response) {
        if (response.data.status <= 0) {
            var style = "is-danger";
        } else {
            var style = "is-success";
            button.disabled = true;
            button.textContent = wnd.msg.send_successfully;

            // 定时器
            var time = data.interval;
            var timer = null;
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
    });
};

/**
 * 流量统计
 */
function wnd_update_views(post_id, interval = 3600) {
    if ('function' != typeof _wnd_update_views) {
        wnd_load_script(wnd.plugin_url + '/static/js/wnd-update-views.js?ver=' + wnd.ver, function() {
            _wnd_update_views(post_id, interval);
        });
    } else {
        _wnd_update_views(post_id, interval);
    }
}

/**
 *@since 2019.07.02 非表单形式发送ajax请求
 */
function wnd_ajax_click(link) {
    var can_click_ajax_link = true;
    // 是否在弹窗中操作
    var is_in_modal = link.closest(".modal.is-active") ? true : false;

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
    var is_cancel = 0 != link.dataset.is_cancel;
    var action = is_cancel ? link.dataset.cancel : link.dataset.action;
    var args = JSON.parse(link.dataset.args);
    args._wnd_sign = link.dataset.sign;
    args._ajax_nonce = is_cancel ? link.dataset.cancel_nonce : link.dataset.action_nonce;

    axios({
        url: wnd_action_api + "/" + action + lang_query,
        method: 'POST',
        data: object_to_formdata(args),
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
                if (!is_in_modal) {
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

    var height = window.getComputedStyle(element).height;
    element.style.transition = 'none'; // 本行2015-05-20新增，mac Safari下，貌似auto也会触发transition, 故要none下~
    element.style.height = 'auto';
    var targetHeight = window.getComputedStyle(element).height;
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
        wnd_append('body',
            '<div id="wnd-side-container"></div>' +
            '<div id="wnd-side-background" class="modal" style="z-index:31;">' +
            '<div class="modal-background"></div>' +
            '</div>'
        );
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
});

/**
 *@since 0.9.25
 *文档加载完成后执行
 */
// window.onload = function() {}