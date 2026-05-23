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
- `lib/Providers/`: provider interface, available Tencent/manual providers, and reserved v1.1 provider adapters.
- `templates/clientarea.tpl`: Lagom/Bootstrap-compatible client KYC page.
- `lang/`: WHMCS addon language files.
- `database/`: install and optional cleanup SQL for review before deployment.

Do not place uploaded identity documents in this module directory. Use the configured private storage path.

Admin document downloads go through the addon controller and require a WHMCS admin session plus token. Clients can see document names/statuses and delete non-verified uploaded documents, but they cannot download original files from the client area.

Default WHMCS customer email templates can be installed from the admin Email Notifications card.
The bundled templates include bilingual customer-facing content and KYC merge fields. Existing PeakRack templates are refreshed only when the administrator selects the refresh option.

The v1.1 development branch adds an admin system checks card and a profile detail view for profile metadata, documents, submissions, provider logs, and audit logs.
