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
