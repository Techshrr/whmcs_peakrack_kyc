<?php

namespace PeakRack\Kyc\Providers;

abstract class ReservedProvider implements ProviderInterface
{
    public function getConfigFields(): array
    {
        return [];
    }

    public function verify(array $payload, array $settings): array
    {
        return [
            'success' => false,
            'status' => 'rejected',
            'code' => 'provider_reserved',
            'message' => 'This provider is reserved for a later release and is not enabled.',
        ];
    }
}
