<?php
// SPDX-License-Identifier: LicenseRef-PeakRack-Proprietary

/**
 * PeakRack KYC for WHMCS
 *
 * Official repository:
 * https://github.com/Techshrr/whmcs_peakrack_kyc
 *
 * Copyright (c) 2026 PeakRack. All rights reserved.
 * Unauthorized copying, modification, distribution, sublicensing, or commercial use
 * is prohibited without prior written permission.
 */

namespace PeakRack\Kyc\Providers;

class AlipayRealNameInfoProvider implements ProviderInterface
{
    public function getName(): string
    {
        return 'alipay_real_name_info';
    }

    public function getConfigFields(): array
    {
        return [
            'alipayRealNameEnabled',
            'alipayAppId',
            'alipayPrivateKey',
            'alipayApiBaseUrl',
            'alipayAuthUrl',
            'alipayOauthScope',
            'alipayCertType',
        ];
    }

    public function verify(array $payload, array $settings): array
    {
        return \peakrackKycAlipayCertdocConsult(
            (int) ($payload['client_id'] ?? 0),
            (string) ($payload['verify_id'] ?? ''),
            (string) ($payload['auth_token'] ?? ''),
            $settings
        );
    }
}
