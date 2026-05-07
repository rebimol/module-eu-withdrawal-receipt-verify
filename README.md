# MageMe_EUWithdrawalReceiptVerify

**Version:** 0.1.0 (pre-release)
**Composer:** `mageme/module-eu-withdrawal-receipt-verify`
**Requires:** `mageme/module-eu-withdrawal: >=0.9 <1.0` (base module)
**Tier:** Pro add-on

Adds tamper-evidence to the durable-medium receipt email shipped by `MageMe_EUWithdrawal` (base module). Computes a SHA-256 hash over the canonicalised (NFC-normalised, key-sorted JSON) receipt DTO at order finalisation, persists it in `mm_eu_withdrawal_request.content_hash`, prints it in the customer's receipt email, and exposes a public `/withdraw-contract/verify/index/` endpoint that recomputes and compares (`hash_equals`) on demand.

When this module is installed, the base module's `RequestFinalizer` and `ReceiptSendConsumer` receive a real `ContentHasherInterface` implementation via DI override and the integrity-hash card + verify button render in the receipt email. When this module is not installed, the base module leaves `content_hash = NULL` and the email's `{{depend content_hash}}` blocks render nothing — the receipt email itself still ships (base module floor under Art. 11a(4) is the email).

## ⚠️ Disclaimer

This module is provided **AS-IS, WITHOUT WARRANTY OF ANY KIND**. It is a technical feature add-on for `MageMe_EUWithdrawal` (base module). It implements deterministic SHA-256 hashing and a verify endpoint — these are objective code features, not legal opinions. **Whether the resulting tamper-evidence satisfies your jurisdiction's evidentiary standards is a question for your counsel.**

The vendor (MageMe / ACTEK d.o.o., Slovenia) makes **no claim** that:

- Hash verification constitutes admissible evidence in any specific jurisdiction
- The receipt email satisfies Art. 11a(4) "durable medium" in every legal context
- Hash mismatch on verification proves tampering (false positives are possible due to data-source drift unrelated to the receipt)

The merchant is solely responsible for legal-context evaluation. By installing this module you accept these terms.

See the parent module's [LICENSE](../EUWithdrawal/LICENSE) and [README disclaimer](../EUWithdrawal/README.md#-disclaimer-—-please-read-before-installing) for full terms.

## Installation

```bash
composer require mageme/module-eu-withdrawal-receipt-verify
bin/magento module:enable MageMe_EUWithdrawalReceiptVerify
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## What it does (technical features)

1. **`Model\Receipt\ContentHasher`** computes SHA-256 over the receipt DTO. The DTO is canonicalised first via `Model\Receipt\ReceiptCanonicalizer` (NFC unicode normalisation + recursive `ksort` of all arrays + JSON encoding with stable flags) so that two semantically-identical receipts produce byte-identical hashes regardless of array key order or unicode form.
2. **DI overrides** on the base module's `RequestFinalizer` and `ReceiptSendConsumer` force-inject the hasher (Magento's ObjectManager auto-wires nullable typed args to `null` even when a `<preference>` exists, so explicit `<argument>` overrides are required).
3. **`Controller\Verify\Index`** renders `/withdraw-contract/verify/index/request_id/{id}/hash/{hex}/`. Validates input (numeric request ID, 64-hex hash), rebuilds the DTO from current state, recomputes the hash, and compares with `hash_equals`. Sets `X-Robots-Tag: noindex, nofollow`. Rate-limited per IP via the base module's `Model\Security\RateLimiter`.
4. **`Block\Withdraw\VerifyResult`** + `view/frontend/templates/verify/result.phtml` render the result page. On match: green "Receipt verified" panel + receipt details + integrity hash with Copy button. On mismatch: red "Verification failed" panel.

## What it doesn't do

- Issue any kind of certificate, signature, or trusted-timestamp. The hash is internally computable only.
- Block tampering in any way. Tampering is *detected* on verify, not prevented.
- Replace the durable-medium email itself — the email is what satisfies Art. 11a(4); this module adds an integrity layer on top.
- Provide proof-of-receipt (i.e. the customer received the email). For that see `mageme/module-eu-withdrawal-audit` (Pro).

## Database

This module does not declare any tables. It uses the base module's `mm_eu_withdrawal_request.content_hash` column (which the base module makes nullable so this module can opt-in to writing).

## Tests

```bash
docker exec -u magento dev_php vendor/bin/phpunit -c app/code/MageMe/EUWithdrawalReceiptVerify/Test/Unit/phpunit.xml.dist
```

Expected: 16 tests / 19 assertions / 0 failures.

## Licence

MageMe EULA — commercial. See https://mageme.com/license/. Licensor: ACTEK d.o.o., Slovenia.
