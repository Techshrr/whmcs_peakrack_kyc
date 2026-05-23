<?php

namespace PeakRack\Kyc\Providers;

interface ProviderInterface
{
    public function getName(): string;

    public function getConfigFields(): array;

    public function verify(array $payload, array $settings): array;
}
