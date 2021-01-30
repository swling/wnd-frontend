var wnd_vue_form = true;

/**
 *@since 0.9.25
 *Vue 根据 Json 动态渲染表单
 */
function _wnd_render_form(container, form_json) {
    // 混入对象（有点类似 PHP Class 中的特性）
    var wnd_input_field_mixin = {
        props: ['attr', 'value'],
        template: '<input v-bind="attr" v-model="value" @input="input(value)"/>',
        methods: {
            input: function(value) {
                this.$emit('update:wnd_value', value);
            }
        }
    }

    // 常规 input 组件
    Vue.component('wnd-text', {
        mixins: [wnd_input_field_mixin]
    });

    // Html 组件
    Vue.component('wnd-html', {
        props: ['value'],
        template: '<div v-html="value"></div>',
    });

    // Radio 组件
    Vue.component('wnd-radio', {
        props: ['value'],
        template: '<div v-html="value"></div>',
    });

    // 定义 Form 输出模板
    // 表单头
    form_template = '<form v-bind="form.attrs">';
    form_template += '<h3 v-bind="form.title.attrs" v-html="form.title.title"></h3>';
    form_template += '<div v-bind="form.message.attrs" v-show="form.message.message"><div class="message-body" v-html="form.message.message"></div></div>';

    // 循环字段
    form_template += '<template v-for="(field, index) in form.fields" :key="index">';
    form_template += '<div :class="get_input_field_class(field)">';
    // label
    form_template += '<label v-html="field.label" class="label"></label>';
    // left addon
    form_template += '<div  v-if="field.addon_left" class="control" v-html="field.addon_left"></div>';

    form_template += '<div :class="get_input_control_class(field)">';
    // 通过组件渲染字段
    form_template += '<component :is="get_field_component(field.type)" :attr="parse_input_attr(field)" :value="field.value" :wnd_value.sync="field.value"/>'
    // input icon
    form_template += '<span v-if="field.icon_left" class="icon is-left"  v-html="field.icon_left"></span>';
    form_template += '<span v-if="field.icon_right"  class="icon is-right" v-html="field.icon_right"></span>';
    form_template += '</div>';
    // right addon
    form_template += '<div  v-if="field.addon_right" class="control" v-html="field.addon_right"></div>';

    form_template += '</div>';
    form_template += '</template>';

    // 提交按钮
    form_template += '<div class="field is-grouped is-grouped-centered">';
    form_template += '<button v-bind="form.submit.attrs" @click="submit" v-text="form.submit.text"></button>';
    form_template += '</div>';

    // 表尾
    form_template += '</form>';

    new Vue({
        el: container,
        // 模板将替换挂载点内的内容
        template: form_template,
        // 数据 合并数据，并进行深拷贝，以保留原生传参 form_json 不随 data 变动
        data: {
            form: JSON.parse(JSON.stringify(form_json)),
        },

        // 事件处理
        methods: {
            get_field_component: function(field_type) {
                // 常规 input 
                if (['text', 'number', 'email', 'password', 'url', 'color', 'date', 'range', 'tel', 'hidden'].includes(field_type)) {
                    return 'wnd-text';
                } else {
                    return 'wnd-' + field_type;
                }
            },

            has_input_addon: function(field) {
                return (field.addon_left || field.addon_right);
            },

            has_input_icon: function(field) {
                return (field.icon_left || field.icon_right);
            },

            get_input_field_class: function(field) {
                return this.has_input_addon(field) ? 'field has-addons' : 'field';
            },

            get_input_control_class: function(field) {
                var el_class = 'control';
                el_class += this.has_input_addon(field) ? ' is-expanded' : '';
                el_class += field.icon_left ? ' has-icons-left' : '';
                el_class += field.icon_right ? ' has-icons-right' : '';
                return el_class;
            },

            parse_input_attr: function(field) {
                // 深拷贝 以免影响 data
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

                return _field;
            },

            // 提交
            submit: function() {
                this.form.submit.attrs.class = form_json.submit.attrs.class + " is-loading";

                // 表单检查
                var can_submit = true;
                this.form.fields.forEach(function(filed, index) {
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

                // GET 将表单序列化
                var params = {};
                if ('get' == this.form.attrs.method) {
                    for (var key of data.keys()) {
                        params[key] = data.get(key);
                    }
                }
                axios({
                        method: this.form.attrs.method,
                        url: this.form.attrs.action,
                        // Post
                        data: data,
                        // Get
                        params: params,
                    })
                    .then(function(response) {
                        _this.handle_response(response.data);
                    })
                    .catch(function(error) { // 请求失败处理
                        console.log(error);
                        _this.form.message.message = wnd.msg.system_error;
                        _this.form.message.attrs.class = form_json.message.attrs.class + ' is-danger';
                        _this.form.submit.attrs.class = form_json.submit.attrs.class;
                    });
            },

            // 处理表单提交响应
            handle_response: function(response) {
                /**
                 *@since 0.8.73
                 *GET 提交弹出 UI 模块
                 */
                if (("get" == this.form.attrs.method)) {
                    if (response.status >= 1) {
                        wnd_alert_modal(response.data);
                    } else {
                        wnd_alert_modal(response.msg);
                    }
                    this.form.submit.attrs.class = form_json.submit.attrs.class;
                    return;
                }

                // POST
                switch (response.status) {
                    // 常规类，展示后端提示信息，状态 8 表示禁用提交按钮 
                    case 1:
                    case 8:
                        break;

                        //更新类
                    case 2:
                        this.form.message.message = wnd.msg.submit_successfully + '<a href="' + response.data.url + '" target="_blank">&nbsp;' + wnd.msg.view + '</a>';
                        this.form.message.attrs.class = form_json.message.attrs.class + ' is-success';
                        this.form.submit.attrs.class = form_json.submit.attrs.class;
                        break;

                        // 跳转类
                    case 3:
                        this.form.message.message = wnd.msg.waiting;
                        this.form.message.attrs.class = form_json.message.attrs.class + ' is-success';
                        window.location.href = response.data.redirect_to;
                        return;

                        // 刷新当前页面
                    case 4:
                        this.form.message.message = response.msg;
                        this.form.message.attrs.class = form_json.message.attrs.class + ' is-success';
                        if ("undefined" == typeof response.data || "undefined" == typeof response.data.waiting) {
                            window.location.reload();
                            return;
                        }

                        // 延迟刷新
                        var timer = null;
                        var time = response.data.waiting;

                        this.form.submit.text = wnd.msg.waiting + " " + time;
                        timer = setInterval(function() {
                            if (time <= 0) {
                                clearInterval(timer);
                                wnd_reset_modal();
                                window.location.reload();
                            } else {
                                this.form.submit.text = wnd.msg.waiting + " " + time;
                                time--;
                            }
                        }, 1000);
                        return;

                        // 弹出信息并自动消失
                    case 5:
                        this.form.message.attrs.class = form_json.message.attrs.class + ' is-success';
                        this.form.submit.attrs.class = form_json.submit.attrs.class;
                        wnd_alert_msg(response.msg, 1);
                        break;

                        // 下载类
                    case 6:
                        this.form.message.message = wnd.msg.downloading;
                        this.form.message.attrs.class = form_json.message.attrs.class + ' is-success';
                        this.form.submit.attrs.class = form_json.submit.attrs.class;
                        window.location.href = response.data.redirect_to;
                        return;

                        // 以响应数据替换当前表单
                    case 7:
                        // $("#" + form_id).replaceWith(response.data);
                        break;

                        // 默认
                    default:
                        this.form.message.message = response.msg;
                        this.form.message.attrs.class = form_json.message.attrs.class + (response.status <= 0 ? ' is-danger' : ' is-success');
                        this.form.submit.attrs.class = form_json.submit.attrs.class;
                        break;
                }
            }
        },
        // 计算
        computed: {},
        // 侦听器
        watch: {},
    });
}