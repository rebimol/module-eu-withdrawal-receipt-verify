# i18n CSVs — translation provenance (Pro Receipt-Verify)

These 22 CSVs hold translations for strings used by the public verify page
(`/withdraw-contract/verify/index`) and the integrity-hash card / verify
button rendered inside the receipt email's `{{depend}}` blocks.

Strings were extracted from the Lite module's i18n CSVs in 2026-04-30 during
the phase-32 Pro split (commit history in
`projects/eu-withdrawal/_plans/2026-04-30-pro-split-migration-map.md`).

When this module is not installed:

- The verify page route (`/withdraw-contract/verify/index`) is not
  registered, so no template renders any of these strings.
- `verify_url` and `content_hash` template vars are empty in the Lite
  receipt email, so the `{{depend verify_url}}{{/depend}}` and
  `{{depend content_hash}}{{/depend}}` blocks render nothing — none of
  these strings appear in the email body.

When this module IS installed:

- Translations load alongside the Lite CSVs (Magento merges all enabled
  modules' i18n dictionaries before locale fallback runs).
- The Lite module's `Plugin\Translate\MergeParentLanguageStrings` plugin
  also walks the locale fallback chain for these strings (so a Belgian
  store using `nl_BE` falls back to `nl_NL` → `en_US`).

## Locales shipped (22)

en_US (master), bg_BG, cs_CZ, da_DK, de_DE, el_GR, es_ES, et_EE, fi_FI,
fr_FR, hr_HR, hu_HU, it_IT, lt_LT, lv_LV, nl_NL, pl_PL, pt_PT, ro_RO,
sk_SK, sl_SI, sv_SE.

## Translation provenance

All non-en_US CSVs were AI-drafted in the Lite phase-10 localization sprint
(2026-04-29). They have NOT been reviewed by counsel.

**Merchant responsibility:** review legal-language strings (Art. 11a CRD
references in particular) for your store's target jurisdictions with local
counsel BEFORE production deployment.
