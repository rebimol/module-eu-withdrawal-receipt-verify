## 1.0.4

- Fix: The note under the hash on the verification page now says what the check does: the store keeps the hash with the receipt and compares it with the receipt's content. It used to say anyone with the email could recompute it.
- Fix: The verification page no longer says the receipt is cryptographically signed; it says the receipt matches its SHA-256 fingerprint, which is what the check does.
- Fix: The receipt verification page shows the submission time in the time zone of the store the order was placed in and names the zone; it used to show UTC without saying so.
- Fix: Returned items on the verification page show the product name next to the SKU, for receipts issued with EU Withdrawal 1.2.0 or later.
- Fix: Visitors who are not signed in get a "Continue Shopping" link on the verification page instead of "Back to my account".
- Fix: In Bulgarian and German, the "Verify this receipt" button in the receipt email now uses the same word for the receipt as the rest of the email.

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
