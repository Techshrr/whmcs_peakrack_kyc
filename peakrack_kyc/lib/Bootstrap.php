<?php

/**
 * Shared runtime helpers for PeakRack KYC.
 *
 * Target runtime: WHMCS 9.0.3 / PHP 8.2-8.3.
 */

use WHMCS\Database\Capsule;
use PeakRack\Kyc\Providers\AlipayFaceProvider;
use PeakRack\Kyc\Providers\AlipayRealNameInfoProvider;
use PeakRack\Kyc\Providers\BankCardProvider;
use PeakRack\Kyc\Providers\CompanyVerificationProvider;
use PeakRack\Kyc\Providers\ManualReviewProvider;
use PeakRack\Kyc\Providers\OverseasKycProvider;
use PeakRack\Kyc\Providers\ProviderInterface;
use PeakRack\Kyc\Providers\TencentPhoneThreeFactorProvider;

if (!defined('WHMCS')) {
    die('No direct access');
}

require_once __DIR__ . '/Providers/ProviderInterface.php';
require_once __DIR__ . '/Providers/ReservedProvider.php';
require_once __DIR__ . '/Providers/TencentPhoneThreeFactorProvider.php';
require_once __DIR__ . '/Providers/ManualReviewProvider.php';
require_once __DIR__ . '/Providers/AlipayRealNameInfoProvider.php';
require_once __DIR__ . '/Providers/AlipayFaceProvider.php';
require_once __DIR__ . '/Providers/BankCardProvider.php';
require_once __DIR__ . '/Providers/CompanyVerificationProvider.php';
require_once __DIR__ . '/Providers/OverseasKycProvider.php';

const PRKYC_MODULE = 'peakrack_kyc';
const PRKYC_VERSION = '1.1.0-dev';
const PRKYC_SETTING_KEY = 'config';
const PRKYC_SETTINGS_TABLE = 'mod_peakrack_kyc_settings';
const PRKYC_PROFILES_TABLE = 'mod_peakrack_kyc_profiles';
const PRKYC_SUBMISSIONS_TABLE = 'mod_peakrack_kyc_submissions';
const PRKYC_DOCUMENTS_TABLE = 'mod_peakrack_kyc_documents';
const PRKYC_PROVIDER_LOGS_TABLE = 'mod_peakrack_kyc_provider_logs';
const PRKYC_RULES_TABLE = 'mod_peakrack_kyc_rules';
const PRKYC_LOGS_TABLE = 'mod_peakrack_kyc_audit_logs';
const PRKYC_API_ATTEMPTS_TABLE = PRKYC_PROVIDER_LOGS_TABLE;

if (!function_exists('peakrackKycDefaults')) {
    function peakrackKycDefaults(): array
    {
        return [
            'enabled' => true,
            'adminLanguage' => 'en',
            'activityLog' => true,
            'manualReviewEnabled' => true,
            'apiVerificationEnabled' => false,
            'apiProvider' => 'tencent_phone_three_factor',
            'tencentSecretId' => '',
            'tencentSecretKey' => '',
            'tencentRegion' => 'ap-guangzhou',
            'tencentEndpoint' => 'faceid.tencentcloudapi.com',
            'tencentVerifyMode' => 'standard',
            'apiTestMode' => false,
            'apiTimeout' => 15,
            'alipayRealNameEnabled' => false,
            'alipayAppId' => '',
            'alipayPrivateKey' => '',
            'alipayApiBaseUrl' => 'https://openapi.alipay.com',
            'alipayAuthUrl' => 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm',
            'alipayOauthScope' => 'auth_base',
            'alipayAuthSource' => 'alipay_wallet',
            'alipayCertType' => 'IDENTITY_CARD',
            'enforcementMode' => 'selected',
            'checkoutMode' => 'block',
            'enforcedProductIds' => [],
            'enforcedProductGroupIds' => [],
            'enforcedTlds' => [],
            'checkoutBlockEnabled' => true,
            'provisioningBlockEnabled' => true,
            'postOrderHoldEnabled' => true,
            'rejectedOrderAction' => 'manual',
            'emailNotifications' => true,
            'emailTemplateSubmitted' => '',
            'emailTemplateApproved' => '',
            'emailTemplateRejected' => '',
            'adminEmailNotifications' => true,
            'maxUploadMb' => 8,
            'allowedExtensions' => ['jpg', 'jpeg', 'png', 'pdf'],
            'storagePath' => '',
            'retentionDays' => 1095,
            'maxLogs' => 20000,
            'clientNotice' => [
                'en' => 'Some products require identity verification before checkout or service activation.',
                'zh' => '部分产品需要完成实名认证后才可下单或开通服务。',
            ],
        ];
    }
}

