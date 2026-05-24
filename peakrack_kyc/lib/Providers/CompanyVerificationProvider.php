<?php

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
            'companyFactorMode',
            'tencentSecretId',
            'tencentSecretKey',
            'tencentOcrRegion',
            'tencentOcrEndpoint',
            'apiTestMode',
        ];
    }

    public function verify(array $payload, array $settings): array
    {
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
