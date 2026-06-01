# MageMe EU Withdrawal — Receipt Verify (Pro)

> Tamper-evidence for withdrawal receipts — a SHA-256 integrity hash in the receipt email and a public page where anyone can confirm a receipt has not been altered.

[![Magento](https://img.shields.io/badge/Magento-2.4.4%20–%202.4.9-EE672F.svg?style=flat-square)](https://magento.com)
[![PHP](https://img.shields.io/badge/PHP-8.1%20%7C%208.2%20%7C%208.3%20%7C%208.4%20%7C%208.5-777BB4.svg?style=flat-square)](https://php.net)
[![Tier](https://img.shields.io/badge/tier-Pro-6E56CF.svg?style=flat-square)](https://mageme.com/magento-2-withdrawal-button-extension.html)
[![License](https://img.shields.io/badge/license-MageMe%20EULA-blue.svg?style=flat-square)](https://mageme.com/license/)

Pro-tier add-on for [`mageme/module-eu-withdrawal`](https://github.com/mageme/module-eu-withdrawal). Adds an integrity hash to the durable-medium receipt email and a public endpoint that verifies it.

**[Documentation](https://docs.mageme.com)** · **[Get EU Withdrawal Pro](https://mageme.com/magento-2-withdrawal-button-extension.html)**

---

## What it does

- A **SHA-256 integrity hash**, computed over the canonicalised receipt at finalisation and printed in the customer's receipt email.
- A public **verify page** (`/withdraw-contract/verify/…`) that recomputes and compares the hash on demand — proving the receipt has not been modified since it was issued.
- Amounts shown in the order's **own currency**; the consumer's name is never shown (data minimisation). The page is `noindex` and rate-limited.

Tampering is **detected on verification, not prevented**. Without this module, the base receipt email still ships — the integrity card and verify button simply do not render.

## Requirements

- **EU Withdrawal** base module (pulled automatically) — Magento **2.4.4–2.4.9**, **PHP 8.1–8.5**
- A valid **EU Withdrawal Pro** licence

## Install

Pro modules are distributed through the private MageMe Composer repository. Add it once with the credentials from your purchase, then require the package:

```bash
composer config repositories.mageme composer https://repo.mageme.com
composer require mageme/module-eu-withdrawal-receipt-verify
bin/magento module:enable MageMe_EUWithdrawalReceiptVerify
bin/magento setup:upgrade
bin/magento cache:flush
```

No configuration is required — once installed, new receipt emails carry the integrity hash and a "Verify this receipt" button that opens the public verify page.

## Custom Magento development

Need a feature an extension doesn't cover, or a bespoke Magento build? MageMe takes on custom extension development and integration work.

→ **[Custom Magento development](https://mageme.com/magento-services/custom-development)**

## Support

- Documentation: [docs.mageme.com](https://docs.mageme.com)

## Legal disclaimer

Provided **AS-IS, without warranty**, and **not legal advice**. Hash verification is internally computable evidence, not a trusted-timestamp or certificate; a mismatch indicates a difference, not necessarily tampering. See the base module's [full disclaimer](https://docs.mageme.com).

## License

Governed by the **MageMe End User License Agreement** ([mageme.com/license](https://mageme.com/license/)). Pro requires a paid commercial licence.

---

**MageMe** builds Magento 2 and Adobe Commerce extensions for B2B merchants — form building, quoting, catalog control, and EU compliance.