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

const CONFIG_PREFIX = "CoinchargeBTCPayShopware.config.";
const CONFIG_KEYS = {
	storeId: `${CONFIG_PREFIX}coinsnapStoreId`,
	apiKey: `${CONFIG_PREFIX}coinsnapApiKey`,
	integrationStatus: `${CONFIG_PREFIX}coinsnapIntegrationStatus`,
};

Component.register("coincharge-coinsnap-button", {
	template,
	inject: ["coinchargeCoinsnapApiService"],
	mixins: [Mixin.getByName("notification")],
	data() {
		return {
			isLoading: false,
			systemConfigService: ApiService.getByName("systemConfigApiService"),
			formVersion: 0,
			fieldListeners: [],
		};
	},

	mounted() {
		this.registerFieldListeners();
	},

	beforeDestroy() {
		this.fieldListeners.forEach(({ element, handler }) => {
			if (element) {
				element.removeEventListener("input", handler);
			}
		});
	},
	computed: {
		isDisabled() {
			return this.isLoading || !this.hasCredentials;
		},

		hasCredentials() {
			// access to formVersion ensures Vue tracks updates from input listeners
			void this.formVersion;

			return [CONFIG_KEYS.storeId, CONFIG_KEYS.apiKey].every(
				(key) => this.getFieldValue(key) !== ""
			);
		},
	},
	methods: {
		registerFieldListeners() {
			let registered = 0;

			[CONFIG_KEYS.storeId, CONFIG_KEYS.apiKey].forEach((key) => {
				const element = document.getElementById(key);

				if (!element) {
					return;
				}

				const handler = () => {
					this.formVersion += 1;
				};

				element.addEventListener("input", handler);
				this.fieldListeners.push({ element, handler });
				registered += 1;
			});

			if (registered === 0) {
				this.$nextTick(() => this.registerFieldListeners());
			}
		},

		async testConnection() {
			if (!this.hasCredentials) {
				await this.systemConfigService.saveValues({
					[CONFIG_KEYS.integrationStatus]: false,
				});

				this.createNotificationWarning({
					title: "Coinsnap",
					message: this.$tc(
						"coincharge-coinsnap-test-connection.missing_credentials"
					),
				});

				return;
			}

			this.isLoading = true;

			try {
				const response = await this.coinchargeCoinsnapApiService.verifyApiKey();

				if (!response?.success) {
					await this.systemConfigService.saveValues({
						[CONFIG_KEYS.integrationStatus]: false,
					});

					this.createNotificationWarning({
						title: "Coinsnap",
						message: response?.message ?? this.$tc("coincharge-coinsnap-test-connection.error"),
					});

					return;
				}

				this.createNotificationSuccess({
					title: "Coinsnap",
					message: this.$tc("coincharge-coinsnap-test-connection.success"),
				});

				window.location.reload();
			} catch (error) {
				this.createNotificationError({
					title: "Coinsnap",
					message: this.$tc("coincharge-coinsnap-test-connection.error"),
				});
			} finally {
				this.isLoading = false;
			}
		},

		async saveCredentials() {
			const values = {
				[CONFIG_KEYS.storeId]: this.getFieldValue(CONFIG_KEYS.storeId),
				[CONFIG_KEYS.apiKey]: this.getFieldValue(CONFIG_KEYS.apiKey),
			};

			await this.systemConfigService.saveValues(values);
			window.location.reload();
		},

		getFieldValue(fieldName) {
			const element = document.getElementById(fieldName);

			if (!element) {
				return "";
			}

			return element.value.trim();
		},
	},
});
