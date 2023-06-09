/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

const ApiService = Shopware.Classes.ApiService;

export default class CoinchargeCoinsnapApiService extends ApiService {
	constructor(httpClient, loginService, apiEndpoint = "coincharge") {
		super(httpClient, loginService, apiEndpoint);
	}
	verifyApiKey() {
		const apiRoute = `/_action/${this.getApiBasePath()}/coinsnap_verify`;
		const headers = this.getBasicHeaders();

		return this.httpClient
			.get(apiRoute, { headers })
			.then((response) => {
				return ApiService.handleResponse(response);
			})
			.catch((error) => {
				throw error;
			});
	}
}
