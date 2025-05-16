<section id="sku-app">
    <div class="container">
        <h1 class="title">SKU 管理</h1>

        <!-- SKU 列表 -->
        <table class="table is-fullwidth is-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th v-for="attr in attributeKeys">{{ attr }}</th>
                    <th>价格</th>
                    <th>库存</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(sku, id) in skus" :key="id" :class="{ 'has-background-warning': editingSkuId === id }">
                    <td>{{ id }}</td>
                    <td v-for="attr in attributeKeys">{{ sku[attr] }}</td>
                    <td>¥{{ sku.price }}</td>
                    <td>{{ sku.stock }}</td>
                    <td>
                        <button class="button is-small is-info" @click="editSku(id)">修改</button>
                        <button class="button is-small is-danger ml-2" @click="removeSku(id)">删除</button>
                        <button class="button is-small is-primary ml-2" @click="copySku(id)">复制</button>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- 动态输入 -->
        <div class="box columns is-multiline">
            <div class="column" v-for="attr in attributeKeys" :key="attr">
                <label class="label">{{ attr }}</label>
                <div class="control">
                    <input class="input" v-model="newSku[attr]" :placeholder="`请输入 ${attr}`">
                </div>
            </div>

            <div class="column">
                <label class="label">价格</label>
                <div class="control">
                    <input class="input" v-model="newSku.price" placeholder="请输入价格">
                </div>
            </div>

            <div class="column">
                <label class="label">库存</label>
                <div class="control">
                    <input class="input" v-model="newSku.stock" placeholder="请输入库存">
                </div>
            </div>

            <div class="column is-full">
                <p class="help is-danger" v-if="duplicateWarning">已有相同属性组合，请勿重复添加</p>
                <div class="buttons is-centered">
                    <button v-if="editingSkuId" class="is-small button is-primary" @click="updateSku()">
                        更新 SKU: {{editingSkuId}}
                    </button>
                    <button v-if="editingSkuId" class="is-small button is-light" @click="cancelEdit">取消编辑</button>
                    <button class="button is-primary is-small" @click="addSku()">添加 SKU</button>
                </div>
            </div>
        </div>

        <!-- 提交按钮 -->
        <div class="has-text-centered mt-4">
            <button :class="`button is-${wnd.color.primary}`" @click="submitSkus">保存</button>
            <div class="notification is-primary is-light mt-2" v-show="msg" v-html="msg"></div>
        </div>
    </div>
</section>

<script>
    {
        const parent = document.querySelector('#sku-app').parentNode;
        const data = JSON.parse(JSON.stringify(module_data));

        const { createApp } = Vue;
        createApp({
            data() {
                return {
                    wnd: wnd,
                    skus: data.skus,
                    sku_keys: data.sku_keys,
                    newSku: {},
                    editingSkuId: null,
                    duplicateWarning: false,
                    msg: '',
                };
            },
            computed: {
                attributeKeys() {
                    const keys = new Set();
                    Object.keys(this.sku_keys).forEach(key => {
                        if (!['price', 'stock', 'name'].includes(key)) {
                            keys.add(key);
                        }
                    });
                    return Array.from(keys);
                }
            },
            methods: {
                getCombinationKey(sku) {
                    return this.attributeKeys.map(k => sku[k] || "").join("||").toLowerCase();
                },
                validateAndSaveSku(isUpdate) {
                    // 去除空格
                    Object.keys(this.newSku).forEach(k => {
                        if (typeof this.newSku[k] === 'string') {
                            this.newSku[k] = this.newSku[k].trim();
                        }
                    });

                    // 检查必填项
                    const missing = this.attributeKeys.find(k => !this.newSku[k]);
                    if (missing) {
                        alert(`属性 [${missing}] 是必填项`);
                        return false;
                    }

                    // 价格和库存字段固定，且为必须
                    if (!this.newSku.price || !this.newSku.stock) {
                        alert("价格和库存必须填写");
                        return false;
                    }

                    // 检查重复
                    const key = this.getCombinationKey(this.newSku);
                    const duplicate = Object.entries(this.skus).some(([id, sku]) =>
                        (!isUpdate || id !== this.editingSkuId) && this.getCombinationKey(sku) === key
                    );
                    if (duplicate) {
                        this.duplicateWarning = true;
                        return false;
                    }

                    // 更新或添加 SKU
                    if (isUpdate) {
                        this.skus[this.editingSkuId] = {
                            ...Object.fromEntries(this.attributeKeys.map(k => [k, this.newSku[k] || ""])),
                            price: parseFloat(this.newSku.price).toFixed(2),
                            stock: parseInt(this.newSku.stock)
                        };
                        this.editingSkuId = null;
                    } else {
                        const newId = "sku_" + Object.keys(this.skus).length;
                        this.skus[newId] = {
                            ...Object.fromEntries(this.attributeKeys.map(k => [k, this.newSku[k] || ""])),
                            price: parseFloat(this.newSku.price).toFixed(2),
                            stock: parseInt(this.newSku.stock)
                        };
                    }

                    this.newSku = {};
                    this.duplicateWarning = false;
                    return true;
                },
                addSku() {
                    this.validateAndSaveSku(false);
                },
                updateSku() {
                    this.validateAndSaveSku(true);
                },
                editSku(skuId) {
                    const sku = this.skus[skuId];
                    this.editingSkuId = skuId;
                    this.newSku = {
                        ...Object.fromEntries(this.attributeKeys.map(k => [k, sku[k] || ""])),
                        price: sku.price,
                        stock: sku.stock
                    };
                },
                copySku(skuId) {
                    const sku = this.skus[skuId];
                    this.editingSkuId = null;
                    this.newSku = {
                        ...Object.fromEntries(this.attributeKeys.map(k => [k, sku[k] || ""])),
                        price: sku.price,
                        stock: sku.stock
                    };
                },
                cancelEdit() {
                    this.editingSkuId = null;
                    this.newSku = {};
                    this.duplicateWarning = false;
                },
                removeSku(id) {
                    if (Object.keys(this.skus).length === 1) {
                        alert("至少保留一个 SKU");
                        return;
                    };
                    delete this.skus[id];
                },
                async submitSkus() {
                    let res = await wnd_ajax_action("common/wnd_set_sku", {
                        "sku": this.skus, "post_id": data.post_id, "_wnd_sign": data.sign
                    });
                    this.msg = res.msg;
                }
            },
            updated() {
                this.$nextTick(() => {
                    funTransitionHeight(parent, trs_time);
                });
            }
        }).mount("#sku-app");
    }
</script>