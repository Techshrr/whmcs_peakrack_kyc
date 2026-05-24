{if $prkyc.message}
    <div class="alert alert-{$prkyc.messageType|escape}">
        {$prkyc.message|escape}
    </div>
{/if}

<div class="panel panel-default card mb-3">
    <div class="panel-heading card-header">
        <h3 class="panel-title card-title m-0">{$prkyc.text.status_title|escape}</h3>
    </div>
    <div class="panel-body card-body">
        <p>{$prkyc.settings.notice|escape}</p>
        <dl class="row">
            <dt class="col-sm-3">{$prkyc.text.status_label|escape}</dt>
            <dd class="col-sm-9">
                <span class="label label-default badge bg-secondary">{$prkyc.profile.status|escape}</span>
            </dd>
            <dt class="col-sm-3">{$prkyc.text.method_label|escape}</dt>
            <dd class="col-sm-9">{$prkyc.profile.verification_method|default:'-'|escape}</dd>
            <dt class="col-sm-3">{$prkyc.text.submitted_label|escape}</dt>
            <dd class="col-sm-9">{$prkyc.profile.submitted_at|default:'-'|escape}</dd>
            <dt class="col-sm-3">{$prkyc.text.verified_label|escape}</dt>
            <dd class="col-sm-9">{$prkyc.profile.verified_at|default:'-'|escape}</dd>
        </dl>
        {if $prkyc.profile.rejection_reason}
            <div class="alert alert-warning">{$prkyc.profile.rejection_reason|escape}</div>
        {/if}
        {if $prkyc.profile.last_error}
            <div class="alert alert-danger">{$prkyc.profile.last_error|escape}</div>
        {/if}
        {if $prkyc.profile.status == 'rejected' || $prkyc.profile.status == 'revoked' || $prkyc.profile.status == 'expired'}
            <div class="alert alert-info">{$prkyc.text.resubmit_notice|escape}</div>
        {/if}
        {if $prkyc.profile.status == 'verified'}
            <div class="alert alert-success">{$prkyc.text.verified_notice|escape}</div>
        {/if}
    </div>
</div>

{if $prkyc.settings.apiVerificationEnabled}
    <div class="panel panel-default card mb-3">
        <div class="panel-heading card-header">
            <h3 class="panel-title card-title m-0">{$prkyc.text.api_title|escape}</h3>
        </div>
        <div class="panel-body card-body">
            <form method="post" action="{$prkyc.modulelink|escape}">
                <input type="hidden" name="token" value="{$prkyc.token|escape}">
                <input type="hidden" name="prkyc_client_action" value="api_three_factor">
                <div class="row">
                    <div class="col-md-4 form-group mb-3">
                        <label for="prkyc-real-name-api">{$prkyc.text.real_name|escape}</label>
                        <input type="text" class="form-control" id="prkyc-real-name-api" name="real_name" autocomplete="name" required>
                    </div>
                    <div class="col-md-4 form-group mb-3">
                        <label for="prkyc-id-number-api">{$prkyc.text.id_number|escape}</label>
                        <input type="text" class="form-control" id="prkyc-id-number-api" name="id_number" autocomplete="off" required>
                    </div>
                    <div class="col-md-4 form-group mb-3">
                        <label for="prkyc-phone-api">{$prkyc.text.phone|escape}</label>
                        <input type="text" class="form-control" id="prkyc-phone-api" name="phone" autocomplete="tel" required>
                    </div>
                    {if $prkyc.settings.apiProvider == 'bank_card_multi_factor'}
                        <div class="col-md-4 form-group mb-3">
                            <label for="prkyc-bank-card-api">{$prkyc.text.bank_card|escape}</label>
                            <input type="text" class="form-control" id="prkyc-bank-card-api" name="bank_card" autocomplete="off" required>
                        </div>
                    {/if}
                </div>
                <button type="submit" class="btn btn-primary">{$prkyc.text.submit_api|escape}</button>
            </form>
        </div>
    </div>
{/if}

{if $prkyc.settings.alipayRealNameEnabled}
    <div class="panel panel-default card mb-3">
        <div class="panel-heading card-header">
            <h3 class="panel-title card-title m-0">{$prkyc.text.alipay_title|escape}</h3>
        </div>
        <div class="panel-body card-body">
            <p class="small text-muted">{$prkyc.text.alipay_help|escape}</p>
            <form method="post" action="{$prkyc.modulelink|escape}">
                <input type="hidden" name="token" value="{$prkyc.token|escape}">
                <input type="hidden" name="prkyc_client_action" value="alipay_real_name_start">
                <div class="row">
                    <div class="col-md-4 form-group mb-3">
                        <label for="prkyc-real-name-alipay">{$prkyc.text.real_name|escape}</label>
                        <input type="text" class="form-control" id="prkyc-real-name-alipay" name="real_name" autocomplete="name" required>
                    </div>
                    <div class="col-md-4 form-group mb-3">
                        <label for="prkyc-id-number-alipay">{$prkyc.text.id_number|escape}</label>
                        <input type="text" class="form-control" id="prkyc-id-number-alipay" name="id_number" autocomplete="off" required>
                    </div>
                    <div class="col-md-4 form-group mb-3">
                        <label for="prkyc-phone-alipay">{$prkyc.text.phone|escape}</label>
                        <input type="text" class="form-control" id="prkyc-phone-alipay" name="phone" autocomplete="tel">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{$prkyc.text.submit_alipay|escape}</button>
            </form>
        </div>
    </div>
{/if}

