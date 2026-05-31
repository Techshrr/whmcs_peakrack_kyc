# Changelog

All notable changes to this project are documented in this file.

This project follows Semantic Versioning where practical.

## [1.2.0] - 2026-06-01

### Added

- Added Hong Kong Traditional Chinese handling for client-area KYC text, checkout validation messages, admin labels, and the default client notice.
- Added a `chinese-hk.php` WHMCS language file for Traditional Chinese client-area installations.
- Added a WHMCS admin-area GitHub shortcut and browser-side update notice for published GitHub releases or tags.
- Added database-backed OAuth state storage for Alipay authorization callbacks.
- Added a storage backend selector with local private storage active and S3-compatible settings reserved for a later adapter.
- Added provider flows for Alipay face verification, Tencent bank-card factors, Tencent company factors, legal-representative face verification, and configurable overseas KYC JSON APIs.
- Added selectable Tencent Cloud and Aliyun Marketplace AppCode channels for bank-card and company verification.
- Added TLD-level enforcement rules for cart and order checks.

### Changed

- Simplified provider settings so the active provider channel shows required fields first.
- Updated system checks so provider credential warnings follow the selected channels.
- Updated English and Chinese documentation for the current provider matrix, storage status, release packaging, security notes, and testing checklist.
- Replaced the admin GitHub shortcut icon with inline SVG so the button does not depend on WHMCS admin icon fonts.

### Security

- Added timeout handling for Alipay face callback state.
- Added safer UTF-8 download filenames for administrator document downloads.
- Added defensive redaction before API attempt responses are stored.

## [1.1.0-dev] - Unreleased

### Added

- Added the provider catalog and provider framework.
- Added reserved provider adapter classes for Alipay face verification, bank-card multi-factor verification, company verification, and overseas KYC.
- Added an admin profile detail view, system checks card, richer bilingual email templates, safe template refresh, manual retention cleanup, and Alipay real-name information verification flow.

### Changed

- Replaced the hidden Tencent-only API provider field with a provider selector.

## [1.0.0] - 2026-05-22

### Added

- Initial PeakRack KYC addon package.
- Added WHMCS addon lifecycle, admin settings, review queue, client-area verification center, Tencent Cloud FaceID phone verification, manual upload review, product-level checkout and provisioning enforcement, private upload storage protections, sensitive field hashing, bilingual text, logs, API attempt records, and retention cleanup.
