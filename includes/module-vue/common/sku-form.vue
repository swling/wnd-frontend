<div id="vue-sku-app" class="container">
    <div class="notification is-light" :class="msg_class" v-show="msg" v-text="msg"></div>
    <form>
        <div v-for="(group, index) in sku" :key="index" class="field">
            <div class="field-body">
                <div v-for="(value, key) in group" :key="key" class="field">
                    <label class="label">{{sku_keys[key]}}</label>
                    <div class="control">
                        <input class="input is-small" :placeholder="key" v-model="group[key]" />
                    </div>
                </div>
            </div>
            <div class="has-text-centered mt-1">
                <button v-if="index == sku.length - 1" class="button is-small" @click="addGroup">+</button>
                <button v-else class="button is-small" title="Remove" @click="removeGroup(index)">-</button>
            </div>
        </div>
        <div class="field has-text-centered">
            <button type="button" :class="submit_class" @click="handleSubmit($event)">{{submit_text}}</button>
        </div>
    </form>
</div>
<script>
    var parent = document.querySelector("#vue-sku-app").parentNode;
    Vue.createApp({
        data() {
            let default_data = {
                "post_id": 0,
                "sku": [],
                "sku_keys": [],
                "sign": "",
                "sign_key": "",
                "msg": "",
                "msg_class": "is-primary",
                "submit_text": "Submit"
            };
            return Object.assign(default_data, app_data);
        },
        methods: {
            addGroup() {
                const newGroup = {};
                for (const key in this.sku[0]) {
                    newGroup[key] = '';
                }
                this.sku.push(newGroup);
            },
            removeGroup(index) {
                if (this.sku.length <= 1) {
                    return;
                }
                this.sku.splice(index, 1);
            },
            async handleSubmit(e) {
                e.target.classList.add("is-loading");
                let data = { "post_id": this.post_id, "sku": this.sku };
                data[this.sign_key] = this.sign;
                let res = await wnd_ajax_action(this.action, data);
                this.msg = res.msg;
                this.msg_class = res.status > 0 ? "is-success" : "is-danger";
                e.target.classList.remove("is-loading");
            },
        },
        computed: {
            submit_class() {
                return `button is-${wnd.color.primary}`;
            }
        },
        updated() {
            this.$nextTick(() => {
                funTransitionHeight(parent, trs_time);
            });
        }
    }).mount('#vue-sku-app');
</script>