# Module Upgrade Notes

Upload this directory to `modules/addons/peakrack_kyc/`, then open the addon page in WHMCS Admin to run schema checks.

Version 1.2.0-dev adds `mod_peakrack_kyc_oauth_states` for Alipay OAuth callback state persistence. Opening the addon page or running the upgrade hook creates the table automatically.

Keep the configured private storage directory and database tables during upgrades.
S3/S3-compatible settings can be prepared in the admin UI, but this build still stores uploaded documents in the local private storage path.
Before enabling advanced providers, configure the matching provider credentials and run a test-mode submission first.
Bank-card and company verification can use Tencent Cloud or Aliyun Marketplace AppCode. For Aliyun Marketplace, configure endpoint, AppCode, request field names, extra JSON parameters, and success JSON path/value for the exact purchased API product.
