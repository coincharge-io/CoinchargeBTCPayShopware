const ApiService = Shopware.Classes.ApiService;

export default class CoinchargeBtcpayApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'coincharge-btcpay') {
        super(httpClient, loginService, apiEndpoint);
    }
    generateApiKey() {

    }
    verifyApiKey(globalPluginConfig, actualPluginConfig) {
        const apiRoute = `${this.getApiBasePath()}/verify-api-key`;
        const headers = this.getBasicHeaders()

        return this.httpClient.post(
            apiRoute,
            {
                globalPluginConfig: globalPluginConfig,
                actualPluginConfig: actualPluginConfig
            },
            {
                headers
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

}