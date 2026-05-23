-- Optional destructive cleanup. Do not run unless KYC audit history and document metadata are no longer required.
DROP TABLE IF EXISTS `mod_peakrack_kyc_provider_logs`;
DROP TABLE IF EXISTS `mod_peakrack_kyc_audit_logs`;
DROP TABLE IF EXISTS `mod_peakrack_kyc_documents`;
DROP TABLE IF EXISTS `mod_peakrack_kyc_submissions`;
DROP TABLE IF EXISTS `mod_peakrack_kyc_rules`;
DROP TABLE IF EXISTS `mod_peakrack_kyc_profiles`;
DROP TABLE IF EXISTS `mod_peakrack_kyc_settings`;
