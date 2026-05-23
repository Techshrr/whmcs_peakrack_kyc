# PeakRack KYC for WHMCS

[English](README.md) | [简体中文](README.zh-CN.md)

PeakRack KYC is a WHMCS addon module for identity verification, document upload, manual review, API-based Chinese mainland three-factor verification, and product-level KYC enforcement.

## Current Version

`1.1.0-dev`

## Features

- Supports WHMCS 9.0.3 and PHP 8.2 / 8.3.
- Provides a client-area identity verification center.
- Provides an admin review queue for manual approval, rejection, revocation, and resubmission requests.
- Provides admin CRUD for product, product group, and TLD enforcement rules.
- Supports Chinese mainland mobile number, name, and ID number three-factor verification through Tencent Cloud FaceID `PhoneVerification`.
- Separates providers behind a common `verify()`, `getName()`, and `getConfigFields()` interface.
- Implements `TencentPhoneThreeFactorProvider` and `ManualReviewProvider`, with reserved provider adapters for the v1.1 roadmap.
- Supports manual document upload for individuals, companies, overseas passport verification, address proof, business licenses, utility bills, and mixed KYC cases.
- Can block checkout for unverified clients when selected products, product groups, or reserved TLD rules require KYC.
- Can abort product provisioning if a required product reaches module creation before the client is verified.
- Can keep post-checkout orders pending for review.
- Can optionally cancel unpaid pending orders after KYC rejection.
- Sends customer/admin email notifications when configured.
- Can install default WHMCS customer email templates and map them automatically.
- Stores uploaded documents in a private path with generated names and deny files to reduce direct-link exposure.
- Requires an authenticated WHMCS admin session and token for document downloads.
- Allows clients to delete their own non-verified uploaded documents without downloading original files.
- Stores sensitive ID, phone, and registration numbers as salted hashes plus last-four display values.
- Provides English and Simplified Chinese admin/client text.
- Uses Bootstrap-compatible markup for WHMCS themes including Lagom.
- Keeps verification logs and API attempt logs with retention cleanup.

## Scope Notes

Version 1.0.0 is frozen on the `release/v1.0.0` branch and `v1.0.0` tag. The `develop/v1.1` branch starts the provider framework for Alipay face verification, bank-card multi-factor verification, legal-representative/company verification, and dedicated overseas KYC providers. Those reserved providers are visible in the admin UI but are not selectable until their runtime verification logic is implemented.

## Package Layout

The deployable addon is the `peakrack_kyc` directory:

```text
peakrack_kyc/
```

Upload or copy it to:

```text
modules/addons/peakrack_kyc/
```

## Installation

1. Upload:

   ```text
   peakrack_kyc/ -> modules/addons/peakrack_kyc/
   ```

2. In WHMCS Admin, open:

   ```text
   System Settings > Addon Modules
   ```

3. Activate **PeakRack KYC**.

4. Open:

   ```text
   Addons > PeakRack KYC
   ```

5. Configure API provider, upload path, enforcement mode, and product/product group/TLD rules.

## Product Enforcement

The addon supports three enforcement modes:

- `Disabled`: no checkout or provisioning enforcement.
- `All products`: every product in cart requires a verified profile.
- `Selected products only`: only configured WHMCS product IDs require verification.

Selected mode supports product IDs, product group IDs, and TLD rules. Configure these rules in **Addons > PeakRack KYC > Product / Product Group / TLD Rules**. When checkout blocking is enabled, `ShoppingCartValidateCheckout` rejects checkout before the order and invoice are created. In allow-pending mode, checkout can continue, but `PreModuleCreate` still aborts service creation until the client is verified.

## API Providers

### TencentPhoneThreeFactorProvider

Use this for official Tencent Cloud `PhoneVerification`.

Required settings:

- `Tencent SecretId`
- `Tencent SecretKey`
- `Tencent Region`
- `Tencent Endpoint` default: `faceid.tencentcloudapi.com`
- `Tencent VerifyMode`
- Test / sandbox mode

### ManualReviewProvider

Use this for manual uploads such as hand-held ID photos, passports, proof of address, utility bills, and business licenses.

Future providers should implement the same provider interface and stay outside the main workflow.

## Secure File Storage

By default, files are stored under:

```text
attachments/peakrack_kyc_private/
```

The addon writes `.htaccess`, `web.config`, and `index.html` deny files. For Nginx or strict compliance deployments, configure `Private storage path` outside the web root and make sure the PHP user can write to it.

Administrators download documents only through the addon controller, with a WHMCS admin session and token check. Clients can see file names, status, and upload time, but they cannot download original document files from the client area.

## Email Templates

In the admin Email Notifications card, click **Install default WHMCS email templates** to create:

- `PeakRack KYC Submitted`
- `PeakRack KYC Approved`
- `PeakRack KYC Rejected`

The installer keeps existing templates and maps the three template settings automatically.

## Database Tables

The addon creates:

- `mod_peakrack_kyc_settings`
- `mod_peakrack_kyc_profiles`
- `mod_peakrack_kyc_submissions`
- `mod_peakrack_kyc_documents`
- `mod_peakrack_kyc_provider_logs`
- `mod_peakrack_kyc_rules`
- `mod_peakrack_kyc_audit_logs`

Tables are kept on deactivation for audit history.

Migration SQL is provided in both `database/mysql.sql` and `peakrack_kyc/database/mysql.sql`. Optional destructive cleanup SQL is provided as `uninstall.sql` in the same locations.

## Runtime Hooks

- `ShoppingCartValidateCheckout`: blocks checkout when a required product is in cart and the client is not verified.
- `PreModuleCreate`: aborts provisioning for required products when the client is not verified.
- `AfterShoppingCartCheckout`: logs orders that must remain pending for KYC.
- `DailyCronJob`: cleans old logs and API attempts.

## ionCube Packaging

The source package is ready for ionCube packaging. Encode PHP files in `peakrack_kyc/` except language files, templates, README files, and upgrade notes. Do not encode or ship local credentials, WHMCS `configuration.php`, uploaded documents, or generated storage files.

See `BUILD.md` for suggested source and ionCube release package structure.

## Verification

Run:

```powershell
php -l peakrack_kyc/peakrack_kyc.php
php -l peakrack_kyc/hooks.php
php -l peakrack_kyc/lib/Bootstrap.php
```

For release builds, also compare the source package against `modules/addons/peakrack_kyc` after deployment.

## Test Checklist

- Activate the addon on WHMCS 9.0.3 with PHP 8.2 and PHP 8.3.
- Save settings with blank SecretKey fields and confirm existing secrets are retained and not rendered in HTML.
- Submit Tencent three-factor verification in test mode.
- Submit manual individual KYC with JPG, PNG, and PDF files.
- Confirm MIME mismatch files are rejected.
- Approve, reject, revoke, and request resubmission from admin.
- Confirm rejected, revoked, and expired clients can submit updated documents.
- Confirm clients can delete non-verified uploaded documents and verified documents require admin handling.
- Confirm customer sees status and rejection reason but cannot download source files.
- Confirm admin download requires an admin session and valid token.
- Install default WHMCS email templates and confirm settings are mapped.
- Configure product ID and product group rules and confirm checkout blocking.
- Configure TLD rules such as `cn` and `com.cn` and confirm domain checkout matching.
- Configure allow-pending mode and confirm `PreModuleCreate` blocks provisioning.
- Reject an unpaid pending order and confirm optional cancellation behavior.
- Confirm paid pending orders are logged for manual handling and no automatic refund is attempted.
- Run `DailyCronJob` cleanup or call retention cleanup in a staging environment.