{if $prkyc.settings.alipayFaceEnabled}
    <div class="panel panel-default card mb-3">
        <div class="panel-heading card-header">
            <h3 class="panel-title card-title m-0">{$prkyc.text.alipay_face_title|escape}</h3>
        </div>
        <div class="panel-body card-body">
            <p class="small text-muted">{$prkyc.text.alipay_face_help|escape}</p>
            <form method="post" action="{$prkyc.modulelink|escape}">
                <input type="hidden" name="token" value="{$prkyc.token|escape}">
                <input type="hidden" name="prkyc_client_action" value="alipay_face_start">
                <div class="row">
                    <div class="col-md-6 form-group mb-3">
                        <label for="prkyc-real-name-alipay-face">{$prkyc.text.real_name|escape}</label>
                        <input type="text" class="form-control" id="prkyc-real-name-alipay-face" name="real_name" autocomplete="name" required>
                    </div>
                    <div class="col-md-6 form-group mb-3">
                        <label for="prkyc-id-number-alipay-face">{$prkyc.text.id_number|escape}</label>
                        <input type="text" class="form-control" id="prkyc-id-number-alipay-face" name="id_number" autocomplete="off" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{$prkyc.text.submit_alipay_face|escape}</button>
            </form>
        </div>
    </div>
{/if}

{if $prkyc.settings.bankCardEnabled}
    <div class="panel panel-default card mb-3">
        <div class="panel-heading card-header">
            <h3 class="panel-title card-title m-0">{$prkyc.text.bank_card_title|escape}</h3>
        </div>
        <div class="panel-body card-body">
            <form method="post" action="{$prkyc.modulelink|escape}">
                <input type="hidden" name="token" value="{$prkyc.token|escape}">
                <input type="hidden" name="prkyc_client_action" value="bank_card_verify">
                <div class="row">
                    <div class="col-md-3 form-group mb-3">
                        <label for="prkyc-real-name-bank">{$prkyc.text.real_name|escape}</label>
                        <input type="text" class="form-control" id="prkyc-real-name-bank" name="real_name" autocomplete="name" required>
                    </div>
                    <div class="col-md-3 form-group mb-3">
                        <label for="prkyc-id-number-bank">{$prkyc.text.id_number|escape}</label>
                        <input type="text" class="form-control" id="prkyc-id-number-bank" name="id_number" autocomplete="off" required>
                    </div>
                    <div class="col-md-3 form-group mb-3">
                        <label for="prkyc-phone-bank">{$prkyc.text.phone|escape}</label>
                        <input type="text" class="form-control" id="prkyc-phone-bank" name="phone" autocomplete="tel" {if $prkyc.settings.bankCardFactorMode == '4'}required{/if}>
                    </div>
                    <div class="col-md-3 form-group mb-3">
                        <label for="prkyc-bank-card">{$prkyc.text.bank_card|escape}</label>
                        <input type="text" class="form-control" id="prkyc-bank-card" name="bank_card" autocomplete="off" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{$prkyc.text.submit_bank_card|escape}</button>
            </form>
        </div>
    </div>
{/if}

{if $prkyc.settings.companyVerificationEnabled}
    <div class="panel panel-default card mb-3">
        <div class="panel-heading card-header">
            <h3 class="panel-title card-title m-0">{$prkyc.text.company_api_title|escape}</h3>
        </div>
        <div class="panel-body card-body">
            <form method="post" action="{$prkyc.modulelink|escape}">
                <input type="hidden" name="token" value="{$prkyc.token|escape}">
                <input type="hidden" name="prkyc_client_action" value="company_verify">
                <div class="row">
                    <div class="col-md-6 form-group mb-3">
                        <label for="prkyc-company-api-name">{$prkyc.text.company_name|escape}</label>
                        <input type="text" class="form-control" id="prkyc-company-api-name" name="company_name" autocomplete="organization" required>
                    </div>
                    <div class="col-md-6 form-group mb-3">
                        <label for="prkyc-company-api-reg">{$prkyc.text.registration_number|escape}</label>
                        <input type="text" class="form-control" id="prkyc-company-api-reg" name="registration_number" autocomplete="off" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group mb-3">
                        <label for="prkyc-legal-name">{$prkyc.text.legal_person_name|escape}</label>
                        <input type="text" class="form-control" id="prkyc-legal-name" name="legal_person_name" autocomplete="name" required>
                    </div>
                    <div class="col-md-6 form-group mb-3">
                        <label for="prkyc-legal-id">{$prkyc.text.legal_person_id|escape}</label>
                        <input type="text" class="form-control" id="prkyc-legal-id" name="legal_person_id" autocomplete="off" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{$prkyc.text.submit_company_api|escape}</button>
            </form>
        </div>
    </div>
{/if}

