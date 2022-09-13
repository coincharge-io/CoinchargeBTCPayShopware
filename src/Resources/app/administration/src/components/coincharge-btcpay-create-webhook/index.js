const { Component, Mixin, ApiService } = Shopware;
import template from './coincharge-btcpay-create-webhook.html.twig';


Component.register('coincharge-btcpay-create-webhook', {
    template,
    inject: [
        ['coinchargeBtcpayApiService']
    ],
    mixins: [
        Mixin.getByName('notification')
    ],
    data() {
        return {
            isLoading: false,
            webhookValues:
            {
                'BTCPayShopware.config.btcpayWebhookSecret': '',
                'BTCPayShopware.config.btcpayWebhookId': ''
            }
        };
    },
    methods: {
        createWebhook() {
            this.isLoading = true;
            const systemConfig = ApiService.getByName('systemConfigApiService')

            this.coinchargeBtcpayApiService.generateWebhook().then((ApiResponse) => {

                if (ApiResponse.success === false) {
                    this.createNotificationWarning({
                        title: 'BTCPay Server',
                        message: ApiResponse.message
                    })
                    this.isLoading = false;
                    return;
                }
                this.webhookValues['BTCPayShopware.config.btcpayWebhookSecret'] = ApiResponse.message.secret
                this.webhookValues['BTCPayShopware.config.btcpayWebhookId'] = ApiResponse.message.id
                systemConfig.saveValues(this.webhookValues)

                this.createNotificationSuccess({
                    title: 'BTCPay Server',
                    message: this.$tc('coincharge-btcpay-test-connection.success')
                });
                this.isLoading = false;
            });
        },
    }
});