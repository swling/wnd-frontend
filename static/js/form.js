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
                    'editor': [],
                    'captcha': '',
                    'ids': [],
                },
                default_data: {}, // 记录表单默认数据，在提交表单时与现有表单合并，以确保所有字段名均包含在提交数据中，从而通过一致性签名
            }
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
            // 富文本编辑器 @link https://tiny.cloud
            build_editor: async function () {
                let _this = this;
                if ('undefined' == typeof tinymce) {
                    let url = static_path + 'editor/tinymce/tinymce.min.js' + cache_suffix;
                    await wnd_load_script(url);
                }
                build_editors();

                function build_editors() {
                    _this.index.editor.forEach(index => {
                        build_tinymce(index);
                    });
                }

                function build_tinymce(index) {
                    let field = _this.form.fields[index];
                    let post_id = _this.form.attrs['data-post-id'] || 0;
                    let selector = `#${_this.form.attrs.id}-${index}`;
                    // 监听编辑器所在元素高度变化
                    const resizeObserver = new ResizeObserver(entries => {
                        funTransitionHeight(parent, trs_time);
                    });
                    resizeObserver.observe(document.querySelector(selector));
                    // jsdeliver CDN 无效添加 suffix
                    tinymce.init({
                        // 基础配置
                        branding: false,
                        selector: selector,
                        menubar: false,
                        language: wnd.lang,
                        cache_suffix: cache_suffix,

                        // 自动保存
                        autosave_restore_when_empty: true,
                        autosave_prefix: 'tinymce-autosave-' + post_id,
                        autosave_ask_before_unload: false,

                        // 设置文件 URL 为绝对路径
                        relative_urls: false,
                        remove_script_host: false,

                        // 将分页符设置为 WP More
                        pagebreak_separator: '<!--more-->',
                        pagebreak_split_block: true,

                        // 定义插件及菜单按钮
                        plugins: 'advlist autolink autoresize autosave code codesample fullscreen image link lists pagebreak wordcount wndimage wndinit',
                        toolbar: 'formatselect | alignleft aligncenter alignright bullist numlist | ' +
                            'blockquote wndimage link codesample  pagebreak wndpaidcontent | removeformat code fullscreen',

                        // 自定义配置
                        wnd_config: {
                            'rest_nonce': wnd.rest_nonce,
                            'upload_url': field.upload_url,
                            'post_parent': post_id,
                            'oss_direct_upload': _this.form.attrs['data-oss-direct-upload'],
                        },
                        setup: function (editor) {
                            texarea = document.querySelector(selector);
                            texarea.style.removeProperty('display');
                            editor.on('change', function () {
                                field.value = tinymce.get(`${_this.form.attrs.id}-${index}`).getContent();
                                parent.style.removeProperty('height');
                            });
                        },
                        init_instance_callback: function (editor) {
                            resizeObserver.observe(document.querySelector(`#${_this.form.attrs.id} .tox-tinymce`));
                        },
                    });
                }
            },

            click_target(selector) {
                document.querySelector(selector).click();
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
                // 关联字段数据
                if (field.linkage) {
                    let current_value = e.target.value; // 此处不能使用 this.get_value() 因为双向绑定值会晚一步
                    let query = field.linkage.query || false;
                    let data = field.linkage.data ? (field.linkage.data[current_value] || false) : false;
                    let params = field.linkage.params || {};
                    params[field.name] = current_value;

                    // 向关联字段指定属性赋值
                    if (data) {
                        linkage(this, data);
                    } else if (query) {
                        let res = await wnd_query(query, params);
                        linkage(this, res.data);
                    }

                    function linkage(_this, data) {
                        field.linkage.fields.forEach(id => {
                            let index = _this.index.ids[id];
                            let field = _this.form.fields[index];
                            field = Object.assign(field, data);
                        });
                    }
                }

                // 常规
                field.class = field.class.replace('is-danger', '');
                field.help.class = field.help.class.replace('is-danger', '');
                field.help.text = field.help.text.replace(' ' + wnd.msg.required, '');
            },

            // 动态联动下拉选择
            selected: async function (e, key, index) {
                let select = this.form.fields[index];
                let query = select.data.query;
                let params = Object.assign(select.data.params, {
                    'parent': e.target.value,
                });

                // change
                this.change(select);

                // Ajax 联动下拉
                let res = await wnd_query(query, params);
                let nextSelect = res.data;
                // 写入或删除 select
                if (Object.keys(nextSelect).length) {
                    select.options.splice(key + 1, select.options.length, nextSelect);
                    // 设置默认选中值，否则下拉首项为空
                    select.selected[key + 1] = '';
                } else {
                    select.options.splice(key + 1, select.options.length);
                    // key 从 0 开始，故此 + 1 为元素个数
                    select.selected.length = key + 1;
                }
            },

            // 文件上传
            upload: function (e, field) {
                let files = e.target.files;
                let form_data = new FormData()
                let _this = this;

                // 循环构造自定义 data-* 属性
                if (field.data) {
                    for (const key in field.data) {
                        form_data.append(key, field.data[key]);
                    }
                }

                // 获取文件，支持多文件上传（文件数据 name 统一设置为 wnd_file[] 这是与后端处理程序的约定 ）
                for (let i = 0, n = files.length; i < n; i++) {
                    // 图片处理
                    if (files[i].type.includes('image/') && (field.data.save_width || field.data.save_height)) {
                        handel_image_upload(i, files[i]);
                    } else {
                        if (_this.form.attrs['data-oss-direct-upload']) {
                            upload_to_oss(files[i]);
                        } else {
                            form_data.set('wnd_file[' + i + ']', files[i]);
                            upload_to_local_server(form_data);
                        }
                    }
                }

                function handel_image_upload(i, source_file) {
                    let image = new Image();
                    let reader = new FileReader();
                    reader.onload = function () {
                        // 通过 reader.result 来访问生成的 DataURL
                        var url = reader.result;
                        image.src = url;
                        image.onload = function (e) {
                            crop_and_upload_img(source_file, image, i);
                        }
                    };
                    reader.readAsDataURL(source_file);
                }

                function crop_and_upload_img(source_file, image, i) {
                    let canvas = document.createElement('canvas');
                    canvas.width = field.data.save_width;
                    canvas.height = field.data.save_height;

                    /**
                     * 缩放、裁剪
                     * @link https://developer.mozilla.org/zh-CN/docs/Web/API/CanvasRenderingContext2D/scale
                     * @link https://developer.mozilla.org/zh-CN/docs/Web/API/HTMLCanvasElement/toBlob
                     **/
                    let s = Math.max(canvas.width / image.naturalWidth, canvas.height / image.naturalHeight);
                    let ctx = canvas.getContext('2d');
                    ctx.scale(s, s);
                    var x = (ctx.canvas.width / s - image.naturalWidth) / 2;
                    var y = (ctx.canvas.height / s - image.naturalHeight) / 2;
                    ctx.drawImage(image, x, y);

                    canvas.toBlob((blob) => {
                        let file = new File([blob], source_file.name, {
                            type: source_file.type
                        });
                        form_data.append('wnd_file[' + i + ']', file);

                        if (_this.form.attrs['data-oss-direct-upload']) {
                            upload_to_oss(file)
                        } else {
                            upload_to_local_server(form_data);
                        }
                    }, source_file.type);
                }

                // Ajax
                function upload_to_local_server() {
                    axios({
                        url: wnd_action_api + '/common/wnd_upload_file',
                        method: 'post',
                        data: form_data,
                        headers: {},
                        //原生获取上传进度的事件
                        onUploadProgress: function (progressEvent) {
                            // field.complete = (progressEvent.loaded / progressEvent.total * 100 | 0);
                            field.help.text = wnd.msg.waiting;
                            field.help.class = 'is-primary';
                        }
                    }).then(response => {
                        if (response.data.status <= 0) {
                            field.help.text = response.data.msg;
                            field.help.class = 'is-danger';
                        } else {
                            let data = response.data.data;
                            for (let i = 0, n = data.length; i < n; i++) {
                                if (data[i].status <= 0) {
                                    field.help.text = data[i].msg;
                                    field.help.class = 'is-danger';
                                } else {
                                    field.help.text = wnd.msg.upload_successfully;
                                    field.help.class = 'is-success';
                                    field.thumbnail = data[i].thumbnail;
                                    field.file_id = data[i].id;
                                    field.file_name = wnd.msg.upload_successfully + '&nbsp<a href="' + data[i].url + '" target="_blank">' + wnd.msg.view + '</a>';
                                }

                                // 单个图片
                                if ('image_upload' == field.type) {

                                    // 单个文件
                                } else if ('file_upload' == field.type) {

                                    // 图片相册
                                } else if ('gallery' == field.type) {

                                }
                            }
                        }
                    }).catch(err => {
                        console.log(err);
                    });
                }

                // 浏览器直传 OSS
                async function upload_to_oss(file) {
                    let upload_res = await wnd_upload_to_oss(file, field.data);
                    if (upload_res) {
                        field.help.text = wnd.msg.upload_successfully;
                        field.help.class = 'is-success';
                        field.thumbnail = upload_res.signed_url || upload_res.url;
                        field.file_id = upload_res.id;
                        field.file_name = wnd.msg.upload_successfully + '&nbsp<a href="' + (upload_res.signed_url || upload_res.url) + '" target="_blank">' + wnd.msg.view + '</a>';
                    } else {
                        field.help.text = wnd.msg.upload_failed;
                        field.help.class = 'is-danger';
                    }
                }
            },

            // 删除文件
            delete_file: function (field, index) {
                if (!field.file_id) {
                    field.file_name = 'Error';
                    return false;
                }

                let data = new FormData();
                data.append('file_id', field.file_id);
                data.append('meta_key', field.data.meta_key);
                axios({
                    url: wnd_action_api + '/common/wnd_delete_file',
                    method: 'post',
                    data: data,
                }).then(response => {
                    if (response.data.status <= 0) {
                        wnd_alert_notification(response.data.msg, 'is-danger');
                        return false;
                    }

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
            suggest_tags: function (text, index) {
                let field = this.form.fields[index];
                let params = {
                    "search": text,
                    "taxonomy": field.data.taxonomy
                };
                axios({
                    'method': 'get',
                    url: wnd_query_api + '/wnd_term_searcher',
                    params: params,
                }).then(response => {
                    field.data.suggestions = response.data.data;
                });
            },
            // 回车输入写入数据并清空当前输入
            enter_tag: function (e, index) {
                let field = this.form.fields[index];
                if (!e.target.value || field.value.length >= field.data.max) {
                    return false;
                }

                field.value.push(e.target.value.trim());
                e.target.value = '';
            },
            // 点击建议 Tag 写入数据并清空当前输入
            enter_tag_by_sg: function (e, index) {
                let field = this.form.fields[index];
                field.value.push(e.target.innerText.trim());
                field.data.suggestions = '';
                let input = e.target.closest('.tags-input').querySelector('[type=text]');
                input.value = '';
            },
            // 删除 Tag
            delete_tag: function (tag, index) {
                let field = this.form.fields[index];
                field.value = field.value.filter(function (item) {
                    return item !== tag;
                });
            },
            // 点击 Tag 输入字段
            handle_tag_input_click: function ($event, index) {
                let field = this.form.fields[index];
                if (field.value.length >= field.data.max) {
                    field.help.text = 'Limit to ' + field.data.max + ' tags.';
                    field.help.class = 'is-danger';
                }
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
                // 销毁可能存在的 TinyMCE 实例
                this.index.editor.forEach(index => {
                    let selector = `#${this.form.attrs.id}-${index}`;
                    if (typeof tinymce != 'undefined') {
                        tinymce.remove(selector);
                    }
                });

                // 重新载入数据
                let res = await axios({ method: 'get', url: api_url });
                this.form = res.data.data.structure;

                // 下一次渲染时重新构建编辑器
                this.$nextTick(() => {
                    if (this.index.editor.length) {
                        this.build_editor();
                    }
                });
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

                if ('editor' == field.type) {
                    this.index.editor.push(index);
                    continue;
                }

                /**
                 * 联动下拉，追加设置下一级选项默认值，否则子下拉菜单首项为空
                 * 注意由于联动下拉的options 及 selected均采用层级作为键名，因此类型为对象
                 **/
                if ('select_linked' == field.type) {
                    let last = Object.keys(field.selected)[Object.keys(field.selected).length - 1];
                    let next = parseInt(last) + 1;
                    field.selected[next] = '';
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
            // 构造富文本编辑器
            if (this.index.editor.length > 0) {
                this.build_editor();
            }

            // v-html 不支持执行 JavaScript 需要通过封装好的 wnd_inser_html
            wnd_inner_html(`#${this.form.attrs.id} .form-script`, this.form.script);
            funTransitionHeight(parent, trs_time);

            // F8 重新载入表单数据
            document.addEventListener('keydown', (event) => {
                if (event.key === 'F8') {
                    event.preventDefault();
                    this.reload();
                }
            });
        },
        updated() {
            funTransitionHeight(parent, trs_time);
        }
    };

    Vue.createApp(form_option).mount(container);

    /******************************************************* 构造 Vue 模板 **********************************************************/
    function build_form_template(form_json) {
        // 定义当前函数，以便于通过字符串变量，动态调用内部函数
        let _this = build_form_template;

        function build() {
            return `
<form v-bind="form.attrs">
<div v-if="form.before_html" v-html="form.before_html"></div>
<div class="field" v-show="form.title.title"><h3 v-bind="form.title.attrs" v-html="form.title.title"></h3></div>
<div v-bind="form.message.attrs" class="notification is-light" v-show="form.message.message" v-html="form.message.message"></div>
${build_fields_template(form_json)}
${build_submit_template(form_json)}
<div class="form-script"></div>
<div v-if="form.after_html" v-html="form.after_html"></div>
</form>`;
        }

        // 选择并构建字段模板
        function build_fields_template(form_json) {
            let t = '';
            for (let index = 0; index < form_json.fields.length; index++) {
                // 需要与定义数据匹配
                let field = form_json.fields[index];
                // 特别注意：此处定义的是 Vue 模板字符串，而非实际数据，Vue 将据此字符串渲染为具体值
                let field_vn = `form.fields[${index}]`;

                // Horizontal
                if (is_horizontal_field()) {
                    t += `
<div class="field is-horizontal">
<div class="field-label ${form_json.size}">
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
                    return form_json.attrs['is-horizontal'] && !['html', 'hidden'].includes(field.type);
                }
            }
            return t;
        }

        function build_submit_template(form_json) {
            return `
<div v-if="form.submit.text" class="field has-text-centered">
<button type="button" v-bind="form.submit.attrs" @click="submit($event)" class="${form_json.size}" v-text="form.submit.text"></button>
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

        // 动态联动多级下拉 Select 组件：options 为二维数组，selected 为数组依次对应 options 中的子数组。每个 select 仍为单选
        _this.build_select_linked = (field, index) => {
            return `
<div :class="get_field_class(${field})">
${build_label(field)}
<div v-if="${field}.addon_left" class="control" v-html="${field}.addon_left"></div>
<div :class="get_control_class(${field})">
<div class="select">
<select class="select" style="display:inline-block" :class="${field}.class + ' ' + form.size" v-bind="parse_input_attr(${field})" v-for="(option, key) in ${field}.options" v-model="${field}.selected[key]" @change="selected($event, key, ${index})" :key="key">
<option disabled value="">- {{${field}.label}} -</option>
<option v-for="(v,k ) in option" :value="v" :key="v">{{k}}</option>
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

        // 文件上传字段
        _this.build_file_upload = (field, index) => {
            return `
<div :id="get_field_id(${field},${index})" class="field" :class="${field}.class">
<div v-if="${field}.complete" class="field">
<progress class="progress is-primary" :value="${field}.complete" max="100"></progress>
</div>
<div class="columns is-mobile is-vcentered">

<div class="column">
<div class="file has-name is-fullwidth" :class="form.size">
<label class="file-label">
<input type="file" class="file file-input" :name="${field}.name" @change="upload($event,${field})">
<span class="file-cta">
<span class="file-icon"><i class="fa fa-upload"></i></span>
</span>
<span class="file-name" v-html="${field}.file_name"></span>
</label>
</div>
</div>

<div v-show="${field}.delete_button && ${field}.file_id" class="column is-narrow">
<a class="delete" @click="delete_file(${field},${index})"></a>
</div>

</div>
<p v-show="${field}.help.text" class="help" :class="${field}.help.class" style="margin-top:-1rem;">{{${field}.help.text}}</p>
</div>`;
        }

        // 单个图像上传字段
        _this.build_image_upload = (field, index) => {
            return `
<div :id="get_field_id(${field},${index})" class="field" :class="${field}.class">
<div v-if="${field}.complete">
<progress class="progress is-primary" :value="${field}.complete" max="100"></progress>
</div>

${build_label(field)}
<a @click="click_target('#' + get_field_id(${field}, ${index}) + ' input[type=file]')">
<img class="thumbnail" :src="${field}.thumbnail" :height="${field}.thumbnail_size.height" :width="${field}.thumbnail_size.width">
</a>
<a v-show="${field}.delete_button && ${field}.file_id" class="delete" @click="delete_file(${field}, ${index})"></a>
<p v-show="${field}.help.text" class="help" :class="${field}.help.class">{{${field}.help.text}}</p>
<div class="file"><input type="file" class="file file-input" accept="image/*" :name="${field}.name" @change="upload($event,${field})"></div>
</div>`;
        }

        // 富文本编辑器
        _this.build_editor = (field, index) => {
            return `
<div :class="get_field_class(${field})">
${build_label(field)}
<textarea :id="form.attrs.id + '-${index}'" class="is-hidden" v-model="${field}.value" v-bind="parse_input_attr(${field})" @change="change(${field}, $event)"></textarea>
<p v-show="${field}.help.text" class="help" :class="${field}.help.class">{{${field}.help.text}}</p>
</div>`;
        };

        // 富文本编辑器
        _this.build_tag_input = (field, index) => {
            // 按需载入 CSS
            let style = document.createElement('style');
            style.innerHTML = `
.tags-input{width:100%;border-bottom:1px solid #ccc}
.tags-input input{border:none;width:100%;font-size:1rem;position:absolute;top:0;bottom:0;height:100%;}
.tags-input input:focus{border:none;box-shadow:none;outline:0;}
.tags-input .tag{margin:5px}
.tags-input .autocomplete{position:relative;display:inline-block;min-height:3rem;}
.tags-input .autocomplete-items{position:absolute;box-shadow:0 2px 10px #999;border-bottom:none;border-top:none;z-index:99;top:100%;left:0}
.tags-input .autocomplete-items li{padding:10px;cursor:pointer;background-color:#fff;border-bottom:1px solid #eee}
.tags-input .autocomplete-items li:hover{background-color:#eee}`;
            document.head.appendChild(style);

            let tags = `
<template v-for="(tag, index) in ${field}.value">
<span class="tag is-medium is-light is-danger">{{tag}}<span class="delete is-small" @click="delete_tag(tag, ${index})"></span></span>
</template>`;
            let suggestions = `
<template v-for="(tag, index) in ${field}.data.suggestions">
<li @click="enter_tag_by_sg($event, ${index})">{{tag}}</li>
</template>`;

            return `
<div :class="get_field_class(${field})">
${build_label(field)}
<div class="tags-input columns is-marginless">
<div class="column is-marginless is-paddingless is-narrow">${tags}</div>
<div class="autocomplete column is-marginless">
<input type="text" :placeholder="${field}.value.length ? '' : ${field}.placeholder" 
:readonly="${field}.value.length >= ${field}.data.max" @input="suggest_tags($event.target.value, ${index})" 
@keypress.enter="enter_tag($event, ${index})" @click="handle_tag_input_click($event, ${index})"/>
<template v-for="(tag, index) in ${field}.value"><input type="hidden" v-bind="parse_input_attr(${field}, ['type'])" v-model="tag" /></template>
<ul v-show="${field}.value.length < ${field}.data.max" class="autocomplete-items">${suggestions}</ul>
</div>
</div>
<p v-show="${field}.help.text" class="help" :class="${field}.help.class">{{${field}.help.text}}</p>
</div>`;
        };

        return build();
    }
}

/**
 *@since 0.9.25
 *字段追加
 */
document.addEventListener('click', function (e) {
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

        /**
         *@since 2020.04.20
         *删除字段
         */
    } else if (button.classList.contains('remove-row')) {
        button.closest('.field').outerHTML = '';
    }

    // Ajax 场景高度调整
    funTransitionHeight(parent, trs_time);
});