{if $prkyc.settings.legalFaceEnabled}
    <div class="panel panel-default card mb-3">
        <div class="panel-heading card-header">
            <h3 class="panel-title card-title m-0">{$prkyc.text.legal_face_title|escape}</h3>
        </div>
        <div class="panel-body card-body">
            <p class="small text-muted">{$prkyc.text.legal_face_help|escape}</p>
            <form method="post" action="{$prkyc.modulelink|escape}">
                <input type="hidden" name="token" value="{$prkyc.token|escape}">
                <input type="hidden" name="prkyc_client_action" value="alipay_face_start">
                <input type="hidden" name="profile_type" value="corporate">
                <div class="row">
                    <div class="col-md-6 form-group mb-3">
                        <label for="prkyc-legal-face-company">{$prkyc.text.company_name|escape}</label>
                        <input type="text" class="form-control" id="prkyc-legal-face-company" name="company_name" autocomplete="organization">
                    </div>
                    <div class="col-md-6 form-group mb-3">
                        <label for="prkyc-legal-face-reg">{$prkyc.text.registration_number|escape}</label>
                        <input type="text" class="form-control" id="prkyc-legal-face-reg" name="registration_number" autocomplete="off">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group mb-3">
                        <label for="prkyc-legal-face-name">{$prkyc.text.legal_person_name|escape}</label>
                        <input type="text" class="form-control" id="prkyc-legal-face-name" name="real_name" autocomplete="name" required>
                    </div>
                    <div class="col-md-6 form-group mb-3">
                        <label for="prkyc-legal-face-id">{$prkyc.text.legal_person_id|escape}</label>
                        <input type="text" class="form-control" id="prkyc-legal-face-id" name="id_number" autocomplete="off" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{$prkyc.text.submit_legal_face|escape}</button>
            </form>
        </div>
    </div>
{/if}

{if $prkyc.settings.overseasKycEnabled}
    <div class="panel panel-default card mb-3">
        <div class="panel-heading card-header">
            <h3 class="panel-title card-title m-0">{$prkyc.text.overseas_api_title|escape}</h3>
        </div>
        <div class="panel-body card-body">
            <form method="post" action="{$prkyc.modulelink|escape}">
                <input type="hidden" name="token" value="{$prkyc.token|escape}">
                <input type="hidden" name="prkyc_client_action" value="overseas_kyc_verify">
                <div class="row">
                    <div class="col-md-4 form-group mb-3">
                        <label for="prkyc-overseas-name">{$prkyc.text.real_name|escape}</label>
                        <input type="text" class="form-control" id="prkyc-overseas-name" name="real_name" autocomplete="name" required>
                    </div>
                    <div class="col-md-4 form-group mb-3">
                        <label for="prkyc-passport-number">{$prkyc.text.passport_number|escape}</label>
                        <input type="text" class="form-control" id="prkyc-passport-number" name="passport_number" autocomplete="off" required>
                    </div>
                    <div class="col-md-2 form-group mb-3">
                        <label for="prkyc-overseas-country">{$prkyc.text.country|escape}</label>
                        <input type="text" class="form-control" id="prkyc-overseas-country" name="country" maxlength="2" placeholder="US" required>
                    </div>
                    <div class="col-md-2 form-group mb-3">
                        <label for="prkyc-overseas-document-type">{$prkyc.text.document_type|escape}</label>
                        <input type="text" class="form-control" id="prkyc-overseas-document-type" name="document_type" value="passport">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{$prkyc.text.submit_overseas_api|escape}</button>
            </form>
        </div>
    </div>
{/if}

