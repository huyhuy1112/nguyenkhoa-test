{* Create / Edit Khách chuyển nhượng — form khớp bảng DATA KHÁCH HÀNG NHƯỢNG QUYỀN *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=ServiceContracts&view=List&app=SALES'}
{assign var=MK_RECORD_ID value=$RECORD_ID|default:''}
{if $MK_RECORD_ID eq '' && !empty($RECORD)}{assign var=MK_RECORD_ID value=$RECORD}{/if}
{assign var=MK_IS_EDIT value=($MK_RECORD_ID neq '' && empty($IS_DUPLICATE))}
<div class="mk-sc-create{if $MK_IS_EDIT} mk-sc-create--edit{/if}" id="mkScCreateWorkspace" data-mk-sc-create="1" data-record-id="{$MK_RECORD_ID|escape:'html'}">
	<header class="mk-sc-page-head">
		<nav class="mk-sc-page-head__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=Home&view=MainPage&app=SALES">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			<span aria-hidden="true">/</span>
			<a href="{$MK_LIST_URL}">{vtranslate($MODULE, $MODULE)}</a>
			<span aria-hidden="true">/</span>
			{if $MK_IS_EDIT}
				<span aria-current="page">{vtranslate('LBL_EDITING', $MODULE)}</span>
			{else}
				<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>
			{/if}
		</nav>
		<div class="mk-sc-page-head__row">
			<div>
				{if $MK_IS_EDIT}
					<h1 class="mk-sc-page-head__title">{vtranslate('LBL_EDITING', $MODULE)} {vtranslate('SINGLE_ServiceContracts', $MODULE)}</h1>
					<p class="mk-sc-page-head__sub" id="mkScAffiliateHint">{vtranslate('LBL_MK_SC_FRANCHISE_FORM_SUB', $MODULE)}</p>
				{else}
					<h1 class="mk-sc-page-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_ServiceContracts', $MODULE)}</h1>
					<p class="mk-sc-page-head__sub">{vtranslate('LBL_MK_SC_FRANCHISE_FORM_SUB', $MODULE)}</p>
				{/if}
			</div>
			<div class="mk-sc-page-head__actions">
				<a class="mk-sc-btn mk-sc-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				<button type="button" class="mk-sc-btn mk-sc-btn--primary" id="mkScSaveTop" data-action="save">
					{vtranslate('LBL_SAVE', $MODULE)}
				</button>
			</div>
		</div>
	</header>

	<div class="mk-sc-form-host" id="mkScFormHost">
		<form class="mk-sc-franchise-form" id="mkScFranchiseForm" autocomplete="off" novalidate>
			<input type="hidden" name="record" id="mkScRecordId" value="{$MK_RECORD_ID|escape:'html'}" />

			<section class="mk-sc-franchise-card">
				<div class="mk-sc-franchise-card__head">
					<h2>{vtranslate('LBL_MK_SC_FRANCHISE_INFO', $MODULE)}</h2>
					<span class="mk-sc-franchise-aff" id="mkScAffiliateBadge" hidden></span>
				</div>
				<div class="mk-sc-franchise-grid">
					<label class="mk-sc-field">
						<span class="mk-sc-field__label">{vtranslate('LBL_MK_SC_RECEIVED_DATE', $MODULE)}</span>
						<input type="date" class="mk-sc-input" name="received_date" id="mkScReceivedDate" />
					</label>
					<label class="mk-sc-field">
						<span class="mk-sc-field__label">{vtranslate('LBL_MK_SC_FULL_NAME', $MODULE)} <em>*</em></span>
						<input type="text" class="mk-sc-input" name="full_name" id="mkScFullName" required maxlength="255" placeholder="{vtranslate('LBL_MK_SC_FULL_NAME', $MODULE)}" />
					</label>
					<label class="mk-sc-field">
						<span class="mk-sc-field__label">{vtranslate('LBL_MK_SC_PHONE', $MODULE)} <em>*</em></span>
						<input type="tel" class="mk-sc-input" name="phone" id="mkScPhone" required maxlength="64" placeholder="09xxxxxxxx" />
					</label>
					<label class="mk-sc-field mk-sc-field--wide">
						<span class="mk-sc-field__label">{vtranslate('LBL_MK_SC_BUSINESS_NOTE', $MODULE)}</span>
						<input type="text" class="mk-sc-input" name="business_note" id="mkScBusinessNote" maxlength="512" placeholder="{vtranslate('LBL_MK_SC_BUSINESS_NOTE', $MODULE)}" />
					</label>
					<label class="mk-sc-field">
						<span class="mk-sc-field__label">{vtranslate('LBL_MK_SC_FRANCHISE_STATUS', $MODULE)}</span>
						<select class="mk-sc-input mk-sc-select" name="franchise_status" id="mkScFranchiseStatus">
							<option value="">—</option>
						</select>
					</label>
					<label class="mk-sc-field">
						<span class="mk-sc-field__label">{vtranslate('LBL_MK_SC_FANPAGE', $MODULE)}</span>
						<select class="mk-sc-input mk-sc-select" name="fanpage" id="mkScFanpage">
							<option value="">—</option>
						</select>
					</label>
					<label class="mk-sc-field">
						<span class="mk-sc-field__label">{vtranslate('LBL_MK_SC_DATA_SOURCE', $MODULE)}</span>
						<select class="mk-sc-input mk-sc-select" name="data_source" id="mkScDataSource">
							<option value="">—</option>
						</select>
					</label>
					<label class="mk-sc-field">
						<span class="mk-sc-field__label">{vtranslate('LBL_MK_SC_REFERRER', $MODULE)}</span>
						<input type="text" class="mk-sc-input" name="referrer" id="mkScReferrer" maxlength="255" />
					</label>
					<label class="mk-sc-field">
						<span class="mk-sc-field__label">{vtranslate('LBL_MK_SC_CONTACT_STATUS', $MODULE)}</span>
						<select class="mk-sc-input mk-sc-select" name="contact_status" id="mkScContactStatus">
							<option value="">—</option>
						</select>
					</label>
				</div>
			</section>

			<section class="mk-sc-franchise-card">
				<div class="mk-sc-franchise-card__head">
					<h2>{vtranslate('LBL_MK_SC_INTERACTIONS', $MODULE)}</h2>
				</div>
				<div class="mk-sc-franchise-grid mk-sc-franchise-grid--stack">
					<label class="mk-sc-field mk-sc-field--wide">
						<span class="mk-sc-field__label">{vtranslate('LBL_MK_SC_INTERACTION_1', $MODULE)}</span>
						<textarea class="mk-sc-input mk-sc-textarea" name="interaction_1" id="mkScInteraction1" rows="3"></textarea>
					</label>
					<label class="mk-sc-field mk-sc-field--wide">
						<span class="mk-sc-field__label">{vtranslate('LBL_MK_SC_INTERACTION_2', $MODULE)}</span>
						<textarea class="mk-sc-input mk-sc-textarea" name="interaction_2" id="mkScInteraction2" rows="3"></textarea>
					</label>
					<label class="mk-sc-field mk-sc-field--wide">
						<span class="mk-sc-field__label">{vtranslate('LBL_MK_SC_INTERACTION_3', $MODULE)}</span>
						<textarea class="mk-sc-input mk-sc-textarea" name="interaction_3" id="mkScInteraction3" rows="3"></textarea>
					</label>
					<label class="mk-sc-field mk-sc-field--wide">
						<span class="mk-sc-field__label">{vtranslate('LBL_MK_SC_INTERACTION_MATERIALS', $MODULE)}</span>
						<textarea class="mk-sc-input mk-sc-textarea" name="interaction_materials" id="mkScInteractionMaterials" rows="4"></textarea>
					</label>
				</div>
			</section>

			<div class="mk-sc-franchise-foot">
				<p class="mk-sc-franchise-error" id="mkScFormError" hidden></p>
				<a class="mk-sc-btn mk-sc-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				<button type="submit" class="mk-sc-btn mk-sc-btn--primary" id="mkScSaveBottom">{vtranslate('LBL_SAVE', $MODULE)}</button>
			</div>
		</form>
	</div>
</div>
{/strip}
