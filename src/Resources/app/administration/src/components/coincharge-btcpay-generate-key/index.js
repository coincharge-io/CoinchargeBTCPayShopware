const { Component, Mixin } = Shopware;
import template from './coincharge-btcpay-generate-key.html.twig';


Component.register('coincharge-btcpay-generate-key', {
    template,
    inject: [
        'coinchargeBtcpayApiService'
    ],
    mixins: [
        Mixin.getByName('notification')
    ],
    data() {
        return {
            isLoading: false,
        };
    },
    computed: {

    },
    methods: {
        check() {
            this.isLoading = true;

            this.coinchargeBtcpayApiService.verifyApiKey().then((ApiResponse) => {
                if (ApiResponse.success === false) {
                    this.createNotificationWarning({
                        title: 'BTCPay Server',
                        message: this.$tc('coincharge-btcpay-generate-key.error')
                    })
                    this.isLoading = false;
                    return;
                }
                this.createNotificationSuccess({
                    title: 'BTCPay Server',
                    message: this.$tc('coincharge-btcpay-generate-key.success')
                });
                this.isLoading = false;
            });
        },
    }
});