{if $prkyc.settings.manualReviewEnabled}
    <div class="panel panel-default card mb-3">
        <div class="panel-heading card-header">
            <h3 class="panel-title card-title m-0">{$prkyc.text.manual_title|escape}</h3>
        </div>
        <div class="panel-body card-body">
            <form method="post" action="{$prkyc.modulelink|escape}" enctype="multipart/form-data">
                <input type="hidden" name="token" value="{$prkyc.token|escape}">
                <input type="hidden" name="prkyc_client_action" value="manual_submit">
                <div class="row">
                    <div class="col-md-4 form-group mb-3">
                        <label for="prkyc-profile-type">{$prkyc.text.profile_type|escape}</label>
                        <select class="form-control" id="prkyc-profile-type" name="profile_type">
                            <option value="individual">{$prkyc.text.individual|escape}</option>
                            <option value="corporate">{$prkyc.text.corporate|escape}</option>
                            <option value="overseas">{$prkyc.text.overseas|escape}</option>
                            <option value="address">{$prkyc.text.address|escape}</option>
                        </select>
                    </div>
                    <div class="col-md-4 form-group mb-3">
                        <label for="prkyc-document-type">{$prkyc.text.document_type|escape}</label>
                        <input type="text" class="form-control" id="prkyc-document-type" name="document_type" placeholder="id_card, passport, business_license, utility_bill">
                    </div>
                    <div class="col-md-4 form-group mb-3">
                        <label for="prkyc-country">{$prkyc.text.country|escape}</label>
                        <input type="text" class="form-control" id="prkyc-country" name="country" maxlength="2" placeholder="US">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group mb-3">
                        <label for="prkyc-real-name">{$prkyc.text.real_name|escape}</label>
                        <input type="text" class="form-control" id="prkyc-real-name" name="real_name" autocomplete="name">
                    </div>
                    <div class="col-md-4 form-group mb-3">
                        <label for="prkyc-id-number">{$prkyc.text.id_number|escape}</label>
                        <input type="text" class="form-control" id="prkyc-id-number" name="id_number" autocomplete="off">
                    </div>
                    <div class="col-md-4 form-group mb-3">
                        <label for="prkyc-phone">{$prkyc.text.phone|escape}</label>
                        <input type="text" class="form-control" id="prkyc-phone" name="phone" autocomplete="tel">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group mb-3">
                        <label for="prkyc-company-name">{$prkyc.text.company_name|escape}</label>
                        <input type="text" class="form-control" id="prkyc-company-name" name="company_name" autocomplete="organization">
                    </div>
                    <div class="col-md-6 form-group mb-3">
                        <label for="prkyc-registration-number">{$prkyc.text.registration_number|escape}</label>
                        <input type="text" class="form-control" id="prkyc-registration-number" name="registration_number" autocomplete="off">
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label for="prkyc-documents">{$prkyc.text.documents|escape}</label>
                    <input type="file" class="form-control" id="prkyc-documents" name="documents[]" multiple required>
                    <p class="help-block small text-muted">
                        {$prkyc.text.allowed|escape}: {$prkyc.settings.allowedExtensions|escape}; {$prkyc.settings.maxUploadMb|escape} MB max each.
                    </p>
                </div>
                <div class="form-group mb-3">
                    <label for="prkyc-notes">{$prkyc.text.notes|escape}</label>
                    <textarea class="form-control" id="prkyc-notes" name="notes" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    {if $prkyc.profile.status == 'rejected' || $prkyc.profile.status == 'revoked' || $prkyc.profile.status == 'expired'}
                        {$prkyc.text.submit_resubmission|escape}
                    {else}
                        {$prkyc.text.submit_manual|escape}
                    {/if}
                </button>
            </form>
        </div>
    </div>
{/if}

{if $prkyc.documents}
    <div class="panel panel-default card">
        <div class="panel-heading card-header">
            <h3 class="panel-title card-title m-0">{$prkyc.text.documents|escape}</h3>
        </div>
        <div class="panel-body card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>{$prkyc.text.document_name|escape}</th>
                        <th>{$prkyc.text.document_type|escape}</th>
                        <th>{$prkyc.text.document_status|escape}</th>
                        <th>{$prkyc.text.document_uploaded|escape}</th>
                        {if $prkyc.canDeleteDocuments}
                            <th>{$prkyc.text.document_action|escape}</th>
                        {/if}
                    </tr>
                    </thead>
                    <tbody>
                    {foreach $prkyc.documents as $document}
                        <tr>
                            <td>{$document.original_name|escape}</td>
                            <td>{$document.document_type|escape}</td>
                            <td>{$document.status|escape}</td>
                            <td>{$document.created_at|escape}</td>
                            {if $prkyc.canDeleteDocuments}
                                <td>
                                    <form method="post" action="{$prkyc.modulelink|escape}" onsubmit="return confirm('{$prkyc.text.delete_document_confirm|escape:'javascript'}');">
                                        <input type="hidden" name="token" value="{$prkyc.token|escape}">
                                        <input type="hidden" name="prkyc_client_action" value="delete_document">
                                        <input type="hidden" name="document_id" value="{$document.id|escape}">
                                        <button type="submit" class="btn btn-default btn-sm">{$prkyc.text.delete_document|escape}</button>
                                    </form>
                                </td>
                            {/if}
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
{/if}
