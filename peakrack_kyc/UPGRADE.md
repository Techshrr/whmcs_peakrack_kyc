# Module Upgrade Notes

Upload this directory to `modules/addons/peakrack_kyc/`, then open the addon page in WHMCS Admin to run schema checks.

Version 1.2.0-dev adds `mod_peakrack_kyc_oauth_states` for Alipay OAuth callback state persistence. Opening the addon page or running the upgrade hook creates the table automatically.

Keep the configured private storage directory and database tables during upgrades.
