# Changelog

## 1.1.0-dev - Unreleased

- Started the v1.1 provider framework on `develop/v1.1`.
- Added a provider catalog so admin UI, settings validation, and provider instantiation use one source of truth.
- Added reserved provider adapter classes for Alipay face verification, bank-card multi-factor verification, company verification, and overseas KYC.
- Replaced the hidden Tencent-only API provider field with a real provider selector that shows available and reserved providers safely.
- Added an admin profile detail view with profile summary, document downloads, submission summaries, provider logs, audit logs, and state actions.
- Added an admin system checks card for PHP version, cURL, OpenSSL, Fileinfo, private storage, storage guard files, and Tencent credential readiness.
- Expanded default WHMCS KYC customer email templates with bilingual content, profile metadata, status fields, reason fields, and KYC center links.
- Added a safe refresh option for bundled PeakRack email templates so existing templates are only overwritten when the administrator opts in.
- Added a manual retention-cleanup button in the admin UI.

## 1.0.0 - 2026-05-22

- Initial PeakRack KYC addon package.
- Added WHMCS addon lifecycle, admin settings, review queue, and client-area verification center.
- Added Tencent Cloud FaceID `PhoneVerification` provider.
- Added manual upload review for individual, corporate, overseas passport, address proof, business license, and utility bill workflows.
- Added product-level checkout and provisioning enforcement.
- Added private upload storage protections, sensitive field hashing, bilingual text, logs, API attempt records, and retention cleanup.
- Refined v1.0 architecture with provider classes, submissions, provider logs, rule table, audit logs, product group/TLD rule support, email notification wrappers, review actions, non-rendered saved secrets, MIME validation, and document deletion.
