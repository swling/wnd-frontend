var wnd_vue_form = true;
var general_input_fields = ['text', 'number', 'email', 'password', 'url', 'color', 'date', 'range', 'tel'];

/**
 *@since 0.9.25
 *Vue 根据 Json 动态渲染表单
 */
function _wnd_render_form(container, form_json) {
    // 数据 合并数据，并进行深拷贝，以保留原生传参 form_json 不随 data 变动
    let form = JSON.parse(JSON.stringify(form_json));
    let parent = document.querySelector(container).parentNode;

    new Vue({
        el: container,
        template: get_form_template(form),
        data: {
            form: form,
        },
        methods: {
            has_addon: function(field) {
                return (field.addon_left || field.addon_right);
            },

            has_icon: function(field) {
                return (field.icon_left || field.icon_right);
            },

            get_field_class: function(field) {
                return this.has_addon(field) ? 'field has-addons' : 'field';
            },

            get_control_class: function(field) {
                var el_class = 'control';
                el_class += this.has_addon(field) ? ' is-expanded' : '';
                el_class += field.icon_left ? ' has-icons-left' : '';
                el_class += field.icon_right ? ' has-icons-right' : '';
                return el_class;
            },

            get_field_id: function(field, index) {
                return field.id || (this.form.attrs.id + '-' + index);
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

            click_target(selector) {
                console.log(selector);
                document.querySelector(selector).click();
            },

            change: function(field) {
                field.class = field.class.replace('is-danger', '');
                field.help.class = field.help.class.replace('is-danger', '');
                field.help.text = field.help.text.replace(' ' + wnd.msg.required, '');
                this.$nextTick(function() {
                    funTransitionHeight(parent);
                });
            },

            // 文件上传
            upload: function(e, field) {
                let files = e.target.files;
                let form_data = new FormData()

                // 循环构造自定义 data-* 属性
                if (field.data) {
                    for (const key in field.data) {
                        if (Object.hasOwnProperty.call(field.data, key)) {
                            form_data.append(key, field.data[key]);
                        }
                    }
                }

                // 获取文件，支持多文件上传（文件数据 name 统一设置为 wnd_file[] 这是与后端处理程序的约定 ）
                for (var i = 0; i < files.length; ++i) {
                    form_data.set('wnd_file[' + i + ']', files[i]);
                }

                // WP Nonce
                form_data.set('_ajax_nonce', field.data.upload_nonce);

                // _this = this;
                axios({
                    url: wnd_action_api + "/wnd_upload_file" + lang_query,
                    method: 'post',
                    data: form_data,
                    headers: {},
                    //原生获取上传进度的事件
                    onUploadProgress: function(progressEvent) {
                        field.complete = (progressEvent.loaded / progressEvent.total * 100 | 0);
                    }
                }).then(function(response) {
                    if (response.data.status <= 0) {
                        field.help.text = response.data.msg;
                        field.help.class = 'is-danger';
                        return false;
                    }

                    for (var i = 0, n = response.data.length; i < n; i++) {
                        field.thumbnail = response.data[i].data.thumbnail;
                        field.file_id = response.data[i].data.id;
                        field.file_name = wnd.msg.upload_successfully + '&nbsp<a href="' + response.data[i].data.url + '" target="_blank">' + wnd.msg.view + '</a>';

                        // 单个图片
                        if ('image_upload' == field.type) {

                            // 单个文件
                        } else if ('file_upload' == field.type) {

                            // 图片相册
                        } else if ('gallery' == field.type) {

                        }
                    }

                }).catch(err => {
                    console.log(err);
                })
            },
            // 删除文件
            delete_file: function(field, index) {
                if (!field.file_id) {
                    field.file_name = 'Error';
                    return false;
                }

                var data = new FormData();
                data.append('file_id', field.file_id);
                data.append('meta_key', field.data.meta_key);
                data.append('_ajax_nonce', field.data.delete_nonce);
                axios({
                    url: wnd_action_api + "/wnd_delete_file" + lang_query,
                    method: 'post',
                    data: data,
                }).then(function(response) {
                    field.thumbnail = form_json.fields[index].default_thumbnail;
                    field.file_name = wnd.msg.deleted;
                    field.file_id = 0;

                    // 单个图片
                    if ('image_upload' == field.type) {

                        // 单个文件
                    } else if ('file_upload' == field.type) {

                        // 图片相册
                    } else if ('gallery' == field.type) {

                    }

                }).catch(err => {
                    console.log(err);
                })
            },

            // 提交
            submit: function() {
                this.form.submit.attrs.class = form_json.submit.attrs.class + ' is-loading';

                // 表单检查
                var can_submit = true;
                this.form.fields.forEach(function(field, index) {
                    if (field.required && !field.value && !field.selected && !field.checked) {
                        field.class = form_json.fields[index].class + ' is-danger';
                        field.help.class = form_json.fields[index].help.class + ' is-danger';
                        field.help.text = form_json.fields[index].help.text + ' ' + wnd.msg.required;
                        can_submit = false;
                        return false; //此处为退出 forEach 循环，而非阻止提交
                    };
                });

                if (!can_submit) {
                    this.form.submit.attrs.class = form_json.submit.attrs.class;
                    // this.form.message.message = wnd.msg.required;
                    // this.form.message.attrs.class = form_json.message.attrs.class + ' is-danger';

                    this.$nextTick(function() {
                        funTransitionHeight(parent);
                    });

                    return false;
                }

                // Ajax 请求
                var _this = this;
                var form = document.querySelector('#' + this.form.attrs.id);
                var data = new FormData(form);

                // GET 请求参数
                var params = {};
                if ('get' == this.form.attrs.method) {
                    data.forEach((value, key) => {
                        if (!Reflect.has(params, key)) {
                            params[key] = value;
                            return;
                        }
                        if (!Array.isArray(params[key])) {
                            params[key] = [params[key]];
                        }
                        params[key].push(value);
                    });
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
                        info = handle_response(response.data, _this.form.attrs.route);
                        _this.form.message.message = info.msg;
                        _this.form.message.attrs.class = form_json.message.attrs.class + ' ' + info.msg_class;
                        _this.form.submit.attrs.class = form_json.submit.attrs.class;
                        _this.$nextTick(function() {
                            funTransitionHeight(parent);
                        });
                    })
                    .catch(function(error) { // 请求失败处理
                        console.log(error);
                    });
            },
        },
        mounted() {
            funTransitionHeight(parent, trs_time);
        },
        // 计算
        computed: {},
        // 侦听器
        watch: {},
    });

    // 定义 Form 输出模板
    function get_form_template(form_json) {
        // 表单头
        let t = '<form v-bind="form.attrs">';
        t += '<h3 v-show="form.title.title" v-bind="form.title.attrs" v-html="form.title.title"></h3>';
        t += '<div v-bind="form.message.attrs" v-show="form.message.message"><div class="message-body" v-html="form.message.message"></div></div>';

        // 循环字段数据，调用对应字段组件
        t += get_fields_template(form_json);

        // 提交按钮
        t += '<div v-if="form.submit.text" class="field is-grouped is-grouped-centered">';
        t += '<button type="button" v-bind="form.submit.attrs" @click="submit" v-text="form.submit.text"></button>';
        t += '</div>';

        // 表尾
        t += '</form>';

        return t;
    }

    // 选择并构建字段模板
    function get_fields_template(form_json) {
        let t = '';
        for (let index = 0; index < form_json.fields.length; index++) {
            // 需要与定义数据匹配
            let field = form_json.fields[index];

            // 特别注意：此处定义的是 Vue 模板字符串，而非实际数据，Vue 将据此字符串渲染为具体值
            let field_vn = 'form.fields[' + index + ']';

            if ('html' == field.type) {
                t += field.value;
            } else if (general_input_fields.includes(field.type)) {
                t += build_text(field_vn, index);
            } else if ('radio' == field.type || 'checkbox' == field.type) {
                t += build_radio(field_vn, index);
            } else if ('hidden' == field.type) {
                t += '<input v-bind="parse_input_attr(' + field_vn + ')" v-model="' + field_vn + '.value" />';
            } else if ('textarea' == field.type) {
                t += build_textarea(field_vn, index);
            } else if ('image_upload' == field.type) {
                t += build_image_upload(field_vn, index);
            } else if ('file_upload' == field.type) {
                t += build_file_upload(field_vn, index);
            } else if ('select' == field.type) {
                t += build_select(field_vn, index);
            }
        }
        return t;
    }

    // 常规 input 组件
    function build_text(field, index) {
        return '<div :class="get_field_class(' + field + ')">' +
            '<label v-if="' + field + '.label" class="label">{{' + field + '.label}}<span v-if="' + field + '.required" class="required">*</span></label>' +
            '<div v-if="' + field + '.addon_left" class="control" v-html="' + field + '.addon_left"></div>' +
            '<div :class="get_control_class(' + field + ')">' +
            '<input v-bind="parse_input_attr(' + field + ')" v-model="' + field + '.value" @change="change(' + field + ')" @keyup.enter="submit"/>' +
            '<span v-if="' + field + '.icon_left" class="icon is-left"  v-html="' + field + '.icon_left"></span>' +
            '<span v-if="' + field + '.icon_right"  class="icon is-right" v-html="' + field + '.icon_right"></span>' +
            '</div>' +
            '<div v-if="' + field + '.addon_right" class="control" v-html="' + field + '.addon_right"></div>' +
            '<p v-if="!has_addon(' + field + ')" v-show="' + field + '.help.text" class="help" :class="' + field + '.help.class">{{' + field + '.help.text}}</p>' +
            '</div>';
    }

    // Radio / checkbox 组件
    function build_radio(field, index) {
        return '<div :class="get_field_class(' + field + ')">' +
            '<div :class="get_control_class(' + field + ')">' +
            '<template v-for="(radio_value, radio_label) in ' + field + '.options">' +
            '<label :class="' + field + '.type">' +
            '<input v-bind="parse_input_attr(' + field + ')" :value="radio_value" v-model="' + field + '.checked" @click="change(' + field + ')">' +
            '{{radio_label}}</label>' +
            '</template>' +
            '</div>' +
            '<p v-show="' + field + '.help.text" class="help" :class="' + field + '.help.class">{{' + field + '.help.text}}</p>' +
            '</div>';
    }

    // 下拉 Select 组件
    function build_select(field, index) {
        return '<div class="field">' +
            '<label v-if="' + field + '.label" class="label">{{' + field + '.label}}<span v-if="' + field + '.required" class="required">*</span></label>' +
            '<div class="control">' +
            '<div class="select" :class="' + field + '.class" @change="change(' + field + ')">' +
            '<select v-bind="parse_input_attr(' + field + ')" v-model="' + field + '.selected">' +
            '<template v-for="(value, name) in ' + field + '.options">' +
            '<option :value="value">{{name}}</option>' +
            '</template>' +
            '</select>' +
            '</div>' +
            '</div>' +
            '<p v-show="' + field + '.help.text" class="help" :class="' + field + '.help.class">{{' + field + '.help.text}}</p>' +
            '</div>';
    }

    // Textarea 组件
    function build_textarea(field, index) {
        return '<div :class="get_field_class(' + field + ')">' +
            '<label v-if="' + field + '.label" class="label">{{' + field + '.label}}<span v-if="' + field + '.required" class="required">*</span></label>' +
            '<textarea v-html="' + field + '.value" v-bind="parse_input_attr(' + field + ')" @change="change(' + field + ')"></textarea>' +
            '<p v-show="' + field + '.help.text" class="help" :class="' + field + '.help.class">{{' + field + '.help.text}}</p>' +
            '</div>';
    };

    // 文件上传字段
    function build_file_upload(field, index) {
        return '<div :id="get_field_id(' + field + ',' + index + ')" class="field" :class="' + field + '.class">' +
            '<div class="field"><div class="ajax-message"></div></div>' +
            '<div class="columns is-mobile is-vcentered">' +

            '<div class="column">' +
            '<div class="file has-name is-fullwidth">' +
            '<label class="file-label">' +
            '<input type="file" class="file file-input" :name="' + field + '.name" @change="upload($event,' + field + ')">' +
            '<span class="file-cta">' +
            '<span class="file-icon"><i class="fa fa-upload"></i></span>' +
            '<span class="file-label">{{' + field + '.label}}</span>' +
            '</span>' +
            '<span class="file-name" v-html="' + field + '.file_name"></span>' +
            '</label>' +
            '</div>' +
            '</div>' +

            '<div v-show="' + field + '.delete_button && ' + field + '.file_id" class="column is-narrow">' +
            '<a class="delete" @click="delete_file(' + field + ',' + index + ')"></a>' +
            '</div>' +

            '</div>' +
            '</div>';
    }

    // 单个图像上传字段
    function build_image_upload(field, index) {
        return '<div :id="get_field_id(' + field + ',' + index + ')" class="field" :class="' + field + '.class">' +
            '<div v-if="' + field + '.complete" class="' + field + '">' +
            '<progress class="progress is-primary" :value="' + field + '.complete" max="100"></progress>' +
            '</div>' +

            '<label class="label">{{' + field + '.label}}<span v-if="' + field + '.required && ' + field + '.label" class="required">*</span></label>' +
            '<a @click="click_target(\'#\'+get_field_id(' + field + ',' + index + ') + \' input[type=file]\')">' +
            '<img class="thumbnail" :src="' + field + '.thumbnail" :height="' + field + '.thumbnail_size.height" :width="' + field + '.thumbnail_size.width">' +
            '</a>' +
            '<a v-show="' + field + '.delete_button && ' + field + '.file_id" class="delete" @click="delete_file(' + field + ',' + index + ')"></a>' +
            '<p v-show="' + field + '.help.text" class="help" :class="' + field + '.help.class">{{' + field + '.help.text}}</p>' +
            '<div class="file"><input type="file" class="file file-input" accept="image/*" :name="' + field + '.name" @change="upload($event,' + field + ')"></div>' +
            '</div>';
    }
}