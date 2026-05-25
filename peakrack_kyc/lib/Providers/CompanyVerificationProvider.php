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

class CompanyVerificationProvider implements ProviderInterface
{
    public function getName(): string
    {
        return 'company_verification';
    }

    public function getConfigFields(): array
    {
        return [
            'companyVerificationEnabled',
            'companyVerificationProvider',
            'companyFactorMode',
            'aliyunCompanyEndpoint',
            'aliyunCompanyAppCode',
            'tencentSecretId',
            'tencentSecretKey',
            'tencentOcrRegion',
            'tencentOcrEndpoint',
            'apiTestMode',
        ];
    }

    public function verify(array $payload, array $settings): array
    {
        if (($settings['companyVerificationProvider'] ?? 'tencent') === 'aliyun') {
            return \peakrackKycAliyunCompanyVerify(
                (int) ($payload['client_id'] ?? 0),
                (string) ($payload['company_name'] ?? ''),
                (string) ($payload['registration_number'] ?? ''),
                (string) ($payload['legal_person_name'] ?? ''),
                (string) ($payload['legal_person_id'] ?? ''),
                $settings
            );
        }

        return \peakrackKycTencentCompanyVerify(
            (int) ($payload['client_id'] ?? 0),
            (string) ($payload['company_name'] ?? ''),
            (string) ($payload['registration_number'] ?? ''),
            (string) ($payload['legal_person_name'] ?? ''),
            (string) ($payload['legal_person_id'] ?? ''),
            $settings
        );
    }
}
