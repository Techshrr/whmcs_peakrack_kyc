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

class OverseasKycProvider implements ProviderInterface
{
    public function getName(): string
    {
        return 'overseas_kyc';
    }

    public function getConfigFields(): array
    {
        return [
            'overseasKycEnabled',
            'overseasKycEndpoint',
            'overseasKycAuthHeader',
            'overseasKycApiKey',
            'overseasKycApiSecret',
            'overseasKycSuccessPath',
            'overseasKycSuccessValue',
        ];
    }

    public function verify(array $payload, array $settings): array
    {
        return \peakrackKycExternalKycVerify('overseas_kyc', $payload, $settings, [
            'endpoint' => 'overseasKycEndpoint',
            'auth_header' => 'overseasKycAuthHeader',
            'api_key' => 'overseasKycApiKey',
            'api_secret' => 'overseasKycApiSecret',
            'success_path' => 'overseasKycSuccessPath',
            'success_value' => 'overseasKycSuccessValue',
        ]);
    }
}
