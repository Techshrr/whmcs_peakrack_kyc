<?php

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
