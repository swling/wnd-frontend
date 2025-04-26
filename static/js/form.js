/**
 *@since 0.9.25
 *Vue 根据 Json 动态渲染表单
 */
function _wnd_render_form(container, form_json, add_class = '', api_url) {
    let general_input_fields = ['text', 'number', 'email', 'password', 'url', 'color', 'date', 'range', 'tel'];

    /******************************************************* Vue 渲染及交互 **********************************************************/
    // 数据 合并数据，并进行深拷贝，以保留原生传参 form_json 不随 data 变动
    let form = JSON.parse(JSON.stringify(form_json));
    let parent = document.querySelector(container);
    if (add_class) {
        parent.classList.add(add_class);
    }

    let form_option = {
        template: build_form_template(form),
        data() {
            return {
                form: form,
                index: {
                    'captcha': '',
                    'ids': [],
                },
                default_data: {}, // 记录表单默认数据，在提交表单时与现有表单合并，以确保所有字段名均包含在提交数据中，从而通过一致性签名
            }
        },
        // 注册的子组件
        components: {
            ThumbnailCard: ThumbnailCard,
        },
        methods: {
            //HTML转义
            html_encode: function (html) {
                let temp = document.createElement('div');
                temp.innerText = html;
                let output = temp.innerHTML;
                temp = null;
                return output;
            },
            //HTML反转义
            html_decode: function (text) {
                var temp = document.createElement('div');
                temp.innerHTML = text;
                var output = temp.innerText;
                temp = null;
                return output;
            },
            has_addon: function (field) {
                return (field.addon_left || field.addon_right);
            },

            has_icon: function (field) {
                return (field.icon_left || field.icon_right);
            },

            get_field_class: function (field) {
                return this.has_addon(field) ? 'field has-addons' : 'field';
            },

            get_control_class: function (field) {
                let el_class = 'control';
                el_class += this.has_addon(field) ? ' is-expanded' : '';
                el_class += field.icon_left ? ' has-icons-left' : '';
                el_class += field.icon_right ? ' has-icons-right' : '';
                return el_class;
            },

            get_field_id: function (field, index) {
                return field.id || (this.form.attrs.id + '-' + index);
            },

            /**
             * 提取表单数据
             * @since 0.9.36
             * 变量如果不为0，null，undefined，false，都会被处理为true。只要变量有非0的值或是某个对象，数组，字符串，都会认为true
             **/
            get_value: function (field) {
                let value = ['checkbox', 'radio'].includes(field.type) ? [] : '';
                let checked_or_selected = field.checked || field.selected;

                if (field.value) {
                    value = field.value;
                } else if (checked_or_selected) {
                    if ('object' != typeof checked_or_selected || checked_or_selected.length || Object.keys(checked_or_selected).length) {
                        value = field.checked || field.selected;
                    }
                }

                value = ('object' == typeof value) ? Object.values(value) : value;
                return value;
            },
            parse_input_attr: function (field, exclude = []) {
                // 深拷贝 以免影响 data
                let _field = JSON.parse(JSON.stringify(field));

                Object.keys(_field).forEach(item => {
                    if (!_field[item] || '' == _field[item]) {
                        delete _field[item]
                    };
                });

                // 排除指定属性
                exclude.forEach(item => {
                    delete _field[item];
                });

                _field['class'] = (general_input_fields.includes(field.type) ? 'input' : field.type) + ' ' + (_field["class"] || '');
                _field['class'] += ' ' + this.form.size;
                delete _field['icon_left'];
                delete _field['icon_right'];
                delete _field['addon_left'];
                delete _field['addon_right'];
                delete _field['options'];
                delete _field['label'];
                delete _field['help'];

                delete _field['value'];
                delete _field['checked'];
                delete _field['selected'];
                delete _field['options'];

                if ('file_upload' == _field['type']) {
                    _field['type'] = 'file';
                } else if ('select' == _field['type'] || 'select_linked' == _field['type']) {
                    delete _field['type'];
                }

                // 自定义 data-* 属性应直接获取使用，无需渲染 DOM
                delete _field['data'];

                // 字段关联属性
                delete _field['linkage'];

                return _field;
            },

            // 字段是否应该设置disabled属性
            should_be_disabled: function (field, value) {
                // checkbox 选项数量限制
                if ('checkbox' == field.type && field.max > 0) {
                    let data = field.checked;
                    if (data.length >= field.max && !data.includes(value)) {
                        return true;
                    }
                }

                return false;
            },

            change: async function (field, e) {
                // 常规
                field.class = field.class.replace('is-danger', '');
                field.help.class = field.help.class.replace('is-danger', '');
                field.help.text = field.help.text.replace(' ' + wnd.msg.required, '');
            },
            // FormData 转 object
            formdata_to_object: function (form_data) {
                let object = {};
                form_data.forEach((value, key) => {
                    // 单个字段
                    if (!key.includes('[]')) {
                        object[key] = value;
                        return;
                    }

                    /**
                     * 数组字段
                     * - 移除键名 []
                     * - 首次：组成数组数据
                     * - 后续：将值写入数组
                     **/
                    key = key.replace('[]', '');
                    if (!Reflect.has(object, key)) {
                        object[key] = [value];
                        return;
                    } else {
                        object[key].push(value);
                    }
                });

                return object;
            },
            // 提交
            submit: function (e) {
                this.form.submit.attrs.class = form_json.submit.attrs.class + ' is-loading';

                // Captcha 验证提交
                if (this.index.captcha) {
                    let captcha = this.form.fields[this.index.captcha];
                    if (!captcha.value) {
                        wnd_submit_via_captcha(e);
                        return;
                    }
                }

                // 核查必选项
                let can_submit = true;
                for (const [index, field] of this.form.fields.entries()) {
                    if (!field.name || !field.required) {
                        continue;
                    }

                    let require_check = true;
                    let value = this.get_value(field);

                    /**
                     * 检测为空的值是否包含在预设的选项中：如各类【开关】选项默认空值
                     * 此类值，不进行常规必选字段检测
                     * @since 0.9.39.3
                     **/
                    let options = Object.values(field.options);
                    if (options.includes(value)) {
                        continue;
                    }

                    if ('string' == typeof value && !value.length) { // 字符串数据
                        require_check = false;
                    } else if (Array.isArray(value) && value.every(el => !el)) { // 数组数据：均为空值
                        require_check = false;
                    } else if (!value && 0 !== value) { // 布尔值或数值
                        require_check = false;
                    }

                    if (!require_check) {
                        field.class = form_json.fields[index].class + ' is-danger';
                        field.help.class = form_json.fields[index].help.class + ' is-danger';
                        field.help.text = form_json.fields[index].help.text + ' ' + wnd.msg.required;
                        can_submit = false;
                    }
                }

                if (!can_submit) {
                    this.form.submit.attrs.class = form_json.submit.attrs.class;
                    return false;
                }

                /**
                 * Ajax 请求
                 * - 之所以不直接提取从 field 中提取 value 组合对象，因为表单中可能存在动态字段，此类字段增删目前采用的是直接操作dom，无法同步 VUE
                 * - 之所以提取表单数据后，又转化为 json，是为了保持后端数据统一为 json，以确保后期适配 APP、小程序等，与站内请求一致
                 * - 将表单数据与默认表单数据合并的原因在于，表单对未选择的单选，复选字段不会构造数据，从而导致数据键值丢失，无法通过表单签名(_wnd_sign)
                 * - Object.assign 执行浅拷贝，故此需要深拷贝一份 default_data 以防止 this.default_data 被表单数据覆盖，导致修改如取消 checkbox 无效
                 **/
                let form = document.querySelector('#' + this.form.attrs.id);
                let form_data = new FormData(form);
                let default_data = JSON.parse(JSON.stringify(this.default_data));
                let json = Object.assign(default_data, this.formdata_to_object(form_data));

                let params = {};
                if ('get' == this.form.attrs.method.toLowerCase()) {
                    params = json;
                }
                axios({
                    method: this.form.attrs.method,
                    url: this.form.attrs.action,
                    data: json,
                    params: params,
                }).then(response => {
                    info = wnd_handle_response(response.data, this.form.attrs.route, parent);
                    this.form.message.message = (false === info.msg) ? this.form.message.message : info.msg;
                    this.form.message.attrs.class = form_json.message.attrs.class + ' ' + info.msg_class;
                    this.form.submit.attrs.class = form_json.submit.attrs.class;
                    /**
                     * 提交后清除 captcha 以便下次重新验证
                     */
                    if (this.index.captcha) {
                        this.form.fields[this.index.captcha].value = '';
                    }
                }).catch(function (error) { // 请求失败处理
                    console.log(error);
                });
            },
            // 重新请求数据并更新表单
            reload: async function (e) {
                // 重新载入数据
                let res = await axios({ method: 'get', url: api_url });
                this.form = res.data.data.structure;
            }
        },
        created() {
            // 提取表单数据
            for (let index = 0, j = this.form.fields.length; index < j; index++) {
                let field = this.form.fields[index];

                if ('captcha' == field.name && '' == field.value) {
                    this.index.captcha = index;
                    continue;
                }

                if (field.name) {
                    let name = field.name.replace('[]', '');
                    this.default_data[name] = '';
                }

                // 将字段 ID 与 index 索引，便于快速通过 ID 定位到指定字段数据
                if (field.id) {
                    this.index.ids[field.id] = index;
                }
            }
        },
        mounted() {
            // v-html 不支持执行 JavaScript 需要通过封装好的 wnd_inser_html
            wnd_inner_html(`#${this.form.attrs.id} .form-script`, this.form.script);
            funTransitionHeight(parent, trs_time);
        },
        updated() {
            funTransitionHeight(parent, trs_time);
        }
    };

    Vue.createApp(form_option).mount(container);

    /******************************************************* 构造 Vue 模板 **********************************************************/
    function build_form_template(form) {
        // 定义当前函数，以便于通过字符串变量，动态调用内部函数
        let _this = build_form_template;

        function build() {
            return `
<form v-bind="form.attrs">
<div v-if="form.before_html" v-html="form.before_html"></div>
<div class="field" v-show="form.title.title"><h3 v-bind="form.title.attrs" v-html="form.title.title"></h3></div>
<div v-bind="form.message.attrs" class="notification is-light" v-show="form.message.message" v-html="form.message.message"></div>
${build_fields_template(form)}
${build_submit_template(form)}
<div class="form-script"></div>
<div v-if="form.after_html" v-html="form.after_html"></div>
</form>`;
        }

        // 选择并构建字段模板
        function build_fields_template(form) {
            let t = '';
            for (let index = 0; index < form.fields.length; index++) {
                // 需要与定义数据匹配
                let field = form.fields[index];
                // 特别注意：此处定义的是 Vue 模板字符串，而非实际数据，Vue 将据此字符串渲染为具体值
                let field_vn = `form.fields[${index}]`;

                // Horizontal
                if (is_horizontal_field()) {
                    t += `
<div class="field is-horizontal">
<div class="field-label ${form.size}">
<label v-if="${field_vn}.label" class="label is-hidden-mobile"><span v-if="${field_vn}.required" class="required">*</span>{{${field_vn}.label}}</label>
</div>
<div class="field-body">`;
                }

                if ('html' == field.type) {
                    t += field.value;
                } else if (general_input_fields.includes(field.type)) {
                    t += _this.build_text(field_vn, index);
                } else if ('radio' == field.type || 'checkbox' == field.type) {
                    t += _this.build_radio(field_vn, index);
                } else if ('hidden' == field.type) {
                    t += '<input v-bind="parse_input_attr(' + field_vn + ')" v-model="' + field_vn + '.value" />';
                } else {
                    let method = 'build_' + field.type;
                    t += _this[method](field_vn, index);
                }

                // Horizontal
                if (is_horizontal_field()) {
                    t += `</div></div>`
                }

                function is_horizontal_field() {
                    return form.attrs['is-horizontal'] && !['html', 'hidden'].includes(field.type);
                }
            }
            return t;
        }

        function build_submit_template(form) {
            return `
<div v-if="form.submit.text" class="field has-text-centered">
<button type="button" v-bind="form.submit.attrs" @click="submit($event)" class="${form.size}" v-text="form.submit.text"></button>
</div>`;
        }

        build_label = (field, index) => {
            return `<label v-if="!form.attrs['is-horizontal'] && ${field}.label" class="label" :class="form.size"><span v-if="${field}.required" class="required">*</span>{{${field}.label}}</label>`;
        }

        /** 
         * 常规 input 组件：
         * 采用如下方法替换 v-model 旨在实现 HTML 转义呈现 textare 同理
         * :value="html_decode(${field}.value)" @input="${field}.value = html_encode($event.target.value)"
         */
        _this.build_text = (field, index) => {
            return `
<div :class="get_field_class(${field})">
${build_label(field)}
<div v-if="${field}.addon_left" class="control" v-html="${field}.addon_left"></div>
<div :class="get_control_class(${field})">
<input v-bind="parse_input_attr(${field})" :value="html_decode(${field}.value)" @input="${field}.value=html_encode($event.target.value)" @change="change(${field}, $event)" @keypress.enter="submit"/>
<span v-if="${field}.icon_left" class="icon is-left"  v-html="${field}.icon_left"></span>
<span v-if="${field}.icon_right" class="icon is-right" v-html="${field}.icon_right"></span>
</div>
<div v-if="${field}.addon_right" class="control" v-html="${field}.addon_right"></div>
<p v-if="!has_addon(${field})" v-show="${field}.help.text" class="help" :class="${field}.help.class">{{${field}.help.text}}</p>
</div>`;
        }

        // Radio / checkbox 组件
        _this.build_radio = (field, index) => {
            return `
<div :class="get_field_class(${field})">
${build_label(field)}
<div :class="get_control_class(${field})">
<template v-for="(radio_value, radio_label) in ${field}.options">
<label :class="${field}.type">
<input v-bind="parse_input_attr(${field})" :value="radio_value" v-model="${field}.checked" @change="change(${field}, $event)" :disabled="should_be_disabled(${field}, radio_value)">
{{radio_label}}</label>
</template>
</div>
<p v-show="${field}.help.text" class="help" :class="${field}.help.class">{{${field}.help.text}}</p>
</div>`;
        }

        // 下拉 Select 组件
        _this.build_select = (field, index) => {
            return `
<div :class="get_field_class(${field})">
${build_label(field)}
<div v-if="${field}.addon_left" class="control" v-html="${field}.addon_left"></div>
<div :class="get_control_class(${field})">
<div class="select" :class="${field}.class + ' ' + form.size">
<select v-bind="parse_input_attr(${field})" v-model="${field}.selected" @change="change(${field}, $event)">
<option disabled value="">- {{${field}.label}} -</option>
<option v-for="(value, name) in ${field}.options" :value="value">{{name}}</option>
</select>
</div>
<span v-if="${field}.icon_left" class="icon is-left"  v-html="${field}.icon_left"></span>
<span v-if="${field}.icon_right" class="icon is-right" v-html="${field}.icon_right"></span>
</div>
<div v-if="${field}.addon_right" class="control" v-html="${field}.addon_right"></div>
<p v-show="${field}.help.text" class="help" :class="${field}.help.class">{{${field}.help.text}}</p>
</div>`;
        }

        // Textarea 组件
        _this.build_textarea = (field, index) => {
            return `
<div :class="get_field_class(${field})">
${build_label(field)}
<textarea :value="html_decode(${field}.value)" @input="${field}.value = html_encode($event.target.value)" v-bind="parse_input_attr(${field})" @change="change(${field}, $event)"></textarea>
<p v-show="${field}.help.text" class="help" :class="${field}.help.class">{{${field}.help.text}}</p>
</div>`;
        };

        // 单个图像上传字段
        _this.build_image_upload = (field, index) => {
            return `
<div :class="get_field_class(${field})">            
<thumbnail-card 
:img-width="${field}.thumbnail_size.width" 
:img-height="${field}.thumbnail_size.height" 
:crop-width="${field}.data.save_width" 
:crop-height="${field}.data.save_height" 
:post_parent="${field}.data.post_parent" 
:meta_key="${field}.data.meta_key" 
:thumbnail="${field}.thumbnail">
</thumbnail-card>
</div>`;
        }

        return build();
    }
}
