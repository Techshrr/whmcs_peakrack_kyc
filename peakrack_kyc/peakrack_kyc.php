<?php

/**
 * PeakRack KYC addon module for WHMCS.
 *
 * Target runtime: WHMCS 9.0.3 / PHP 8.2-8.3.
 */

use WHMCS\Database\Capsule;

if (!defined('WHMCS')) {
    die('No direct access');
}

require_once __DIR__ . '/lib/Bootstrap.php';

function peakrack_kyc_config(): array
{
    return [
        'name' => 'PeakRack KYC',
        'description' => 'Identity verification, document upload, and product-level KYC enforcement for WHMCS.',
        'version' => PRKYC_VERSION,
        'author' => 'PeakRack',
        'language' => 'english',
        'fields' => [],
    ];
}

function peakrack_kyc_activate(): array
{
    try {
        peakrackKycCreateTables();
        $settings = peakrackKycLoadSettings();
        peakrackKycSaveSettings($settings);
        peakrackKycEnsureStorage(peakrackKycStoragePath($settings));

        return [
            'status' => 'success',
            'description' => 'PeakRack KYC has been activated.',
        ];
    } catch (Throwable $e) {
        return [
            'status' => 'error',
            'description' => 'Activation failed: ' . $e->getMessage(),
        ];
    }
}

function peakrack_kyc_deactivate(): array
{
    return [
        'status' => 'success',
        'description' => 'PeakRack KYC has been deactivated. Verification data and audit logs were kept.',
    ];
}

function peakrack_kyc_upgrade($vars): void
{
    peakrackKycCreateTables();
}

function peakrack_kyc_output(array $vars): void
{
    if (($_GET['prkyc_action'] ?? '') === 'download') {
        peakrackKycCreateTables();
        peakrackKycStreamDocument((int) ($_GET['docid'] ?? 0));
    }

    $message = '';
    $messageType = 'success';

    try {
        peakrackKycCreateTables();
        $settings = peakrackKycLoadSettings();
    } catch (Throwable $e) {
        $settings = peakrackKycDefaults();
        $message = 'Schema check failed: ' . $e->getMessage();
        $messageType = 'danger';
    }

    if (in_array((string) ($_GET['prkyc_admin_lang'] ?? ''), ['en', 'zh'], true)) {
        $settings['adminLanguage'] = (string) $_GET['prkyc_admin_lang'];
    }
    $language = peakrack_kyc_admin_language($settings);

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $language = in_array((string) ($_POST['adminLanguage'] ?? $language), ['en', 'zh'], true)
            ? (string) ($_POST['adminLanguage'] ?? $language)
            : $language;

        if (!peakrack_kyc_verify_admin_token()) {
            $message = peakrackKycText($language, 'token_failed');
            $messageType = 'danger';
        } else {
            $action = (string) ($_POST['prkyc_action'] ?? '');
            if ($action === 'save_settings') {
                $settings = peakrack_kyc_settings_from_post($settings);
                peakrackKycSaveSettings($settings);
                peakrackKycEnsureStorage(peakrackKycStoragePath($settings));
                $settings = peakrackKycLoadSettings();
                $language = peakrack_kyc_admin_language($settings);
                $message = peakrackKycText($language, 'saved');
            } elseif ($action === 'save_rule') {
                $result = peakrackKycSaveRule(
                    (int) ($_POST['rule_id'] ?? 0),
                    (string) ($_POST['scope_type'] ?? ''),
                    (string) ($_POST['scope_value'] ?? ''),
                    (string) ($_POST['requirement'] ?? 'verified'),
                    peakrackKycBool($_POST['enabled'] ?? false),
                    (string) ($_POST['notes'] ?? ''),
                    (int) ($_SESSION['adminid'] ?? 0)
                );
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
            } elseif ($action === 'delete_rule') {
                $result = peakrackKycDeleteRule((int) ($_POST['rule_id'] ?? 0), (int) ($_SESSION['adminid'] ?? 0));
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
            } elseif ($action === 'install_email_templates') {
                $result = peakrackKycEnsureEmailTemplates($settings);
                if (!empty($result['success']) && is_array($result['settings'] ?? null)) {
                    $settings = $result['settings'];
                    peakrackKycSaveSettings($settings);
                    $settings = peakrackKycLoadSettings();
                }
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
            } elseif ($action === 'review_profile') {
                $result = peakrackKycReviewProfile(
                    (int) ($_POST['profile_id'] ?? 0),
                    (string) ($_POST['decision'] ?? ''),
                    (string) ($_POST['reason'] ?? ''),
                    (int) ($_SESSION['adminid'] ?? 0),
                    $settings
                );
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
            } elseif ($action === 'delete_document') {
                $result = peakrackKycDeleteDocument((int) ($_POST['document_id'] ?? 0), (int) ($_SESSION['adminid'] ?? 0));
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
            }
        }
    }

    echo peakrack_kyc_render_admin($settings, $message, $messageType, $language);
}

function peakrack_kyc_clientarea(array $vars): array
{
    peakrackKycCreateTables();
    $settings = peakrackKycLoadSettings();
    $clientId = (int) ($_SESSION['uid'] ?? 0);
    $language = peakrackKycClientLanguage($clientId, $vars);
    $message = '';
    $messageType = 'info';

    if ($clientId > 0 && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (!peakrack_kyc_verify_client_token()) {
            $message = peakrackKycText($language, 'token_failed');
            $messageType = 'danger';
        } else {
            $action = (string) ($_POST['prkyc_client_action'] ?? '');
            if ($action === 'api_three_factor') {
                $result = peakrack_kyc_handle_api_submission($clientId, $settings);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
            } elseif ($action === 'manual_submit') {
                $result = peakrack_kyc_handle_manual_submission($clientId, $settings);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
            } elseif ($action === 'delete_document') {
                $result = peakrackKycDeleteClientDocument((int) ($_POST['document_id'] ?? 0), $clientId);
                $message = !empty($result['success']) ? peakrackKycText($language, 'document_deleted') : (string) ($result['message'] ?? '');
                $messageType = $result['success'] ? 'success' : 'danger';
            }
        }
    }

    $profile = peakrackKycGetProfileArray($clientId);
    $documents = peakrackKycDocumentsForProfile((int) $profile['id']);
    $text = peakrack_kyc_client_texts($language);

    return [
        'pagetitle' => $language === 'zh' ? '实名认证中心' : 'Identity Verification',
        'breadcrumb' => [
            'index.php?m=peakrack_kyc' => $language === 'zh' ? '实名认证中心' : 'Identity Verification',
        ],
        'templatefile' => 'templates/clientarea',
        'requirelogin' => true,
        'forcessl' => true,
        'vars' => [
            'prkyc' => [
                'modulelink' => $vars['modulelink'] ?? 'index.php?m=peakrack_kyc',
                'settings' => [
                    'enabled' => $settings['enabled'],
                    'manualReviewEnabled' => $settings['manualReviewEnabled'],
                    'apiVerificationEnabled' => $settings['apiVerificationEnabled'],
                    'allowedExtensions' => implode(', ', $settings['allowedExtensions']),
                    'maxUploadMb' => (int) $settings['maxUploadMb'],
                    'notice' => $settings['clientNotice'][$language],
                ],
                'profile' => $profile,
                'canDeleteDocuments' => (string) ($profile['status'] ?? '') !== 'verified',
                'documents' => array_map('peakrack_kyc_document_view', $documents),
                'message' => $message,
                'messageType' => $messageType,
                'token' => peakrack_kyc_client_token_value(),
                'text' => $text,
            ],
        ],
    ];
}

