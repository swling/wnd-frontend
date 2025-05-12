<div id="product-order-app" class="box">
    <!-- 筛选属性 -->
    <div class="field is-horizontal" v-for="(values, key) in options" :key="key">
        <div class="field-label is-normal">
            <label class="label">{{ key }}</label>
        </div>
        <div class="field-body">
            <div class="field is-grouped is-grouped-multiline">
                <div class="control" v-for="val in values" :key="val">
                    <button class="button" :class="{ 'is-danger': selected[key] === val }" :disabled="!isOptionAvailable(key, val)" @click="selectOption(key, val)">
                        {{ val }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 数量 -->
    <div class="field is-horizontal">
        <div class="field-label is-normal">
            <label class="label">数量</label>
        </div>
        <div class="field-body">
            <div class="field">
                <div class="control">
                    <input class="input" type="number" min="1" v-model.number="quantity" />
                </div>
                <p class="help is-danger" v-if="selectedSku && quantity > selectedSku.stock">
                    库存不足，仅剩 {{ selectedSku.stock }} 件
                </p>
            </div>
        </div>
    </div>

    <!-- 显示价格和库存 -->
    <div v-if="selectedSku" class="notification is-warning is-light">
        当前价格：¥{{ selectedSku.price }} 当前库存：{{ selectedSku.stock }} 总价格：¥{{ totalPrice }}
        SKU：{{selected}} {{selectedSkuId}}
    </div>
    <div v-else class="notification is-warning">
        请选择完整的商品属性组合
    </div>

    <!-- 提交 -->
    <div class="field is-grouped">
        <div class="control">
            <button :class="`button is-${wnd.color.primary}`" :disabled="!selectedSku || quantity > selectedSku.stock" @click="submitOrder">
                {{t('submit')}}
            </button>
        </div>
    </div>
</div>

<script>
    {
        let data = JSON.parse(JSON.stringify(module_data));
        const app = Vue.createApp({
            data() {
                return {
                    wnd: wnd,
                    post_id: data.post_id,
                    skus: data.skus,
                    isVirtual: ('1' == data.is_virtual) ? 1 : 0,
                    selected: {},
                    quantity: 1
                };
            },
            computed: {
                options() {
                    const map = {};
                    for (const sku of Object.values(this.skus)) {
                        for (const key in sku) {
                            if (["price", "stock", "name"].includes(key)) continue;
                            map[key] ||= new Set();
                            map[key].add(sku[key]);
                        }
                    }
                    return Object.fromEntries(Object.entries(map).map(([k, v]) => [k, [...v]]));
                },
                selectedSku() {
                    return Object.entries(this.skus).find(([id, sku]) =>
                        Object.entries(this.selected).every(([k, v]) => sku[k] === v)
                    )?.[1] || null;
                },
                selectedSkuId() {
                    return Object.entries(this.skus).find(([id, sku]) =>
                        Object.entries(this.selected).every(([k, v]) => sku[k] === v)
                    )?.[0] || null;
                },
                totalPrice() {
                    if (!this.selectedSku) return "0.00";
                    const total = parseFloat(this.selectedSku.price) * this.quantity;
                    return total.toFixed(2);
                }
            },
            methods: {
                t(key) {
                    const lang = {
                        en_US: {
                            'submit': 'Submit',
                        },
                        zh_CN: {
                            'submit': '立即购买',
                        }
                    };
                    return lang[data.lang] ? lang[data.lang][key] : lang.en_US[key];
                },
                selectOption(key, val) {
                    this.selected[key] = this.selected[key] === val ? null : val;
                },
                isOptionAvailable(key, val) {
                    // 生成假设选项集合
                    const hypothetical = { ...this.selected, [key]: val };

                    return Object.values(this.skus).some((sku) => {
                        // 检查是否匹配所有已选项
                        return Object.entries(hypothetical).every(([k, v]) => v == null || sku[k] === v)
                            && sku.stock > 0;
                    });
                },
                async submitOrder() {
                    const payload = {
                        post_id: this.post_id,
                        is_virtual: this.isVirtual,
                        quantity: this.quantity,
                        sku_id: this.selectedSkuId,
                    };

                    if (this.isVirtual) {
                        let res = wnd_ajax_modal("common/wnd_payment_form", payload);
                    } else {
                        const queryString = new URLSearchParams(payload).toString();
                        const targetURL = wnd.dashboard_url + `/?module=common/wnd_payment_form&${queryString}`;
                        // 跳转
                        window.location.href = targetURL;
                        // 或：window.location.assign(targetURL);
                    }
                }
            },
            mounted() {
                const entry = Object.values(this.skus).find(sku => sku.stock > 0);
                if (entry) {
                    const selected = {};
                    for (const key in entry) {
                        if (!["price", "stock", "name"].includes(key)) {
                            selected[key] = entry[key];
                        }
                    }
                    this.selected = selected;
                }
            }
        });
        app.mount("#product-order-app");
    }
</script>