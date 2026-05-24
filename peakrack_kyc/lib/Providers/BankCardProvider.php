<?php

namespace PeakRack\Kyc\Providers;

class BankCardProvider implements ProviderInterface
{
    public function getName(): string
    {
        return 'bank_card_multi_factor';
    }

    public function getConfigFields(): array
    {
        return [
            'bankCardEnabled',
            'bankCardFactorMode',
            'bankCardCertType',
            'tencentSecretId',
            'tencentSecretKey',
            'tencentRegion',
            'tencentEndpoint',
            'apiTestMode',
        ];
    }

    public function verify(array $payload, array $settings): array
    {
        return \peakrackKycTencentBankCardVerify(
            (int) ($payload['client_id'] ?? 0),
            (string) ($payload['real_name'] ?? ''),
            (string) ($payload['id_number'] ?? ''),
            (string) ($payload['phone'] ?? ''),
            (string) ($payload['bank_card'] ?? ''),
            $settings
        );
    }
}
