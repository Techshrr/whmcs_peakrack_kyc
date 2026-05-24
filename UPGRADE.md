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
5. Run `php -l` on the addon PHP files before publishing to production.

### Data Safety

The addon keeps verification profiles, documents, logs, and API attempt records when deactivated. Do not delete the private storage directory unless the matching document records are intentionally retired.
