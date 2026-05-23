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
