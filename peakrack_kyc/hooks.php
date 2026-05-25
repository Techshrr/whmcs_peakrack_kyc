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

if (!defined('WHMCS')) {
    die('No direct access');
}

require_once __DIR__ . '/lib/Bootstrap.php';

add_hook('ShoppingCartValidateCheckout', 1, static function (array $vars): array {
    return peakrackKycCheckoutValidation($vars);
});

add_hook('PreModuleCreate', 1, static function (array $vars): array {
    return peakrackKycPreModuleCreate($vars);
});

add_hook('AfterShoppingCartCheckout', 1, static function (array $vars): void {
    peakrackKycAfterCheckout($vars);
});

add_hook('DailyCronJob', 1, static function (array $vars): void {
    try {
        peakrackKycCleanupRetention(peakrackKycLoadSettings());
    } catch (Throwable $e) {
        if (function_exists('logActivity')) {
            logActivity('PeakRack KYC: retention cleanup failed: ' . $e->getMessage());
        }
    }
});
