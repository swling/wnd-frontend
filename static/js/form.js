let general_input_fields = ['text', 'number', 'email', 'password', 'url', 'color', 'date', 'range', 'tel'];

// max tags limit
var max_tag_num = ('undefined' != typeof max_tag_num) ? max_tag_num : 3;

/**
 *@since 0.9.25
 *Vue 根据 Json 动态渲染表单
 */
function _wnd_render_form(container, form_json, add_class = '') {
    // 数据 合并数据，并进行深拷贝，以保留原生传参 form_json 不随 data 变动
    let form = JSON.parse(JSON.stringify(form_json));
    let parent = document.querySelector(container);
    if (add_class) {
        parent.classList.add(add_class);
    }

    wnd_inner_html(parent, '<div class="vue-app"></div>');
    new Vue({
        el: container + ' .vue-app',
        template: get_form_template(form),
        data: {
            form: form,
            index: {
                'editor': '',
                'captcha': '',
            }
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
                let el_class = 'control';
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
                delete _field['help'];

                delete _field['value'];
                delete _field['field'];

                if ('file_upload' == _field['type']) {
                    _field['type'] = 'file';
                }

                // 自定义 data-* 属性应直接获取使用，无需渲染 DOM
                delete _field['data'];

                return _field;
            },
            // 富文本编辑器 @link https://doc.wangeditor.com/
            build_editor: function() {
                let _this = this;
                if ('undefined' == typeof wangEditor) {
                    let url = static_path + 'editor/wangEditor.min.js?ver=' + wnd.ver;
                    wnd_load_script(url, function() {
                        build();
                    });
                } else {
                    build();
                }

                function build() {
                    field = _this.form.fields[_this.index.editor];
                    const editor = new wangEditor(`#${_this.form.attrs.id}-${_this.index.editor}`);
                    // Rest Nonce
                    editor.config.uploadImgHeaders = {
                        'X-WP-Nonce': wnd.rest_nonce,
                    }

                    // 配置图片上传
                    editor.config.uploadImgServer = wnd_action_api + '/wnd_upload_file_editor';
                    editor.config.uploadFileName = 'wnd_file[]';
                    editor.config.uploadImgParams = {
                        type: 'wangeditor',
                        post_parent: _this.form.attrs['data-post-id'] || 0,
                        _ajax_nonce: field._ajax_nonce
                    }
                    editor.config.showLinkImg = false;

                    // 配置 onchange 回调函数，将数据同步到 vue 中
                    editor.config.onchange = (newHtml) => {
                        field.value = newHtml
                    }

                    // 精简菜单按钮
                    editor.config.excludeMenus = [
                        'fontSize',
                        'fontName',
                        'indent',
                        'todo',
                        'lineHeight',
                        'emoticon',
                        'video'
                    ]

                    // 其他
                    editor.config.zIndex = 9;
                    // editor.config.height = 500;

                    // 创建编辑器
                    editor.create()

                    // 内容初始化
                    editor.txt.html(field.value);

                    // 编辑器是动态按需加载，需要额外再设置一次高度适应
                    _this.$nextTick(function() {
                        funTransitionHeight(parent, trs_time);
                    });
                }
            },

            click_target(selector) {
                document.querySelector(selector).click();
            },

            change: function(field) {
                field.class = field.class.replace('is-danger', '');
                field.help.class = field.help.class.replace('is-danger', '');
                field.help.text = field.help.text.replace(' ' + wnd.msg.required, '');
                this.$nextTick(function() {
                    funTransitionHeight(parent, trs_time);
                });
            },

            // 文件上传
            upload: function(e, field) {
                let files = e.target.files;
                let form_data = new FormData()

                // 循环构造自定义 data-* 属性
                if (field.data) {
                    for (const key in field.data) {
                        form_data.append(key, field.data[key]);
                    }
                }

                // 获取文件，支持多文件上传（文件数据 name 统一设置为 wnd_file[] 这是与后端处理程序的约定 ）
                for (let i = 0, n = files.length; i < n; i++) {
                    form_data.set('wnd_file[' + i + ']', files[i]);
                }

                // WP Nonce
                form_data.set('_ajax_nonce', field.data.upload_nonce);

                axios({
                    url: wnd_action_api + '/wnd_upload_file',
                    method: 'post',
                    data: form_data,
                    headers: {},
                    //原生获取上传进度的事件
                    onUploadProgress: function(progressEvent) {
                        field.complete = (progressEvent.loaded / progressEvent.total * 100 | 0);
                    }
                }).then(response => {
                    if (response.data.status <= 0) {
                        field.help.text = response.data.msg;
                        field.help.class = 'is-danger';
                        return false;
                    }

                    for (let i = 0, n = response.data.length; i < n; i++) {
                        field.thumbnail = response.data[i].data.thumbnail;
                        field.file_id = response.data[i].data.id;
                        field.file_name = wnd.msg.upload_successfully + '&nbsp<a href="' + response.data[i].data.url + '" target="_blank">' + wnd.msg.view + '</a>';
                        this.form.message.message = wnd.msg.upload_successfully;

                        // 单个图片
                        if ('image_upload' == field.type) {

                            // 单个文件
                        } else if ('file_upload' == field.type) {

                            // 图片相册
                        } else if ('gallery' == field.type) {

                        }
                    }

                    this.$nextTick(function() {
                        funTransitionHeight(parent, trs_time);
                    });
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

                let data = new FormData();
                data.append('file_id', field.file_id);
                data.append('meta_key', field.data.meta_key);
                data.append('_ajax_nonce', field.data.delete_nonce);
                axios({
                    url: wnd_action_api + '/wnd_delete_file',
                    method: 'post',
                    data: data,
                }).then(response => {
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
            // 根据当前输入查询已有 tags
            suggest_tags: function(text, index) {
                let params = {
                    "search": text,
                    "taxonomy": this.form.fields[index].taxonomy
                };
                axios({
                    'method': 'get',
                    url: wnd_jsonget_api + '/wnd_term_searcher',
                    params: params,
                }).then(response => {
                    this.form.fields[index].suggestions = response.data.data;
                });
            },
            // 回车输入写入数据并清空当前输入
            enter_tag: function(e, index) {
                if (!e.target.value || this.form.fields[index].tags.length >= max_tag_num) {
                    return false;
                }

                this.form.fields[index].tags.push(e.target.value.trim());
                e.target.value = '';
            },
            // 点击建议 Tag 写入数据并清空当前输入
            enter_tag_by_sg: function(e, index) {
                this.form.fields[index].tags.push(e.target.innerText.trim());
                this.form.fields[index].suggestions = '';
                let input = e.target.closest('.tags-input').querySelector('[type=text]');
                input.value = '';
            },
            // 删除 Tag
            delete_tag: function(tag, index) {
                this.form.fields[index].tags = this.form.fields[index].tags.filter(function(item) {
                    return item !== tag;
                });
            },
            // 点击 Tag 输入字段
            handle_tag_input_click: function($event, index) {
                if (this.form.fields[index].tags.length >= max_tag_num) {
                    this.form.fields[index].help.text = '最多' + max_tag_num + '个标签';
                }
            },
            // 提交
            submit: function(e) {
                this.form.submit.attrs.class = form_json.submit.attrs.class + ' is-loading';

                // Captcha 验证提交
                if (this.index.captcha) {
                    let captcha = this.form.fields[this.index.captcha];
                    if (!captcha.value) {
                        wnd_submit_via_captcha(e);
                        return;
                    }
                }

                // 表单检查
                let can_submit = true;
                for (const [index, field] of this.form.fields.entries()) {
                    if (field.required && !field.value && !field.selected && !field.checked) {
                        field.class = form_json.fields[index].class + ' is-danger';
                        field.help.class = form_json.fields[index].help.class + ' is-danger';
                        field.help.text = form_json.fields[index].help.text + ' ' + wnd.msg.required;
                        can_submit = false;
                    };
                }

                if (!can_submit) {
                    this.form.submit.attrs.class = form_json.submit.attrs.class;
                    // this.form.message.message = wnd.msg.required;

                    this.$nextTick(function() {
                        funTransitionHeight(parent, trs_time);
                    });

                    return false;
                }

                // Ajax 请求
                let form = document.querySelector('#' + this.form.attrs.id);
                let data = new FormData(form);

                // GET 请求参数
                let params = {};
                if ('get' == this.form.attrs.method.toLowerCase()) {
                    params = Object.fromEntries(data);
                }

                axios({
                        method: this.form.attrs.method,
                        url: this.form.attrs.action,
                        // Post
                        data: data,
                        // Get
                        params: params,
                    })
                    .then(response => {
                        info = wnd_handle_response(response.data, this.form.attrs.route, parent);
                        this.form.message.message = info.msg;
                        this.form.message.attrs.class = form_json.message.attrs.class + ' ' + info.msg_class;
                        this.form.submit.attrs.class = form_json.submit.attrs.class;
                        /*提交后清除 captcha 以便下次重新验证
                         * Vue 直接修改数组的值无法触发重新渲染
                         * @link https://cn.vuejs.org/v2/guide/reactivity.html#%E6%A3%80%E6%B5%8B%E5%8F%98%E5%8C%96%E7%9A%84%E6%B3%A8%E6%84%8F%E4%BA%8B%E9%A1%B9
                         */
                        if (this.index.captcha) {
                            Vue.set(this.form.fields[this.index.captcha], 'value', '');
                        }
                        this.$nextTick(function() {
                            funTransitionHeight(parent, trs_time);
                        });
                    })
                    .catch(function(error) { // 请求失败处理
                        console.log(error);
                    });
            },
        },
        mounted() {
            // 索引特殊字段
            this.form.fields.forEach((field, index) => {
                if ('captcha' == field.name && '' == field.value) {
                    this.index.captcha = index;
                }

                if ('editor' == field.type) {
                    this.index.editor = index;
                }

            })
            // 构造富文本编辑器
            if (this.index.editor) {
                this.build_editor();
            }

            funTransitionHeight(parent, trs_time);
            // v-html 不支持执行 JavaScript 需要通过封装好的 wnd_inser_html
            wnd_inner_html(`#${this.form.attrs.id} .form-script`, this.form.script);
        },
        // 计算
        computed: {},
        // 侦听器
        watch: {},
    });

    // 定义 Form 输出模板
    function get_form_template(form_json) {
        return `
        <form v-bind="form.attrs">
        <div v-if="form.before_html" v-html="form.before_html"></div>
        <div class="field" v-show="form.title.title"><h3 v-bind="form.title.attrs" v-html="form.title.title"></h3></div>
        <div v-bind="form.message.attrs" class="message" v-show="form.message.message"><div class="message-body" v-html="form.message.message"></div></div>
        ${get_fields_template(form_json)}
        <div v-if="form.submit.text" class="field is-grouped is-grouped-centered">
        <button type="button" v-bind="form.submit.attrs" @click="submit($event)" v-text="form.submit.text"></button>
        </div>
        <div class="form-script"></div>
        <div v-if="form.after_html" v-html="form.after_html"></div>
        </form>`;
    }

    // 选择并构建字段模板
    function get_fields_template(form_json) {
        let t = '';
        for (let index = 0; index < form_json.fields.length; index++) {
            // 需要与定义数据匹配
            let field = form_json.fields[index];

            // 特别注意：此处定义的是 Vue 模板字符串，而非实际数据，Vue 将据此字符串渲染为具体值
            let field_vn = `form.fields[${index}]`;

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
            } else if ('editor' == field.type) {
                t += build_editor(field_vn, index);
            } else if ('image_upload' == field.type) {
                t += build_image_upload(field_vn, index);
            } else if ('file_upload' == field.type) {
                t += build_file_upload(field_vn, index);
            } else if ('select' == field.type) {
                t += build_select(field_vn, index);
            } else if ('tag_input' == field.type) {
                t += build_tag_input(field_vn, index);
            }
        }
        return t;
    }

    // 常规 input 组件
    function build_text(field, index) {
        return `
		<div :class="get_field_class(${field})">
		<label v-if="${field}.label" class="label">{{${field}.label}}<span v-if="${field}.required" class="required">*</span></label>
		<div v-if="${field}.addon_left" class="control" v-html="${field}.addon_left"></div>
		<div :class="get_control_class(${field})">
		<input v-bind="parse_input_attr(${field})" v-model="${field}.value" @change="change(${field})" @keypress.enter="submit"/>
		<span v-if="${field}.icon_left" class="icon is-left"  v-html="${field}.icon_left"></span>
		<span v-if="${field}.icon_right"  class="icon is-right" v-html="${field}.icon_right"></span>
		</div>
		<div v-if="${field}.addon_right" class="control" v-html="${field}.addon_right"></div>
		<p v-if="!has_addon(${field})" v-show="${field}.help.text" class="help" :class="${field}.help.class">{{${field}.help.text}}</p>
		</div>`;
    }

    // Radio / checkbox 组件
    function build_radio(field, index) {
        return `
		<div :class="get_field_class(${field})">
		<div :class="get_control_class(${field})">
		<template v-for="(radio_value, radio_label) in ${field}.options">
		<label :class="${field}.type">
		<input v-bind="parse_input_attr(${field})" :value="radio_value" v-model="${field}.checked" @click="change(${field})">
		{{radio_label}}</label>
		</template>
		</div>
		<p v-show="${field}.help.text" class="help" :class="${field}.help.class">{{${field}.help.text}}</p>
		</div>`;
    }

    // 下拉 Select 组件
    function build_select(field, index) {
        return `
		<div class="field">
		<label v-if="${field}.label" class="label">{{${field}.label}}<span v-if="${field}.required" class="required">*</span></label>
		<div class="control">
		<div class="select" :class="${field}.class" @change="change(${field})">
		<select v-bind="parse_input_attr(${field})" v-model="${field}.selected">
		<template v-for="(value, name) in ${field}.options">
		<option :value="value">{{name}}</option>
		</template>
		</select>
		</div>
		</div>
		<p v-show="${field}.help.text" class="help" :class="${field}.help.class">{{${field}.help.text}}</p>
		</div>`;
    }

    // Textarea 组件
    function build_textarea(field, index) {
        return `
		<div :class="get_field_class(${field})">
		<label v-if="${field}.label" class="label">{{${field}.label}}<span v-if="${field}.required" class="required">*</span></label>
		<textarea v-model="${field}.value" v-bind="parse_input_attr(${field})" @change="change(${field})"></textarea>
		<p v-show="${field}.help.text" class="help" :class="${field}.help.class">{{${field}.help.text}}</p>
		</div>`;
    };

    // 文件上传字段
    function build_file_upload(field, index) {
        return `
		<div :id="get_field_id(${field},${index})" class="field" :class="${field}.class">
		<div class="field"><div class="ajax-message"></div></div>
		<div class="columns is-mobile is-vcentered">

		<div class="column">
		<div class="file has-name is-fullwidth">
		<label class="file-label">
		<input type="file" class="file file-input" :name="${field}.name" @change="upload($event,${field})">
		<span class="file-cta">
		<span class="file-icon"><i class="fa fa-upload"></i></span>
		<span class="file-label">{{${field}.label}}</span>
		</span>
		<span class="file-name" v-html="${field}.file_name"></span>
		</label>
		</div>
		</div>

		<div v-show="${field}.delete_button && ${field}.file_id" class="column is-narrow">
		<a class="delete" @click="delete_file(${field},${index})"></a>
		</div>

		</div>
		</div>`;
    }

    // 单个图像上传字段
    function build_image_upload(field, index) {
        return `
		<div :id="get_field_id(${field},${index})" class="field" :class="${field}.class">
		<div v-if="${field}.complete">
		<progress class="progress is-primary" :value="${field}.complete" max="100"></progress>
		</div>

		<label class="label">{{${field}.label}}<span v-if="${field}.required && ${field}.label" class="required">*</span></label>
		<a @click="click_target('#' + get_field_id(${field}, ${index}) + ' input[type=file]')">
		<img class="thumbnail" :src="${field}.thumbnail" :height="${field}.thumbnail_size.height" :width="${field}.thumbnail_size.width">
		</a>
		<a v-show="${field}.delete_button && ${field}.file_id" class="delete" @click="delete_file(${field}, ${index})"></a>
		<p v-show="${field}.help.text" class="help" :class="${field}.help.class">{{${field}.help.text}}</p>
		<div class="file"><input type="file" class="file file-input" accept="image/*" :name="${field}.name" @change="upload($event,${field})"></div>
		</div>`;
    }

    // 富文本编辑器
    function build_editor(field, index) {
        return `
		<div :class="get_field_class(${field})">
		<div :id="form.attrs.id + '-${index}'" class="rich-editor"></div>
		<label v-if="${field}.label" class="label">{{${field}.label}}<span v-if="${field}.required" class="required">*</span></label>
		<textarea style="display:none" v-model="${field}.value" v-bind="parse_input_attr(${field})" @change="change(${field})"></textarea>
		<p v-show="${field}.help.text" class="help" :class="${field}.help.class">{{${field}.help.text}}</p>
		</div>`;
    };

    // 富文本编辑器
    function build_tag_input(field, index) {
        // 按需载入 CSS
        let style = document.createElement('style');
        style.type = 'text/css';
        style.innerHTML = `
        .tags-input{width:100%;border-bottom:1px solid #ccc}
        .tags-input input{border:none;width:100%;font-size:1rem;padding-top:0!important;padding-bottom:0!important}
        .tags-input input:focus{border:none;box-shadow:none;outline:0;padding-top:0!important;padding-bottom:0!important}
        .tags-input .tag{margin:5px}
        .tags-input .autocomplete{position:relative;display:inline-block}
        .tags-input .autocomplete-items{position:absolute;box-shadow:0 2px 10px #999;border-bottom:none;border-top:none;z-index:99;top:100%;left:0}
        .tags-input .autocomplete-items li{padding:10px;cursor:pointer;background-color:#fff;border-bottom:1px solid #eee}
        .tags-input .autocomplete-items li:hover{background-color:#eee}`;
        document.head.appendChild(style);

        let tags = `
		<template v-for="(tag, index) in ${field}.tags">
		<span class="tag is-medium is-light is-danger">{{tag}}<span class="delete" @click="delete_tag(tag, ${index})"></span></span>
		</template>`;
        let suggestions = `
		<template v-for="(tag, index) in ${field}.suggestions">
		<li @click="enter_tag_by_sg($event, ${index})">{{tag}}</li>
		</template>`;

        return `
		<div :class="get_field_class(${field})">
		<label v-if="${field}.label" class="label">{{${field}.label}}<span v-if="${field}.required" class="required">*</span></label>
		<div class="tags-input columns is-marginless">
		<div class="column is-marginless is-paddingless is-narrow">${tags}</div>
		<div class="autocomplete column is-marginless">
		<input type="text" :readonly="${field}.tags.length >= max_tag_num" @input="suggest_tags($event.target.value, ${index})" @keypress.enter="enter_tag($event, ${index})" @click="handle_tag_input_click($event, ${index})"/>
		<input type="hidden" v-bind="parse_input_attr(${field})" v-model="${field}.tags" />
		<ul v-show="${field}.tags.length < max_tag_num" class="autocomplete-items">${suggestions}</ul>
		</div>
		</div>
		<p v-show="${field}.help.text" class="help" :class="${field}.help.class">{{${field}.help.text}}</p>
		</div>`;
    };
}

/**
 *@since 0.9.25
 *字段追加
 */
document.addEventListener('click', function(e) {
    let button = e.target.closest('button');
    if (!button) {
        return;
    }
    let form = button.closest('form');
    if (!form) {
        return;
    }
    let parent = form.parentNode;

    /**
     *@since 2020.04.20
     *动态追加字段
     */
    if (button.classList.contains('add-row')) {
        let field = button.closest(".field");
        // 临时改变属性，以便复制给即将创建的字段
        button.classList.remove('add-row');
        button.classList.add('remove-row');
        button.innerText = '-';

        // 克隆（复制）改变后的元素
        let new_field = button.closest('.field').cloneNode(true);

        // 还原当前字段
        button.classList.add('add-row');
        button.classList.remove('remove-row');
        button.innerText = '+';

        // 追加新字段
        field.after(new_field);

        // Ajax 场景高度调整
        funTransitionHeight(parent, trs_time);

        /**
         *@since 2020.04.20
         *删除字段
         */
    } else if (button.classList.contains('remove-row')) {
        button.closest('.field').outerHTML = '';

        // Ajax 场景高度调整
        funTransitionHeight(parent, trs_time);
    }
});