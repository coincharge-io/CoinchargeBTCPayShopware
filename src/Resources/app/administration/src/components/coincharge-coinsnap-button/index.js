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
				"CoinchargeBTCPayShopware.config.btcpayServerUrl": "",
			},
		};
	},
	methods: {
		testConnection() {
			this.isLoading = true;

			// if (!this.credentialsExist()) {
			// 	this.isLoading = false;
			// 	return this.createNotificationWarning({
			// 		title: "Coinsnap",
			// 		message: this.$tc(
			// 			"coincharge-coinsnap-test-connection.missing_credentials",
			// 		),
			// 	});
			// }
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
		credentialsExist() {
			if (
				document.getElementById(
					"CoinchargeBTCPayShopware.config.btcpayServerUrl",
				).value === "" ||
				document.getElementById("CoinchargeBTCPayShopware.config.btcpayApiKey")
					.value === "" ||
				document.getElementById(
					"CoinchargeBTCPayShopware.config.btcpayServerStoreId",
				).value === ""
			) {
				return false;
			}
			return true;
		},
	},
});