if (!function_exists('peakrackKycCreateTables')) {
    function peakrackKycCreateTables(): void
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable(PRKYC_SETTINGS_TABLE)) {
            $schema->create(PRKYC_SETTINGS_TABLE, static function ($table): void {
                $table->increments('id');
                $table->string('setting', 100)->unique();
                $table->longText('value');
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!$schema->hasTable(PRKYC_PROFILES_TABLE)) {
            $schema->create(PRKYC_PROFILES_TABLE, static function ($table): void {
                $table->increments('id');
                $table->unsignedInteger('client_id')->unique();
                $table->string('type', 40)->index();
                $table->string('status', 30)->index();
                $table->string('verification_method', 40)->nullable()->index();
                $table->string('real_name', 191)->nullable();
                $table->string('company_name', 191)->nullable();
                $table->string('country', 2)->nullable()->index();
                $table->string('document_type', 60)->nullable()->index();
                $table->string('id_number_hash', 128)->nullable()->index();
                $table->string('id_number_last4', 16)->nullable();
                $table->string('phone_hash', 128)->nullable()->index();
                $table->string('phone_last4', 16)->nullable();
                $table->string('registration_number_hash', 128)->nullable()->index();
                $table->string('registration_number_last4', 16)->nullable();
                $table->longText('data_json')->nullable();
                $table->longText('rejection_reason')->nullable();
                $table->longText('last_error')->nullable();
                $table->longText('admin_notes')->nullable();
                $table->unsignedInteger('reviewed_by')->nullable()->index();
                $table->timestamp('submitted_at')->nullable()->index();
                $table->timestamp('verified_at')->nullable()->index();
                $table->timestamp('reviewed_at')->nullable()->index();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!$schema->hasTable(PRKYC_SUBMISSIONS_TABLE)) {
            $schema->create(PRKYC_SUBMISSIONS_TABLE, static function ($table): void {
                $table->increments('id');
                $table->unsignedInteger('profile_id')->nullable()->index();
                $table->unsignedInteger('client_id')->index();
                $table->string('type', 40)->index();
                $table->string('provider', 80)->index();
                $table->string('status', 30)->index();
                $table->longText('payload_json')->nullable();
                $table->longText('result_json')->nullable();
                $table->longText('admin_notes')->nullable();
                $table->longText('rejection_reason')->nullable();
                $table->unsignedInteger('reviewed_by')->nullable()->index();
                $table->timestamp('submitted_at')->nullable()->index();
                $table->timestamp('reviewed_at')->nullable()->index();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!$schema->hasTable(PRKYC_DOCUMENTS_TABLE)) {
            $schema->create(PRKYC_DOCUMENTS_TABLE, static function ($table): void {
                $table->increments('id');
                $table->unsignedInteger('profile_id')->index();
                $table->unsignedInteger('submission_id')->nullable()->index();
                $table->unsignedInteger('client_id')->index();
                $table->string('document_type', 60)->index();
                $table->string('original_name', 191);
                $table->string('stored_name', 191)->unique();
                $table->string('storage_path', 500);
                $table->string('file_hash', 128)->index();
                $table->string('mime_type', 120)->nullable();
                $table->unsignedInteger('file_size');
                $table->string('status', 30)->index();
                $table->timestamp('created_at')->nullable()->index();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('deleted_at')->nullable()->index();
            });
        }

        if (!$schema->hasTable(PRKYC_RULES_TABLE)) {
            $schema->create(PRKYC_RULES_TABLE, static function ($table): void {
                $table->increments('id');
                $table->string('scope_type', 40)->index();
                $table->string('scope_value', 120)->index();
                $table->string('requirement', 40)->default('verified')->index();
                $table->boolean('enabled')->default(true)->index();
                $table->longText('notes')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unique(['scope_type', 'scope_value']);
            });
        }

        if (!$schema->hasTable(PRKYC_LOGS_TABLE)) {
            $schema->create(PRKYC_LOGS_TABLE, static function ($table): void {
                $table->increments('id');
                $table->unsignedInteger('client_id')->nullable()->index();
                $table->unsignedInteger('order_id')->nullable()->index();
                $table->string('level', 20)->index();
                $table->string('message', 255);
                $table->longText('context')->nullable();
                $table->timestamp('created_at')->nullable()->index();
            });
        }

        if (!$schema->hasTable(PRKYC_API_ATTEMPTS_TABLE)) {
            $schema->create(PRKYC_API_ATTEMPTS_TABLE, static function ($table): void {
                $table->increments('id');
                $table->unsignedInteger('client_id')->index();
                $table->string('provider', 80)->index();
                $table->string('status', 30)->index();
                $table->string('result_code', 80)->nullable()->index();
                $table->string('description', 255)->nullable();
                $table->string('isp', 80)->nullable();
                $table->string('request_id', 120)->nullable();
                $table->longText('response_json')->nullable();
                $table->timestamp('created_at')->nullable()->index();
            });
        }

        peakrackKycEnsureSchema();
    }
}

if (!function_exists('peakrackKycEnsureSchema')) {
    function peakrackKycEnsureSchema(): void
    {
        peakrackKycEnsureColumn(PRKYC_PROFILES_TABLE, 'admin_notes', static function ($table): void {
            $table->longText('admin_notes')->nullable();
        });
        peakrackKycEnsureColumn(PRKYC_PROFILES_TABLE, 'expires_at', static function ($table): void {
            $table->timestamp('expires_at')->nullable()->index();
        });
        peakrackKycEnsureColumn(PRKYC_DOCUMENTS_TABLE, 'submission_id', static function ($table): void {
            $table->unsignedInteger('submission_id')->nullable()->index();
        });
        peakrackKycEnsureColumn(PRKYC_DOCUMENTS_TABLE, 'deleted_at', static function ($table): void {
            $table->timestamp('deleted_at')->nullable()->index();
        });
        peakrackKycEnsureColumn(PRKYC_PROVIDER_LOGS_TABLE, 'description', static function ($table): void {
            $table->string('description', 255)->nullable();
        });
        peakrackKycEnsureColumn(PRKYC_PROVIDER_LOGS_TABLE, 'isp', static function ($table): void {
            $table->string('isp', 80)->nullable();
        });
    }
}

if (!function_exists('peakrackKycEnsureColumn')) {
    function peakrackKycEnsureColumn(string $table, string $column, callable $definition): void
    {
        $schema = Capsule::schema();
        if ($schema->hasTable($table) && !$schema->hasColumn($table, $column)) {
            $schema->table($table, $definition);
        }
    }
}

if (!function_exists('peakrackKycLoadSettings')) {
    function peakrackKycLoadSettings(): array
    {
        try {
            peakrackKycCreateTables();
            $row = Capsule::table(PRKYC_SETTINGS_TABLE)
                ->where('setting', PRKYC_SETTING_KEY)
                ->first();
            $stored = $row ? peakrackKycJsonDecode((string) $row->value, []) : [];
        } catch (Throwable $e) {
            $stored = [];
        }

        return peakrackKycMergeSettings(peakrackKycDefaults(), $stored);
    }
}

if (!function_exists('peakrackKycSaveSettings')) {
    function peakrackKycSaveSettings(array $settings): void
    {
        peakrackKycCreateTables();
        $settings = peakrackKycMergeSettings(peakrackKycDefaults(), $settings);
        $payload = [
            'setting' => PRKYC_SETTING_KEY,
            'value' => peakrackKycJsonEncode($settings),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $exists = Capsule::table(PRKYC_SETTINGS_TABLE)
            ->where('setting', PRKYC_SETTING_KEY)
            ->exists();

        if ($exists) {
            Capsule::table(PRKYC_SETTINGS_TABLE)
                ->where('setting', PRKYC_SETTING_KEY)
                ->update($payload);
            return;
        }

        Capsule::table(PRKYC_SETTINGS_TABLE)->insert($payload);
    }
}

if (!function_exists('peakrackKycProviderCatalog')) {
    function peakrackKycProviderCatalog(): array
    {
        return [
            'tencent_phone_three_factor' => [
                'code' => 'tencent_phone_three_factor',
                'class' => TencentPhoneThreeFactorProvider::class,
                'label' => 'TencentPhoneThreeFactorProvider',
                'kind' => 'api',
                'status' => 'available',
                'selectable' => true,
                'description_key' => 'provider_tencent_desc',
            ],
            'manual_review' => [
                'code' => 'manual_review',
                'class' => ManualReviewProvider::class,
                'label' => 'ManualReviewProvider',
                'kind' => 'manual',
                'status' => 'available',
                'selectable' => false,
                'description_key' => 'provider_manual_desc',
            ],
            'alipay_real_name_info' => [
                'code' => 'alipay_real_name_info',
                'class' => AlipayRealNameInfoProvider::class,
                'label' => 'AlipayRealNameInfoProvider',
                'kind' => 'oauth',
                'status' => 'available',
                'selectable' => false,
                'description_key' => 'provider_alipay_real_name_desc',
            ],
            'alipay_face' => [
                'code' => 'alipay_face',
                'class' => AlipayFaceProvider::class,
                'label' => 'AlipayFaceProvider',
                'kind' => 'api',
                'status' => 'reserved',
                'selectable' => false,
                'description_key' => 'provider_alipay_desc',
            ],
            'bank_card_multi_factor' => [
                'code' => 'bank_card_multi_factor',
                'class' => BankCardProvider::class,
                'label' => 'BankCardProvider',
                'kind' => 'api',
                'status' => 'reserved',
                'selectable' => false,
                'description_key' => 'provider_bank_card_desc',
            ],
            'company_verification' => [
                'code' => 'company_verification',
                'class' => CompanyVerificationProvider::class,
                'label' => 'CompanyVerificationProvider',
                'kind' => 'api',
                'status' => 'reserved',
                'selectable' => false,
                'description_key' => 'provider_company_desc',
            ],
            'overseas_kyc' => [
                'code' => 'overseas_kyc',
                'class' => OverseasKycProvider::class,
                'label' => 'OverseasKycProvider',
                'kind' => 'api',
                'status' => 'reserved',
                'selectable' => false,
                'description_key' => 'provider_overseas_desc',
            ],
        ];
    }
}

if (!function_exists('peakrackKycAvailableApiProviders')) {
    function peakrackKycAvailableApiProviders(): array
    {
        $providers = [];
        foreach (peakrackKycProviderCatalog() as $code => $metadata) {
            if (($metadata['kind'] ?? '') === 'api' && !empty($metadata['selectable'])) {
                $providers[] = $code;
            }
        }

        return $providers;
    }
}

if (!function_exists('peakrackKycMergeSettings')) {
    function peakrackKycMergeSettings(array $defaults, array $stored): array
    {
        $settings = array_replace_recursive($defaults, $stored);
        $settings['enabled'] = peakrackKycBool($settings['enabled'] ?? $defaults['enabled']);
        $settings['activityLog'] = peakrackKycBool($settings['activityLog'] ?? $defaults['activityLog']);
        $settings['manualReviewEnabled'] = peakrackKycBool($settings['manualReviewEnabled'] ?? $defaults['manualReviewEnabled']);
        $settings['apiVerificationEnabled'] = peakrackKycBool($settings['apiVerificationEnabled'] ?? $defaults['apiVerificationEnabled']);
        $settings['alipayRealNameEnabled'] = peakrackKycBool($settings['alipayRealNameEnabled'] ?? $defaults['alipayRealNameEnabled']);
        $settings['checkoutBlockEnabled'] = peakrackKycBool($settings['checkoutBlockEnabled'] ?? $defaults['checkoutBlockEnabled']);
        $settings['provisioningBlockEnabled'] = peakrackKycBool($settings['provisioningBlockEnabled'] ?? $defaults['provisioningBlockEnabled']);
        $settings['postOrderHoldEnabled'] = peakrackKycBool($settings['postOrderHoldEnabled'] ?? $defaults['postOrderHoldEnabled']);
        $settings['adminLanguage'] = in_array((string) ($settings['adminLanguage'] ?? 'en'), ['en', 'zh'], true)
            ? (string) $settings['adminLanguage']
            : 'en';
        if (($settings['apiProvider'] ?? '') === 'tencent_faceid') {
            $settings['apiProvider'] = 'tencent_phone_three_factor';
        }
        $settings['apiProvider'] = in_array((string) ($settings['apiProvider'] ?? ''), peakrackKycAvailableApiProviders(), true)
            ? (string) $settings['apiProvider']
            : 'tencent_phone_three_factor';
        $settings['enforcementMode'] = in_array((string) ($settings['enforcementMode'] ?? ''), ['none', 'all', 'selected'], true)
            ? (string) $settings['enforcementMode']
            : 'selected';
        $settings['rejectedOrderAction'] = in_array((string) ($settings['rejectedOrderAction'] ?? ''), ['manual', 'cancel_unpaid'], true)
            ? (string) $settings['rejectedOrderAction']
            : 'manual';
        $settings['checkoutMode'] = in_array((string) ($settings['checkoutMode'] ?? ''), ['block', 'allow_pending'], true)
            ? (string) $settings['checkoutMode']
            : (!empty($settings['checkoutBlockEnabled']) ? 'block' : 'allow_pending');
        $settings['apiTimeout'] = peakrackKycClampInt($settings['apiTimeout'] ?? $defaults['apiTimeout'], 3, 60, (int) $defaults['apiTimeout']);
        $settings['apiTestMode'] = peakrackKycBool($settings['apiTestMode'] ?? $defaults['apiTestMode']);
        $settings['maxUploadMb'] = peakrackKycClampInt($settings['maxUploadMb'] ?? $defaults['maxUploadMb'], 1, 64, (int) $defaults['maxUploadMb']);
        $settings['retentionDays'] = peakrackKycClampInt($settings['retentionDays'] ?? $defaults['retentionDays'], 0, 3650, (int) $defaults['retentionDays']);
        $settings['maxLogs'] = peakrackKycClampInt($settings['maxLogs'] ?? $defaults['maxLogs'], 0, 1000000, (int) $defaults['maxLogs']);
        $settings['enforcedProductIds'] = peakrackKycNormalizeIntList($settings['enforcedProductIds'] ?? []);
        $settings['enforcedProductGroupIds'] = peakrackKycNormalizeIntList($settings['enforcedProductGroupIds'] ?? []);
        $settings['enforcedTlds'] = peakrackKycNormalizeTldList($settings['enforcedTlds'] ?? []);
        $settings['allowedExtensions'] = peakrackKycNormalizeExtensionList($settings['allowedExtensions'] ?? $defaults['allowedExtensions']);
        $settings['emailNotifications'] = peakrackKycBool($settings['emailNotifications'] ?? $defaults['emailNotifications']);
        $settings['adminEmailNotifications'] = peakrackKycBool($settings['adminEmailNotifications'] ?? $defaults['adminEmailNotifications']);

        foreach (['tencentSecretId', 'tencentSecretKey', 'tencentRegion', 'tencentEndpoint', 'tencentVerifyMode', 'alipayAppId', 'alipayPrivateKey', 'alipayApiBaseUrl', 'alipayAuthUrl', 'alipayOauthScope', 'alipayAuthSource', 'alipayCertType', 'storagePath', 'emailTemplateSubmitted', 'emailTemplateApproved', 'emailTemplateRejected'] as $key) {
            $settings[$key] = trim((string) ($settings[$key] ?? ''));
        }

        if ($settings['tencentEndpoint'] === '') {
            $settings['tencentEndpoint'] = $defaults['tencentEndpoint'];
        }
        if ($settings['alipayApiBaseUrl'] === '') {
            $settings['alipayApiBaseUrl'] = $defaults['alipayApiBaseUrl'];
        }
        if ($settings['alipayAuthUrl'] === '') {
            $settings['alipayAuthUrl'] = $defaults['alipayAuthUrl'];
        }
        if ($settings['alipayOauthScope'] === '') {
            $settings['alipayOauthScope'] = $defaults['alipayOauthScope'];
        }
        if ($settings['alipayAuthSource'] === '') {
            $settings['alipayAuthSource'] = $defaults['alipayAuthSource'];
        }
        if ($settings['alipayCertType'] === '') {
            $settings['alipayCertType'] = $defaults['alipayCertType'];
        }

        foreach (['en', 'zh'] as $language) {
            if (!isset($settings['clientNotice'][$language]) || !is_scalar($settings['clientNotice'][$language])) {
                $settings['clientNotice'][$language] = $defaults['clientNotice'][$language];
            }
        }

        return $settings;
    }
}

if (!function_exists('peakrackKycProvider')) {
    function peakrackKycProvider(string $name): ProviderInterface
    {
        $catalog = peakrackKycProviderCatalog();
        $metadata = $catalog[$name] ?? $catalog['tencent_phone_three_factor'];
        $class = (string) ($metadata['class'] ?? TencentPhoneThreeFactorProvider::class);

        if (class_exists($class)) {
            $provider = new $class();
            if ($provider instanceof ProviderInterface) {
                return $provider;
            }
        }

        return new TencentPhoneThreeFactorProvider();
    }
}

if (!function_exists('peakrackKycSyncRulesFromSettings')) {
    function peakrackKycSyncRulesFromSettings(array $settings): void
    {
        peakrackKycCreateTables();
        Capsule::table(PRKYC_RULES_TABLE)->delete();
        $now = date('Y-m-d H:i:s');
        $rows = [];

        foreach ($settings['enforcedProductIds'] as $id) {
            $rows[] = [
                'scope_type' => 'product',
                'scope_value' => (string) $id,
                'requirement' => 'verified',
                'enabled' => true,
                'notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($settings['enforcedProductGroupIds'] as $id) {
            $rows[] = [
                'scope_type' => 'product_group',
                'scope_value' => (string) $id,
                'requirement' => 'verified',
                'enabled' => true,
                'notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($settings['enforcedTlds'] as $tld) {
            $rows[] = [
                'scope_type' => 'tld',
                'scope_value' => strtolower($tld),
                'requirement' => 'verified',
                'enabled' => true,
                'notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            Capsule::table(PRKYC_RULES_TABLE)->insert($chunk);
        }
    }
}

if (!function_exists('peakrackKycLoadRules')) {
    function peakrackKycLoadRules(): array
    {
        try {
            $rows = Capsule::table(PRKYC_RULES_TABLE)
                ->where('enabled', true)
                ->get();
        } catch (Throwable $e) {
            return [
                'product' => [],
                'product_group' => [],
                'tld' => [],
            ];
        }

        $rules = [
            'product' => [],
            'product_group' => [],
            'tld' => [],
        ];
        foreach ($rows as $row) {
            $type = (string) ($row->scope_type ?? '');
            $value = (string) ($row->scope_value ?? '');
            if (!array_key_exists($type, $rules) || $value === '') {
                continue;
            }

            $rules[$type][] = $type === 'tld' ? strtolower($value) : (int) $value;
        }

        foreach ($rules as $type => $values) {
            $rules[$type] = array_values(array_unique($values));
        }

        return $rules;
    }
}

if (!function_exists('peakrackKycListRules')) {
    function peakrackKycListRules(bool $includeDisabled = true): array
    {
        try {
            $query = Capsule::table(PRKYC_RULES_TABLE);
            if (!$includeDisabled) {
                $query->where('enabled', true);
            }

            return $query
                ->orderBy('scope_type')
                ->orderBy('scope_value')
                ->get()
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('peakrackKycNormalizeRuleInput')) {
    function peakrackKycNormalizeRuleInput(string $scopeType, string $scopeValue, string $requirement = 'verified'): array
    {
        $scopeType = strtolower(trim($scopeType));
        if (!in_array($scopeType, ['product', 'product_group', 'tld'], true)) {
            return ['success' => false, 'message' => 'Invalid rule scope.'];
        }

        $scopeValue = trim($scopeValue);
        if ($scopeType === 'product' || $scopeType === 'product_group') {
            $id = (int) $scopeValue;
            if ($id <= 0) {
                return ['success' => false, 'message' => 'Product and product group rules require a numeric ID.'];
            }
            $scopeValue = (string) $id;
        } else {
            $tlds = peakrackKycNormalizeTldList([$scopeValue]);
            if (empty($tlds)) {
                return ['success' => false, 'message' => 'TLD rules require a valid TLD without a leading dot.'];
            }
            $scopeValue = (string) $tlds[0];
        }

        $requirement = strtolower(trim($requirement));
        if (!in_array($requirement, ['verified'], true)) {
            $requirement = 'verified';
        }

        return [
            'success' => true,
            'scope_type' => $scopeType,
            'scope_value' => $scopeValue,
            'requirement' => $requirement,
        ];
    }
}

if (!function_exists('peakrackKycSaveRule')) {
    function peakrackKycSaveRule(int $ruleId, string $scopeType, string $scopeValue, string $requirement, bool $enabled, string $notes, int $adminId = 0): array
    {
        $normalized = peakrackKycNormalizeRuleInput($scopeType, $scopeValue, $requirement);
        if (empty($normalized['success'])) {
            return ['success' => false, 'message' => (string) $normalized['message']];
        }

        $now = date('Y-m-d H:i:s');
        $payload = [
            'scope_type' => $normalized['scope_type'],
            'scope_value' => $normalized['scope_value'],
            'requirement' => $normalized['requirement'],
            'enabled' => $enabled ? 1 : 0,
            'notes' => peakrackKycNullableText(mb_substr(trim($notes), 0, 2000)),
            'updated_at' => $now,
        ];

        try {
            $duplicateQuery = Capsule::table(PRKYC_RULES_TABLE)
                ->where('scope_type', $payload['scope_type'])
                ->where('scope_value', $payload['scope_value']);
            if ($ruleId > 0) {
                $duplicateQuery->where('id', '<>', $ruleId);
            }
            $duplicate = $duplicateQuery->first();
            if ($duplicate) {
                return ['success' => false, 'message' => 'A rule for this scope already exists.'];
            }

            if ($ruleId > 0) {
                $updated = Capsule::table(PRKYC_RULES_TABLE)
                    ->where('id', $ruleId)
                    ->update($payload);
                if (!$updated) {
                    return ['success' => false, 'message' => 'Rule not found.'];
                }
            } else {
                $payload['created_at'] = $now;
                $ruleId = (int) Capsule::table(PRKYC_RULES_TABLE)->insertGetId($payload);
            }

            peakrackKycLog('info', 'KYC rule saved', 0, 0, [
                'rule_id' => $ruleId,
                'scope_type' => (string) $payload['scope_type'],
                'scope_value' => (string) $payload['scope_value'],
                'admin_id' => $adminId,
            ]);

            return ['success' => true, 'message' => 'Rule saved.'];
        } catch (Throwable $e) {
            peakrackKycLog('error', 'Unable to save KYC rule: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Unable to save rule.'];
        }
    }
}

if (!function_exists('peakrackKycDeleteRule')) {
    function peakrackKycDeleteRule(int $ruleId, int $adminId = 0): array
    {
        if ($ruleId <= 0) {
            return ['success' => false, 'message' => 'Invalid rule.'];
        }

        try {
            $rule = Capsule::table(PRKYC_RULES_TABLE)->where('id', $ruleId)->first();
            if (!$rule) {
                return ['success' => false, 'message' => 'Rule not found.'];
            }

            Capsule::table(PRKYC_RULES_TABLE)->where('id', $ruleId)->delete();
            peakrackKycLog('warning', 'KYC rule deleted', 0, 0, [
                'rule_id' => $ruleId,
                'scope_type' => (string) ($rule->scope_type ?? ''),
                'scope_value' => (string) ($rule->scope_value ?? ''),
                'admin_id' => $adminId,
            ]);

            return ['success' => true, 'message' => 'Rule deleted.'];
        } catch (Throwable $e) {
            peakrackKycLog('error', 'Unable to delete KYC rule: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Unable to delete rule.'];
        }
    }
}

if (!function_exists('peakrackKycGetProfile')) {
    function peakrackKycGetProfile(int $clientId): ?object
    {
        if ($clientId <= 0) {
            return null;
        }

        try {
            return Capsule::table(PRKYC_PROFILES_TABLE)
                ->where('client_id', $clientId)
                ->first();
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('peakrackKycGetProfileArray')) {
    function peakrackKycGetProfileArray(int $clientId): array
    {
        $profile = peakrackKycGetProfile($clientId);
        if (!$profile) {
            return [
                'id' => 0,
                'client_id' => $clientId,
                'type' => '',
                'status' => 'unsubmitted',
                'verification_method' => '',
                'real_name' => '',
                'company_name' => '',
                'country' => '',
                'document_type' => '',
                'submitted_at' => '',
                'verified_at' => '',
                'reviewed_at' => '',
                'rejection_reason' => '',
                'last_error' => '',
            ];
        }

        return [
            'id' => (int) ($profile->id ?? 0),
            'client_id' => (int) ($profile->client_id ?? 0),
            'type' => (string) ($profile->type ?? ''),
            'status' => (string) ($profile->status ?? 'unsubmitted'),
            'verification_method' => (string) ($profile->verification_method ?? ''),
            'real_name' => (string) ($profile->real_name ?? ''),
            'company_name' => (string) ($profile->company_name ?? ''),
            'country' => (string) ($profile->country ?? ''),
            'document_type' => (string) ($profile->document_type ?? ''),
            'submitted_at' => (string) ($profile->submitted_at ?? ''),
            'verified_at' => (string) ($profile->verified_at ?? ''),
            'reviewed_at' => (string) ($profile->reviewed_at ?? ''),
            'rejection_reason' => (string) ($profile->rejection_reason ?? ''),
            'last_error' => (string) ($profile->last_error ?? ''),
        ];
    }
}

if (!function_exists('peakrackKycIsClientVerified')) {
    function peakrackKycIsClientVerified(int $clientId): bool
    {
        $profile = peakrackKycGetProfile($clientId);
        if (!$profile || (string) ($profile->status ?? '') !== 'verified') {
            return false;
        }

        $expiresAt = (string) ($profile->expires_at ?? '');
        if ($expiresAt !== '' && strtotime($expiresAt) !== false && strtotime($expiresAt) < time()) {
            try {
                Capsule::table(PRKYC_PROFILES_TABLE)
                    ->where('id', (int) ($profile->id ?? 0))
                    ->update([
                        'status' => 'expired',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                peakrackKycLog('warning', 'KYC profile expired', (int) ($profile->client_id ?? 0), 0, [
                    'profile_id' => (int) ($profile->id ?? 0),
                ]);
            } catch (Throwable $e) {
            }

            return false;
        }

        return true;
    }
}

if (!function_exists('peakrackKycUpsertProfile')) {
    function peakrackKycUpsertProfile(int $clientId, array $data): int
    {
        peakrackKycCreateTables();
        $now = date('Y-m-d H:i:s');
        $existing = peakrackKycGetProfile($clientId);
        $payload = [
            'client_id' => $clientId,
            'type' => peakrackKycNormalizeProfileType($data['type'] ?? 'individual'),
            'status' => peakrackKycNormalizeStatus($data['status'] ?? 'pending'),
            'verification_method' => peakrackKycNullableString($data['verification_method'] ?? null, 40),
            'real_name' => peakrackKycNullableString($data['real_name'] ?? null, 191),
            'company_name' => peakrackKycNullableString($data['company_name'] ?? null, 191),
            'country' => peakrackKycCountry($data['country'] ?? ''),
            'document_type' => peakrackKycNullableString($data['document_type'] ?? null, 60),
            'id_number_hash' => peakrackKycSensitiveHash($data['id_number'] ?? ''),
            'id_number_last4' => peakrackKycLast4($data['id_number'] ?? ''),
            'phone_hash' => peakrackKycSensitiveHash($data['phone'] ?? ''),
            'phone_last4' => peakrackKycLast4($data['phone'] ?? ''),
            'registration_number_hash' => peakrackKycSensitiveHash($data['registration_number'] ?? ''),
            'registration_number_last4' => peakrackKycLast4($data['registration_number'] ?? ''),
            'data_json' => peakrackKycJsonEncode(peakrackKycProfilePublicData($data)),
            'rejection_reason' => peakrackKycNullableText($data['rejection_reason'] ?? null),
            'last_error' => peakrackKycNullableText($data['last_error'] ?? null),
            'admin_notes' => peakrackKycNullableText($data['admin_notes'] ?? null),
            'updated_at' => $now,
        ];

        if (!$existing) {
            $payload['submitted_at'] = $data['submitted_at'] ?? $now;
            $payload['created_at'] = $now;
            if ($payload['status'] === 'verified') {
                $payload['verified_at'] = $data['verified_at'] ?? $now;
            }
            if (array_key_exists('expires_at', $data)) {
                $payload['expires_at'] = $data['expires_at'];
            }

            return (int) Capsule::table(PRKYC_PROFILES_TABLE)->insertGetId($payload);
        }

        if (array_key_exists('submitted_at', $data)) {
            $payload['submitted_at'] = $data['submitted_at'];
        } elseif ($payload['status'] === 'pending') {
            $payload['submitted_at'] = $now;
        }

        if ($payload['status'] === 'verified') {
            $payload['verified_at'] = $data['verified_at'] ?? $now;
        }
        if (array_key_exists('expires_at', $data)) {
            $payload['expires_at'] = $data['expires_at'];
        }

        Capsule::table(PRKYC_PROFILES_TABLE)
            ->where('id', (int) $existing->id)
            ->update($payload);

        return (int) $existing->id;
    }
}

if (!function_exists('peakrackKycReviewProfile')) {
    function peakrackKycReviewDecisionStatus(string $decision): ?string
    {
        $statusMap = [
            'approve' => 'verified',
            'reject' => 'rejected',
            'revoke' => 'revoked',
            'request_resubmit' => 'rejected',
        ];

        return $statusMap[$decision] ?? null;
    }

    function peakrackKycAllowedReviewDecisions(string $status): array
    {
        $status = peakrackKycNormalizeStatus($status);
        $allowed = [
            'unsubmitted' => ['request_resubmit'],
            'pending' => ['approve', 'reject', 'request_resubmit'],
            'verified' => ['revoke', 'request_resubmit'],
            'rejected' => ['approve', 'request_resubmit'],
            'expired' => ['approve', 'request_resubmit'],
            'revoked' => ['approve', 'request_resubmit'],
        ];

        return $allowed[$status] ?? [];
    }

    function peakrackKycReviewProfile(int $profileId, string $decision, string $reason, int $adminId, array $settings): array
    {
        $profile = Capsule::table(PRKYC_PROFILES_TABLE)->where('id', $profileId)->first();
        if (!$profile) {
            return ['success' => false, 'message' => 'Profile not found.'];
        }

        $now = date('Y-m-d H:i:s');
        $status = peakrackKycReviewDecisionStatus($decision);
        if ($status === null) {
            return ['success' => false, 'message' => 'Invalid review decision.'];
        }

        $currentStatus = peakrackKycNormalizeStatus((string) ($profile->status ?? 'unsubmitted'));
        if (!in_array($decision, peakrackKycAllowedReviewDecisions($currentStatus), true)) {
            return ['success' => false, 'message' => 'This review decision is not allowed from the current status.'];
        }

        $reason = trim($reason);
        if (in_array($decision, ['reject', 'request_resubmit', 'revoke'], true) && $reason === '') {
            $reason = $decision === 'request_resubmit'
                ? 'Please update and resubmit your verification materials.'
                : 'No reason provided.';
        }

        $payload = [
            'status' => $status,
            'verification_method' => (string) ($profile->verification_method ?? '') !== '' ? (string) $profile->verification_method : 'manual',
            'reviewed_by' => $adminId > 0 ? $adminId : null,
            'reviewed_at' => $now,
            'updated_at' => $now,
            'rejection_reason' => in_array($status, ['rejected', 'revoked'], true) ? $reason : null,
            'last_error' => null,
            'admin_notes' => $reason !== '' ? $reason : null,
        ];
        if ($status === 'verified') {
            $payload['verified_at'] = $now;
            $payload['expires_at'] = null;
        }

        Capsule::table(PRKYC_PROFILES_TABLE)->where('id', $profileId)->update($payload);
        Capsule::table(PRKYC_DOCUMENTS_TABLE)
            ->where('profile_id', $profileId)
            ->update([
                'status' => $status,
                'reviewed_at' => $now,
            ]);
        Capsule::table(PRKYC_SUBMISSIONS_TABLE)
            ->where('profile_id', $profileId)
            ->where('status', 'pending')
            ->update([
                'status' => $status,
                'reviewed_by' => $adminId > 0 ? $adminId : null,
                'reviewed_at' => $now,
                'rejection_reason' => in_array($status, ['rejected', 'revoked'], true) ? $reason : null,
                'admin_notes' => $reason !== '' ? $reason : null,
                'updated_at' => $now,
            ]);

        $clientId = (int) ($profile->client_id ?? 0);
        $logMessages = [
            'approve' => 'KYC profile approved',
            'reject' => 'KYC profile rejected',
            'request_resubmit' => 'KYC resubmission requested',
            'revoke' => 'KYC profile revoked',
        ];
        peakrackKycLog(
            'info',
            $logMessages[$decision] ?? 'KYC profile updated',
            $clientId,
            0,
            [
                'profile_id' => $profileId,
                'admin_id' => $adminId,
                'from_status' => $currentStatus,
                'to_status' => $status,
                'reason' => in_array($status, ['rejected', 'revoked'], true) ? $reason : '',
            ]
        );

        peakrackKycSendClientNotification($clientId, $status, [
            'reason' => $reason,
            'profile_id' => $profileId,
        ], $settings);

        if (in_array($status, ['rejected', 'revoked'], true)) {
            peakrackKycApplyRejectedOrderPolicy($clientId, $settings, $reason);
        }

        $messages = [
            'verified' => 'Profile approved.',
            'rejected' => $decision === 'request_resubmit' ? 'Resubmission requested.' : 'Profile rejected.',
            'revoked' => 'Profile revoked.',
        ];

        return ['success' => true, 'message' => $messages[$status] ?? 'Profile updated.'];
    }
}

if (!function_exists('peakrackKycCreateSubmission')) {
    function peakrackKycCreateSubmission(int $clientId, int $profileId, string $type, string $provider, string $status, array $payload = [], array $result = []): int
    {
        $now = date('Y-m-d H:i:s');
        try {
            return (int) Capsule::table(PRKYC_SUBMISSIONS_TABLE)->insertGetId([
                'profile_id' => $profileId > 0 ? $profileId : null,
                'client_id' => $clientId,
                'type' => peakrackKycNormalizeProfileType($type),
                'provider' => substr($provider, 0, 80),
                'status' => peakrackKycNormalizeStatus($status),
                'payload_json' => !empty($payload) ? peakrackKycJsonEncode(peakrackKycRedactApiResponse($payload)) : null,
                'result_json' => !empty($result) ? peakrackKycJsonEncode(peakrackKycRedactApiResponse($result)) : null,
                'submitted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (Throwable $e) {
            peakrackKycLog('error', 'Unable to create KYC submission: ' . $e->getMessage(), $clientId);
            return 0;
        }
    }
}

if (!function_exists('peakrackKycStoreUploads')) {
    function peakrackKycStoreUploads(int $clientId, int $profileId, string $documentType, array $settings, int $submissionId = 0): array
    {
        $stored = [];
        $errors = [];

        if (empty($_FILES['documents']) || !is_array($_FILES['documents']['name'] ?? null)) {
            return ['stored' => $stored, 'errors' => ['No files uploaded.']];
        }

        $storagePath = peakrackKycStoragePath($settings);
        $storageReady = peakrackKycEnsureStorage($storagePath);
        if (!$storageReady['success']) {
            return ['stored' => $stored, 'errors' => [$storageReady['message']]];
        }

        $maxBytes = (int) $settings['maxUploadMb'] * 1024 * 1024;
        $allowed = $settings['allowedExtensions'];
        $names = $_FILES['documents']['name'];
        $tmpNames = $_FILES['documents']['tmp_name'];
        $sizes = $_FILES['documents']['size'];
        $errorsIn = $_FILES['documents']['error'];

        foreach ($names as $index => $name) {
            if ((int) ($errorsIn[$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ((int) ($errorsIn[$index] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $errors[] = 'Upload failed for ' . (string) $name . '.';
                continue;
            }

            $original = peakrackKycSanitizeFilename((string) $name);
            $extension = strtolower((string) pathinfo($original, PATHINFO_EXTENSION));
            $size = (int) ($sizes[$index] ?? 0);
            $tmp = (string) ($tmpNames[$index] ?? '');

            $validation = peakrackKycValidateUploadFile($tmp, $original, $extension, $size, $maxBytes, $allowed);
            if (empty($validation['success'])) {
                $errors[] = (string) $validation['message'];
                continue;
            }

            if (!is_uploaded_file($tmp)) {
                $errors[] = $original . ' was not accepted as a valid upload.';
                continue;
            }

            $storedName = sprintf('%d_%d_%s.%s', $clientId, $profileId, bin2hex(random_bytes(16)), $extension);
            $target = rtrim($storagePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $storedName;
            if (!move_uploaded_file($tmp, $target)) {
                $errors[] = 'Unable to store ' . $original . '.';
                continue;
            }

            @chmod($target, 0640);
            $fileHash = hash_file('sha256', $target) ?: '';
            $mime = (string) $validation['mime'];
            $storedMime = peakrackKycMimeType($target);
            if (!peakrackKycAllowedMimeForExtension($storedMime, $extension) || !peakrackKycFileSignatureMatches($target, $extension)) {
                @unlink($target);
                $errors[] = $original . ' failed MIME validation.';
                continue;
            }
            $docId = (int) Capsule::table(PRKYC_DOCUMENTS_TABLE)->insertGetId([
                'profile_id' => $profileId,
                'submission_id' => $submissionId > 0 ? $submissionId : null,
                'client_id' => $clientId,
                'document_type' => $documentType,
                'original_name' => $original,
                'stored_name' => $storedName,
                'storage_path' => $target,
                'file_hash' => $fileHash,
                'mime_type' => $mime,
                'file_size' => $size,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $stored[] = ['id' => $docId, 'name' => $original];
        }

        return ['stored' => $stored, 'errors' => $errors];
    }
}

if (!function_exists('peakrackKycApiVerifyThreeFactor')) {
    function peakrackKycApiVerifyThreeFactor(int $clientId, string $realName, string $idNumber, string $phone, array $settings): array
    {
        if (!$settings['apiVerificationEnabled']) {
            return ['success' => false, 'code' => 'disabled', 'message' => 'API verification is disabled.'];
        }

        if (!empty($settings['apiTestMode'])) {
            peakrackKycRecordApiAttempt($clientId, (string) $settings['apiProvider'], 'passed', 'test_mode', 'test-mode', [
                'description' => 'Test mode verification passed without remote API call.',
            ]);
            return [
                'success' => true,
                'code' => 'test_mode',
                'message' => 'Test mode verification passed.',
                'request_id' => 'test-mode',
            ];
        }

        return peakrackKycProvider((string) $settings['apiProvider'])->verify([
            'client_id' => $clientId,
            'real_name' => $realName,
            'id_number' => $idNumber,
            'phone' => $phone,
        ], $settings);
    }
}

if (!function_exists('peakrackKycTencentFaceIdVerify')) {
    function peakrackKycTencentFaceIdVerify(int $clientId, string $realName, string $idNumber, string $phone, array $settings): array
    {
        $secretId = (string) $settings['tencentSecretId'];
        $secretKey = (string) $settings['tencentSecretKey'];
        $host = (string) $settings['tencentEndpoint'];
        $region = (string) $settings['tencentRegion'];

        if ($secretId === '' || $secretKey === '' || $host === '') {
            return ['success' => false, 'code' => 'missing_credentials', 'message' => 'Tencent Cloud credentials are incomplete.'];
        }

        $requestPayload = [
            'Name' => $realName,
            'IdCard' => $idNumber,
            'Phone' => $phone,
        ];
        if ((string) ($settings['tencentVerifyMode'] ?? '') !== '') {
            $requestPayload['VerifyMode'] = (string) $settings['tencentVerifyMode'];
        }
        $payload = peakrackKycJsonEncode($requestPayload);
        $timestamp = time();
        $date = gmdate('Y-m-d', $timestamp);
        $service = 'faceid';
        $algorithm = 'TC3-HMAC-SHA256';
        $canonicalHeaders = "content-type:application/json; charset=utf-8\nhost:" . $host . "\n";
        $signedHeaders = 'content-type;host';
        $canonicalRequest = "POST\n/\n\n" . $canonicalHeaders . "\n" . $signedHeaders . "\n" . hash('sha256', $payload);
        $credentialScope = $date . '/' . $service . '/tc3_request';
        $stringToSign = $algorithm . "\n" . $timestamp . "\n" . $credentialScope . "\n" . hash('sha256', $canonicalRequest);
        $secretDate = hash_hmac('sha256', $date, 'TC3' . $secretKey, true);
        $secretService = hash_hmac('sha256', $service, $secretDate, true);
        $secretSigning = hash_hmac('sha256', 'tc3_request', $secretService, true);
        $signature = hash_hmac('sha256', $stringToSign, $secretSigning);
        $authorization = $algorithm
            . ' Credential=' . $secretId . '/' . $credentialScope
            . ', SignedHeaders=' . $signedHeaders
            . ', Signature=' . $signature;

        $headers = [
            'Authorization: ' . $authorization,
            'Content-Type: application/json; charset=utf-8',
            'Host: ' . $host,
            'X-TC-Action: PhoneVerification',
            'X-TC-Version: 2018-03-01',
            'X-TC-Timestamp: ' . $timestamp,
        ];
        if ($region !== '') {
            $headers[] = 'X-TC-Region: ' . $region;
        }

        $response = peakrackKycHttpPost('https://' . $host, $payload, $headers, (int) $settings['apiTimeout']);
        if (!$response['success']) {
            peakrackKycRecordApiAttempt($clientId, 'tencent_phone_three_factor', 'error', 'transport_error', '', ['message' => $response['message']]);
            return ['success' => false, 'code' => 'transport_error', 'message' => $response['message']];
        }

        $decoded = peakrackKycJsonDecode($response['body'], []);
        $responseNode = is_array($decoded['Response'] ?? null) ? $decoded['Response'] : [];
        $result = (string) ($responseNode['Result'] ?? ($responseNode['Error']['Code'] ?? ''));
        $description = (string) ($responseNode['Description'] ?? ($responseNode['Error']['Message'] ?? ''));
        $requestId = (string) ($responseNode['RequestId'] ?? '');
        $success = $result === '0';

        peakrackKycRecordApiAttempt($clientId, 'tencent_phone_three_factor', $success ? 'passed' : 'failed', $result, $requestId, [
            'result' => $result,
            'description' => $description,
            'isp' => (string) ($responseNode['Isp'] ?? ''),
            'request_id' => $requestId,
        ]);

        return [
            'success' => $success,
            'code' => $result,
            'message' => $description !== '' ? $description : ($success ? 'Verified.' : 'Verification failed.'),
            'request_id' => $requestId,
        ];
    }
}

if (!function_exists('peakrackKycHttpPost')) {
    function peakrackKycHttpPost(string $url, string $payload, array $headers, int $timeout): array
    {
        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'PHP cURL extension is not available.', 'body' => ''];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if (!is_string($body)) {
            return ['success' => false, 'message' => $error !== '' ? $error : 'Empty HTTP response.', 'body' => ''];
        }

        if ($status < 200 || $status >= 300) {
            return ['success' => false, 'message' => 'HTTP ' . $status, 'body' => $body];
        }

        return ['success' => true, 'message' => 'OK', 'body' => $body];
    }
}

if (!function_exists('peakrackKycHttpRequest')) {
    function peakrackKycHttpRequest(string $method, string $url, string $payload, array $headers, int $timeout): array
    {
        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'PHP cURL extension is not available.', 'body' => '', 'headers' => [], 'status' => 0];
        }

        $method = strtoupper($method);
        $ch = curl_init($url);
        $options = [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $payload;
        } elseif ($method !== 'GET') {
            $options[CURLOPT_CUSTOMREQUEST] = $method;
            if ($payload !== '') {
                $options[CURLOPT_POSTFIELDS] = $payload;
            }
        }
        curl_setopt_array($ch, $options);

        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        if (!is_string($raw)) {
            return ['success' => false, 'message' => $error !== '' ? $error : 'Empty HTTP response.', 'body' => '', 'headers' => [], 'status' => $status];
        }

        $headerText = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);
        $responseHeaders = [];
        foreach (preg_split('/\r\n|\r|\n/', (string) $headerText) as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $responseHeaders[strtolower(trim($name))] = trim($value);
        }

        if ($status < 200 || $status >= 300) {
            return ['success' => false, 'message' => 'HTTP ' . $status, 'body' => $body, 'headers' => $responseHeaders, 'status' => $status];
        }

        return ['success' => true, 'message' => 'OK', 'body' => $body, 'headers' => $responseHeaders, 'status' => $status];
    }
}

if (!function_exists('peakrackKycAlipayCertdocPreconsult')) {
    function peakrackKycAlipayCertdocPreconsult(int $clientId, string $realName, string $idNumber, string $phone, array $settings): array
    {
        $payload = [
            'user_name' => $realName,
            'cert_type' => (string) ($settings['alipayCertType'] ?? 'IDENTITY_CARD'),
            'cert_no' => $idNumber,
        ];
        if ($phone !== '') {
            $payload['mobile'] = $phone;
        }

        $result = peakrackKycAlipayV3Request('POST', '/v3/alipay/user/certdoc/certverify/preconsult', $payload, $settings);
        $node = peakrackKycAlipayResponseNode($result['json'] ?? [], 'alipay_user_certdoc_certverify_preconsult_response');
        $code = (string) ($node['code'] ?? ($result['code'] ?? ''));
        $message = peakrackKycAlipayResultMessage($node, $result);
        $verifyId = (string) ($node['verify_id'] ?? ($result['json']['verify_id'] ?? ''));
        $requestId = (string) ($result['request_id'] ?? '');
        $success = !empty($result['success']) && ($code === '' || $code === '10000') && $verifyId !== '';

        peakrackKycRecordApiAttempt($clientId, 'alipay_real_name_info', $success ? 'passed' : 'failed', $code !== '' ? $code : ($success ? '10000' : 'preconsult_failed'), $requestId, [
            'description' => $message,
            'request_id' => $requestId,
            'verify_id' => $verifyId,
            'code' => $code,
        ]);

        return [
            'success' => $success,
            'code' => $code !== '' ? $code : ($success ? '10000' : 'preconsult_failed'),
            'message' => $message !== '' ? $message : ($success ? 'Alipay preconsult succeeded.' : 'Alipay preconsult failed.'),
            'request_id' => $requestId,
            'verify_id' => $verifyId,
            'raw' => $result['json'] ?? [],
        ];
    }

    function peakrackKycAlipayOauthToken(int $clientId, string $authCode, array $settings): array
    {
        $payload = [
            'grant_type' => 'authorization_code',
            'code' => $authCode,
        ];

        $result = peakrackKycAlipayV3Request('POST', '/v3/alipay/system/oauth/token', $payload, $settings);
        $node = peakrackKycAlipayResponseNode($result['json'] ?? [], 'alipay_system_oauth_token_response');
        $accessToken = (string) ($node['access_token'] ?? ($result['json']['access_token'] ?? ''));
        $requestId = (string) ($result['request_id'] ?? '');
        $code = (string) ($node['code'] ?? ($result['code'] ?? ''));
        $message = peakrackKycAlipayResultMessage($node, $result);
        $success = !empty($result['success']) && $accessToken !== '';

        peakrackKycRecordApiAttempt($clientId, 'alipay_real_name_info', $success ? 'passed' : 'failed', $code !== '' ? $code : ($success ? '10000' : 'oauth_token_failed'), $requestId, [
            'description' => $message !== '' ? $message : ($success ? 'OAuth token exchanged.' : 'OAuth token exchange failed.'),
            'request_id' => $requestId,
            'open_id' => (string) ($node['open_id'] ?? ''),
            'user_id' => (string) ($node['user_id'] ?? ''),
        ]);

        return [
            'success' => $success,
            'code' => $code !== '' ? $code : ($success ? '10000' : 'oauth_token_failed'),
            'message' => $message !== '' ? $message : ($success ? 'OAuth token exchanged.' : 'Unable to exchange Alipay authorization code.'),
            'request_id' => $requestId,
            'access_token' => $accessToken,
            'open_id' => (string) ($node['open_id'] ?? ''),
            'user_id' => (string) ($node['user_id'] ?? ''),
            'raw' => $result['json'] ?? [],
        ];
    }

    function peakrackKycAlipayCertdocConsult(int $clientId, string $verifyId, string $authToken, array $settings): array
    {
        if ($verifyId === '' || $authToken === '') {
            return ['success' => false, 'code' => 'missing_parameters', 'message' => 'Alipay verify_id or auth token is missing.'];
        }

        $result = peakrackKycAlipayV3Request('GET', '/v3/alipay/user/certdoc/certverify/consult', [], $settings, [
            'auth_token' => $authToken,
            'verify_id' => $verifyId,
        ]);
        $node = peakrackKycAlipayResponseNode($result['json'] ?? [], 'alipay_user_certdoc_certverify_consult_response');
        $passed = strtoupper((string) ($node['passed'] ?? ($result['json']['passed'] ?? '')));
        $code = (string) ($node['code'] ?? ($result['code'] ?? ''));
        $requestId = (string) ($result['request_id'] ?? '');
        $message = peakrackKycAlipayResultMessage($node, $result);
        if ($message === '' && (string) ($node['fail_reason'] ?? '') !== '') {
            $message = (string) $node['fail_reason'];
        }
        $success = !empty($result['success']) && ($code === '' || $code === '10000') && in_array($passed, ['T', 'Y', 'TRUE', 'PASS', 'PASSED', 'SUCCESS', '1'], true);

        peakrackKycRecordApiAttempt($clientId, 'alipay_real_name_info', $success ? 'passed' : 'failed', $code !== '' ? $code : ($success ? '10000' : 'not_passed'), $requestId, [
            'description' => $message !== '' ? $message : ($success ? 'Alipay real-name information matched.' : 'Alipay real-name information did not match.'),
            'request_id' => $requestId,
            'passed' => $passed,
            'fail_reason' => (string) ($node['fail_reason'] ?? ''),
        ]);

        return [
            'success' => $success,
            'code' => $code !== '' ? $code : ($success ? '10000' : 'not_passed'),
            'message' => $message !== '' ? $message : ($success ? 'Alipay real-name information matched.' : 'Alipay real-name information did not match.'),
            'request_id' => $requestId,
            'passed' => $passed,
            'raw' => $result['json'] ?? [],
        ];
    }

    function peakrackKycAlipayAuthUrl(string $state, array $settings): string
    {
        $callbackUrl = peakrackKycAlipayCallbackUrl();
        $params = [
            'app_id' => (string) ($settings['alipayAppId'] ?? ''),
            'scope' => (string) ($settings['alipayOauthScope'] ?? 'auth_base'),
            'redirect_uri' => $callbackUrl,
            'state' => $state,
        ];
        $source = trim((string) ($settings['alipayAuthSource'] ?? 'alipay_wallet'));
        if ($source !== '') {
            $params['source'] = $source;
        }

        return rtrim((string) ($settings['alipayAuthUrl'] ?? ''), '?') . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    function peakrackKycAlipayCallbackUrl(): string
    {
        return peakrackKycSystemUrl() . '/index.php?m=peakrack_kyc&prkyc_client_action=alipay_real_name_callback';
    }

    function peakrackKycAlipayV3Request(string $method, string $path, array $payload, array $settings, array $query = []): array
    {
        $appId = trim((string) ($settings['alipayAppId'] ?? ''));
        $privateKey = peakrackKycAlipayNormalizePrivateKey((string) ($settings['alipayPrivateKey'] ?? ''));
        if ($appId === '' || $privateKey === '') {
            return ['success' => false, 'code' => 'missing_credentials', 'message' => 'Alipay AppID or private key is missing.', 'json' => []];
        }

        $method = strtoupper($method);
        $pathWithQuery = $path;
        if (!empty($query)) {
            $pathWithQuery .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }
        $body = $method === 'GET' ? '' : peakrackKycJsonEncode($payload);
        $requestId = substr(bin2hex(random_bytes(16)), 0, 32);
        $nonce = bin2hex(random_bytes(16));
        $timestamp = (string) round(microtime(true) * 1000);
        $authString = 'app_id=' . $appId . ',nonce=' . $nonce . ',timestamp=' . $timestamp;
        $stringToSignParts = [$authString, $method, $pathWithQuery];
        if ($body !== '') {
            $stringToSignParts[] = $body;
        }
        $stringToSign = implode("\n", $stringToSignParts) . "\n";

        $signature = '';
        $key = openssl_pkey_get_private($privateKey);
        if (!$key || !openssl_sign($stringToSign, $signature, $key, OPENSSL_ALGO_SHA256)) {
            return ['success' => false, 'code' => 'sign_failed', 'message' => 'Unable to sign Alipay request.', 'json' => []];
        }

        $authorization = 'ALIPAY-SHA256withRSA ' . $authString . ',sign=' . base64_encode($signature);
        $headers = [
            'Authorization: ' . $authorization,
            'Accept: application/json',
            'Content-Type: application/json; charset=UTF-8',
            'alipay-request-id: ' . $requestId,
        ];

        $url = peakrackKycAlipayApiBaseUrl($settings) . $pathWithQuery;
        $response = peakrackKycHttpRequest($method, $url, $body, $headers, (int) ($settings['apiTimeout'] ?? 15));
        $decoded = peakrackKycJsonDecode((string) ($response['body'] ?? ''), []);
        $responseHeaders = is_array($response['headers'] ?? null) ? $response['headers'] : [];
        $traceId = (string) ($responseHeaders['alipay-traceid'] ?? $requestId);

        if (empty($response['success'])) {
            $message = (string) ($decoded['message'] ?? ($decoded['msg'] ?? ($response['message'] ?? 'Alipay API request failed.')));
            return [
                'success' => false,
                'code' => (string) ($decoded['code'] ?? 'transport_error'),
                'message' => $message,
                'request_id' => $traceId,
                'json' => $decoded,
                'status' => (int) ($response['status'] ?? 0),
            ];
        }

        return [
            'success' => true,
            'code' => (string) ($decoded['code'] ?? '10000'),
            'message' => (string) ($decoded['message'] ?? ($decoded['msg'] ?? 'OK')),
            'request_id' => $traceId,
            'json' => $decoded,
            'status' => (int) ($response['status'] ?? 200),
        ];
    }

    function peakrackKycAlipayApiBaseUrl(array $settings): string
    {
        $base = rtrim(trim((string) ($settings['alipayApiBaseUrl'] ?? 'https://openapi.alipay.com')), '/');
        if ($base === '') {
            $base = 'https://openapi.alipay.com';
        }
        if (str_ends_with($base, '/v3')) {
            $base = substr($base, 0, -3);
        }

        return rtrim($base, '/');
    }

    function peakrackKycAlipayNormalizePrivateKey(string $privateKey): string
    {
        $privateKey = trim(str_replace('\\n', "\n", $privateKey));
        if ($privateKey === '') {
            return '';
        }
        if (str_contains($privateKey, '-----BEGIN')) {
            return $privateKey;
        }

        $compact = preg_replace('/\s+/', '', $privateKey);
        return "-----BEGIN PRIVATE KEY-----\n" . chunk_split((string) $compact, 64, "\n") . "-----END PRIVATE KEY-----";
    }

    function peakrackKycAlipayResponseNode(array $json, string $wrapper): array
    {
        if (isset($json[$wrapper]) && is_array($json[$wrapper])) {
            return $json[$wrapper];
        }
        if (isset($json['response']) && is_array($json['response'])) {
            return $json['response'];
        }
        if (isset($json['error_response']) && is_array($json['error_response'])) {
            return $json['error_response'];
        }

        return $json;
    }

    function peakrackKycAlipayResultMessage(array $node, array $result): string
    {
        foreach (['sub_msg', 'message', 'msg', 'fail_reason'] as $key) {
            if (isset($node[$key]) && is_scalar($node[$key]) && trim((string) $node[$key]) !== '') {
                return trim((string) $node[$key]);
            }
        }
        foreach (['message', 'msg'] as $key) {
            if (isset($result[$key]) && is_scalar($result[$key]) && trim((string) $result[$key]) !== '') {
                return trim((string) $result[$key]);
            }
        }

        return '';
    }
}

if (!function_exists('peakrackKycRequiresVerificationForCart')) {
    function peakrackKycRequiresVerificationForCart(array $settings, array $vars = []): bool
    {
        if (!$settings['enabled'] || $settings['enforcementMode'] === 'none') {
            return false;
        }

        $productIds = peakrackKycCartProductIds($vars);
        if ($settings['enforcementMode'] === 'all') {
            return !empty($productIds) || peakrackKycCartLooksNonEmpty();
        }

        $rules = peakrackKycLoadRules();
        $productGroupIds = peakrackKycProductGroupIds($productIds);
        $tlds = peakrackKycCartTlds($vars);

        return count(array_intersect($productIds, $rules['product'])) > 0
            || count(array_intersect($productGroupIds, $rules['product_group'])) > 0
            || count(array_intersect($tlds, $rules['tld'])) > 0;
    }
}

if (!function_exists('peakrackKycRequiresVerificationForProduct')) {
    function peakrackKycRequiresVerificationForProduct(int $productId, array $settings): bool
    {
        if (!$settings['enabled'] || $settings['enforcementMode'] === 'none' || $productId <= 0) {
            return false;
        }

        if ($settings['enforcementMode'] === 'all') {
            return true;
        }

        $rules = peakrackKycLoadRules();
        $productGroupIds = peakrackKycProductGroupIds([$productId]);

        return in_array($productId, $rules['product'], true)
            || count(array_intersect($productGroupIds, $rules['product_group'])) > 0;
    }
}

if (!function_exists('peakrackKycCheckoutValidation')) {
    function peakrackKycCheckoutValidation(array $vars): array
    {
        $settings = peakrackKycLoadSettings();
        if (!$settings['enabled'] || $settings['checkoutMode'] !== 'block' || !$settings['checkoutBlockEnabled'] || !peakrackKycRequiresVerificationForCart($settings, $vars)) {
            return [];
        }

        $clientId = (int) ($vars['clientId'] ?? ($_SESSION['uid'] ?? 0));
        if ($clientId > 0 && peakrackKycIsClientVerified($clientId)) {
            return [];
        }

        $language = peakrackKycClientLanguage($clientId, $vars);
        $link = 'index.php?m=peakrack_kyc';
        if ($language === 'zh') {
            return ['此产品需要先完成实名认证。请前往 <a href="' . $link . '">实名认证中心</a> 提交资料或完成 API 校验。'];
        }

        return ['This product requires identity verification. Please visit the <a href="' . $link . '">KYC center</a> before checkout.'];
    }
}

if (!function_exists('peakrackKycPreModuleCreate')) {
    function peakrackKycPreModuleCreate(array $vars): array
    {
        $settings = peakrackKycLoadSettings();
        if (!$settings['enabled'] || !$settings['provisioningBlockEnabled']) {
            return [];
        }

        $params = is_array($vars['params'] ?? null) ? $vars['params'] : [];
        $clientId = (int) ($params['userid'] ?? ($params['clientsdetails']['userid'] ?? 0));
        $productId = (int) ($params['pid'] ?? ($params['packageid'] ?? 0));
        if ($productId <= 0 && isset($params['serviceid'])) {
            $productId = peakrackKycServiceProductId((int) $params['serviceid']);
        }

        if (!$clientId || !peakrackKycRequiresVerificationForProduct($productId, $settings) || peakrackKycIsClientVerified($clientId)) {
            return [];
        }

        peakrackKycLog('warning', 'Provisioning blocked pending KYC', $clientId, 0, ['product_id' => $productId]);
        return [
            'abortcmd' => true,
        ];
    }
}

if (!function_exists('peakrackKycAfterCheckout')) {
    function peakrackKycAfterCheckout(array $vars): void
    {
        $settings = peakrackKycLoadSettings();
        if (!$settings['enabled'] || !$settings['postOrderHoldEnabled']) {
            return;
        }

        $orderId = (int) ($vars['OrderID'] ?? ($vars['orderid'] ?? 0));
        $clientId = peakrackKycOrderClientId($orderId);
        if ($orderId <= 0 || $clientId <= 0 || peakrackKycIsClientVerified($clientId)) {
            return;
        }

        if (!peakrackKycOrderRequiresVerification($orderId, $settings)) {
            return;
        }

        peakrackKycLog('warning', 'Order is pending KYC verification', $clientId, $orderId);
        $invoiceId = (int) ($vars['InvoiceID'] ?? 0);
        $invoiceStatus = '';
        if ($invoiceId > 0) {
            try {
                $invoice = Capsule::table('tblinvoices')->where('id', $invoiceId)->first();
                $invoiceStatus = strtolower((string) ($invoice->status ?? ''));
            } catch (Throwable $e) {
                $invoiceStatus = '';
            }
        }

        if ($invoiceStatus === 'paid') {
            peakrackKycSendAdminNotification('paid_pending', [
                'client_id' => $clientId,
                'order_id' => $orderId,
                'invoice_id' => $invoiceId,
            ], $settings);
        }
        if (function_exists('logActivity')) {
            logActivity('PeakRack KYC: Order #' . $orderId . ' is pending identity verification.', $clientId);
        }
    }
}

if (!function_exists('peakrackKycOrderRequiresVerification')) {
    function peakrackKycOrderRequiresVerification(int $orderId, array $settings): bool
    {
        if ($orderId <= 0 || !$settings['enabled'] || $settings['enforcementMode'] === 'none') {
            return false;
        }

        if ($settings['enforcementMode'] === 'all') {
            return Capsule::table('tblhosting')->where('orderid', $orderId)->exists();
        }

        $rules = peakrackKycLoadRules();
        $count = Capsule::table('tblhosting')
            ->where('orderid', $orderId)
            ->whereIn('packageid', $rules['product'])
            ->count();
        if ($count > 0) {
            return true;
        }

        if (!empty($rules['product_group'])) {
            $groupCount = Capsule::table('tblhosting')
                ->join('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid')
                ->where('tblhosting.orderid', $orderId)
                ->whereIn('tblproducts.gid', $rules['product_group'])
                ->count();
            if ($groupCount > 0) {
                return true;
            }
        }

        if (!empty($rules['tld'])) {
            $domainCount = Capsule::table('tbldomains')
                ->where('orderid', $orderId)
                ->get()
                ->filter(static function ($domain) use ($rules): bool {
                    return count(array_intersect(peakrackKycTldCandidates((string) ($domain->domain ?? '')), $rules['tld'])) > 0;
                })
                ->count();

            if ($domainCount > 0) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('peakrackKycApplyRejectedOrderPolicy')) {
    function peakrackKycApplyRejectedOrderPolicy(int $clientId, array $settings, string $reason = ''): void
    {
        if ($clientId <= 0 || $settings['rejectedOrderAction'] !== 'cancel_unpaid') {
            return;
        }

        try {
            $orders = Capsule::table('tblorders')
                ->where('userid', $clientId)
                ->where('status', 'Pending')
                ->orderBy('id', 'desc')
                ->limit(50)
                ->get();

            foreach ($orders as $order) {
                $orderId = (int) ($order->id ?? 0);
                if (!peakrackKycOrderRequiresVerification($orderId, $settings)) {
                    continue;
                }

                $invoiceId = (int) ($order->invoiceid ?? 0);
                $invoice = $invoiceId > 0 ? Capsule::table('tblinvoices')->where('id', $invoiceId)->first() : null;
                $invoiceStatus = strtolower((string) ($invoice->status ?? ''));
                if ($invoice && !in_array($invoiceStatus, ['unpaid', 'cancelled'], true)) {
                    peakrackKycLog('warning', 'KYC rejected but paid order requires manual handling', $clientId, $orderId, ['invoice_status' => $invoiceStatus]);
                    peakrackKycSendAdminNotification('rejected_paid', [
                        'client_id' => $clientId,
                        'order_id' => $orderId,
                        'invoice_id' => $invoiceId,
                        'invoice_status' => $invoiceStatus,
                        'reason' => $reason,
                    ], $settings);
                    continue;
                }

                Capsule::table('tblorders')->where('id', $orderId)->update(['status' => 'Cancelled']);
                Capsule::table('tblhosting')->where('orderid', $orderId)->update(['domainstatus' => 'Cancelled']);
                peakrackKycLog('warning', 'Unpaid pending order cancelled after KYC rejection', $clientId, $orderId, ['reason' => $reason]);
            }
        } catch (Throwable $e) {
            peakrackKycLog('error', 'Rejected order policy failed: ' . $e->getMessage(), $clientId);
        }
    }
}

if (!function_exists('peakrackKycRecordApiAttempt')) {
    function peakrackKycRecordApiAttempt(int $clientId, string $provider, string $status, string $resultCode, string $requestId, array $response): void
    {
        try {
            Capsule::table(PRKYC_API_ATTEMPTS_TABLE)->insert([
                'client_id' => $clientId,
                'provider' => $provider,
                'status' => $status,
                'result_code' => $resultCode !== '' ? substr($resultCode, 0, 80) : null,
                'description' => isset($response['description']) ? substr((string) $response['description'], 0, 255) : null,
                'isp' => isset($response['isp']) ? substr((string) $response['isp'], 0, 80) : null,
                'request_id' => $requestId !== '' ? substr($requestId, 0, 120) : null,
                'response_json' => peakrackKycJsonEncode($response),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('peakrackKycLog')) {
    function peakrackKycLog(string $level, string $message, int $clientId = 0, int $orderId = 0, array $context = []): void
    {
        try {
            Capsule::table(PRKYC_LOGS_TABLE)->insert([
                'client_id' => $clientId > 0 ? $clientId : null,
                'order_id' => $orderId > 0 ? $orderId : null,
                'level' => substr($level, 0, 20),
                'message' => substr($message, 0, 255),
                'context' => !empty($context) ? peakrackKycJsonEncode($context) : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
        }

        $settings = $GLOBALS['peakrackKycSettingsForLog'] ?? null;
        if (is_array($settings) && empty($settings['activityLog'])) {
            return;
        }

        if (function_exists('logActivity') && in_array($level, ['warning', 'error'], true)) {
            logActivity('PeakRack KYC: ' . $message, $clientId > 0 ? $clientId : 0);
        }
    }
}

if (!function_exists('peakrackKycDefaultEmailTemplates')) {
    function peakrackKycDefaultEmailTemplates(): array
    {
        $footer = '<p style="margin-top:18px;color:#667085;font-size:12px;">PeakRack KYC notification / PeakRack 实名认证通知</p>';
        return [
            'emailTemplateSubmitted' => [
                'name' => 'PeakRack KYC Submitted',
                'subject' => 'Identity verification submitted',
                'message' => '<p>Hello {$client_name},</p>'
                    . '<p>We have received your identity verification materials. Your submission is now waiting for administrator review.</p>'
                    . '<p>您好，{$client_name}：</p>'
                    . '<p>我们已经收到你的实名认证资料，目前正在等待管理员审核。</p>'
                    . '<table cellpadding="6" cellspacing="0" border="0" style="border-collapse:collapse;margin:12px 0;">'
                    . '<tr><td><strong>Profile ID</strong></td><td>#{$profile_id}</td></tr>'
                    . '<tr><td><strong>Status</strong></td><td>{$kyc_status}</td></tr>'
                    . '<tr><td><strong>Type</strong></td><td>{$kyc_type}</td></tr>'
                    . '<tr><td><strong>Method</strong></td><td>{$kyc_method}</td></tr>'
                    . '<tr><td><strong>Document</strong></td><td>{$document_type}</td></tr>'
                    . '<tr><td><strong>Country</strong></td><td>{$country}</td></tr>'
                    . '<tr><td><strong>Submitted</strong></td><td>{$submitted_at}</td></tr>'
                    . '</table>'
                    . '<p>You can view the latest status from the identity verification center:</p>'
                    . '<p><a href="{$kyc_center_url}">{$kyc_center_url}</a></p>'
                    . '<p>你可以在实名认证中心查看最新审核状态：</p>'
                    . '<p><a href="{$kyc_center_url}">{$kyc_center_url}</a></p>'
                    . $footer,
            ],
            'emailTemplateApproved' => [
                'name' => 'PeakRack KYC Approved',
                'subject' => 'Identity verification approved',
                'message' => '<p>Hello {$client_name},</p>'
                    . '<p>Your identity verification has been approved. Services that require verified identity can now continue according to the product rules.</p>'
                    . '<p>您好，{$client_name}：</p>'
                    . '<p>你的实名认证已经通过。需要实名的产品和服务现在可以按规则继续下单或开通。</p>'
                    . '<table cellpadding="6" cellspacing="0" border="0" style="border-collapse:collapse;margin:12px 0;">'
                    . '<tr><td><strong>Profile ID</strong></td><td>#{$profile_id}</td></tr>'
                    . '<tr><td><strong>Status</strong></td><td>{$kyc_status}</td></tr>'
                    . '<tr><td><strong>Type</strong></td><td>{$kyc_type}</td></tr>'
                    . '<tr><td><strong>Method</strong></td><td>{$kyc_method}</td></tr>'
                    . '<tr><td><strong>Document</strong></td><td>{$document_type}</td></tr>'
                    . '<tr><td><strong>Country</strong></td><td>{$country}</td></tr>'
                    . '<tr><td><strong>Reviewed</strong></td><td>{$reviewed_at}</td></tr>'
                    . '</table>'
                    . '<p>If your identity or company information changes later, please contact support before placing new restricted orders.</p>'
                    . '<p>如果后续实名主体、企业信息或证件信息发生变化，请在购买受限产品前联系支持团队处理。</p>'
                    . $footer,
            ],
            'emailTemplateRejected' => [
                'name' => 'PeakRack KYC Rejected',
                'subject' => 'Identity verification requires attention',
                'message' => '<p>Hello {$client_name},</p>'
                    . '<p>Your identity verification requires attention before it can be approved.</p>'
                    . '<p>您好，{$client_name}：</p>'
                    . '<p>你的实名认证资料需要更新后才能通过审核。</p>'
                    . '<table cellpadding="6" cellspacing="0" border="0" style="border-collapse:collapse;margin:12px 0;">'
                    . '<tr><td><strong>Profile ID</strong></td><td>#{$profile_id}</td></tr>'
                    . '<tr><td><strong>Status</strong></td><td>{$kyc_status}</td></tr>'
                    . '<tr><td><strong>Type</strong></td><td>{$kyc_type}</td></tr>'
                    . '<tr><td><strong>Method</strong></td><td>{$kyc_method}</td></tr>'
                    . '<tr><td><strong>Document</strong></td><td>{$document_type}</td></tr>'
                    . '<tr><td><strong>Country</strong></td><td>{$country}</td></tr>'
                    . '<tr><td><strong>Reviewed</strong></td><td>{$reviewed_at}</td></tr>'
                    . '<tr><td><strong>Reason</strong></td><td>{$reason}</td></tr>'
                    . '</table>'
                    . '<p>Please open the identity verification center and submit corrected materials:</p>'
                    . '<p><a href="{$kyc_center_url}">{$kyc_center_url}</a></p>'
                    . '<p>请前往实名认证中心重新提交修正后的资料：</p>'
                    . '<p><a href="{$kyc_center_url}">{$kyc_center_url}</a></p>'
                    . '<p>If an order has already been paid, it may remain pending until manual handling is complete.</p>'
                    . '<p>如果相关订单已经付款，订单可能会保持 Pending 状态，等待人工处理。</p>'
                    . $footer,
            ],
        ];
    }
}

if (!function_exists('peakrackKycEnsureEmailTemplates')) {
    function peakrackKycEnsureEmailTemplates(array $settings, bool $refreshExisting = false): array
    {
        try {
            $schema = Capsule::schema();
            if (!$schema->hasTable('tblemailtemplates')) {
                return ['success' => false, 'message' => 'WHMCS email template table was not found.', 'settings' => $settings];
            }

            $now = date('Y-m-d H:i:s');
            $created = 0;
            $updated = 0;
            $templateNames = [];
            foreach (peakrackKycDefaultEmailTemplates() as $settingKey => $template) {
                $name = (string) $template['name'];
                $templateNames[$settingKey] = $name;
                $existing = Capsule::table('tblemailtemplates')
                    ->where('type', 'general')
                    ->where('name', $name)
                    ->first();
                if ($existing && !$refreshExisting) {
                    continue;
                }

                $candidates = [
                    'type' => 'general',
                    'name' => $name,
                    'subject' => (string) $template['subject'],
                    'message' => (string) $template['message'],
                    'attachments' => '',
                    'fromname' => '',
                    'fromemail' => '',
                    'copyto' => '',
                    'language' => '',
                    'custom' => 1,
                    'disabled' => 0,
                    'plaintext' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $payload = [];
                foreach ($candidates as $column => $value) {
                    if ($schema->hasColumn('tblemailtemplates', $column)) {
                        $payload[$column] = $value;
                    }
                }

                if ($existing) {
                    $updatePayload = [];
                    foreach (['subject', 'message', 'plaintext', 'updated_at'] as $column) {
                        if (array_key_exists($column, $payload)) {
                            $updatePayload[$column] = $payload[$column];
                        }
                    }
                    if (!empty($updatePayload)) {
                        Capsule::table('tblemailtemplates')->where('id', (int) $existing->id)->update($updatePayload);
                        $updated++;
                    }
                    continue;
                }

                Capsule::table('tblemailtemplates')->insert($payload);
                $created++;
            }

            foreach ($templateNames as $settingKey => $name) {
                $settings[$settingKey] = $name;
            }

            return [
                'success' => true,
                'message' => $created > 0 || $updated > 0
                    ? 'Email templates installed/refreshed.'
                    : 'Email templates already exist.',
                'settings' => $settings,
                'created' => $created,
                'updated' => $updated,
            ];
        } catch (Throwable $e) {
            peakrackKycLog('error', 'Unable to install KYC email templates: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Unable to install email templates.', 'settings' => $settings];
        }
    }
}

if (!function_exists('peakrackKycSystemUrl')) {
    function peakrackKycSystemUrl(): string
    {
        $url = '';
        if (isset($GLOBALS['CONFIG']['SystemURL'])) {
            $url = (string) $GLOBALS['CONFIG']['SystemURL'];
        }
        if ($url === '' && isset($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $url = $scheme . '://' . (string) $_SERVER['HTTP_HOST'];
        }

        return rtrim($url, '/');
    }
}

if (!function_exists('peakrackKycBuildEmailContext')) {
    function peakrackKycBuildEmailContext(int $clientId, string $event, array $context): array
    {
        $context['client_id'] = $clientId;
        $context['kyc_event'] = $event;
        $context['kyc_center_url'] = peakrackKycSystemUrl() . '/index.php?m=peakrack_kyc';
        $context['reason'] = trim((string) ($context['reason'] ?? ''));

        try {
            $client = Capsule::table('tblclients')->where('id', $clientId)->first();
            if ($client) {
                $name = trim((string) ($client->firstname ?? '') . ' ' . (string) ($client->lastname ?? ''));
                if ($name === '') {
                    $name = trim((string) ($client->companyname ?? ''));
                }
                $context['client_name'] = $name !== '' ? $name : ('Client #' . $clientId);
                $context['client_email'] = (string) ($client->email ?? '');
            }
        } catch (Throwable $e) {
            $context['client_name'] = $context['client_name'] ?? ('Client #' . $clientId);
        }

        try {
            $profile = null;
            $profileId = (int) ($context['profile_id'] ?? 0);
            if ($profileId > 0) {
                $profile = Capsule::table(PRKYC_PROFILES_TABLE)->where('id', $profileId)->first();
            }
            if (!$profile) {
                $profile = Capsule::table(PRKYC_PROFILES_TABLE)->where('client_id', $clientId)->first();
            }

            if ($profile) {
                $context['profile_id'] = (int) ($profile->id ?? 0);
                $context['kyc_status'] = (string) ($profile->status ?? '');
                $context['kyc_type'] = (string) ($profile->type ?? '');
                $context['kyc_method'] = (string) ($profile->verification_method ?? '');
                $context['document_type'] = (string) ($profile->document_type ?? '');
                $context['country'] = (string) ($profile->country ?? '');
                $context['company_name'] = (string) ($profile->company_name ?? '');
                $context['submitted_at'] = (string) ($profile->submitted_at ?? '');
                $context['reviewed_at'] = (string) ($profile->reviewed_at ?? '');
                $context['verified_at'] = (string) ($profile->verified_at ?? '');
                $context['expires_at'] = (string) ($profile->expires_at ?? '');
                $context['masked_identity'] = trim(
                    'ID ****' . (string) ($profile->id_number_last4 ?? '')
                    . ' / Phone ****' . (string) ($profile->phone_last4 ?? '')
                    . ' / Reg ****' . (string) ($profile->registration_number_last4 ?? '')
                );
                if ($context['reason'] === '' && (string) ($profile->rejection_reason ?? '') !== '') {
                    $context['reason'] = (string) $profile->rejection_reason;
                }
            }
        } catch (Throwable $e) {
        }

        foreach (['client_name', 'kyc_status', 'kyc_type', 'kyc_method', 'document_type', 'country', 'company_name', 'submitted_at', 'reviewed_at', 'verified_at', 'expires_at', 'masked_identity'] as $key) {
            if (!isset($context[$key]) || !is_scalar($context[$key])) {
                $context[$key] = '';
            }
        }
        if (!isset($context['profile_id'])) {
            $context['profile_id'] = 0;
        }

        return $context;
    }
}

if (!function_exists('peakrackKycSendClientNotification')) {
    function peakrackKycSendClientNotification(int $clientId, string $event, array $context, array $settings): void
    {
        if ($clientId <= 0 || empty($settings['emailNotifications'])) {
            return;
        }

        $subjects = [
            'submitted' => 'Identity verification submitted',
            'verified' => 'Identity verification approved',
            'rejected' => 'Identity verification rejected',
            'revoked' => 'Identity verification revoked',
        ];
        $messages = [
            'submitted' => 'Your identity verification materials have been submitted and are waiting for review.',
            'verified' => 'Your identity verification has been approved.',
            'rejected' => 'Your identity verification was rejected. Reason: ' . (string) ($context['reason'] ?? ''),
            'revoked' => 'Your identity verification status was revoked. Reason: ' . (string) ($context['reason'] ?? ''),
        ];
        $templateMap = [
            'submitted' => 'emailTemplateSubmitted',
            'verified' => 'emailTemplateApproved',
            'rejected' => 'emailTemplateRejected',
            'revoked' => 'emailTemplateRejected',
        ];

        $template = (string) ($settings[$templateMap[$event] ?? ''] ?? '');
        $emailContext = peakrackKycBuildEmailContext($clientId, $event, $context);
        try {
            if ($template !== '' && function_exists('sendMessage')) {
                sendMessage($template, $clientId, $emailContext);
                return;
            }

            if (function_exists('localAPI')) {
                localAPI('SendEmail', [
                    'id' => $clientId,
                    'customtype' => 'general',
                    'customsubject' => $subjects[$event] ?? 'Identity verification update',
                    'custommessage' => ($messages[$event] ?? 'Your identity verification status was updated.')
                        . "\n\nProfile ID: " . (string) ($emailContext['profile_id'] ?? '')
                        . "\nStatus: " . (string) ($emailContext['kyc_status'] ?? '')
                        . "\nKYC center: " . (string) ($emailContext['kyc_center_url'] ?? ''),
                ]);
            }
        } catch (Throwable $e) {
            peakrackKycLog('error', 'Client email notification failed: ' . $e->getMessage(), $clientId);
        }
    }
}

if (!function_exists('peakrackKycSendAdminNotification')) {
    function peakrackKycSendAdminNotification(string $event, array $context, array $settings): void
    {
        if (empty($settings['adminEmailNotifications'])) {
            return;
        }

        $subjects = [
            'new_submission' => 'New PeakRack KYC submission',
            'paid_pending' => 'Paid order pending KYC verification',
            'rejected_paid' => 'Paid order requires manual KYC handling',
        ];
        $message = "PeakRack KYC notification\n\n" . json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            if (function_exists('localAPI')) {
                localAPI('SendAdminEmail', [
                    'type' => 'system',
                    'customsubject' => $subjects[$event] ?? 'PeakRack KYC notification',
                    'custommessage' => $message,
                ]);
            }
        } catch (Throwable $e) {
            peakrackKycLog('error', 'Admin email notification failed: ' . $e->getMessage(), (int) ($context['client_id'] ?? 0), (int) ($context['order_id'] ?? 0));
        }
    }
}

if (!function_exists('peakrackKycCleanupRetention')) {
    function peakrackKycCleanupRetention(array $settings): array
    {
        $deleted = ['logs' => 0, 'api_attempts' => 0, 'documents' => 0];
        $days = (int) $settings['retentionDays'];
        if ($days > 0) {
            $cutoff = date('Y-m-d H:i:s', time() - ($days * 86400));
            $deleted['logs'] += (int) Capsule::table(PRKYC_LOGS_TABLE)->where('created_at', '<', $cutoff)->delete();
            $deleted['api_attempts'] += (int) Capsule::table(PRKYC_API_ATTEMPTS_TABLE)->where('created_at', '<', $cutoff)->delete();

            $oldDocuments = Capsule::table(PRKYC_DOCUMENTS_TABLE)
                ->whereNotNull('deleted_at')
                ->where('deleted_at', '<', $cutoff)
                ->get();
            foreach ($oldDocuments as $document) {
                if (is_file((string) ($document->storage_path ?? ''))) {
                    @unlink((string) $document->storage_path);
                }
                $deleted['documents'] += (int) Capsule::table(PRKYC_DOCUMENTS_TABLE)->where('id', (int) $document->id)->delete();
            }
        }

        $maxLogs = (int) $settings['maxLogs'];
        if ($maxLogs > 0) {
            $overflow = max(0, (int) Capsule::table(PRKYC_LOGS_TABLE)->count() - $maxLogs);
            if ($overflow > 0) {
                $ids = Capsule::table(PRKYC_LOGS_TABLE)
                    ->orderBy('id', 'asc')
                    ->limit($overflow)
                    ->pluck('id')
                    ->all();
                if (!empty($ids)) {
                    $deleted['logs'] += (int) Capsule::table(PRKYC_LOGS_TABLE)->whereIn('id', $ids)->delete();
                }
            }
        }

        return $deleted;
    }
}

if (!function_exists('peakrackKycDeleteDocument')) {
    function peakrackKycDeleteDocument(int $documentId, int $adminId = 0): array
    {
        $document = Capsule::table(PRKYC_DOCUMENTS_TABLE)->where('id', $documentId)->first();
        if (!$document) {
            return ['success' => false, 'message' => 'Document not found.'];
        }

        if (is_file((string) ($document->storage_path ?? ''))) {
            @unlink((string) $document->storage_path);
        }

        Capsule::table(PRKYC_DOCUMENTS_TABLE)
            ->where('id', $documentId)
            ->update([
                'status' => 'deleted',
                'deleted_at' => date('Y-m-d H:i:s'),
            ]);

        peakrackKycLog('warning', 'KYC document deleted', (int) ($document->client_id ?? 0), 0, [
            'document_id' => $documentId,
            'admin_id' => $adminId,
        ]);

        return ['success' => true, 'message' => 'Document deleted.'];
    }
}

if (!function_exists('peakrackKycDeleteClientDocument')) {
    function peakrackKycDeleteClientDocument(int $documentId, int $clientId): array
    {
        if ($documentId <= 0 || $clientId <= 0) {
            return ['success' => false, 'message' => 'Document not found.'];
        }

        $document = Capsule::table(PRKYC_DOCUMENTS_TABLE)
            ->where('id', $documentId)
            ->where('client_id', $clientId)
            ->whereNull('deleted_at')
            ->first();
        if (!$document) {
            return ['success' => false, 'message' => 'Document not found.'];
        }

        $profile = Capsule::table(PRKYC_PROFILES_TABLE)
            ->where('id', (int) ($document->profile_id ?? 0))
            ->where('client_id', $clientId)
            ->first();
        if (!$profile) {
            return ['success' => false, 'message' => 'Profile not found.'];
        }

        $profileStatus = peakrackKycNormalizeStatus((string) ($profile->status ?? 'unsubmitted'));
        if ($profileStatus === 'verified') {
            return ['success' => false, 'message' => 'Verified documents require administrator removal.'];
        }

        if (is_file((string) ($document->storage_path ?? ''))) {
            @unlink((string) $document->storage_path);
        }

        $now = date('Y-m-d H:i:s');
        Capsule::table(PRKYC_DOCUMENTS_TABLE)
            ->where('id', $documentId)
            ->update([
                'status' => 'deleted',
                'deleted_at' => $now,
            ]);

        $remaining = (int) Capsule::table(PRKYC_DOCUMENTS_TABLE)
            ->where('profile_id', (int) ($document->profile_id ?? 0))
            ->whereNull('deleted_at')
            ->count();
        if ($remaining === 0 && $profileStatus === 'pending') {
            Capsule::table(PRKYC_PROFILES_TABLE)
                ->where('id', (int) ($profile->id ?? 0))
                ->update([
                    'status' => 'unsubmitted',
                    'updated_at' => $now,
                    'last_error' => null,
                    'admin_notes' => 'Client deleted all pending uploaded documents.',
                ]);
            Capsule::table(PRKYC_SUBMISSIONS_TABLE)
                ->where('profile_id', (int) ($profile->id ?? 0))
                ->where('status', 'pending')
                ->update([
                    'status' => 'revoked',
                    'admin_notes' => 'Client deleted all pending uploaded documents.',
                    'updated_at' => $now,
                ]);
        }

        peakrackKycLog('warning', 'Client deleted KYC document', $clientId, 0, [
            'document_id' => $documentId,
            'profile_id' => (int) ($profile->id ?? 0),
        ]);

        return ['success' => true, 'message' => 'Document deleted.'];
    }
}

if (!function_exists('peakrackKycDocumentsForProfile')) {
    function peakrackKycDocumentsForProfile(int $profileId): array
    {
        if ($profileId <= 0) {
            return [];
        }

        try {
            return Capsule::table(PRKYC_DOCUMENTS_TABLE)
                ->where('profile_id', $profileId)
                ->whereNull('deleted_at')
                ->orderBy('id', 'desc')
                ->get()
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('peakrackKycRecentProfiles')) {
    function peakrackKycRecentProfiles(int $limit = 50, array $filters = []): array
    {
        try {
            $query = Capsule::table(PRKYC_PROFILES_TABLE);
            if (!empty($filters['client_id'])) {
                $query->where('client_id', (int) $filters['client_id']);
            }
            if (!empty($filters['status'])) {
                $query->where('status', (string) $filters['status']);
            }
            if (!empty($filters['type'])) {
                $query->where('type', (string) $filters['type']);
            }
            if (!empty($filters['country'])) {
                $query->where('country', strtoupper((string) $filters['country']));
            }
            if (!empty($filters['method'])) {
                $query->where('verification_method', (string) $filters['method']);
            }
            if (!empty($filters['document_type'])) {
                $query->where('document_type', (string) $filters['document_type']);
            }
            if (!empty($filters['submitted_from'])) {
                $query->where('submitted_at', '>=', (string) $filters['submitted_from'] . ' 00:00:00');
            }
            if (!empty($filters['submitted_to'])) {
                $query->where('submitted_at', '<=', (string) $filters['submitted_to'] . ' 23:59:59');
            }
            if (!empty($filters['query'])) {
                $keyword = trim((string) $filters['query']);
                $last4 = substr(preg_replace('/\s+/', '', $keyword) ?: $keyword, -4);
                $query->where(static function ($inner) use ($keyword, $last4): void {
                    $inner->where('real_name', 'like', '%' . $keyword . '%')
                        ->orWhere('company_name', 'like', '%' . $keyword . '%')
                        ->orWhere('id_number_last4', $last4)
                        ->orWhere('phone_last4', $last4)
                        ->orWhere('registration_number_last4', $last4);
                });
            }

            return $query
                ->orderByRaw("case when status = 'pending' then 0 when status = 'rejected' then 1 when status = 'revoked' then 2 else 3 end")
                ->orderBy('updated_at', 'desc')
                ->limit($limit)
                ->get()
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('peakrackKycRecentLogs')) {
    function peakrackKycRecentLogs(int $limit = 30): array
    {
        try {
            return Capsule::table(PRKYC_LOGS_TABLE)
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get()
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('peakrackKycProfileById')) {
    function peakrackKycProfileById(int $profileId): ?object
    {
        if ($profileId <= 0) {
            return null;
        }

        try {
            $profile = Capsule::table(PRKYC_PROFILES_TABLE)->where('id', $profileId)->first();
            return is_object($profile) ? $profile : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('peakrackKycSubmissionsForProfile')) {
    function peakrackKycSubmissionsForProfile(int $profileId, int $limit = 25): array
    {
        if ($profileId <= 0) {
            return [];
        }

        try {
            return Capsule::table(PRKYC_SUBMISSIONS_TABLE)
                ->where('profile_id', $profileId)
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get()
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('peakrackKycProviderLogsForClient')) {
    function peakrackKycProviderLogsForClient(int $clientId, int $limit = 25): array
    {
        if ($clientId <= 0) {
            return [];
        }

        try {
            return Capsule::table(PRKYC_PROVIDER_LOGS_TABLE)
                ->where('client_id', $clientId)
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get()
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('peakrackKycAuditLogsForClient')) {
    function peakrackKycAuditLogsForClient(int $clientId, int $limit = 25): array
    {
        if ($clientId <= 0) {
            return [];
        }

        try {
            return Capsule::table(PRKYC_LOGS_TABLE)
                ->where('client_id', $clientId)
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get()
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('peakrackKycSystemChecks')) {
    function peakrackKycSystemChecks(array $settings): array
    {
        $storagePath = peakrackKycStoragePath($settings);
        $storageCheck = peakrackKycEnsureStorage($storagePath);
        $denyFiles = [
            $storagePath . DIRECTORY_SEPARATOR . '.htaccess',
            $storagePath . DIRECTORY_SEPARATOR . 'web.config',
            $storagePath . DIRECTORY_SEPARATOR . 'index.html',
        ];
        $denyFilesOk = true;
        foreach ($denyFiles as $denyFile) {
            if (!is_file($denyFile)) {
                $denyFilesOk = false;
                break;
            }
        }

        $checks = [
            [
                'key' => 'php_version',
                'label_key' => 'check_php_version',
                'status' => version_compare(PHP_VERSION, '8.2.0', '>=') && version_compare(PHP_VERSION, '8.4.0', '<') ? 'ok' : 'warn',
                'message' => PHP_VERSION,
            ],
            [
                'key' => 'curl',
                'label_key' => 'check_curl',
                'status' => function_exists('curl_init') ? 'ok' : 'fail',
                'message' => function_exists('curl_init') ? 'cURL available' : 'cURL extension is required for API providers.',
            ],
            [
                'key' => 'openssl',
                'label_key' => 'check_openssl',
                'status' => extension_loaded('openssl') ? 'ok' : 'fail',
                'message' => extension_loaded('openssl') ? 'OpenSSL available' : 'OpenSSL is required for signed API requests.',
            ],
            [
                'key' => 'fileinfo',
                'label_key' => 'check_fileinfo',
                'status' => class_exists('finfo') ? 'ok' : 'fail',
                'message' => class_exists('finfo') ? 'Fileinfo available' : 'Fileinfo is required for MIME validation.',
            ],
            [
                'key' => 'storage',
                'label_key' => 'check_storage',
                'status' => !empty($storageCheck['success']) ? 'ok' : 'fail',
                'message' => (string) ($storageCheck['message'] ?? $storagePath),
            ],
            [
                'key' => 'storage_guards',
                'label_key' => 'check_storage_guards',
                'status' => $denyFilesOk ? 'ok' : 'warn',
                'message' => $denyFilesOk ? 'Storage deny files are present.' : 'Storage deny files should be regenerated.',
            ],
            [
                'key' => 'tencent_credentials',
                'label_key' => 'check_tencent_credentials',
                'status' => empty($settings['apiVerificationEnabled']) || ((string) ($settings['tencentSecretId'] ?? '') !== '' && (string) ($settings['tencentSecretKey'] ?? '') !== '') ? 'ok' : 'warn',
                'message' => empty($settings['apiVerificationEnabled']) ? 'API verification is disabled.' : 'Tencent credentials are required when API verification is enabled.',
            ],
            [
                'key' => 'alipay_credentials',
                'label_key' => 'check_alipay_credentials',
                'status' => empty($settings['alipayRealNameEnabled']) || ((string) ($settings['alipayAppId'] ?? '') !== '' && (string) ($settings['alipayPrivateKey'] ?? '') !== '') ? 'ok' : 'warn',
                'message' => empty($settings['alipayRealNameEnabled']) ? 'Alipay real-name verification is disabled.' : 'Alipay AppID and private key are required when Alipay verification is enabled.',
            ],
        ];

        return $checks;
    }
}

if (!function_exists('peakrackKycStreamDocument')) {
    function peakrackKycStreamDocument(int $documentId): void
    {
        if (empty($_SESSION['adminid'])) {
            header('HTTP/1.1 403 Forbidden');
            echo 'Forbidden';
            exit;
        }

        if (function_exists('check_token') && !check_token('WHMCS.admin.default')) {
            header('HTTP/1.1 403 Forbidden');
            echo 'Invalid token';
            exit;
        }

        $document = Capsule::table(PRKYC_DOCUMENTS_TABLE)
            ->where('id', $documentId)
            ->whereNull('deleted_at')
            ->first();
        if (!$document || !is_file((string) $document->storage_path)) {
            header('HTTP/1.1 404 Not Found');
            echo 'Not found';
            exit;
        }

        $downloadName = (string) ($document->original_name ?? ('document-' . $documentId));
        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize((string) $document->storage_path));
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $downloadName) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile((string) $document->storage_path);
        exit;
    }
}

if (!function_exists('peakrackKycStoragePath')) {
    function peakrackKycStoragePath(array $settings): string
    {
        $configured = trim((string) ($settings['storagePath'] ?? ''));
        if ($configured !== '') {
            return rtrim($configured, "\\/");
        }

        $root = defined('ROOTDIR') ? ROOTDIR : dirname(__DIR__, 4);
        return rtrim($root, "\\/") . DIRECTORY_SEPARATOR . 'attachments' . DIRECTORY_SEPARATOR . 'peakrack_kyc_private';
    }
}

if (!function_exists('peakrackKycEnsureStorage')) {
    function peakrackKycEnsureStorage(string $path): array
    {
        if (!is_dir($path) && !@mkdir($path, 0750, true) && !is_dir($path)) {
            return ['success' => false, 'message' => 'Unable to create private storage directory.'];
        }

        @file_put_contents($path . DIRECTORY_SEPARATOR . '.htaccess', "Require all denied\nDeny from all\n");
        @file_put_contents($path . DIRECTORY_SEPARATOR . 'web.config', "<?xml version=\"1.0\" encoding=\"UTF-8\"?><configuration><system.webServer><authorization><deny users=\"*\" /></authorization></system.webServer></configuration>");
        @file_put_contents($path . DIRECTORY_SEPARATOR . 'index.html', '');

        if (!is_writable($path)) {
            return ['success' => false, 'message' => 'Private storage directory is not writable.'];
        }

        return ['success' => true, 'message' => 'OK'];
    }
}

if (!function_exists('peakrackKycCartProductIds')) {
    function peakrackKycCartProductIds(array $vars = []): array
    {
        $ids = [];
        $sources = [];
        if (isset($vars['products']) && is_array($vars['products'])) {
            $sources[] = $vars['products'];
        }
        if (isset($_SESSION['cart']['products']) && is_array($_SESSION['cart']['products'])) {
            $sources[] = $_SESSION['cart']['products'];
        }

        foreach ($sources as $products) {
            foreach ($products as $product) {
                if (is_array($product)) {
                    $id = (int) ($product['pid'] ?? ($product['productid'] ?? 0));
                    if ($id > 0) {
                        $ids[] = $id;
                    }
                }
            }
        }

        return array_values(array_unique($ids));
    }
}

if (!function_exists('peakrackKycProductGroupIds')) {
    function peakrackKycProductGroupIds(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if (empty($productIds)) {
            return [];
        }

        try {
            return Capsule::table('tblproducts')
                ->whereIn('id', $productIds)
                ->pluck('gid')
                ->map(static fn($value): int => (int) $value)
                ->filter(static fn(int $value): bool => $value > 0)
                ->unique()
                ->values()
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('peakrackKycCartTlds')) {
    function peakrackKycCartTlds(array $vars = []): array
    {
        $domains = [];
        if (isset($vars['domains']) && is_array($vars['domains'])) {
            $domains = array_merge($domains, $vars['domains']);
        }
        if (isset($_SESSION['cart']['domains']) && is_array($_SESSION['cart']['domains'])) {
            $domains = array_merge($domains, $_SESSION['cart']['domains']);
        }

        $tlds = [];
        foreach ($domains as $domain) {
            if (is_array($domain)) {
                $domainName = (string) ($domain['domain'] ?? (($domain['sld'] ?? '') . '.' . ($domain['tld'] ?? '')));
                $candidates = peakrackKycTldCandidates(isset($domain['tld']) ? (string) $domain['tld'] : $domainName, isset($domain['tld']));
            } else {
                $candidates = peakrackKycTldCandidates((string) $domain);
            }

            foreach ($candidates as $tld) {
                $tlds[] = $tld;
            }
        }

        return array_values(array_unique($tlds));
    }
}

if (!function_exists('peakrackKycTldCandidates')) {
    function peakrackKycTldCandidates(string $domain, bool $includeWhole = false): array
    {
        $domain = strtolower(trim($domain));
        $domain = ltrim($domain, '.');
        if ($domain === '') {
            return [];
        }

        $parts = array_values(array_filter(explode('.', $domain), static fn($part): bool => $part !== ''));
        if (empty($parts)) {
            return [];
        }

        $candidates = [];
        if ($includeWhole) {
            $candidates[] = implode('.', $parts);
        }

        if (count($parts) === 1) {
            $candidates[] = $parts[0];
        } else {
            for ($i = 1; $i < count($parts); $i++) {
                $candidates[] = implode('.', array_slice($parts, $i));
            }
        }

        return array_values(array_unique(array_filter($candidates, static function (string $candidate): bool {
            return (bool) preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)*$/', $candidate);
        })));
    }
}

if (!function_exists('peakrackKycExtractTld')) {
    function peakrackKycExtractTld(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = ltrim($domain, '.');
        if ($domain === '') {
            return '';
        }

        $parts = explode('.', $domain);
        return count($parts) > 1 ? (string) end($parts) : $domain;
    }
}

if (!function_exists('peakrackKycCartLooksNonEmpty')) {
    function peakrackKycCartLooksNonEmpty(): bool
    {
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            return false;
        }

        foreach (['products', 'addons', 'domains'] as $key) {
            if (!empty($_SESSION['cart'][$key]) && is_array($_SESSION['cart'][$key])) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('peakrackKycOrderClientId')) {
    function peakrackKycOrderClientId(int $orderId): int
    {
        if ($orderId <= 0) {
            return 0;
        }

        try {
            $order = Capsule::table('tblorders')->where('id', $orderId)->first();
            return $order ? (int) ($order->userid ?? 0) : 0;
        } catch (Throwable $e) {
            return 0;
        }
    }
}

if (!function_exists('peakrackKycServiceProductId')) {
    function peakrackKycServiceProductId(int $serviceId): int
    {
        if ($serviceId <= 0) {
            return 0;
        }

        try {
            $service = Capsule::table('tblhosting')->where('id', $serviceId)->first();
            return $service ? (int) ($service->packageid ?? 0) : 0;
        } catch (Throwable $e) {
            return 0;
        }
    }
}

if (!function_exists('peakrackKycClientLanguage')) {
    function peakrackKycClientLanguage(int $clientId = 0, array $vars = []): string
    {
        $language = strtolower((string) ($vars['language'] ?? ($_SESSION['Language'] ?? '')));
        if ($language === '' && $clientId > 0) {
            try {
                $client = Capsule::table('tblclients')->where('id', $clientId)->first();
                $language = strtolower((string) ($client->language ?? ''));
            } catch (Throwable $e) {
                $language = '';
            }
        }

        return str_contains($language, 'chinese') || str_contains($language, 'zh') ? 'zh' : 'en';
    }
}

if (!function_exists('peakrackKycText')) {
    function peakrackKycText(string $language, string $key): string
    {
        $texts = [
            'en' => [
                'saved' => 'Settings saved.',
                'token_failed' => 'Security token validation failed. Refresh the page and try again.',
                'submitted' => 'Your verification documents were submitted for manual review.',
                'api_verified' => 'Identity verification passed.',
                'api_failed' => 'Identity verification failed:',
                'upload_required' => 'Please upload at least one document.',
                'document_deleted' => 'The document was deleted.',
            ],
            'zh' => [
                'saved' => '设置已保存。',
                'token_failed' => '安全令牌验证失败，请刷新页面后重试。',
                'submitted' => '实名资料已提交，等待人工审核。',
                'api_verified' => '实名认证已通过。',
                'api_failed' => '实名认证失败：',
                'upload_required' => '请至少上传一个证件或证明文件。',
                'document_deleted' => '文件已删除。',
            ],
        ];

        return $texts[$language][$key] ?? $texts['en'][$key] ?? $key;
    }
}

if (!function_exists('peakrackKycJsonEncode')) {
    function peakrackKycJsonEncode(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($json) ? $json : '{}';
    }
}

if (!function_exists('peakrackKycJsonDecode')) {
    function peakrackKycJsonDecode(?string $json, array $fallback = []): array
    {
        if (!is_string($json) || trim($json) === '') {
            return $fallback;
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : $fallback;
    }
}

if (!function_exists('peakrackKycBool')) {
    function peakrackKycBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}

if (!function_exists('peakrackKycClampInt')) {
    function peakrackKycClampInt($value, int $min, int $max, int $fallback): int
    {
        if (!is_numeric($value)) {
            return $fallback;
        }

        return max($min, min($max, (int) $value));
    }
}

if (!function_exists('peakrackKycNormalizeIntList')) {
    function peakrackKycNormalizeIntList($value): array
    {
        $items = is_array($value) ? $value : preg_split('/[\s,;]+/', (string) $value);
        $result = [];
        foreach ($items ?: [] as $item) {
            $int = (int) trim((string) $item);
            if ($int > 0) {
                $result[] = $int;
            }
        }

        return array_values(array_unique($result));
    }
}

if (!function_exists('peakrackKycNormalizeList')) {
    function peakrackKycNormalizeList($value): array
    {
        $items = is_array($value) ? $value : preg_split('/[\s,;]+/', (string) $value);
        $result = [];
        foreach ($items ?: [] as $item) {
            $item = trim((string) $item);
            if ($item !== '') {
                $result[] = $item;
            }
        }

        return array_values(array_unique($result));
    }
}

if (!function_exists('peakrackKycNormalizeExtensionList')) {
    function peakrackKycNormalizeExtensionList($value): array
    {
        $items = peakrackKycNormalizeList($value);
        $result = [];
        foreach ($items as $item) {
            $extension = strtolower(ltrim($item, '.'));
            if (preg_match('/^[a-z0-9]{2,8}$/', $extension)) {
                $result[] = $extension;
            }
        }

        return $result ?: ['jpg', 'jpeg', 'png', 'pdf'];
    }
}

if (!function_exists('peakrackKycNormalizeTldList')) {
    function peakrackKycNormalizeTldList($value): array
    {
        $items = peakrackKycNormalizeList($value);
        $result = [];
        foreach ($items as $item) {
            $tld = strtolower(ltrim(trim($item), '.'));
            if (preg_match('/^[a-z0-9-]{2,63}(\.[a-z0-9-]{2,63})*$/', $tld)) {
                $result[] = $tld;
            }
        }

        return array_values(array_unique($result));
    }
}

if (!function_exists('peakrackKycNormalizeProfileType')) {
    function peakrackKycNormalizeProfileType($value): string
    {
        $value = (string) $value;
        return in_array($value, ['individual', 'corporate', 'overseas', 'address'], true) ? $value : 'individual';
    }
}

if (!function_exists('peakrackKycNormalizeStatus')) {
    function peakrackKycNormalizeStatus($value): string
    {
        $value = (string) $value;
        if ($value === 'unverified' || $value === 'failed') {
            $value = $value === 'failed' ? 'rejected' : 'unsubmitted';
        }

        return in_array($value, ['unsubmitted', 'pending', 'verified', 'rejected', 'expired', 'revoked'], true) ? $value : 'pending';
    }
}

if (!function_exists('peakrackKycCountry')) {
    function peakrackKycCountry($country): ?string
    {
        $country = strtoupper(trim((string) $country));
        return preg_match('/^[A-Z]{2}$/', $country) ? $country : null;
    }
}

if (!function_exists('peakrackKycNullableString')) {
    function peakrackKycNullableString($value, int $max): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : mb_substr($value, 0, $max);
    }
}

if (!function_exists('peakrackKycNullableText')) {
    function peakrackKycNullableText($value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}

if (!function_exists('peakrackKycHashPepper')) {
    function peakrackKycHashPepper(): string
    {
        global $cc_encryption_hash;
        $hash = is_string($cc_encryption_hash ?? null) ? $cc_encryption_hash : '';
        return $hash !== '' ? $hash : PRKYC_MODULE;
    }
}

if (!function_exists('peakrackKycSensitiveHash')) {
    function peakrackKycSensitiveHash($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return hash('sha256', peakrackKycHashPepper() . '|' . $value);
    }
}

if (!function_exists('peakrackKycLast4')) {
    function peakrackKycLast4($value): ?string
    {
        $clean = preg_replace('/\s+/', '', (string) $value);
        if (!is_string($clean) || $clean === '') {
            return null;
        }

        return substr($clean, -4);
    }
}

if (!function_exists('peakrackKycProfilePublicData')) {
    function peakrackKycProfilePublicData(array $data): array
    {
        return [
            'type' => peakrackKycNormalizeProfileType($data['type'] ?? 'individual'),
            'document_type' => (string) ($data['document_type'] ?? ''),
            'country' => (string) ($data['country'] ?? ''),
            'notes' => mb_substr(trim((string) ($data['notes'] ?? '')), 0, 2000),
            'id_number_last4' => peakrackKycLast4($data['id_number'] ?? ''),
            'phone_last4' => peakrackKycLast4($data['phone'] ?? ''),
            'registration_number_last4' => peakrackKycLast4($data['registration_number'] ?? ''),
        ];
    }
}

if (!function_exists('peakrackKycSanitizeFilename')) {
    function peakrackKycSanitizeFilename(string $name): string
    {
        $base = basename($name);
        $base = preg_replace('/[^\pL\pN._ -]+/u', '_', $base);
        $base = trim((string) $base, " .\t\n\r\0\x0B");
        return $base !== '' ? mb_substr($base, 0, 180) : 'document';
    }
}

if (!function_exists('peakrackKycMimeType')) {
    function peakrackKycMimeType(string $path): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            }
        }

        return 'application/octet-stream';
    }
}

if (!function_exists('peakrackKycValidateUploadFile')) {
    function peakrackKycValidateUploadFile(string $path, string $original, string $extension, int $size, int $maxBytes, array $allowedExtensions): array
    {
        $extension = strtolower(ltrim($extension, '.'));
        if (!in_array($extension, $allowedExtensions, true)) {
            return ['success' => false, 'message' => $original . ' uses a file type that is not allowed.'];
        }

        if ($size <= 0 || $size > $maxBytes) {
            return ['success' => false, 'message' => $original . ' exceeds the configured upload size limit.'];
        }

        if ($path === '' || !is_file($path)) {
            return ['success' => false, 'message' => $original . ' was not accepted as a valid upload.'];
        }

        $mime = peakrackKycMimeType($path);
        if (!peakrackKycAllowedMimeForExtension($mime, $extension)) {
            return ['success' => false, 'message' => $original . ' failed MIME validation.'];
        }

        if (!peakrackKycFileSignatureMatches($path, $extension)) {
            return ['success' => false, 'message' => $original . ' failed file signature validation.'];
        }

        return ['success' => true, 'mime' => $mime];
    }
}

if (!function_exists('peakrackKycAllowedMimeForExtension')) {
    function peakrackKycAllowedMimeForExtension(string $mime, string $extension): bool
    {
        $mime = strtolower(trim($mime));
        $extension = strtolower(ltrim($extension, '.'));
        $allowed = [
            'jpg' => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png' => ['image/png', 'image/x-png'],
            'pdf' => ['application/pdf', 'application/x-pdf'],
        ];

        return isset($allowed[$extension]) && in_array($mime, $allowed[$extension], true);
    }
}

if (!function_exists('peakrackKycFileSignatureMatches')) {
    function peakrackKycFileSignatureMatches(string $path, string $extension): bool
    {
        $extension = strtolower(ltrim($extension, '.'));
        $handle = @fopen($path, 'rb');
        if (!$handle) {
            return false;
        }

        $header = (string) fread($handle, 16);
        fclose($handle);

        if ($extension === 'pdf') {
            return strncmp($header, '%PDF-', 5) === 0;
        }

        if ($extension === 'png') {
            if (substr($header, 0, 8) !== "\x89PNG\r\n\x1A\n") {
                return false;
            }

            if (!function_exists('getimagesize')) {
                return false;
            }

            $image = @getimagesize($path);
            return is_array($image) && ($image['mime'] ?? '') === 'image/png';
        }

        if ($extension === 'jpg' || $extension === 'jpeg') {
            if (substr($header, 0, 2) !== "\xFF\xD8") {
                return false;
            }

            if (!function_exists('getimagesize')) {
                return false;
            }

            $image = @getimagesize($path);
            return is_array($image) && ($image['mime'] ?? '') === 'image/jpeg';
        }

        return false;
    }
}

if (!function_exists('peakrackKycFlattenScalarValues')) {
    function peakrackKycFlattenScalarValues(array $data): array
    {
        $values = [];
        foreach ($data as $value) {
            if (is_array($value)) {
                $values = array_merge($values, peakrackKycFlattenScalarValues($value));
            } elseif (is_scalar($value)) {
                $values[] = (string) $value;
            }
        }

        return $values;
    }
}

if (!function_exists('peakrackKycRedactApiResponse')) {
    function peakrackKycRedactApiResponse(array $data): array
    {
        $redacted = [];
        foreach ($data as $key => $value) {
            $normalized = strtolower((string) $key);
            if (preg_match('/(idcard|id_card|phone|mobile|name|cert|cardno|bank|token|auth|sign|key|secret)/', $normalized)) {
                $redacted[$key] = '[REDACTED]';
                continue;
            }

            $redacted[$key] = is_array($value) ? peakrackKycRedactApiResponse($value) : $value;
        }

        return $redacted;
    }
}

if (!function_exists('peakrackKycE')) {
    function peakrackKycE($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
