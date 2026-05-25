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
            'bankCardProvider',
            'bankCardFactorMode',
            'bankCardCertType',
            'aliyunBankCardEndpoint',
            'aliyunBankCardAppCode',
            'tencentSecretId',
            'tencentSecretKey',
            'tencentRegion',
            'tencentEndpoint',
            'apiTestMode',
        ];
    }

    public function verify(array $payload, array $settings): array
    {
        if (($settings['bankCardProvider'] ?? 'tencent') === 'aliyun') {
            return \peakrackKycAliyunBankCardVerify(
                (int) ($payload['client_id'] ?? 0),
                (string) ($payload['real_name'] ?? ''),
                (string) ($payload['id_number'] ?? ''),
                (string) ($payload['phone'] ?? ''),
                (string) ($payload['bank_card'] ?? ''),
                $settings
            );
        }

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
