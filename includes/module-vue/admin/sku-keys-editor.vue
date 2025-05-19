<div id="sku-keys-app">
    <p class="has-text-danger mb-3">
        <i class="fas fa-info-circle"></i>【价格】【库存】为固定属性无需单独添加
    </p>
    <div class="box" v-for="(group, gIndex) in groups" :key="gIndex" style="border-left:3px solid #F14668">
        <div class="level">
            <div class="level-left">
                <div class="level-item">
                    <input class="input is-small" v-model="group.name" :disabled="!group.editable" placeholder="产品类型名称" />
                </div>
            </div>
            <div class="level-right">
                <div class="level-item">
                    <button class="button is-small is-warning" @click="toggleEdit(gIndex)">
                        <i class="fas" :class="group.editable ? 'fa-times' : 'fa-pen'"></i>
                    </button>
                    <button class="button is-small is-success ml-2" @click="saveGroup(gIndex)" :disabled="!group.editable">
                        <i class="fas fa-save mr-1"></i> 保存
                    </button>
                    <button class="button is-small is-info ml-2" title="复制整个属性集合" @click="copyGroup(gIndex)">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button class="button is-small is-danger ml-2" @click="deleteGroup(gIndex)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="is-flex is-flex-wrap-wrap" style="gap: 1rem; margin-top: 0.5rem;">
            <div class="is-flex is-align-items-center" style="max-width:200px; flex-grow:1;" v-for="(attr, index) in group.attrs" :key="index">
                <input class="input is-small" v-model="group.attrs[index]" :disabled="!group.editable" @blur="deduplicate(gIndex)" @keydown.enter.prevent="saveGroup(gIndex)" placeholder="属性名" />
                <button class="button is-small is-danger" v-if="group.editable" @click="removeAttr(gIndex, index)" title="删除属性">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>

        <button class="button is-link is-light is-small mt-3" v-if="group.editable" @click="addAttr(gIndex)">
            <i class="fas fa-plus mr-1"></i> 添加属性
        </button>
    </div>
    <div class="has-text-right">
        <button class="button is-primary is-small mt-4" @click="addGroup">
            <i class="fas fa-layer-group mr-1"></i> 添加 SKU
        </button>
    </div>
    <div class="buttons is-centered is-hidden">
        <button class="button is-warning mt-4" @click="submit" :class="{ 'is-loading': isSubmitting }">
            保存
        </button>
    </div>
    <div class="notification is-light is-primary" v-html="msg" v-show="msg" :class="{'is-danger': res.status < 1 }"></div>
</div>

<script>
    {
        const parent = document.querySelector('#sku-keys-app').parentNode;
        const data = JSON.parse(JSON.stringify(module_data));
        let groups = [{ name: '', attrs: [], editable: true }];
        if (data.sku_keys.length) {
            groups = data.sku_keys;
        }
        const app = Vue.createApp({
            data() {
                return {
                    groups: groups,
                    isSubmitting: false,
                    res: {},
                    msg: null
                };
            },
            methods: {
                addGroup() {
                    this.groups.push({
                        name: '',
                        attrs: [],
                        editable: true
                    });
                },
                deleteGroup(index) {
                    if (confirm('确认删除该属性集合？')) {
                        this.groups.splice(index, 1);
                    }

                    this.submit();
                },
                saveGroup(groupIndex) {
                    const group = this.groups[groupIndex];
                    this.deduplicate(groupIndex);
                    group.editable = false;
                    // 修改后及时提交保存
                    this.submit();
                },
                addAttr(groupIndex) {
                    this.groups[groupIndex].attrs.push('');
                },
                removeAttr(groupIndex, attrIndex) {
                    this.groups[groupIndex].attrs.splice(attrIndex, 1);
                },
                deduplicate(groupIndex) {
                    const seen = new Set();
                    this.groups[groupIndex].attrs = this.groups[groupIndex].attrs.filter(attr => {
                        const trimmed = attr.trim();
                        if (!trimmed || seen.has(trimmed)) return false;
                        seen.add(trimmed);
                        return true;
                    });
                },
                toggleEdit(groupIndex) {
                    this.groups[groupIndex].editable = !this.groups[groupIndex].editable;
                },
                copyGroup(groupIndex) {
                    const srcGroup = this.groups[groupIndex];
                    const newGroup = {
                        name: srcGroup.name + '_copy',
                        attrs: [...srcGroup.attrs],
                        editable: true
                    };
                    this.groups.push(newGroup);
                },
                validateGroups() {
                    const nameMap = new Map();
                    for (let i = 0; i < this.groups.length; i++) {
                        const name = this.groups[i].name.trim();
                        if (!name) {
                            alert(`第 ${i + 1} 组属性名称不能为空`);
                            return false;
                        }
                        if (nameMap.has(name)) {
                            alert(`产品类型名称重复：「${name}」`);
                            return false;
                        }
                        nameMap.set(name, true);
                    }
                    return true;
                },
                async submit() {
                    if (!this.validateGroups()) return;
                    this.isSubmitting = true;

                    // 过滤掉 attrs 为空数组的 group
                    const filteredGroups = this.groups.filter(group => group.attrs.length > 0);
                    // 深拷贝并移除 editable
                    const payload = this.groups.map(({ name, attrs }) => ({
                        name: name.trim(),
                        attrs: attrs.map(a => a.trim()).filter(Boolean)
                    }));

                    // 提交过滤后的数据
                    this.msg = null;
                    const res = await wnd_ajax_action("admin/wnd_update_store_settings", { "sku_keys": payload });
                    this.isSubmitting = false;
                    this.res = res;
                    this.msg = res.msg;
                }
            },
            updated() {
                this.$nextTick(() => {
                    funTransitionHeight(parent, trs_time);
                });
            }
        });
        app.mount('#sku-keys-app');
        vueInstances.push(app);
    }
</script>