<!-- ********************************* 组件结束 ********************************* -->
<div id="app-post-form">
    <form ref="formRef" @submit.prevent="handle_submit" v-show="res.status > 0">
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Title</label>
            </div>
            <div class="field-body">
                <input type="text" class="input" v-model="post.post_title" :placeholder="`ID ${post.ID}`" required />
            </div>
        </div>
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Slug</label>
            </div>
            <div class="field-body">
                <input type="text" class="input" v-model="post.post_name" :placeholder="`Post Slug ${post.ID}`" required />
            </div>
        </div>

        <paid-content :post_parent="post.ID" v-model:price="meta.price" :file_id="file_id" :file_url="file_url"></paid-content>

        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Template</label>
            </div>
            <div class="field-body">
                <dropdown-search :options="templates" v-model="template_selected"></dropdown-search>
            </div>
        </div>

        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Content</label>
            </div>
            <div class="field-body">
                <rich-editor v-model="post.post_content" v-model:post_id="post.ID" :parent_node="parent_node" v-if="!loading"></rich-editor>
                <div v-else style="height: 100px;"></div>
            </div>
        </div>

        <div class="field is-grouped is-grouped-centered">
            <div class="has-text-centered">
                <button :class="[`button is-${wnd.color.primary}`, { 'is-loading': submitting }]">
                    submit
                </button>
            </div>
        </div>
    </form>
    <div class="notification is-light has-text-centered is-primary" v-show="msg || link" :class="{'is-danger' : has_error}">
        <p v-html="msg" v-show="msg"></p>
        <p v-show="submit_res.status > 0"><span><a :href="link" target="_blank">{{link}}</a></span></p>
    </div>
    <div class="notification is-light has-text-centered is-danger" v-show="`revision`==post.post_type" :class="{'is-danger' : has_error}">
        <p>编辑修订版本 / Edit the revision</p>
    </div>
</div>
<script>
    {
        class MyPageEditor extends PostFormComponent {
            data() {
                let base = super.data();
                base.templates = [];
                base.template_selected = "";
                return base;
            }

            init_data(data) {
                super.init_data(data);

                // 对象转数组
                this.templates = Object.entries(data.templates).map(([key, value]) => ({
                    name: key,
                    value: value
                }));
                this.template_selected = data.meta._wp_page_template;
            }

            get_meta_data() {
                return {
                    _wpmeta__wp_page_template: this.template_selected,
                };
            }
        }

        const custom = new MyPageEditor("#app-post-form");
        const vueComponent = custom.toVueComponent();
        const app = Vue.createApp(vueComponent);
        app.mount("#app-post-form");

        vueInstances.push(app);
    }
</script>