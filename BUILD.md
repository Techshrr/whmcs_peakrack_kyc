# Build and Release Notes

Recommended release layout:

```text
dist/
  peakrack_kyc-v1.0.0-source.zip
  peakrack_kyc-v1.0.0-ioncube-php82.zip
  peakrack_kyc-v1.0.0-ioncube-php83.zip
```

Source package contents:

```text
peakrack_kyc/
  hooks.php
  peakrack_kyc.php
  lib/
  lang/
  templates/
  README.md
  README.zh-CN.md
  UPGRADE.md
  UPGRADE.zh-CN.md
```

ionCube package guidance:

- Encode PHP runtime files under `peakrack_kyc/`.
- Do not encode `lang/`, `templates/`, README files, upgrade notes, or config samples.
- Build separate encoded packages for PHP 8.2 and PHP 8.3 if the encoder requires target-version-specific output.
- Do not include WHMCS `configuration.php`, uploaded KYC documents, private storage files, API credentials, database dumps, or local logs.
- Verify encoded packages in a clean WHMCS 9.0.3 staging install before release.
