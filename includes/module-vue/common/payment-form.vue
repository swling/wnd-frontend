<section id="payment-app" class="container">
    <div v-show="paymentInterface" id="payment-interface" class="has-text-centered"></div>
    <div v-show="!paymentInterface">
        <div class="notification is-danger is-light" v-show="msg" v-html="msg"></div>
        <h3 class="subtitle">{{title}}</h3>
        <!-- 商品表格 -->
        <table v-if="'single'!=skuId" class="table is-fullwidth is-bordered is-striped is-size-7-mobile" style="table-layout: fixed;">
            <thead>
                <tr>
                    <th v-for="(key, index) in displayKeys" :key="index">{{ keyLabels[key] || key }}</th>
                    <th>数量（库存 {{allSkus[skuId].stock}}）</th>
                    <th>小计</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td v-for="key in displayKeys" :key="key">{{ allSkus[skuId][key] }}</td>
                    <td>
                        <input class="input is-small" type="number" min="0" :max="allSkus[skuId].stock" :value="quantity" @input="updateQuantity($event.target.value)">
                    </td>
                    <td>¥{{ (quantity * parseFloat(allSkus[skuId].price)).toFixed(2) }} </td>
                </tr>
            </tbody>
        </table>
        <!-- 支付方式 -->
        <div class="box">
            <p class="subtitle">
                总金额：<strong class="has-text-danger">¥{{ totalPrice.toFixed(2) }}</strong>（余额：¥{{ userBalance.toFixed(2) }}）
            </p>
            <div class="buttons has-addons">
                <button v-for="method in availableMethods" :key="method.value" class="button" :class="{ 'is-danger': paymentMethod === method.value }" @click="selectPayment(method.value)" :disabled="method.value === 'internal' && !canUseBalance">
                    <span class="icon is-small" v-if="method.icon" v-html="method.icon"></span>
                    <span>{{ method.label }}</span>
                </button>
            </div>
        </div>
        <div class="box mt-5" v-if="'1'!=isVirtual">
            <h3 class="title is-6 mb-3">收货信息</h3>
            <div class="columns">
                <div class="column is-2">姓名<text-editor :pre_editing="!receiver.name" v-model="receiver.name"></text-editor></div>
                <div class="column is-2">电话<text-editor :pre_editing="!receiver.phone" v-model="receiver.phone"></text-editor></div>
                <div class="column">地址<text-editor :pre_editing="!receiver.address" v-model="receiver.address"></text-editor></div>
            </div>
        </div>
        <!-- 提交按钮 -->
        <div class="field mt-4 has-text-centered">
            <button :class="`button is-${wnd.color.primary}`" @click="submitOrder" v-text="t('submit')"></button>
        </div>
    </div>
