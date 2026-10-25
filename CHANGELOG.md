## 1.0.4

- Fix: The verification page explains the hash check correctly and no longer calls the receipt cryptographically signed.
- Fix: The page shows the submission time in the store's time zone and the currency of the right order.
- Fix: Returned items show the product name next to the SKU (receipts from EU Withdrawal 1.2.0).
- Fix: Guests get a "Continue Shopping" link instead of "Back to my account".
- Fix: The "Verify this receipt" button in Bulgarian and German uses the same word as the email.

## 1.0.3

- Fix: The hash explanation on the receipt verification page now appears in all supported languages.
* Other: The tax row on the receipt-verification page is now labelled "Of which VAT", matching the withdrawal form.

## 1.0.2

* Other: The verification page now shows the receipt's creation date.

## 1.0.1

* Other: Internal cleanup of the verify page's copy-to-clipboard script.

## 1.0.0

+ New: First public release. Tamper-evidence for withdrawal receipts — a SHA-256 integrity hash is printed in the receipt email and a public verify page lets anyone confirm a receipt has not been altered.
+ New: Amounts on the verify page render in the order's own currency; the consumer's name is never shown (data minimisation).
