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

class ManualReviewProvider implements ProviderInterface
{
    public function getName(): string
    {
        return 'manual_review';
    }

    public function getConfigFields(): array
    {
        return [
            'manualReviewEnabled',
            'maxUploadMb',
            'allowedExtensions',
            'storagePath',
        ];
    }

    public function verify(array $payload, array $settings): array
    {
        if (empty($settings['manualReviewEnabled'])) {
            return [
                'success' => false,
                'status' => 'rejected',
                'code' => 'manual_disabled',
                'message' => 'Manual review is not enabled.',
            ];
        }

        return [
            'success' => true,
            'status' => 'pending',
            'code' => 'manual_pending',
            'message' => 'Submitted for manual review.',
        ];
    }
}
