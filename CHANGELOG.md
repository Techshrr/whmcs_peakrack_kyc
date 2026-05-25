# Changelog

## 1.2.0-dev - Unreleased

- Started the v1.2 branch on `develop/v1.2`.
- Added database-backed OAuth state storage for Alipay authorization callbacks, with session fallback for older in-flight callbacks.
- Added OAuth state cleanup to retention cleanup and documented the new `mod_peakrack_kyc_oauth_states` table.
- Added a storage backend selector with local private storage active today and S3/S3-compatible configuration saved for a later storage adapter.
- Added advanced provider flows for Alipay face verification, Tencent bank-card three/four-factor verification, Tencent company four-factor verification, legal-representative face verification, and configurable overseas KYC JSON APIs.
- Added selectable Tencent Cloud / Aliyun Marketplace AppCode channels for bank-card and company verification so administrators can switch providers without code changes.
- Promoted TLD-level rules as part of the production enforcement path for cart and order checks.

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
- Added `AlipayRealNameInfoProvider` with OpenAPI V3 preconsult, OAuth callback, token exchange, consult result handling, admin settings, system checks, and client-area submission flow.

## 1.0.0 - 2026-05-22

- Initial PeakRack KYC addon package.
- Added WHMCS addon lifecycle, admin settings, review queue, and client-area verification center.
- Added Tencent Cloud FaceID `PhoneVerification` provider.
- Added manual upload review for individual, corporate, overseas passport, address proof, business license, and utility bill workflows.
- Added product-level checkout and provisioning enforcement.
- Added private upload storage protections, sensitive field hashing, bilingual text, logs, API attempt records, and retention cleanup.
- Refined v1.0 architecture with provider classes, submissions, provider logs, rule table, audit logs, product group/TLD rule support, email notification wrappers, review actions, non-rendered saved secrets, MIME validation, and document deletion.
