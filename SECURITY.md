# Security Policy

Do not publish customer identity documents, WHMCS `configuration.php`, API credentials, database dumps, or encoded commercial build secrets in this repository.

KYC uploads should be stored outside the public web root whenever possible. If the default attachment path is used, confirm web server rules block direct access.

Document downloads must stay behind the addon controller. Do not expose the private storage directory through Nginx, Apache aliases, CDN rules, or WHMCS public download paths.

Clients must not receive original document URLs. They may only see document metadata and can delete non-verified uploads through the authenticated client-area form.

Report security issues privately to PeakRack before public disclosure.
