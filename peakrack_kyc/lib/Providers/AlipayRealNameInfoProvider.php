<?php

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
