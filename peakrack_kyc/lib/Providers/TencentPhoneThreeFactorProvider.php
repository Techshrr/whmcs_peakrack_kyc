<?php

namespace PeakRack\Kyc\Providers;

class TencentPhoneThreeFactorProvider implements ProviderInterface
{
    public function getName(): string
    {
        return 'tencent_phone_three_factor';
    }

    public function getConfigFields(): array
    {
        return [
            'tencentSecretId',
            'tencentSecretKey',
            'tencentRegion',
            'tencentEndpoint',
            'tencentVerifyMode',
            'apiTestMode',
        ];
    }

    public function verify(array $payload, array $settings): array
    {
        return \peakrackKycTencentFaceIdVerify(
            (int) ($payload['client_id'] ?? 0),
            (string) ($payload['real_name'] ?? ''),
            (string) ($payload['id_number'] ?? ''),
            (string) ($payload['phone'] ?? ''),
            $settings
        );
    }
}