</section>
<script>
    {
        if ("undefined" == typeof TextEditor) {
            TextEditor = false;
        }
        const parent = document.querySelector('#payment-app').parentNode;
        const { createApp } = Vue;
        let data = JSON.parse(JSON.stringify(module_data));
        createApp({
            components: {
                TextEditor: TextEditor,
            },
            data() {
                return {
                    wnd: wnd,
                    msg: data.msg,

                    postId: data.post_id || 0,
                    skuId: data.sku_id || "single", // single 即为未设置 sku 的固定价格产品，如：付费阅读
                    quantity: data.quantity || 1,
                    isVirtual: data.is_virtual, //是否为虚拟产品（无需实体物流）
                    title: data.title || "订单结算",

                    allSkus: data.skus,

                    userBalance: data.balance,
                    paymentMethod: data.default_gateway,
                    availableMethods: data.payments,
                    receiver: data.receiver,

                    // 模拟提交结果
                    paymentInterface: null,
                    paymentChecker: null,
                };
            },
            computed: {
                totalPrice() {
                    const price = parseFloat(this.allSkus[this.skuId]?.price || 0);
                    const quantity = parseInt(this.quantity) || 0;
                    return quantity * price;
                },
                canUseBalance() {
                    return this.userBalance >= this.totalPrice && this.totalPrice >= 0;
                },
                displayKeys() {
                    const firstSku = Object.values(this.allSkus)[0];
                    // 排除非展示字段
                    const excluded = ['name', 'stock'];
                    return Object.keys(firstSku).filter(key => !excluded.includes(key));
                },
                keyLabels() {
                    // 可选：美化表头名称
                    return {
                        name: "名称",
                        price: "价格",
                        stock: "库存",
                        color: "颜色",
                        size: "尺寸",
                    };
                }
            },
            watch: {
                totalPrice(newVal) {
                    if (!this.canUseBalance && this.paymentMethod === 'internal') {
                        this.paymentMethod = '';
                    }
                }
            },
            methods: {
                t(key) {
                    const lang = {
                        en_US: {
                            'submit': 'Submit',
                        },
                        zh_CN: {
                            'submit': '提交订单',
                        }
                    };
                    return lang[data.lang] ? lang[data.lang][key] : lang.en_US[key];
                },
                updateQuantity(val) {
                    const intVal = parseInt(val.trim());
                    const stock = this.allSkus[this.skuId].stock;

                    if (isNaN(intVal) || intVal < 1) {
                        this.quantity = 0;
                    } else if (intVal > stock) {
                        this.quantity = stock;
                        alert(`库存不足，仅剩 ${stock} 件`);
                    } else {
                        this.quantity = intVal;
                    }
                },
                selectPayment(method) {
                    this.paymentMethod = method;
                },
                async submitOrder() {
                    if (!this.check_order()) {
                        return;
                    }

                    // 保存用户收货地址
                    if (wnd.user_id) {
                        wnd_ajax_action("user/wnd_update_receiver", { "receiver": this.receiver });
                    }

                    const order = {
                        post_id: this.postId,
                        sku_id: this.skuId,
                        quantity: this.quantity,
                        payment_gateway: this.paymentMethod,
                        receiver: this.receiver,
                        is_virtual: this.isVirtual,
                        '_wnd_sign': data.sign,
                    };

                    let res = await wnd_ajax_action("common/wnd_do_payment", order);
                    if (res.status <= 0) {
                        alert(res.msg);
                        return;
                    }
                    this.paymentInterface = res.data.interface;
                    wnd_inner_html("#payment-interface", this.paymentInterface);
                    this.check_transaction(res.data.transaction.slug);
                },
                check_order() {
                    if (!wnd.user_id && !navigator.cookieEnabled) {
                        alert("您的浏览器禁用了 cookie，不支持匿名支付！");
                        return;
                    }

                    if (this.quantity <= 0) {
                        alert("数量不能为 0");
                        return false;
                    }
                    if ("1" == this.isVirtual) {
                        return true;
                    }

                    if (!this.receiver.name.trim()) {
                        alert("请填写收货人姓名");
                        return false;
                    }
                    if (!/^1[3-9]\d{9}$/.test(this.receiver.phone.trim())) {
                        alert("请填写有效的手机号码");
                        return false;
                    }
                    if (!this.receiver.address.trim()) {
                        alert("请填写收货地址");
                        return false;
                    }

                    return true;
                },
                check_transaction(slug) {
                    this.paymentChecker = setInterval(this.wnd_check_payment, 3000, slug);
                    document.removeEventListener("click", this.handleModalClose);
                    document.addEventListener("click", this.handleModalClose);
                },
                async wnd_check_payment(slug) {
                    let response = await wnd_query("wnd_get_transaction", { slug: slug });

                    if (response.data.status == "completed" || response.data.status == "paid") {
                        let title = document.querySelector("#payment-title");
                        if (title) {
                            title.innerText = "支付成功！/ Payment successful!";
                        }
                        clearInterval(this.paymentChecker);

                        if (response.data.object_id > 0) {
                            window.location.href = response.data.object_url;
                        }
                        if (typeof wnd_payment_callback === "function") {
                            wnd_payment_callback(response);
                        }
                    }
                },
                handleModalClose(e) {
                    if (e.target.classList.contains("modal-close")) {
                        clearInterval(this.paymentChecker);
                        return;
                    }

                    let div = e.target.closest("div");
                    if (div && div.classList.contains("modal-background")) {
                        clearInterval(this.paymentChecker);
                        return;
                    }
                },
            },
            beforeUnmount() {
                // 清理定时器和事件监听器
                if (this.paymentChecker) {
                    clearInterval(this.paymentChecker);
                }
                document.removeEventListener("click", this.handleModalClose);
            },
            updated() {
                funTransitionHeight(parent, trs_time);
            }
        }).mount('#payment-app');
    }
</script>