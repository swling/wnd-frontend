<!-- ********************************* 组件结束 ********************************* -->
<div id="app-post-form">
    <form ref="formRef" @submit.prevent="handle_submit">
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Thumb</label>
            </div>
            <div class="field-body">
                <thumbnail-card :img-width="80" :img-height="60" :crop-width="400" :crop-height="300" :post_parent="post.ID" meta_key="_thumbnail_id" :thumbnail="thumbnail"></thumbnail-card>
            </div>
        </div>
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Title</label>
            </div>
            <div class="field-body">
                <input type="text" class="input" v-model="post.post_title" :placeholder="`ID ${post.ID}`" required />
            </div>
        </div>

        <paid-content :post_parent="post.ID" v-model:price="meta.price" :file_id="file_id" :file_url="file_url"></paid-content>

        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Category</label>
            </div>
            <div class="field-body">
                <dropdown-search :options="category_options" v-model.number="category_selected" required="1"></dropdown-search>
            </div>
        </div>
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Tag</label>
            </div>
            <div class="field-body">
                <tags-input :options="tag_options" :max-tags="3" v-model="tag_selected"></tags-input>
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

        <div class="notification is-light has-text-centered is-primary" v-show="msg || link" :class="{'is-danger' : has_error}">
            <p>{{msg}} <span v-show="submit_res.status > 0"> : <a :href="link" target="_blank">{{link}}</a></span></p>
        </div>
    </form>
</div>
<script>
    {
        class MyPostEditor extends PostFormComponent {
            data() {
                let base = super.data();
                let data = {
                    category_options: [],
                    category_selected: 0,

                    tag_options: [],
                    tag_selected: [],
                };

                return { ...base, ...data };
            }

            init_data(data) {
                super.init_data(data);

                const categoryData = this.processCatOptions(data.term_options.category, data.terms.category);
                this.category_options = categoryData.options;
                this.category_selected = categoryData.selected;

                const tagData = this.processTagOptions(data.term_options.post_tag, data.terms.post_tag);
                this.tag_options = tagData.options;
                this.tag_selected = tagData.selected;
            }

            get_meta_data() {
                return {
                    _wpmeta_price: this.meta.price,
                };
            }

            get_term_data() {
                return {
                    _term_category: this.category_selected,
                    _term_post_tag: this.tag_selected,
                };
            }
        }

        const custom = new MyPostEditor("#app-post-form");
        const vueComponent = custom.toVueComponent();
        const app = Vue.createApp(vueComponent);
        app.mount("#app-post-form");

        vueInstances.push(app);
    }
</script>