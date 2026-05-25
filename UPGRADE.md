# Upgrade Notes

## 1.2.0-dev

- Adds `mod_peakrack_kyc_oauth_states` for Alipay OAuth callback state persistence.
- Open the addon page once after upload so `peakrackKycCreateTables()` can create the new table automatically.
- If you use manual SQL deployment, apply `database/mysql.sql` before enabling Alipay real-name verification.

## 1.0.0

Initial release.

### Fresh Install

Upload:

```text
peakrack_kyc/ -> modules/addons/peakrack_kyc/
```

Then activate **PeakRack KYC** under `System Settings > Addon Modules`.

### Upgrade Steps

1. Back up the current `modules/addons/peakrack_kyc/` directory if it already exists.
2. Upload the new `peakrack_kyc/` directory over the old one.
3. Open the addon in WHMCS Admin once so schema checks and default settings run.
4. Review API settings, product enforcement mode, and private storage path.
5. Review the new advanced provider settings before enabling Alipay face, bank-card, company, legal-representative face, or overseas KYC flows.
6. If `S3 / S3-compatible` is selected, keep the local private storage path configured. In this build S3 settings are saved for future use, but uploads still use local private storage.
7. For bank-card and company verification, choose either Tencent Cloud or Aliyun Marketplace AppCode. Aliyun Marketplace products vary by field names and response shape, so configure the field mapping and success JSON path/value before enabling live calls.
5. Run `php -l` on the addon PHP files before publishing to production.

### Data Safety

The addon keeps verification profiles, documents, logs, and API attempt records when deactivated. Do not delete the private storage directory unless the matching document records are intentionally retired.
