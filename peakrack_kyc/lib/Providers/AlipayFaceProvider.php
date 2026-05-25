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

class AlipayFaceProvider implements ProviderInterface
{
    public function getName(): string
    {
        return 'alipay_face';
    }

    public function getConfigFields(): array
    {
        return [
            'alipayFaceEnabled',
            'alipayAppId',
            'alipayPrivateKey',
            'alipayFaceGatewayUrl',
            'alipayFaceBizCode',
        ];
    }

    public function verify(array $payload, array $settings): array
    {
        return \peakrackKycAlipayFaceQuery(
            (int) ($payload['client_id'] ?? 0),
            (string) ($payload['certify_id'] ?? ''),
            $settings
        );
    }
}
