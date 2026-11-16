# 1.2.0

Security release. After updating, open the plugin settings and check that the BTCPay Server Store ID is your own store. If it isn't, click "Generate API key" to connect again.

- Fix: the BTCPay connect callback saved any API key and store ID posted to it, without login. It now requires a one-time state created by the logged-in admin, reads the key's permissions from BTCPay, and accepts only a key limited to one store with the permissions the plugin needs. The authorize page now uses `strict=true`.
- The webhook registration routes now require an admin login.
- Decide payment from the invoice the Coinsnap or BTCPay API returns, never from the webhook body. A signed webhook can no longer mark an order paid on its own.
- Mark an order paid only when the invoice is settled for the order's amount and currency, and was created by this plugin for that order. BTCPay invoices settled by hand ("Marked") are not accepted.
- Mark each invoice paid only once, using a new `coincharge_processed_invoice` table.
- Never move a paid, refunded, cancelled or charged-back order back with a webhook. Expired and Invalid only affect unpaid orders.
- Reject webhooks when no secret is stored, instead of checking against an empty key.
- Verify Coinsnap webhooks with the timestamped `X-Coinsnap-Signature` header and reject ones older than 5 minutes.
- Reply 200 to test webhooks and unknown invoices, and 503 when the payment API is unreachable, so the provider retries.
- Keep the current webhook and secret when the API can't be reached during "Test connection", instead of creating a new one.
- Fail checkout when the gateway returns no checkout link, instead of sending the customer to the order page unpaid.
- Log only the method, path and status of API calls. Invoice payloads, API responses and raw webhook bodies are no longer written to the log.
- Log every rejected webhook with a fixed reason (bad signature, stale timestamp, forged status, amount mismatch), at most 10 lines a minute.
- Hide the webhook secrets from the settings page and mask the API key fields.

# 1.1.6

- Fix compatibility with Shopware 6.7 / Symfony 7: switch route attributes to the `Symfony\Component\Routing\Attribute\Route` namespace
- Make media registration idempotent so re-install and upgrade no longer fail with "file already exists"
- Replace the removed `EntityRepositoryInterface` with `EntityRepository`
- Drop the dead `csrf_protected` route default (removed since Shopware 6.5)
- Migrate administration `$tc()` to `$t()` for Vue 3 i18n
- Read admin settings from saved system config via the API instead of the form DOM / Vuex store, which Shopware 6.7 no longer exposes; fixes the "Generate API key" flow and credential detection on 6.7

# 1.1.5

- Add USDT support for BTCPay Server
- Add support for BTCPay Server v2
- Add support for Shopware v6.7

# 1.1.4

- Updated logger

# 1.1.3

- Fixed arguments for Route registration

# 1.1.2

- Add support for Bitcoin and Crypto payment methods

- # 1.1.1
- Add support for new Shopware version

# 1.1.0

- Add Coinsnap gateway

# 1.0.1

- Display order overview after payment

# 1.0.0

- First version
