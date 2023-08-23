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
			coinsnapStoreId: "",
			coinsnapApiKey: "",
		};
	},
	mounted() {
		const systemConfig = ApiService.getByName("systemConfigApiService");
		systemConfig
			.getValues("CoinchargeBTCPayShopware.config")
			.then((r) => {
				this.coinsnapApiKey =
					r["CoinchargeBTCPayShopware.config.coinsnapApiKey"];
				this.coinsnapStoreId =
					r["CoinchargeBTCPayShopware.config.coinsnapStoreId"];
			})
			.catch((e) => console.log(e));
	},
	computed: {
		isDisabled() {
			if (!this.coinsnapStoreId || !this.coinsnapApiKey) {
				return true;
			}
		},
	},
	methods: {
		testConnection() {
			this.isLoading = true;
			if (!this.credentialsExist()) {
				this.isLoading = false;
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
		saveCredentials() {
			const systemConfig = ApiService.getByName("systemConfigApiService");
			const coinsnapStoreId = document.getElementById(
				"CoinchargeBTCPayShopware.config.coinsnapStoreId"
			).value;
			const coinsnapApiKey = document.getElementById(
				"CoinchargeBTCPayShopware.config.coinsnapApiKey"
			).value;
			systemConfig.saveValues({
				"CoinchargeBTCPayShopware.config.coinsnapStoreId": coinsnapStoreId,
				"CoinchargeBTCPayShopware.config.coinsnapApiKey": coinsnapApiKey,
			});
			window.location.reload();
		},
		credentialsExist() {
			const systemConfig = ApiService.getByName("systemConfigApiService");
			if (
				document.getElementById(
					"CoinchargeBTCPayShopware.config.coinsnapStoreId"
				).value == "" ||
				document.getElementById(
					"CoinchargeBTCPayShopware.config.coinsnapApiKey"
				).value == ""
			) {
				systemConfig.saveValues({
					"CoinchargeBTCPayShopware.config.coinsnapIntegrationStatus": false,
				});
				return false;
			}
			return true;
		},
	},
});
