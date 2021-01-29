var wnd_vue_form = true;

/**
 *@since 0.9.25
 *Vue 根据 Json 动态渲染表单
 */
function _wnd_render_form(container, form_json) {
    // 混入对象（有点类似 PHP Class 中的特性）
    var wnd_input_field_mixin = {
        props: ['attr', 'value', "index"],
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

    // 密码 组件
    Vue.component('wnd-password', {
        mixins: [wnd_input_field_mixin]
    });

    // Hidden 组件
    Vue.component('wnd-hidden', {
        mixins: [wnd_input_field_mixin]
    });

    // Html 组件
    Vue.component('wnd-html', {
        props: ['value'],
        template: '<div v-html=" value"></div>',
    });

    // Radio 组件
    Vue.component('wnd-radio', {
        props: ['value'],
        template: '<div v-html=" value"></div>',
    });

    // 定义 Form 输出模板
    var form_template = '<div class="vue-form">';

    // 表单头
    form_template += '<form v-bind="form.attrs">';
    form_template += '<h3 v-bind="form.title.attrs" v-html="form.title.title"></h3>';
    form_template += '<div v-bind="form.message.attrs" v-show="form.message.message"><div class="message-body" v-html="form.message.message"></div></div>';

    // 循环字段
    form_template += '<template v-for="(field, index) in form.fields" :key="index">';
    form_template += '<div :class="get_input_field_class(field)">';
    // label
    form_template += '<label v-html="field.label" class="label"></label>';

    form_template += '<div  v-if="field.addon_left" class="control" v-html="field.addon_left"></div>';

    form_template += '<div :class="get_input_control_class(field)">';
    form_template += '<component :is="\'wnd-\' + field.type" :attr="parse_input_attr(field)" :value="field.value" :wnd_value.sync="field.value"/>'
    form_template += '<span v-if="field.icon_left" class="icon is-left"  v-html="field.icon_left"></span>';
    form_template += '<span v-if="field.icon_right"  class="icon is-right" v-html="field.icon_right"></span>';
    form_template += '</div>';

    form_template += '<div  v-if="field.addon_right" class="control" v-html="field.addon_right"></div>';

    form_template += '</div>';
    form_template += '</template>';

    // 提交按钮
    form_template += '<div class="field is-grouped is-grouped-centered">';
    form_template += '<button v-bind="form.submit.attrs" @click="submit" v-text="form.submit.text"></button>';
    form_template += '</div>';

    // 表尾
    form_template += '</form>';

    // loading 遮罩
    form_template += '</div>';

    new Vue({
        el: container,
        // 模板将替换挂载点内的内容
        template: form_template,
        // 数据
        data: {
            form: Object.assign({
                attrs: [],
                title: [],
                message: [],
                fields: [],
                submit: [],
            }, form_json),
        },

        // 事件处理
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
                // 按钮
                var submit_class = this.form.submit.attrs.class;
                this.form.submit.attrs.class = submit_class + " is-loading";

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
                axios.post(this.form.attrs.action, data)
                    .then(function(response) {
                        _this.form.message.message = response.data.msg;
                        _this.form.message.attrs.class = (response.data.status <= 0) ? _this.form.message.attrs.class + ' is-danger' : _this.form.message.attrs.class;
                        _this.form.submit.attrs.class = submit_class;

                    })
                    .catch(function(error) { // 请求失败处理
                        console.log(error);
                    });
            }
        },
        // 计算
        computed: {},
        // 侦听器
        watch: {},
    });
}