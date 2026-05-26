# PeakRack KYC Module

This directory is the deployable WHMCS addon.

Install it at:

```text
modules/addons/peakrack_kyc/
```

Main files:

- `peakrack_kyc.php`: addon lifecycle, admin UI, and client-area handler.
- `hooks.php`: checkout, provisioning, post-checkout, and cron hooks.
- `lib/Bootstrap.php`: settings, storage, KYC status, rule CRUD, API providers, uploads, logs, email templates, and enforcement helpers.
- `lib/Providers/`: provider interface, Tencent phone factors, Alipay real-name, Alipay face, bank-card, company, overseas KYC, manual review, and reserved provider adapters.
- `templates/clientarea.tpl`: Lagom/Bootstrap-compatible client KYC page.
- `lang/`: WHMCS addon language files.
- `database/`: install and optional cleanup SQL for review before deployment.

Do not place uploaded identity documents in this module directory. Use the configured private storage path.
The admin storage selector can save S3/S3-compatible settings for future rollout, but document uploads still use the local private path in this build.

Admin document downloads go through the addon controller and require a WHMCS admin session plus token. Clients can see document names/statuses and delete non-verified uploaded documents, but they cannot download original files from the client area.

Default WHMCS customer email templates can be installed from the admin Email Notifications card.
The bundled templates include bilingual customer-facing content and KYC merge fields. Existing PeakRack templates are refreshed only when the administrator selects the refresh option.

The v1.2 development branch keeps the provider framework, adds database-backed OAuth state storage for Alipay callbacks, and exposes advanced provider flows for Alipay face, bank-card factors, company factors, legal-representative face verification, and configurable overseas KYC APIs. Bank-card and company verification can be switched between Tencent Cloud and Aliyun Marketplace AppCode channels in the admin UI. Provider setup is grouped so the active channel shows the required fields first, while custom field mapping and response parsing stay in advanced sections.

## License

This deployable module directory includes its own [LICENSE](LICENSE) and [NOTICE](NOTICE) files.

Licensing and written-permission requests should be sent to `legal@peakrack.com`.
