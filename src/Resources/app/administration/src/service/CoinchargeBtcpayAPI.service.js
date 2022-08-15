const ApiService = Shopware.Classes.ApiService;

export default class CoinchargeBtcpayApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'coincharge-btcpay') {
        super(httpClient, loginService, apiEndpoint);
    }
    generateApiKey(btcpayServerUrl, shopwareSettingsPageUrl) {
        const apiRoute = `${this.getApiBasePath()}/generate-api-key`;
        const headers = this.getBasicHeaders()
        return this.httpClient.post(
            apiRoute,
            {
                btcpayServerUrl, shopwareSettingsPageUrl
            },
            {
                headers
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
    verifyApiKey() {
        const apiRoute = `${this.getApiBasePath()}/verify-api-key`;
        const headers = this.getBasicHeaders()

        return this.httpClient.post(
            apiRoute,
            {

            },
            {
                headers
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}
