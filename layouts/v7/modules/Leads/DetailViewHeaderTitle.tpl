{* Leads Detail hero — name, company, status, contact meta. *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
	{assign var=MK_LEAD_COMPANY      value=$RECORD->getDisplayValue('company')}
	{assign var=MK_LEAD_PHONE        value=$RECORD->getDisplayValue('phone')}
	{assign var=MK_LEAD_EMAIL        value=$RECORD->getDisplayValue('email')}
	{assign var=MK_LEAD_STATUS_RAW   value=$RECORD->get('leadstatus')}
	{assign var=MK_LEAD_STATUS_LABEL value=$RECORD->getDisplayValue('leadstatus')}
	{assign var=MK_LEAD_SOURCE       value=$RECORD->getDisplayValue('leadsource')}
	<div class="mk-lead-detail-hero__left">
		<div class="mk-lead-detail-hero__identity clearfix">
			<div class="mk-lead-detail-hero__icon recordImage bg{$MODULE|lower} app-{(isset($SELECTED_MENU_CATEGORY)) ? $SELECTED_MENU_CATEGORY : ''}">
				<span class="mk-lead-detail-hero__icon-glyph" aria-hidden="true">{include file="partials/LeadDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='LEAD'}</span>
			</div>
			<div class="mk-lead-detail-hero__text recordBasicInfo">
				<div class="info-row mk-lead-detail-hero__name-row">
					<h1 class="mk-lead-detail-hero__title">
						<span class="recordLabel pushDown" title="{$RECORD->getName()}">
							{foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
								{assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
								{if $FIELD_MODEL->getPermissions()}
									<span class="{$NAME_FIELD}">{decode_html($RECORD->get($NAME_FIELD))}</span>&nbsp;
								{/if}
							{/foreach}
						</span>
					</h1>
				</div>
				{if !empty($MK_LEAD_COMPANY)}
					<p class="mk-lead-detail-hero__subtitle" title="{vtranslate('company', $MODULE)}">{$MK_LEAD_COMPANY}</p>
				{/if}
				<div class="mk-lead-detail-hero__meta">
					{if !empty($MK_LEAD_PHONE)}
						<span class="mk-lead-detail-hero__meta-item" title="{vtranslate('phone', $MODULE)}">
							<span class="mk-lead-detail-hero__meta-ic" aria-hidden="true">{include file="partials/LeadDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='PHONE'}</span>
							<span class="mk-lead-detail-hero__meta-text">{$MK_LEAD_PHONE}</span>
						</span>
					{/if}
					{if !empty($MK_LEAD_EMAIL)}
						<span class="mk-lead-detail-hero__meta-item" title="{vtranslate('email', $MODULE)}">
							<span class="mk-lead-detail-hero__meta-ic" aria-hidden="true">{include file="partials/LeadDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='EMAIL_META'}</span>
							<span class="mk-lead-detail-hero__meta-text">{$MK_LEAD_EMAIL}</span>
						</span>
					{/if}
					{if !empty($MK_LEAD_SOURCE)}
						<span class="mk-lead-detail-hero__meta-item" title="{vtranslate('leadsource', $MODULE)}">
							<span class="mk-lead-detail-hero__meta-ic" aria-hidden="true">{include file="partials/LeadDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='SOURCE'}</span>
							<span class="mk-lead-detail-hero__meta-text">{$MK_LEAD_SOURCE}</span>
						</span>
					{/if}
					{if !empty($MK_LEAD_STATUS_LABEL)}
						{assign var=MK_LEAD_STATUS_KEY value=$MK_LEAD_STATUS_RAW|lower|regex_replace:"/[^a-z0-9]+/":"-"}
						<span class="mk-lead-detail-hero__stage mk-lead-stage-pill mk-lead-stage-pill--{$MK_LEAD_STATUS_KEY}" data-stage="{$MK_LEAD_STATUS_RAW}">
							<span class="mk-lead-stage-pill__dot" aria-hidden="true"></span>
							<span class="mk-lead-stage-pill__text">{$MK_LEAD_STATUS_LABEL}</span>
						</span>
					{/if}
				</div>
			</div>
		</div>
	</div>
{else}
	<div class="col-sm-6 col-lg-6 col-md-6">
		<div class="record-header clearfix">
			<div class="recordImage bgleads app-{$SELECTED_MENU_CATEGORY}">
				<div class="name"><span><strong>{$MODULE_MODEL->getModuleIcon()}</strong></span></div>
			</div>
			<div class="recordBasicInfo">
				<div class="info-row">
					<h4>
						<span class="recordLabel pushDown" title="{$RECORD->getName()}">
							{foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
								{assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
								{if $FIELD_MODEL->getPermissions()}
									<span class="{$NAME_FIELD}">{$RECORD->get($NAME_FIELD)}</span>&nbsp;
								{/if}
							{/foreach}
						</span>
					</h4>
				</div>
				{include file="DetailViewHeaderFieldsView.tpl"|vtemplate_path:'Vtiger'}
			</div>
		</div>
	</div>
{/if}
{/strip}
