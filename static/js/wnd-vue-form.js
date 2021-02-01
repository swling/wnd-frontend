var wnd_vue_form = true;
var general_input_fields = ['text', 'number', 'email', 'password', 'url', 'color', 'date', 'range', 'tel'];

/**
 *@since 0.9.25
 *Vue 根据 Json 动态渲染表单
 */
function _wnd_render_form(container, form_json) {

    new Vue({
        el: container,
        // 模板将替换挂载点内的内容
        template: get_form_template(),
        // 数据 合并数据，并进行深拷贝，以保留原生传参 form_json 不随 data 变动
        data: {
            form: JSON.parse(JSON.stringify(form_json)),
        },

        // 事件处理
        methods: {
            // 提交
            submit: function() {
                this.form.submit.attrs.class = form_json.submit.attrs.class + ' is-loading';

                // 表单检查
                var can_submit = true;
                this.form.fields.forEach(function(field, index) {
                    // 组字段
                    if ('group' == field.type) {
                        field.fields.forEach(function(child_field, child_index) {
                            if (child_field.required && !child_field.value && !child_field.selected && !child_field.checked) {
                                child_field.class = form_json.fields[index].fields[child_index].class + ' is-danger';
                                can_submit = false;
                                return false; //此处为退出 forEach 循环，而非阻止提交
                            }
                        });
                    };

                    // 单个
                    if (field.required && !field.value && !field.selected && !field.checked) {
                        field.class = form_json.fields[index].class + ' is-danger';
                        can_submit = false;
                        return false; //此处为退出 forEach 循环，而非阻止提交
                    };
                });

                if (!can_submit) {
                    this.form.submit.attrs.class = form_json.submit.attrs.class;
                    this.form.message.message = wnd.msg.required;
                    this.form.message.attrs.class = form_json.message.attrs.class + ' is-danger';
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
                if (('get' == this.form.attrs.method)) {
                    if (response.status >= 1) {
                        wnd_alert_modal(response.data);
                    } else {
                        wnd_alert_modal(response.msg);
                    }
                    this.form.submit.attrs.class = form_json.submit.attrs.class;
                    return;
                }

                // POST
                this.form.message.message = response.msg;
                this.form.message.attrs.class = form_json.message.attrs.class + (response.status <= 0 ? ' is-danger' : ' is-success');
                this.form.submit.attrs.class = form_json.submit.attrs.class;
                switch (response.status) {
                    // 常规类，展示后端提示信息，状态 8 表示禁用提交按钮 
                    case 1:
                    case 8:
                        break;

                        //更新类
                    case 2:
                        this.form.message.message = wnd.msg.submit_successfully + '<a href="' + response.data.url + '" target="_blank">&nbsp;' + wnd.msg.view + '</a>';
                        break;

                        // 跳转类
                    case 3:
                        this.form.message.message = wnd.msg.waiting;
                        window.location.href = response.data.redirect_to;
                        return;

                        // 刷新当前页面
                    case 4:
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
                        wnd_alert_msg(response.msg, 1);
                        break;

                        // 下载类
                    case 6:
                        this.form.message.message = wnd.msg.downloading;
                        window.location.href = response.data.redirect_to;
                        return;

                        // 以响应数据替换当前表单
                    case 7:
                        // $("#" + form_id).replaceWith(response.data);
                        break;

                        // 默认
                    default:
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

// 定义 Form 输出模板
function get_form_template() {
    // 表单头
    t = '<form v-bind="form.attrs">';
    t += '<h3 v-bind="form.title.attrs" v-html="form.title.title"></h3>';
    t += '<div v-bind="form.message.attrs" v-show="form.message.message"><div class="message-body" v-html="form.message.message"></div></div>';

    // 循环字段数据，调用对应字段组件
    t += get_fields_template('form.fields', 'field');

    // 提交按钮
    t += '<div class="field is-grouped is-grouped-centered">';
    t += '<button v-bind="form.submit.attrs" @click="submit" v-text="form.submit.text"></button>';
    t += '</div>';

    // 表尾
    t += '</form>';

    return t;
}

// 循环字段数据，调用对应字段组件 fileds_vn, field_vn 分别为字段数据合集及单个字段数据的变量名称，需要匹配对应模板中的定义
function get_fields_template(fileds_vn, field_vn) {
    t = '<template v-for="(' + field_vn + ', index) in ' + fileds_vn + '" :key="index">';
    t += '<wnd-radio v-if="\'radio\' == ' + field_vn + '.type || \'checkbox\' == ' + field_vn + '.type" :field="' + field_vn + '" :checked.sync="' + field_vn + '.checked"></wnd-radio>'
    t += '<wnd-file-upload v-else-if="\'file_upload\' == ' + field_vn + '.type" :field="' + field_vn + '"></wnd-file-upload>'
    t += '<wnd-image-upload v-else-if="\'image_upload\' == ' + field_vn + '.type" :field="' + field_vn + '"></wnd-image-upload>'
    t += '<wnd-select v-else-if="\'select\' == ' + field_vn + '.type" :field="' + field_vn + '" :selected.sync="' + field_vn + '.selected"></wnd-select>'
    t += '<wnd-text v-else-if="general_input_fields.includes(' + field_vn + '.type)" :field="' + field_vn + '" :value.sync="' + field_vn + '.value"></wnd-text>'
    t += '<component v-else :is="\'wnd-\'+' + field_vn + '.type" :field="' + field_vn + '" :value.sync="' + field_vn + '.value"></component>'
    t += '</template>';

    return t;
}

// 混入对象（有点类似 PHP Class 中的特性）
var field_mixin = {
    methods: {
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

            _field["class"] = (general_input_fields.includes(field.type) ? 'input' : field.type) + ' ' + (_field["class"] || '');
            delete _field['icon_left'];
            delete _field['icon_right'];
            delete _field['addon_left'];
            delete _field['addon_right'];
            delete _field['options'];
            delete _field['label'];

            delete _field['value'];
            delete _field['field'];

            if ('file_upload' == _field['type']) {
                _field['type'] = 'file';
            }

            // 自定义 data-* 属性应直接获取使用，无需渲染 DOM
            delete _field['data'];

            return _field;
        },

        is_checked: function(field, value) {
            return field.checked == value ? true : false;
        },

        // 生成随机字符
        random: function() {
            return Math.random().toString(36).substring(2);
        },

        input: function(value) {
            this.$emit('update:value', value);
        },

        checked: function(checked) {
            this.$emit('update:checked', checked);
        },

        selected: function(selected) {
            this.$emit('update:selected', selected);
        },
    }
}

// 文件上传混入
var upload_mixin = {
    methods: {
        upload: function(e) {
            let files = e.target.files
            let form_data = new FormData()

            // 循环构造自定义 data-* 属性
            if (this.field.data) {
                for (const key in this.field.data) {
                    if (Object.hasOwnProperty.call(this.field.data, key)) {
                        form_data.append(key, this.field.data[key]);
                    }
                }
            }

            // 获取文件，支持多文件上传（文件数据 name 统一设置为 wnd_file[] 这是与后端处理程序的约定 ）
            for (var i = 0; i < files.length; ++i) {
                form_data.set('wnd_file[' + i + ']', files[i]);
            }

            // WP Nonce
            form_data.set('_ajax_nonce', this.field.data.upload_nonce);

            _this = this;
            axios({
                url: wnd_action_api + "/wnd_upload_file" + lang_query,
                method: 'post',
                data: form_data,
                headers: {},
                //原生获取上传进度的事件
                onUploadProgress: function(progressEvent) {
                    _this.field.complete = (progressEvent.loaded / progressEvent.total * 100 | 0);
                }
            }).then(function(response) {
                console.log(response.data);
                for (var i = 0, n = response.data.length; i < n; i++) {
                    // 单个图片
                    if ('image_upload' == _this.field.type) {
                        _this.field.thumbnail = response.data[i].data.thumbnail;

                        // 单个文件
                    } else if ('file_upload' == _this.field.type) {

                        // 图片相册
                    } else if ('gallery' == _this.field.type) {

                    }
                }

            }).catch(err => {
                console.log(err);
            })
        },
    },
}

// 常规 input 组件
Vue.component('wnd-text', {
    mixins: [field_mixin],
    props: ['field', 'value'],
    template: '<div :class="get_input_field_class(field)">' +
        '<label v-if="field.label" class="label">{{field.label}}<span v-if="field.required" class="required">*</span></label>' +
        '<div v-if="field.addon_left" class="control" v-html="field.addon_left"></div>' +
        '<div :class="get_input_control_class(field)">' +
        '<input v-bind="parse_input_attr(field)" v-model="value" @input="input(value)"/>' +
        '<span v-if="field.icon_left" class="icon is-left"  v-html="field.icon_left"></span>' +
        '<span v-if="field.icon_right"  class="icon is-right" v-html="field.icon_right"></span>' +
        '</div>' +
        '<div v-if="field.addon_right" class="control" v-html="field.addon_right"></div>' +
        '</div>',
});

// hidden 组件
Vue.component('wnd-hidden', {
    mixins: [field_mixin],
    props: ['field', 'value'],
    template: '<div><input v-bind="parse_input_attr(field)" v-model="value" /></div>',
});

// Html 组件
Vue.component('wnd-html', {
    props: ['value'],
    template: '<div class="vue-html-wrap field" v-html="value"></div>',
});

// Radio / checkbox 组件
Vue.component('wnd-radio', {
    mixins: [field_mixin],
    props: ['field'],
    template: '<div :class="get_input_field_class(field)">' +
        '<template v-for="(radio_value, radio_label) in field.options">' +
        '<input :id="field.name + radio_value" v-bind="parse_input_attr(field)" :value="radio_value" :name="field.name" :checked="is_checked(field, radio_value)" @change="checked(radio_value)">' +
        '<label :for="field.name + radio_value">{{radio_label}}</label>' +
        '</template>' +
        '</div>',
});

// Textarea 组件
Vue.component('wnd-textarea', {
    mixins: [field_mixin],
    props: ['field', 'value'],
    template: '<div :class="get_input_field_class(field)">' +
        '<label v-if="field.label" class="label">{{field.label}}<span v-if="field.required" class="required">*</span></label>' +
        '<textarea v-html="value" v-bind="parse_input_attr(field)"></textarea>' +
        '</div>',
});

// Group 组字段组件
Vue.component('wnd-group', {
    mixins: [field_mixin],
    props: ['field'],
    template: '<div v-bind="field.attrs">' + get_fields_template('field.fields', 'child_field') + '</div>',
});

// 文件上传字段
Vue.component('wnd-file-upload', {
    mixins: [upload_mixin],
    props: ['field'],
    template: '<div :id="field.id" :class="field.class">' +
        '<div class="field"><div class="ajax-message"></div></div>' +
        '<div class="columns is-mobile is-vcentered">' +

        '<div class="column">' +
        '<div class="file has-name is-fullwidth">' +
        '<label class="file-label">' +
        '<input type="file" class="file file-input" :name="field.name" @change="upload">' +
        '<span class="file-cta">' +
        '<span class="file-icon"><i class="fa fa-upload"></i></span>' +
        '<span class="file-label">{{field.label}}</span>' +
        '</span>' +
        '<span class="file-name" v-html="field.file_name"></span>' +
        '</label>' +
        '</div>' +
        '</div>' +

        '<div v-if="field.delete_button" class="column is-narrow">' +
        '<a class="delete" :data-id="field.id" :data-file_id="field.file_id"></a>' +
        '</div>' +

        '</div>' +
        '</div>',
});

// 单个图像上传字段
Vue.component('wnd-image-upload', {
    mixins: [upload_mixin],
    props: ['field'],
    template: '<div :id="field.id" :class="field.class">' +
        '<div v-if="field.complete" class="field"><progress class="progress is-primary" :value="field.complete" max="100"></progress></div>' +

        '<div class="field">' +
        '<label class="label">' +
        '<div>{{field.label}}<span v-if="field.required && field.label" class="required">*</span></div>' +
        '<a><img class="thumbnail" :src="field.thumbnail" :height="field.thumbnail_size.height" :width="field.thumbnail_size.width"></a>' +
        '<div class="file"><input type="file" class="file-input" :name="field.name" @change="upload"></div>' +
        '</label>' +
        '<a v-if="field.delete_button" class="delete" :data-id="field.id" :data-file_id="field.file_id"></a>' +
        '</div>' +

        '</div>',
});