<section id="recharge-app" class="section container">
    <div v-show="paymentInterface" id="payment-interface" class="has-text-centered"></div>

    <div v-show="!paymentInterface">
        <div class="notification is-danger is-light" v-show="msg" v-html="msg"></div>
        <div class="box">
            <div class="field">
                <input type="number" v-model="amount" class="input" :placeholder="t('amount')" min="0.01" step="0.01" />
            </div>
            <div class="buttons has-addons is-centered">
                <button v-for="method in availableMethods" :key="method.value" class="button" :class="{ 'is-danger': paymentMethod === method.value }" @click="selectPayment(method.value)">
                    <span class="icon is-small" v-if="method.icon" v-html="method.icon"></span>
                    <span>{{ method.label }}</span>
                </button>
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
        const parent = document.querySelector('#recharge-app').parentNode;
        const { createApp } = Vue;
        let data = JSON.parse(JSON.stringify(module_data));
        createApp({
            data() {
                return {
                    wnd: wnd,
                    msg: data.msg,
                    amount: "",
                    paymentMethod: data.default_gateway,
                    availableMethods: data.payments,

                    // 提交结果
                    paymentInterface: null,
                    paymentChecker: null,
                };
            },
            methods: {
                t(key) {
                    const lang = {
                        en_US: {
                            'amount': 'Amount',
                            'submit': 'Submit',
                        },
                        zh_CN: {
                            'amount': '充值金额',
                            'submit': '提交',
                        }
                    };
                    return lang[data.lang] ? lang[data.lang][key] : lang.en_US[key];
                },
                selectPayment(method) {
                    this.paymentMethod = method;
                },
                async submitOrder() {

                    const order = {
                        total_amount: this.amount ? this.amount.toFixed(2) : 0,
                        payment_gateway: this.paymentMethod,
                        _wnd_sign: data.sign,
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
                check_transaction(slug) {
                    this.paymentChecker = setInterval(this.wnd_check_payment, 3000, slug);
                    document.removeEventListener("click", this.handleModalClose);
                    document.addEventListener("click", this.handleModalClose);
                },
                async wnd_check_payment(slug) {
                    let response = await wnd_query("wnd_get_transaction", { slug: slug });

                    if (response.data.status === "completed") {
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
        }).mount('#recharge-app');
    }
</script>