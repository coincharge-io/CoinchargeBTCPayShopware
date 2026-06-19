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