function peakrack_kyc_handle_api_submission(int $clientId, array $settings): array
{
    $realName = trim((string) ($_POST['real_name'] ?? ''));
    $idNumber = trim((string) ($_POST['id_number'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $language = peakrackKycClientLanguage($clientId);

    if ($realName === '' || $idNumber === '' || $phone === '') {
        return ['success' => false, 'message' => $language === 'zh' ? '请填写姓名、身份证号码和手机号。' : 'Name, ID number, and phone number are required.'];
    }

    $result = peakrackKycApiVerifyThreeFactor($clientId, $realName, $idNumber, $phone, $settings);
    if ($result['success']) {
        $profileId = peakrackKycUpsertProfile($clientId, [
            'type' => 'individual',
            'status' => 'verified',
            'verification_method' => 'api_three_factor',
            'real_name' => $realName,
            'id_number' => $idNumber,
            'phone' => $phone,
            'document_type' => 'cn_id_card',
            'country' => 'CN',
            'notes' => 'API request id: ' . (string) ($result['request_id'] ?? ''),
        ]);
        peakrackKycCreateSubmission($clientId, $profileId, 'individual', (string) $settings['apiProvider'], 'verified', [
            'real_name' => $realName,
            'id_number' => $idNumber,
            'phone' => $phone,
        ], $result);
        peakrackKycLog('info', 'Client passed API three-factor KYC', $clientId, 0, ['code' => (string) ($result['code'] ?? '')]);
        peakrackKycSendClientNotification($clientId, 'verified', ['profile_id' => $profileId], $settings);
        return ['success' => true, 'message' => peakrackKycText($language, 'api_verified')];
    }

    $profileId = peakrackKycUpsertProfile($clientId, [
        'type' => 'individual',
        'status' => 'rejected',
        'verification_method' => 'api_three_factor',
        'real_name' => $realName,
        'id_number' => $idNumber,
        'phone' => $phone,
        'document_type' => 'cn_id_card',
        'country' => 'CN',
        'last_error' => (string) ($result['message'] ?? 'Verification failed.'),
        'rejection_reason' => (string) ($result['message'] ?? 'Verification failed.'),
    ]);
    peakrackKycCreateSubmission($clientId, $profileId, 'individual', (string) $settings['apiProvider'], 'rejected', [
        'real_name' => $realName,
        'id_number' => $idNumber,
        'phone' => $phone,
    ], $result);
    peakrackKycLog('warning', 'Client failed API three-factor KYC', $clientId, 0, ['code' => (string) ($result['code'] ?? '')]);

    $code = (string) ($result['code'] ?? '');
    $clientMessage = in_array($code, ['transport_error', 'missing_credentials'], true)
        ? ($language === 'zh' ? '实名服务暂时不可用，请稍后重试或提交人工审核。' : 'The verification service is temporarily unavailable. Please try again later or submit documents for manual review.')
        : (string) ($result['message'] ?? '');

    return ['success' => false, 'message' => peakrackKycText($language, 'api_failed') . ' ' . $clientMessage];
}

function peakrack_kyc_handle_manual_submission(int $clientId, array $settings): array
{
    $language = peakrackKycClientLanguage($clientId);
    if (!$settings['manualReviewEnabled']) {
        return ['success' => false, 'message' => $language === 'zh' ? '人工审核通道暂未启用。' : 'Manual review is not enabled.'];
    }

    if (!peakrack_kyc_has_uploads()) {
        return ['success' => false, 'message' => peakrackKycText($language, 'upload_required')];
    }

    $type = (string) ($_POST['profile_type'] ?? 'individual');
    $documentType = (string) ($_POST['document_type'] ?? '');
    $provider = peakrackKycProvider('manual_review');
    $providerResult = $provider->verify(['client_id' => $clientId], $settings);
    if (!$providerResult['success']) {
        return ['success' => false, 'message' => (string) ($providerResult['message'] ?? 'Manual review is unavailable.')];
    }

    $profileId = peakrackKycUpsertProfile($clientId, [
        'type' => $type,
        'status' => 'pending',
        'verification_method' => 'manual',
        'real_name' => $_POST['real_name'] ?? '',
        'company_name' => $_POST['company_name'] ?? '',
        'id_number' => $_POST['id_number'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'registration_number' => $_POST['registration_number'] ?? '',
        'document_type' => $documentType,
        'country' => $_POST['country'] ?? '',
        'notes' => $_POST['notes'] ?? '',
    ]);
    $submissionId = peakrackKycCreateSubmission($clientId, $profileId, $type, $provider->getName(), 'pending', [
        'type' => $type,
        'document_type' => $documentType,
        'country' => $_POST['country'] ?? '',
    ], $providerResult);

    $upload = peakrackKycStoreUploads($clientId, $profileId, $documentType !== '' ? $documentType : $type, $settings, $submissionId);
    if (empty($upload['stored'])) {
        peakrackKycLog('warning', 'Manual KYC submission had no accepted files', $clientId, 0, ['errors' => $upload['errors']]);
        return ['success' => false, 'message' => implode(' ', $upload['errors'])];
    }

    peakrackKycLog('info', 'Client submitted manual KYC documents', $clientId, 0, ['profile_id' => $profileId, 'files' => count($upload['stored'])]);
    peakrackKycSendClientNotification($clientId, 'submitted', ['profile_id' => $profileId], $settings);
    peakrackKycSendAdminNotification('new_submission', [
        'client_id' => $clientId,
        'profile_id' => $profileId,
        'submission_id' => $submissionId,
    ], $settings);
    $message = peakrackKycText($language, 'submitted');
    if (!empty($upload['errors'])) {
        $message .= ' ' . implode(' ', $upload['errors']);
    }

    return ['success' => true, 'message' => $message];
}

function peakrack_kyc_has_uploads(): bool
{
    if (empty($_FILES['documents']) || !is_array($_FILES['documents']['error'] ?? null)) {
        return false;
    }

    foreach ($_FILES['documents']['error'] as $error) {
        if ((int) $error !== UPLOAD_ERR_NO_FILE) {
            return true;
        }
    }

    return false;
}

function peakrack_kyc_settings_from_post(array $current): array
{
    $settings = $current;
    $settings['enabled'] = peakrackKycBool($_POST['enabled'] ?? false);
    $settings['activityLog'] = peakrackKycBool($_POST['activityLog'] ?? false);
    $settings['manualReviewEnabled'] = peakrackKycBool($_POST['manualReviewEnabled'] ?? false);
    $settings['apiVerificationEnabled'] = peakrackKycBool($_POST['apiVerificationEnabled'] ?? false);
    $settings['checkoutBlockEnabled'] = peakrackKycBool($_POST['checkoutBlockEnabled'] ?? false);
    $settings['provisioningBlockEnabled'] = peakrackKycBool($_POST['provisioningBlockEnabled'] ?? false);
    $settings['postOrderHoldEnabled'] = peakrackKycBool($_POST['postOrderHoldEnabled'] ?? false);
    $settings['adminLanguage'] = in_array((string) ($current['adminLanguage'] ?? 'en'), ['en', 'zh'], true) ? (string) $current['adminLanguage'] : 'en';
    $settings['apiProvider'] = 'tencent_phone_three_factor';
    $settings['tencentSecretId'] = trim((string) ($_POST['tencentSecretId'] ?? ''));
    $postedTencentSecretKey = trim((string) ($_POST['tencentSecretKey'] ?? ''));
    $settings['tencentSecretKey'] = $postedTencentSecretKey !== '' ? $postedTencentSecretKey : (string) ($current['tencentSecretKey'] ?? '');
    $settings['tencentRegion'] = trim((string) ($_POST['tencentRegion'] ?? ''));
    $settings['tencentEndpoint'] = trim((string) ($_POST['tencentEndpoint'] ?? ''));
    $settings['tencentVerifyMode'] = trim((string) ($_POST['tencentVerifyMode'] ?? 'standard'));
    $settings['apiTestMode'] = peakrackKycBool($_POST['apiTestMode'] ?? false);
    $settings['apiTimeout'] = (int) ($_POST['apiTimeout'] ?? 15);
    $settings['enforcementMode'] = in_array((string) ($_POST['enforcementMode'] ?? ''), ['none', 'all', 'selected'], true) ? (string) $_POST['enforcementMode'] : 'selected';
    $settings['checkoutMode'] = in_array((string) ($_POST['checkoutMode'] ?? ''), ['block', 'allow_pending'], true) ? (string) $_POST['checkoutMode'] : 'block';
    $settings['enforcedProductIds'] = array_key_exists('enforcedProductIds', $_POST)
        ? peakrackKycNormalizeIntList($_POST['enforcedProductIds'])
        : peakrackKycNormalizeIntList($current['enforcedProductIds'] ?? []);
    $settings['enforcedProductGroupIds'] = array_key_exists('enforcedProductGroupIds', $_POST)
        ? peakrackKycNormalizeIntList($_POST['enforcedProductGroupIds'])
        : peakrackKycNormalizeIntList($current['enforcedProductGroupIds'] ?? []);
    $settings['enforcedTlds'] = array_key_exists('enforcedTlds', $_POST)
        ? peakrackKycNormalizeTldList($_POST['enforcedTlds'])
        : peakrackKycNormalizeTldList($current['enforcedTlds'] ?? []);
    $settings['rejectedOrderAction'] = in_array((string) ($_POST['rejectedOrderAction'] ?? ''), ['manual', 'cancel_unpaid'], true) ? (string) $_POST['rejectedOrderAction'] : 'manual';
    $settings['emailNotifications'] = peakrackKycBool($_POST['emailNotifications'] ?? false);
    $settings['adminEmailNotifications'] = peakrackKycBool($_POST['adminEmailNotifications'] ?? false);
    $settings['emailTemplateSubmitted'] = trim((string) ($_POST['emailTemplateSubmitted'] ?? ''));
    $settings['emailTemplateApproved'] = trim((string) ($_POST['emailTemplateApproved'] ?? ''));
    $settings['emailTemplateRejected'] = trim((string) ($_POST['emailTemplateRejected'] ?? ''));
    $settings['maxUploadMb'] = (int) ($_POST['maxUploadMb'] ?? 8);
    $settings['allowedExtensions'] = peakrackKycNormalizeExtensionList($_POST['allowedExtensions'] ?? '');
    $settings['storagePath'] = trim((string) ($_POST['storagePath'] ?? ''));
    $settings['retentionDays'] = (int) ($_POST['retentionDays'] ?? 1095);
    $settings['maxLogs'] = (int) ($_POST['maxLogs'] ?? 20000);
    $settings['clientNotice']['en'] = trim((string) ($_POST['clientNoticeEn'] ?? $current['clientNotice']['en']));
    $settings['clientNotice']['zh'] = trim((string) ($_POST['clientNoticeZh'] ?? $current['clientNotice']['zh']));

    return peakrackKycMergeSettings(peakrackKycDefaults(), $settings);
}

function peakrack_kyc_render_admin(array $settings, string $message, string $messageType, string $language): string
{
    $t = peakrack_kyc_admin_texts($language);
    $filters = [
        'client_id' => (int) ($_GET['filter_client_id'] ?? 0),
        'status' => (string) ($_GET['filter_status'] ?? ''),
        'type' => (string) ($_GET['filter_type'] ?? ''),
        'country' => strtoupper((string) ($_GET['filter_country'] ?? '')),
        'method' => (string) ($_GET['filter_method'] ?? ''),
        'document_type' => (string) ($_GET['filter_document_type'] ?? ''),
        'submitted_from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['filter_submitted_from'] ?? '')) ? (string) $_GET['filter_submitted_from'] : '',
        'submitted_to' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['filter_submitted_to'] ?? '')) ? (string) $_GET['filter_submitted_to'] : '',
        'query' => (string) ($_GET['filter_query'] ?? ''),
    ];
    $profiles = peakrackKycRecentProfiles(80, $filters);
    $rules = peakrackKycListRules(true);
    $logs = peakrackKycRecentLogs(30);
    $storage = peakrackKycStoragePath($settings);
    $storageCheck = peakrackKycEnsureStorage($storage);

    ob_start();
    ?>
    <style>
        .prkyc-wrap { max-width: 1280px; }
        .prkyc-header { display: flex; justify-content: space-between; gap: 16px; align-items: center; margin: 10px 0 18px; }
        .prkyc-card { border: 1px solid #d9dee3; border-radius: 6px; background: #fff; margin-bottom: 18px; }
        .prkyc-card h2 { font-size: 18px; margin: 0; padding: 14px 16px; border-bottom: 1px solid #e7eaee; }
        .prkyc-card .inner { padding: 16px; }
        .prkyc-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 18px; }
        .prkyc-grid label { font-weight: 600; display: block; }
        .prkyc-grid input[type=text], .prkyc-grid input[type=password], .prkyc-grid input[type=number], .prkyc-grid select, .prkyc-grid textarea { width: 100%; }
        .prkyc-checks label { margin-right: 20px; font-weight: 400; }
        .prkyc-section-title { font-size: 14px; font-weight: 700; margin: 4px 0 12px; }
        .prkyc-provider-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
        .prkyc-provider { border: 1px solid #d9dee3; border-radius: 6px; padding: 12px; min-height: 92px; background: #fbfcfd; }
        .prkyc-provider strong { display: block; margin-bottom: 4px; }
        .prkyc-provider.available { border-color: #9fd4ad; background: #f4fbf6; }
        .prkyc-provider.reserved { color: #667085; }
        .prkyc-rule-row { border-top: 1px solid #e7eaee; padding: 12px 0; }
        .prkyc-rule-form { display: grid; grid-template-columns: 150px 180px 150px 120px minmax(180px, 1fr) auto; gap: 10px; align-items: end; }
        .prkyc-rule-actions { display: flex; gap: 8px; align-items: center; }
        .prkyc-filters { display: grid; grid-template-columns: repeat(5, minmax(120px, 1fr)); gap: 10px; margin-bottom: 12px; align-items: end; }
        .prkyc-table { width: 100%; border-collapse: collapse; }
        .prkyc-table th, .prkyc-table td { border-top: 1px solid #e7eaee; padding: 8px; vertical-align: top; }
        .prkyc-muted { color: #667085; }
        .prkyc-status { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 12px; background: #eef2f7; }
        .prkyc-status.verified { background: #dcfce7; color: #166534; }
        .prkyc-status.pending { background: #fef9c3; color: #854d0e; }
        .prkyc-status.rejected, .prkyc-status.failed { background: #fee2e2; color: #991b1b; }
        .prkyc-status.revoked, .prkyc-status.expired { background: #f3f4f6; color: #374151; }
        .prkyc-status.available { background: #dcfce7; color: #166534; }
        .prkyc-status.reserved { background: #eef2f7; color: #475467; }
        @media (max-width: 900px) { .prkyc-grid, .prkyc-rule-form, .prkyc-filters { grid-template-columns: 1fr; } .prkyc-header { display: block; } }
        @media (max-width: 900px) { .prkyc-provider-grid { grid-template-columns: 1fr; } }
    </style>
    <div class="prkyc-wrap">
        <div class="prkyc-header">
            <div>
                <p class="prkyc-muted"><?php echo peakrackKycE($t['subtitle']); ?> <strong><?php echo peakrackKycE(PRKYC_VERSION); ?></strong></p>
            </div>
            <div>
                <a class="btn btn-default btn-sm" href="<?php echo peakrackKycE(peakrack_kyc_admin_url('en')); ?>">English</a>
                <a class="btn btn-default btn-sm" href="<?php echo peakrackKycE(peakrack_kyc_admin_url('zh')); ?>">中文</a>
            </div>
        </div>

        <?php if ($message !== '') { ?>
            <div class="alert alert-<?php echo peakrackKycE($messageType); ?>"><?php echo peakrackKycE($message); ?></div>
        <?php } ?>

        <?php if (!$storageCheck['success']) { ?>
            <div class="alert alert-danger"><?php echo peakrackKycE($storageCheck['message']); ?></div>
        <?php } ?>

        <form method="post" action="addonmodules.php?module=peakrack_kyc">
            <?php echo peakrack_kyc_admin_token_field(); ?>
            <input type="hidden" name="prkyc_action" value="save_settings">
            <?php echo peakrack_kyc_render_basic_settings($settings, $t); ?>
            <?php echo peakrack_kyc_render_rule_settings($settings, $t, $storage); ?>
            <?php echo peakrack_kyc_render_provider_settings($settings, $t); ?>
            <?php echo peakrack_kyc_render_email_settings($settings, $t); ?>
            <?php echo peakrack_kyc_render_client_notice_settings($settings, $t); ?>
            <p><button type="submit" class="btn btn-primary"><?php echo peakrackKycE($t['save']); ?></button></p>
        </form>

        <?php echo peakrack_kyc_render_rule_manager($rules, $t); ?>

        <div class="prkyc-card">
            <h2><?php echo peakrackKycE($t['review_queue']); ?></h2>
            <div class="inner">
                <?php echo peakrack_kyc_render_filters($t); ?>
                <?php echo peakrack_kyc_render_profiles_table($profiles, $language); ?>
            </div>
        </div>

        <div class="prkyc-card">
            <h2><?php echo peakrackKycE($t['recent_logs']); ?></h2>
            <div class="inner">
                <?php echo peakrack_kyc_render_logs_table($logs); ?>
            </div>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function peakrack_kyc_render_basic_settings(array $settings, array $t): string
{
    ob_start();
    ?>
    <div class="prkyc-card">
        <h2><?php echo peakrackKycE($t['settings']); ?></h2>
        <div class="inner">
            <div class="prkyc-section-title"><?php echo peakrackKycE($t['module_controls']); ?></div>
            <div class="prkyc-checks">
                <?php echo peakrack_kyc_checkbox('enabled', $settings['enabled'], $t['enabled']); ?>
                <?php echo peakrack_kyc_checkbox('manualReviewEnabled', $settings['manualReviewEnabled'], $t['manual_review']); ?>
                <?php echo peakrack_kyc_checkbox('apiVerificationEnabled', $settings['apiVerificationEnabled'], $t['api_verification']); ?>
                <?php echo peakrack_kyc_checkbox('checkoutBlockEnabled', $settings['checkoutBlockEnabled'], $t['block_checkout']); ?>
                <?php echo peakrack_kyc_checkbox('provisioningBlockEnabled', $settings['provisioningBlockEnabled'], $t['block_provisioning']); ?>
                <?php echo peakrack_kyc_checkbox('postOrderHoldEnabled', $settings['postOrderHoldEnabled'], $t['post_order_hold']); ?>
                <?php echo peakrack_kyc_checkbox('activityLog', $settings['activityLog'], $t['activity_log']); ?>
            </div>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function peakrack_kyc_render_rule_settings(array $settings, array $t, string $storage): string
{
    ob_start();
    ?>
    <div class="prkyc-card">
        <h2><?php echo peakrackKycE($t['rule_settings']); ?></h2>
        <div class="inner">
            <div class="prkyc-grid">
                <?php echo peakrack_kyc_field_select('enforcementMode', $settings['enforcementMode'], $t['enforcement_mode'], ['none' => $t['mode_none'], 'all' => $t['mode_all'], 'selected' => $t['mode_selected']]); ?>
                <?php echo peakrack_kyc_field_select('checkoutMode', $settings['checkoutMode'], $t['checkout_mode'], ['block' => $t['checkout_block'], 'allow_pending' => $t['checkout_allow_pending']]); ?>
                <?php echo peakrack_kyc_field_select('rejectedOrderAction', $settings['rejectedOrderAction'], $t['rejected_action'], ['manual' => $t['rejected_manual'], 'cancel_unpaid' => $t['rejected_cancel_unpaid']]); ?>
                <?php echo peakrack_kyc_field_number('maxUploadMb', (string) $settings['maxUploadMb'], $t['max_upload']); ?>
                <?php echo peakrack_kyc_field_text('allowedExtensions', implode(', ', $settings['allowedExtensions']), $t['allowed_extensions']); ?>
                <?php echo peakrack_kyc_field_text('storagePath', $settings['storagePath'], $t['storage_path'], $t['storage_path_help'] . ' ' . $storage); ?>
                <?php echo peakrack_kyc_field_number('retentionDays', (string) $settings['retentionDays'], $t['retention_days']); ?>
                <?php echo peakrack_kyc_field_number('maxLogs', (string) $settings['maxLogs'], $t['max_logs']); ?>
            </div>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function peakrack_kyc_render_rule_manager(array $rules, array $t): string
{
    ob_start();
    ?>
    <div class="prkyc-card">
        <h2><?php echo peakrackKycE($t['rule_manager']); ?></h2>
        <div class="inner">
            <form method="post" action="addonmodules.php?module=peakrack_kyc" class="prkyc-rule-form" style="border-bottom:1px solid #e7eaee; padding-bottom:14px;">
                <?php echo peakrack_kyc_admin_token_field(); ?>
                <input type="hidden" name="prkyc_action" value="save_rule">
                <input type="hidden" name="rule_id" value="0">
                <?php echo peakrack_kyc_rule_scope_select('scope_type', 'product', $t); ?>
                <?php echo peakrack_kyc_field_text('scope_value', '', $t['scope_value']); ?>
                <?php echo peakrack_kyc_field_select('requirement', 'verified', $t['requirement'], ['verified' => $t['requirement_verified']]); ?>
                <?php echo peakrack_kyc_checkbox('enabled', true, $t['rule_enabled']); ?>
                <?php echo peakrack_kyc_field_text('notes', '', $t['rule_notes']); ?>
                <button type="submit" class="btn btn-primary btn-sm"><?php echo peakrackKycE($t['add_rule']); ?></button>
            </form>

            <?php if (empty($rules)) { ?>
                <p class="prkyc-muted" style="margin-top:12px;"><?php echo peakrackKycE($t['no_rules']); ?></p>
            <?php } else { ?>
                <?php foreach ($rules as $rule) {
                    $ruleId = (int) ($rule->id ?? 0);
                    $enabled = (int) ($rule->enabled ?? 0) === 1;
                    ?>
                    <div class="prkyc-rule-row">
                        <form method="post" action="addonmodules.php?module=peakrack_kyc" class="prkyc-rule-form">
                            <?php echo peakrack_kyc_admin_token_field(); ?>
                            <input type="hidden" name="prkyc_action" value="save_rule">
                            <input type="hidden" name="rule_id" value="<?php echo $ruleId; ?>">
                            <?php echo peakrack_kyc_rule_scope_select('scope_type', (string) ($rule->scope_type ?? ''), $t); ?>
                            <?php echo peakrack_kyc_field_text('scope_value', (string) ($rule->scope_value ?? ''), $t['scope_value']); ?>
                            <?php echo peakrack_kyc_field_select('requirement', (string) ($rule->requirement ?? 'verified'), $t['requirement'], ['verified' => $t['requirement_verified']]); ?>
                            <?php echo peakrack_kyc_checkbox('enabled', $enabled, $t['rule_enabled']); ?>
                            <?php echo peakrack_kyc_field_text('notes', (string) ($rule->notes ?? ''), $t['rule_notes']); ?>
                            <div class="prkyc-rule-actions">
                                <button type="submit" class="btn btn-default btn-sm"><?php echo peakrackKycE($t['save_rule']); ?></button>
                            </div>
                        </form>
                        <form method="post" action="addonmodules.php?module=peakrack_kyc" style="margin-top:8px;">
                            <?php echo peakrack_kyc_admin_token_field(); ?>
                            <input type="hidden" name="prkyc_action" value="delete_rule">
                            <input type="hidden" name="rule_id" value="<?php echo $ruleId; ?>">
                            <button type="submit" class="btn btn-link btn-xs" onclick="return confirm('<?php echo peakrackKycE($t['delete_rule_confirm']); ?>');"><?php echo peakrackKycE($t['delete_rule']); ?></button>
                        </form>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function peakrack_kyc_render_provider_settings(array $settings, array $t): string
{
    ob_start();
    ?>
    <div class="prkyc-card">
        <h2><?php echo peakrackKycE($t['provider_settings']); ?></h2>
        <div class="inner">
            <input type="hidden" name="apiProvider" value="tencent_phone_three_factor">
            <div class="prkyc-provider-grid">
                <?php echo peakrack_kyc_provider_card('TencentPhoneThreeFactorProvider', $t['provider_ready'], $t['provider_tencent_desc'], 'available'); ?>
                <?php echo peakrack_kyc_provider_card('ManualReviewProvider', $t['provider_ready'], $t['provider_manual_desc'], 'available'); ?>
                <?php echo peakrack_kyc_provider_card('AlipayFaceProvider', $t['provider_reserved'], $t['provider_reserved_desc'], 'reserved'); ?>
                <?php echo peakrack_kyc_provider_card('BankCardProvider', $t['provider_reserved'], $t['provider_reserved_desc'], 'reserved'); ?>
                <?php echo peakrack_kyc_provider_card('CompanyVerificationProvider', $t['provider_reserved'], $t['provider_reserved_desc'], 'reserved'); ?>
                <?php echo peakrack_kyc_provider_card('OverseasKycProvider', $t['provider_reserved'], $t['provider_reserved_desc'], 'reserved'); ?>
            </div>
            <div class="prkyc-grid">
                <?php echo peakrack_kyc_checkbox('apiTestMode', $settings['apiTestMode'], $t['test_mode']); ?>
                <?php echo peakrack_kyc_field_number('apiTimeout', (string) $settings['apiTimeout'], $t['api_timeout']); ?>
                <?php echo peakrack_kyc_field_text('tencentSecretId', $settings['tencentSecretId'], 'Tencent SecretId'); ?>
                <?php echo peakrack_kyc_field_password('tencentSecretKey', '', 'Tencent SecretKey', $t['secret_help']); ?>
                <?php echo peakrack_kyc_field_text('tencentRegion', $settings['tencentRegion'], 'Tencent Region'); ?>
                <?php echo peakrack_kyc_field_text('tencentEndpoint', $settings['tencentEndpoint'], 'Tencent Endpoint'); ?>
                <?php echo peakrack_kyc_field_text('tencentVerifyMode', $settings['tencentVerifyMode'], 'Tencent VerifyMode'); ?>
            </div>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function peakrack_kyc_render_email_settings(array $settings, array $t): string
{
    $templates = peakrack_kyc_email_template_options($t);
    ob_start();
    ?>
    <div class="prkyc-card">
        <h2><?php echo peakrackKycE($t['email_settings']); ?></h2>
        <div class="inner">
            <div class="prkyc-checks">
                <?php echo peakrack_kyc_checkbox('emailNotifications', $settings['emailNotifications'], $t['client_email']); ?>
                <?php echo peakrack_kyc_checkbox('adminEmailNotifications', $settings['adminEmailNotifications'], $t['admin_email']); ?>
            </div>
            <hr>
            <div class="prkyc-grid">
                <?php echo peakrack_kyc_field_select('emailTemplateSubmitted', $settings['emailTemplateSubmitted'], $t['email_template_submitted'], $templates); ?>
                <?php echo peakrack_kyc_field_select('emailTemplateApproved', $settings['emailTemplateApproved'], $t['email_template_approved'], $templates); ?>
                <?php echo peakrack_kyc_field_select('emailTemplateRejected', $settings['emailTemplateRejected'], $t['email_template_rejected'], $templates); ?>
            </div>
            <p style="margin-top:12px;"><button type="submit" name="prkyc_action" value="install_email_templates" class="btn btn-default btn-sm"><?php echo peakrackKycE($t['install_email_templates']); ?></button></p>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function peakrack_kyc_render_client_notice_settings(array $settings, array $t): string
{
    ob_start();
    ?>
    <div class="prkyc-card">
        <h2><?php echo peakrackKycE($t['client_notice']); ?></h2>
        <div class="inner prkyc-grid">
            <?php echo peakrack_kyc_field_textarea('clientNoticeEn', $settings['clientNotice']['en'], 'English'); ?>
            <?php echo peakrack_kyc_field_textarea('clientNoticeZh', $settings['clientNotice']['zh'], '中文'); ?>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

function peakrack_kyc_provider_card(string $name, string $status, string $description, string $class): string
{
    return '<div class="prkyc-provider ' . peakrackKycE($class) . '"><strong>' . peakrackKycE($name) . '</strong><span class="prkyc-status ' . peakrackKycE($class) . '">' . peakrackKycE($status) . '</span><p class="prkyc-muted" style="margin:8px 0 0;">' . peakrackKycE($description) . '</p></div>';
}

function peakrack_kyc_render_filters(array $t): string
{
    $status = (string) ($_GET['filter_status'] ?? '');
    $type = (string) ($_GET['filter_type'] ?? '');
    $method = (string) ($_GET['filter_method'] ?? '');
    ob_start();
    ?>
    <form method="get" action="addonmodules.php" class="prkyc-filters">
        <input type="hidden" name="module" value="peakrack_kyc">
        <input type="number" class="form-control input-sm" name="filter_client_id" placeholder="<?php echo peakrackKycE($t['client']); ?> ID" value="<?php echo (int) ($_GET['filter_client_id'] ?? 0) ?: ''; ?>">
        <select class="form-control input-sm" name="filter_status">
            <option value=""><?php echo peakrackKycE($t['any_status']); ?></option>
            <?php foreach (['unsubmitted', 'pending', 'verified', 'rejected', 'expired', 'revoked'] as $option) { ?>
                <option value="<?php echo $option; ?>" <?php echo $status === $option ? 'selected' : ''; ?>><?php echo $option; ?></option>
            <?php } ?>
        </select>
        <select class="form-control input-sm" name="filter_type">
            <option value=""><?php echo peakrackKycE($t['any_type']); ?></option>
            <?php foreach (['individual', 'corporate', 'overseas', 'address'] as $option) { ?>
                <option value="<?php echo $option; ?>" <?php echo $type === $option ? 'selected' : ''; ?>><?php echo $option; ?></option>
            <?php } ?>
        </select>
        <select class="form-control input-sm" name="filter_method">
            <option value=""><?php echo peakrackKycE($t['any_method']); ?></option>
            <?php foreach (['manual', 'api_three_factor'] as $option) { ?>
                <option value="<?php echo $option; ?>" <?php echo $method === $option ? 'selected' : ''; ?>><?php echo $option; ?></option>
            <?php } ?>
        </select>
        <input type="text" class="form-control input-sm" name="filter_document_type" placeholder="<?php echo peakrackKycE($t['document_type_filter']); ?>" value="<?php echo peakrackKycE((string) ($_GET['filter_document_type'] ?? '')); ?>">
        <input type="text" class="form-control input-sm" name="filter_country" maxlength="2" placeholder="<?php echo peakrackKycE($t['country_filter']); ?>" value="<?php echo peakrackKycE((string) ($_GET['filter_country'] ?? '')); ?>">
        <input type="date" class="form-control input-sm" name="filter_submitted_from" value="<?php echo peakrackKycE((string) ($_GET['filter_submitted_from'] ?? '')); ?>">
        <input type="date" class="form-control input-sm" name="filter_submitted_to" value="<?php echo peakrackKycE((string) ($_GET['filter_submitted_to'] ?? '')); ?>">
        <input type="text" class="form-control input-sm" name="filter_query" placeholder="<?php echo peakrackKycE($t['keyword_filter']); ?>" value="<?php echo peakrackKycE((string) ($_GET['filter_query'] ?? '')); ?>">
        <div>
            <button type="submit" class="btn btn-default btn-sm"><?php echo peakrackKycE($t['filter']); ?></button>
            <a class="btn btn-link btn-sm" href="addonmodules.php?module=peakrack_kyc"><?php echo peakrackKycE($t['clear']); ?></a>
        </div>
    </form>
    <?php
    return (string) ob_get_clean();
}

function peakrack_kyc_render_profiles_table(array $profiles, string $language): string
{
    $t = peakrack_kyc_admin_texts($language);
    if (empty($profiles)) {
        return '<p class="prkyc-muted">' . peakrackKycE($t['no_profiles']) . '</p>';
    }

    ob_start();
    ?>
    <table class="prkyc-table">
        <thead>
        <tr>
            <th>ID</th>
            <th><?php echo peakrackKycE($t['client']); ?></th>
            <th><?php echo peakrackKycE($t['type']); ?></th>
            <th><?php echo peakrackKycE($t['status']); ?></th>
            <th><?php echo peakrackKycE($t['identity']); ?></th>
            <th><?php echo peakrackKycE($t['documents']); ?></th>
            <th><?php echo peakrackKycE($t['action']); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($profiles as $profile) {
            $profileId = (int) ($profile->id ?? 0);
            $documents = peakrackKycDocumentsForProfile($profileId);
            $allowedDecisions = function_exists('peakrackKycAllowedReviewDecisions')
                ? peakrackKycAllowedReviewDecisions((string) ($profile->status ?? 'unsubmitted'))
                : ['approve', 'reject', 'request_resubmit', 'revoke'];
            ?>
            <tr>
                <td><?php echo $profileId; ?></td>
                <td><?php echo peakrack_kyc_client_link((int) ($profile->client_id ?? 0)); ?></td>
                <td><?php echo peakrackKycE((string) ($profile->type ?? '')); ?><br><span class="prkyc-muted"><?php echo peakrackKycE((string) ($profile->document_type ?? '')); ?></span></td>
                <td><span class="prkyc-status <?php echo peakrackKycE((string) ($profile->status ?? '')); ?>"><?php echo peakrackKycE((string) ($profile->status ?? '')); ?></span><br><span class="prkyc-muted"><?php echo peakrackKycE((string) ($profile->verification_method ?? '')); ?></span></td>
                <td>
                    <?php echo peakrackKycE((string) ($profile->real_name ?? '')); ?>
                    <?php if ((string) ($profile->company_name ?? '') !== '') { ?><br><?php echo peakrackKycE((string) $profile->company_name); ?><?php } ?>
                    <br><span class="prkyc-muted">ID ****<?php echo peakrackKycE((string) ($profile->id_number_last4 ?? '')); ?> / Phone ****<?php echo peakrackKycE((string) ($profile->phone_last4 ?? '')); ?></span>
                </td>
                <td>
                    <?php foreach ($documents as $document) { ?>
                        <div>
                            <a href="addonmodules.php?module=peakrack_kyc&prkyc_action=download&docid=<?php echo (int) ($document->id ?? 0); ?><?php echo peakrack_kyc_admin_token_query(); ?>">
                                <?php echo peakrackKycE((string) ($document->original_name ?? 'document')); ?>
                            </a>
                            <span class="prkyc-muted">(<?php echo number_format(((int) ($document->file_size ?? 0)) / 1024, 1); ?> KB)</span>
                            <form method="post" action="addonmodules.php?module=peakrack_kyc" style="display:inline;">
                                <?php echo peakrack_kyc_admin_token_field(); ?>
                                <input type="hidden" name="prkyc_action" value="delete_document">
                                <input type="hidden" name="document_id" value="<?php echo (int) ($document->id ?? 0); ?>">
                                <button type="submit" class="btn btn-link btn-xs" onclick="return confirm('Delete this document?');">Delete</button>
                            </form>
                        </div>
                    <?php } ?>
                </td>
                <td>
                    <form method="post" action="addonmodules.php?module=peakrack_kyc" style="min-width: 230px;">
                        <?php echo peakrack_kyc_admin_token_field(); ?>
                        <input type="hidden" name="prkyc_action" value="review_profile">
                        <input type="hidden" name="profile_id" value="<?php echo $profileId; ?>">
                        <textarea name="reason" class="form-control" rows="2" placeholder="<?php echo peakrackKycE($t['reason']); ?>"></textarea>
                        <div style="margin-top: 6px;">
                            <?php if (in_array('approve', $allowedDecisions, true)) { ?><button type="submit" name="decision" value="approve" class="btn btn-success btn-xs"><?php echo peakrackKycE($t['approve']); ?></button><?php } ?>
                            <?php if (in_array('reject', $allowedDecisions, true)) { ?><button type="submit" name="decision" value="reject" class="btn btn-danger btn-xs"><?php echo peakrackKycE($t['reject']); ?></button><?php } ?>
                            <?php if (in_array('request_resubmit', $allowedDecisions, true)) { ?><button type="submit" name="decision" value="request_resubmit" class="btn btn-warning btn-xs"><?php echo peakrackKycE($t['resubmit']); ?></button><?php } ?>
                            <?php if (in_array('revoke', $allowedDecisions, true)) { ?><button type="submit" name="decision" value="revoke" class="btn btn-default btn-xs"><?php echo peakrackKycE($t['revoke']); ?></button><?php } ?>
                        </div>
                    </form>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    <?php
    return (string) ob_get_clean();
}

function peakrack_kyc_render_logs_table(array $logs): string
{
    if (empty($logs)) {
        return '<p class="prkyc-muted">No logs yet.</p>';
    }

    $html = '<table class="prkyc-table"><thead><tr><th>Time</th><th>Level</th><th>Client</th><th>Order</th><th>Message</th></tr></thead><tbody>';
    foreach ($logs as $log) {
        $html .= '<tr>'
            . '<td>' . peakrackKycE((string) ($log->created_at ?? '')) . '</td>'
            . '<td>' . peakrackKycE((string) ($log->level ?? '')) . '</td>'
            . '<td>' . peakrack_kyc_client_link((int) ($log->client_id ?? 0)) . '</td>'
            . '<td>' . peakrackKycE((string) ($log->order_id ?? '')) . '</td>'
            . '<td>' . peakrackKycE((string) ($log->message ?? '')) . '</td>'
            . '</tr>';
    }
    $html .= '</tbody></table>';
    return $html;
}

function peakrack_kyc_client_link(int $clientId): string
{
    if ($clientId <= 0) {
        return '-';
    }

    try {
        $client = Capsule::table('tblclients')->where('id', $clientId)->first();
        $name = trim((string) ($client->firstname ?? '') . ' ' . (string) ($client->lastname ?? ''));
        if ($name === '') {
            $name = 'Client #' . $clientId;
        }
    } catch (Throwable $e) {
        $name = 'Client #' . $clientId;
    }

    return '<a href="clientssummary.php?userid=' . $clientId . '">' . peakrackKycE($name) . '</a>';
}

function peakrack_kyc_admin_texts(string $language): array
{
    $texts = [
        'en' => [
            'subtitle' => 'WHMCS 9.0.3 KYC addon for API verification, document review, and product enforcement. Version',
            'settings' => 'Settings',
            'module_controls' => 'Module Controls',
            'rule_settings' => 'KYC Rules and Storage',
            'rule_manager' => 'Product / Product Group / TLD Rules',
            'provider_settings' => 'Provider Settings',
            'email_settings' => 'Email Notifications',
            'enabled' => 'Enable module',
            'manual_review' => 'Manual review',
            'api_verification' => 'API verification',
            'block_checkout' => 'Block checkout',
            'block_provisioning' => 'Block provisioning',
            'post_order_hold' => 'Hold post-checkout orders',
            'activity_log' => 'Mirror warnings to Activity Log',
            'client_email' => 'Client email notifications',
            'admin_email' => 'Admin email notifications',
            'admin_language' => 'Admin language',
            'enforcement_mode' => 'Enforcement mode',
            'checkout_mode' => 'Checkout mode',
            'checkout_block' => 'Block before checkout',
            'checkout_allow_pending' => 'Allow order, hold provisioning',
            'mode_none' => 'Disabled',
            'mode_all' => 'All products',
            'mode_selected' => 'Selected products only',
            'add_rule' => 'Add Rule',
            'save_rule' => 'Save Rule',
            'delete_rule' => 'Delete Rule',
            'delete_rule_confirm' => 'Delete this KYC rule?',
            'no_rules' => 'No rules configured. Add product, product group, or TLD rules here.',
            'scope_type' => 'Scope',
            'scope_value' => 'Value',
            'scope_product' => 'Product ID',
            'scope_product_group' => 'Product Group ID',
            'scope_tld' => 'TLD',
            'requirement' => 'Requirement',
            'requirement_verified' => 'Verified KYC',
            'rule_enabled' => 'Enabled',
            'rule_notes' => 'Notes',
            'product_ids' => 'Required product IDs',
            'product_ids_help' => 'Comma-separated WHMCS product IDs.',
            'product_group_ids' => 'Required product group IDs',
            'product_group_ids_help' => 'Comma-separated WHMCS product group IDs.',
            'tlds' => 'Required TLD rules',
            'tlds_help' => 'Comma-separated TLDs without dots, reserved for domain KYC rules.',
            'rejected_action' => 'Rejected profile order action',
            'rejected_manual' => 'Manual handling',
            'rejected_cancel_unpaid' => 'Cancel unpaid pending orders',
            'max_upload' => 'Max upload size (MB)',
            'allowed_extensions' => 'Allowed extensions',
            'storage_path' => 'Private storage path',
            'storage_path_help' => 'Leave blank to use default private attachment path:',
            'retention_days' => 'Log retention days',
            'max_logs' => 'Maximum log rows',
            'api_settings' => 'API Settings',
            'api_provider' => 'API provider',
            'provider_ready' => 'Ready',
            'provider_reserved' => 'Reserved',
            'provider_tencent_desc' => 'Tencent Cloud PhoneVerification for Chinese mainland phone, name, and ID matching.',
            'provider_manual_desc' => 'Manual upload and admin review for individual, company, passport, and address-proof workflows.',
            'provider_reserved_desc' => 'Reserved for a later provider adapter; no v1.0 runtime code is enabled.',
            'test_mode' => 'Test / sandbox mode',
            'secret_help' => 'Leave blank to keep the saved secret. Saved secrets are not rendered back to the page.',
            'api_timeout' => 'API timeout seconds',
            'email_template_submitted' => 'Submitted email template',
            'email_template_approved' => 'Approved email template',
            'email_template_rejected' => 'Rejected email template',
            'email_builtin_default' => 'Built-in default notification',
            'templates_unavailable' => 'templates unavailable',
            'install_email_templates' => 'Install default WHMCS email templates',
            'client_notice' => 'Client Notice',
            'save' => 'Save Settings',
            'review_queue' => 'Review Queue',
            'recent_logs' => 'Recent Logs',
            'no_profiles' => 'No verification profiles yet.',
            'client' => 'Client',
            'type' => 'Type',
            'status' => 'Status',
            'identity' => 'Identity',
            'documents' => 'Documents',
            'action' => 'Action',
            'reason' => 'Reject reason',
            'approve' => 'Approve',
            'reject' => 'Reject',
            'resubmit' => 'Request Resubmission',
            'revoke' => 'Revoke',
            'filter' => 'Filter',
            'clear' => 'Clear',
            'any_status' => 'Any status',
            'any_type' => 'Any type',
            'any_method' => 'Any method',
            'document_type_filter' => 'Document type',
            'country_filter' => 'Country',
            'keyword_filter' => 'Name / company / last 4',
        ],
        'zh' => [
            'subtitle' => '适用于 WHMCS 9.0.3 的实名认证插件，支持 API 校验、证件审核和指定产品强制实名。版本',
            'settings' => '设置',
            'module_controls' => '模块开关',
            'rule_settings' => '实名规则与存储',
            'rule_manager' => '产品 / 产品组 / TLD 规则',
            'provider_settings' => 'Provider 设置',
            'email_settings' => '邮件通知',
            'enabled' => '启用模块',
            'manual_review' => '人工审核',
            'api_verification' => 'API 校验',
            'block_checkout' => '下单前拦截',
            'block_provisioning' => '开通前拦截',
            'post_order_hold' => '下单后保持待处理',
            'activity_log' => '警告写入活动日志',
            'client_email' => '客户邮件通知',
            'admin_email' => '管理员邮件通知',
            'admin_language' => '后台语言',
            'enforcement_mode' => '强制实名范围',
            'checkout_mode' => '下单模式',
            'checkout_block' => '下单前拦截',
            'checkout_allow_pending' => '允许下单但禁止开通',
            'mode_none' => '不强制',
            'mode_all' => '全部产品',
            'mode_selected' => '仅指定产品',
            'add_rule' => '新增规则',
            'save_rule' => '保存规则',
            'delete_rule' => '删除规则',
            'delete_rule_confirm' => '确定删除这条实名规则吗？',
            'no_rules' => '还没有配置规则。可在这里添加产品、产品组或 TLD 规则。',
            'scope_type' => '范围',
            'scope_value' => '值',
            'scope_product' => '产品 ID',
            'scope_product_group' => '产品组 ID',
            'scope_tld' => 'TLD',
            'requirement' => '要求',
            'requirement_verified' => '已实名',
            'rule_enabled' => '启用',
            'rule_notes' => '备注',
            'product_ids' => '需要实名的产品 ID',
            'product_ids_help' => '填写 WHMCS 产品 ID，多个用英文逗号分隔。',
            'product_group_ids' => '需要实名的产品组 ID',
            'product_group_ids_help' => '填写 WHMCS 产品组 ID，多个用英文逗号分隔。',
            'tlds' => '需要实名的 TLD',
            'tlds_help' => '填写不带点的后缀，例如 cn, com.cn；为后续域名实名规则预留。',
            'rejected_action' => '驳回后的订单处理',
            'rejected_manual' => '人工处理',
            'rejected_cancel_unpaid' => '取消未付款待处理订单',
            'max_upload' => '最大上传大小（MB）',
            'allowed_extensions' => '允许的文件扩展名',
            'storage_path' => '私有存储路径',
            'storage_path_help' => '留空使用默认私有附件路径：',
            'retention_days' => '日志保留天数',
            'max_logs' => '最大日志行数',
            'api_settings' => 'API 设置',
            'api_provider' => 'API 提供方',
            'provider_ready' => '可用',
            'provider_reserved' => '预留',
            'provider_tencent_desc' => '腾讯云 PhoneVerification，用于中国大陆手机号、姓名、身份证三要素核验。',
            'provider_manual_desc' => '人工上传和后台审核，覆盖个人、企业、护照和地址证明流程。',
            'provider_reserved_desc' => '预留给后续 Provider 适配器，v1.0 不启用运行代码。',
            'test_mode' => '测试 / 沙箱模式',
            'secret_help' => '留空表示保留已保存密钥，已保存密钥不会回显到页面。',
            'api_timeout' => 'API 超时秒数',
            'email_template_submitted' => '提交成功邮件模板',
            'email_template_approved' => '审核通过邮件模板',
            'email_template_rejected' => '审核驳回邮件模板',
            'email_builtin_default' => '使用内置默认通知',
            'templates_unavailable' => '无法读取模板',
            'install_email_templates' => '安装默认 WHMCS 邮件模板',
            'client_notice' => '客户提示文案',
            'save' => '保存设置',
            'review_queue' => '审核队列',
            'recent_logs' => '最近日志',
            'no_profiles' => '暂无实名资料。',
            'client' => '客户',
            'type' => '类型',
            'status' => '状态',
            'identity' => '身份信息',
            'documents' => '文件',
            'action' => '操作',
            'reason' => '驳回原因',
            'approve' => '通过',
            'reject' => '驳回',
            'resubmit' => '要求重提',
            'revoke' => '撤销',
            'filter' => '筛选',
            'clear' => '清除',
            'any_status' => '全部状态',
            'any_type' => '全部类型',
            'any_method' => '全部方式',
            'document_type_filter' => '证件类型',
            'country_filter' => '国家',
            'keyword_filter' => '姓名 / 企业 / 后四位',
        ],
    ];

    $texts['en'] = array_replace($texts['en'], [
        'status_label' => 'Status',
        'method_label' => 'Method',
        'submitted_label' => 'Submitted',
        'verified_label' => 'Verified',
        'document_name' => 'Name',
        'document_status' => 'Status',
        'document_uploaded' => 'Uploaded',
        'document_action' => 'Action',
        'submit_resubmission' => 'Submit Updated Documents',
        'delete_document' => 'Delete',
        'delete_document_confirm' => 'Delete this document?',
        'resubmit_notice' => 'Your verification requires updated materials. Review the reason above and submit corrected documents.',
        'verified_notice' => 'Your identity verification is approved. Contact support if your information changes.',
    ]);
    $texts['zh'] = array_replace($texts['zh'], [
        'status_title' => '实名状态',
        'manual_title' => '人工证件审核',
        'api_title' => '中国大陆手机号三要素核验',
        'status_label' => '状态',
        'method_label' => '方式',
        'submitted_label' => '提交时间',
        'verified_label' => '通过时间',
        'real_name' => '真实姓名',
        'id_number' => '证件号码',
        'phone' => '手机号',
        'profile_type' => '实名类型',
        'individual' => '个人',
        'corporate' => '企业',
        'overseas' => '海外护照',
        'address' => '地址证明',
        'company_name' => '企业名称',
        'registration_number' => '统一社会信用代码/注册号',
        'country' => '国家/地区代码',
        'document_type' => '证件类型',
        'documents' => '证件/证明文件',
        'document_name' => '名称',
        'document_status' => '状态',
        'document_uploaded' => '上传时间',
        'document_action' => '操作',
        'notes' => '备注',
        'submit_manual' => '提交人工审核',
        'submit_resubmission' => '提交更新资料',
        'submit_api' => 'API 核验',
        'allowed' => '允许文件',
        'delete_document' => '删除',
        'delete_document_confirm' => '确定删除这个文件吗？',
        'resubmit_notice' => '你的实名资料需要更新。请查看上方原因后重新提交修正后的材料。',
        'verified_notice' => '你的实名认证已通过。如实名信息发生变化，请联系支持处理。',
    ]);

    return $texts[$language] ?? $texts['en'];
}

function peakrack_kyc_client_texts(string $language): array
{
    $texts = [
        'en' => [
            'status_title' => 'Verification Status',
            'manual_title' => 'Manual Document Review',
            'api_title' => 'Chinese Mainland Three-Factor Verification',
            'real_name' => 'Legal name',
            'id_number' => 'ID number',
            'phone' => 'Mobile number',
            'profile_type' => 'Verification type',
            'individual' => 'Individual',
            'corporate' => 'Company',
            'overseas' => 'Overseas passport',
            'address' => 'Address proof',
            'company_name' => 'Company name',
            'registration_number' => 'Registration number',
            'country' => 'Country code',
            'document_type' => 'Document type',
            'documents' => 'Documents',
            'notes' => 'Notes',
            'submit_manual' => 'Submit for Review',
            'submit_api' => 'Verify by API',
            'allowed' => 'Allowed files',
        ],
        'zh' => [
            'status_title' => '实名状态',
            'manual_title' => '人工证件审核',
            'api_title' => '中国大陆手机号三要素校验',
            'real_name' => '真实姓名',
            'id_number' => '证件号码',
            'phone' => '手机号',
            'profile_type' => '实名类型',
            'individual' => '个人',
            'corporate' => '企业',
            'overseas' => '海外护照',
            'address' => '地址证明',
            'company_name' => '企业名称',
            'registration_number' => '统一社会信用代码/注册号',
            'country' => '国家/地区代码',
            'document_type' => '证件类型',
            'documents' => '证件/证明文件',
            'notes' => '备注',
            'submit_manual' => '提交人工审核',
            'submit_api' => 'API 校验',
            'allowed' => '允许文件',
        ],
    ];

    return $texts[$language] ?? $texts['en'];
}

function peakrack_kyc_document_view(object $document): array
{
    return [
        'id' => (int) ($document->id ?? 0),
        'document_type' => (string) ($document->document_type ?? ''),
        'original_name' => (string) ($document->original_name ?? ''),
        'file_size' => (int) ($document->file_size ?? 0),
        'status' => (string) ($document->status ?? ''),
        'created_at' => (string) ($document->created_at ?? ''),
    ];
}

function peakrack_kyc_admin_language(array $settings): string
{
    $requested = (string) ($_GET['prkyc_admin_lang'] ?? '');
    if (in_array($requested, ['en', 'zh'], true)) {
        $_SESSION['peakrack_kyc_admin_lang'] = $requested;
        return $requested;
    }

    $sessionLanguage = (string) ($_SESSION['peakrack_kyc_admin_lang'] ?? '');
    if (in_array($sessionLanguage, ['en', 'zh'], true)) {
        return $sessionLanguage;
    }

    return in_array((string) ($settings['adminLanguage'] ?? 'en'), ['en', 'zh'], true) ? (string) $settings['adminLanguage'] : 'en';
}

function peakrack_kyc_admin_url(string $language): string
{
    return 'addonmodules.php?' . http_build_query([
        'module' => 'peakrack_kyc',
        'prkyc_admin_lang' => $language,
    ]);
}

function peakrack_kyc_verify_admin_token(): bool
{
    if (function_exists('check_token')) {
        return (bool) check_token('WHMCS.admin.default');
    }

    return true;
}

function peakrack_kyc_verify_client_token(): bool
{
    if (function_exists('check_token')) {
        return (bool) check_token('WHMCS.default');
    }

    return true;
}

function peakrack_kyc_admin_token_field(): string
{
    if (function_exists('generate_token')) {
        return '<input type="hidden" name="token" value="' . peakrackKycE((string) generate_token('plain')) . '">';
    }

    return '';
}

function peakrack_kyc_admin_token_query(): string
{
    if (function_exists('generate_token')) {
        return '&token=' . rawurlencode((string) generate_token('plain'));
    }

    return '';
}

function peakrack_kyc_client_token_value(): string
{
    return function_exists('generate_token') ? (string) generate_token('plain') : '';
}

function peakrack_kyc_email_template_options(array $t): array
{
    $options = [
        '' => $t['email_builtin_default'] ?? 'Built-in default notification',
    ];

    try {
        $rows = Capsule::table('tblemailtemplates')
            ->select(['name', 'type', 'language', 'disabled'])
            ->where(function ($query): void {
                $query->whereNull('disabled')->orWhere('disabled', '0')->orWhere('disabled', '');
            })
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        foreach ($rows as $row) {
            $name = trim((string) ($row->name ?? ''));
            if ($name === '') {
                continue;
            }

            $type = trim((string) ($row->type ?? ''));
            $language = trim((string) ($row->language ?? ''));
            $label = $name;
            if ($type !== '') {
                $label .= ' [' . $type . ']';
            }
            if ($language !== '') {
                $label .= ' - ' . $language;
            }
            $options[$name] = $label;
        }
    } catch (Throwable $e) {
        $options[''] = ($t['email_builtin_default'] ?? 'Built-in default notification') . ' (' . ($t['templates_unavailable'] ?? 'templates unavailable') . ')';
    }

    return $options;
}

function peakrack_kyc_checkbox(string $name, bool $checked, string $label): string
{
    return '<label><input type="checkbox" name="' . peakrackKycE($name) . '" value="1" ' . ($checked ? 'checked' : '') . '> ' . peakrackKycE($label) . '</label>';
}

function peakrack_kyc_field_text(string $name, string $value, string $label, string $help = ''): string
{
    return '<label>' . peakrackKycE($label) . '<input type="text" class="form-control" name="' . peakrackKycE($name) . '" value="' . peakrackKycE($value) . '"></label>' . ($help !== '' ? '<p class="prkyc-muted">' . peakrackKycE($help) . '</p>' : '');
}

function peakrack_kyc_field_password(string $name, string $value, string $label, string $help = ''): string
{
    return '<label>' . peakrackKycE($label) . '<input type="password" class="form-control" name="' . peakrackKycE($name) . '" value="' . peakrackKycE($value) . '" autocomplete="new-password"></label>' . ($help !== '' ? '<p class="prkyc-muted">' . peakrackKycE($help) . '</p>' : '');
}

function peakrack_kyc_field_number(string $name, string $value, string $label): string
{
    return '<label>' . peakrackKycE($label) . '<input type="number" class="form-control" name="' . peakrackKycE($name) . '" value="' . peakrackKycE($value) . '"></label>';
}

function peakrack_kyc_field_textarea(string $name, string $value, string $label): string
{
    return '<label>' . peakrackKycE($label) . '<textarea class="form-control" rows="3" name="' . peakrackKycE($name) . '">' . peakrackKycE($value) . '</textarea></label>';
}

function peakrack_kyc_field_select(string $name, string $value, string $label, array $options): string
{
    $html = '<label>' . peakrackKycE($label) . '<select class="form-control" name="' . peakrackKycE($name) . '">';
    foreach ($options as $optionValue => $optionLabel) {
        $html .= '<option value="' . peakrackKycE((string) $optionValue) . '" ' . ((string) $optionValue === $value ? 'selected' : '') . '>' . peakrackKycE((string) $optionLabel) . '</option>';
    }
    $html .= '</select></label>';
    return $html;
}

function peakrack_kyc_rule_scope_select(string $name, string $value, array $t): string
{
    return peakrack_kyc_field_select($name, $value, $t['scope_type'], [
        'product' => $t['scope_product'],
        'product_group' => $t['scope_product_group'],
        'tld' => $t['scope_tld'],
    ]);
}
