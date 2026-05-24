<?php

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
