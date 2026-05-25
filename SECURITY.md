# Security Policy

Do not publish customer identity documents, WHMCS `configuration.php`, API credentials, database dumps, or encoded commercial build secrets in this repository.

KYC uploads should be stored outside the public web root whenever possible. If the default attachment path is used, confirm web server rules block direct access.

Document downloads must stay behind the addon controller. Do not expose the private storage directory through Nginx, Apache aliases, CDN rules, or WHMCS public download paths.

Clients must not receive original document URLs. They may only see document metadata and can delete non-verified uploads through the authenticated client-area form.

Provider secrets must not be rendered back into admin forms. Leave secret fields blank to keep the stored value. Do not copy real SecretKeys, AppCodes, private keys, or object storage credentials into screenshots, issue comments, module logs, or WHMCS activity logs.

API request and response logging should remain redacted. Full ID numbers, passport numbers, mobile numbers, bank cards, tokens, signatures, and private keys must not be stored in provider logs or audit logs.

S3/S3-compatible settings can be saved in the admin UI for a later storage adapter, but the current build still stores and serves uploaded documents through the local private storage path. Do not assume S3 lifecycle or bucket policy controls protect current uploads until the storage adapter is enabled and tested.

Report security issues privately to PeakRack before public disclosure.
