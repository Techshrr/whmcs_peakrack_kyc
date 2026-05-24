<?php

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
