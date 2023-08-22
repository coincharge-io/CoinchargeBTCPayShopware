/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

const { Component, Mixin, ApiService } = Shopware;
import template from "./coincharge-coinsnap-button.html.twig";
import "./coincharge-coinsnap-button.scss";

Component.register("coincharge-coinsnap-button", {
	template: template,
	inject: [["coinchargeCoinsnapApiService"]],
	mixins: [Mixin.getByName("notification")],
	data() {
		return {
			isLoading: false,
			config: {
				"CoinchargeBTCPayShopware.config.coinsnapStoreId": "",
				"CoinchargeBTCPayShopware.config.coinsnapApiKey": "",
			},
		};
	},
	methods: {
		testConnection() {
			this.isLoading = true;
			const systemConfig = ApiService.getByName("systemConfigApiService");
			const coinsnapStoreId = document.getElementById(
				"CoinchargeBTCPayShopware.config.coinsnapStoreId"
			).value;
			const coinsnapApiKey = document.getElementById(
				"CoinchargeBTCPayShopware.config.coinsnapApiKey"
			).value;
			systemConfig.saveValues({
				"CoinchargeBTCPayShopware.config.coinsnapApiKey": coinsnapApiKey,
				"CoinchargeBTCPayShopware.config.coinsnapStoreId": coinsnapStoreId,
			});
			if (coinsnapApiKey == "" || coinsnapStoreId == "") {
				return this.createNotificationWarning({
					title: "BTCPay Server",
					message: this.$tc(
						"coincharge-coinsnap-test-connection.missing_credentials"
					),
				});
			}
			this.coinchargeCoinsnapApiService
				.verifyApiKey()
				.then((ApiResponse) => {
					if (ApiResponse.success === false) {
						this.createNotificationWarning({
							title: "Coinsnap",
							message: ApiResponse.message,
						});
						this.isLoading = false;
						return;
					}
					this.createNotificationSuccess({
						title: "Coinsnap",
						message: this.$tc("coincharge-coinsnap-test-connection.success"),
					});

					this.isLoading = false;
					window.location.reload();
				})
				.catch((e) => {
					this.isLoading = false;
					return this.createNotificationError({
						title: "Coinsnap",
						message: this.$tc("coincharge-coinsnap-test-connection.error"),
					});
				});
		},
	},
});
