{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
	{assign var=MK_QT_STAGE_LABEL value=$RECORD->getDisplayValue('quotestage')}
	{assign var=MK_QT_ACCOUNT value=''}
	{assign var=MK_QT_CONTACT value=''}
	{assign var=MK_QT_POTENTIAL value=''}
	{assign var=MK_QT_TOTAL value=''}
	{* getDisplayValue reference trả về HTML <a> — strip thành plain text cho hero *}
	{assign var=ACC_FIELD value=$MODULE_MODEL->getField('account_id')}
	{if $ACC_FIELD && $ACC_FIELD->getPermissions()}
		{assign var=MK_QT_ACCOUNT value=$RECORD->getDisplayValue('account_id')|strip_tags|trim}
	{/if}
	{assign var=CON_FIELD value=$MODULE_MODEL->getField('contact_id')}
	{if $CON_FIELD && $CON_FIELD->getPermissions()}
		{assign var=MK_QT_CONTACT value=$RECORD->getDisplayValue('contact_id')|strip_tags|trim}
	{/if}
	{assign var=POT_FIELD value=$MODULE_MODEL->getField('potential_id')}
	{if $POT_FIELD && $POT_FIELD->getPermissions()}
		{assign var=MK_QT_POTENTIAL value=$RECORD->getDisplayValue('potential_id')|strip_tags|trim}
	{/if}
	{assign var=TOT_FIELD value=$MODULE_MODEL->getField('hdnGrandTotal')}
	{if $TOT_FIELD && $TOT_FIELD->getPermissions()}
		{assign var=MK_QT_TOTAL value=$RECORD->getDisplayValue('hdnGrandTotal')|strip_tags|trim}
	{/if}
	<div class="mk-qt-detail-hero__left">
		<div class="mk-qt-detail-hero__identity clearfix">
			<div class="mk-qt-detail-hero__icon recordImage bgquotes app-{(isset($SELECTED_MENU_CATEGORY)) ? $SELECTED_MENU_CATEGORY : ''}">
				{assign var=IMAGE_DETAILS value=$RECORD->getImageDetails()}
				{foreach key=ITER item=IMAGE_INFO from=$IMAGE_DETAILS}
					{if !empty($IMAGE_INFO.url)}
						<img src="{$IMAGE_INFO.url}" alt="{$IMAGE_INFO.orgname|escape:'html'}" title="{$IMAGE_INFO.orgname|escape:'html'}">
					{/if}
				{/foreach}
				{assign var=MK_HAS_QT_IMAGE value=false}
				{foreach key=ITER item=IMAGE_INFO from=$IMAGE_DETAILS}
					{if !empty($IMAGE_INFO.url)}{assign var=MK_HAS_QT_IMAGE value=true}{/if}
				{/foreach}
				{if !$MK_HAS_QT_IMAGE}
					<span class="mk-qt-detail-hero__icon-svg" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15h6M9 11h6"/></svg></span>
				{/if}
			</div>
			<div class="mk-qt-detail-hero__text mk-qt-detail-hero__content recordBasicInfo">
				{* BA: chỗ tiêu đề = tên khách hàng (plain text, không subject) *}
				{assign var=MK_HERO_TITLE value=$MK_QT_ACCOUNT}
				{if $MK_HERO_TITLE eq '' || $MK_HERO_TITLE eq '--'}
					{assign var=MK_HERO_TITLE value=$MK_QT_CONTACT}
				{/if}
				{if $MK_HERO_TITLE eq '' || $MK_HERO_TITLE eq '--'}
					{foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
						{if $NAME_FIELD ne 'subject'}
							{assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
							{if $FIELD_MODEL && $FIELD_MODEL->getPermissions()}
								{assign var=MK_HERO_TITLE value=decode_html(trim($RECORD->get($NAME_FIELD)))|strip_tags|trim}
							{/if}
						{/if}
					{/foreach}
				{/if}
				{if $MK_HERO_TITLE eq '' || $MK_HERO_TITLE eq '--'}
					{assign var=MK_HERO_TITLE value=$RECORD->getDisplayValue('quote_no')|strip_tags|trim}
				{/if}
				<h1 class="mk-qt-detail-hero__title">
					<span class="recordLabel" title="{$MK_HERO_TITLE|escape:'html'}">
						<span class="account_id">{$MK_HERO_TITLE|escape:'html'}</span>
					</span>
				</h1>
				<div class="mk-qt-detail-hero__meta">
					{if !empty($MK_QUOTE_CONVERTED_BADGE) || !empty($MK_QUOTE_SO_REF_VIEW)}
						<span class="mk-qt-detail-hero__converted-badge" title="Báo giá đã chuyển thành đơn hàng">Đã chuyển đơn hàng</span>
					{/if}
					{if $MK_QT_STAGE_LABEL ne ''}
						<span class="mk-qt-detail-hero__quote-stage" title="{vtranslate('quotestage', $MODULE)}">{$MK_QT_STAGE_LABEL}</span>
					{/if}
					{if $MK_QT_CONTACT ne '' && $MK_QT_CONTACT ne $MK_HERO_TITLE}
						<span class="mk-qt-detail-hero__meta-item mk-qt-detail-hero__meta-item--contact" title="{vtranslate('contact_id', $MODULE)}">
							<span class="mk-qt-meta-svg" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
							<span class="mk-qt-detail-hero__meta-text">{$MK_QT_CONTACT|escape:'html'}</span>
						</span>
					{/if}
					{if $MK_QT_TOTAL ne ''}
						<span class="mk-qt-detail-hero__meta-item mk-qt-detail-hero__meta-item--total" title="{vtranslate('hdnGrandTotal', $MODULE)}">
							<span class="mk-qt-meta-svg" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
							<span class="mk-qt-detail-hero__meta-text">{$MK_QT_TOTAL|escape:'html'}</span>
						</span>
					{/if}
					{if $MK_QT_POTENTIAL ne ''}
						<span class="mk-qt-detail-hero__meta-item mk-qt-detail-hero__meta-item--opp" title="{vtranslate('potential_id', $MODULE)}">
							<span class="mk-qt-meta-svg" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"><path d="M12 2l2.2 6.8H21l-5.5 4 2.1 6.7L12 15.3 6.4 20.5l2.1-6.7L3 8.8h6.8L12 2z"/></svg></span>
							<span class="mk-qt-detail-hero__meta-text">{$MK_QT_POTENTIAL|escape:'html'}</span>
						</span>
					{/if}
				</div>
			</div>
		</div>
	</div>
{else}
    <div class="col-sm-6">
        <div class="record-header clearfix">
            <div class="recordImage bgquotes app-{$SELECTED_MENU_CATEGORY}">
                {assign var=IMAGE_DETAILS value=$RECORD->getImageDetails()}
                {foreach key=ITER item=IMAGE_INFO from=$IMAGE_DETAILS}
                    {if !empty($IMAGE_INFO.url)}
                        <img src="{$IMAGE_INFO.url}" alt="{$IMAGE_INFO.orgname}" title="{$IMAGE_INFO.orgname}" width="100%" height="100%" align="left"><br>
                    {else}
                        <img src="{vimage_path('summary_organizations.png')}" class="summaryImg"/>
                    {/if}
                {/foreach}
				{if empty($IMAGE_DETAILS)}
					<div class="name"><span><strong>{$MODULE_MODEL->getModuleIcon()}</strong></span></div>
				{/if}
            </div>
            <div class="recordBasicInfo">
                <div class="info-row">
                    <h4>
                        <span class="recordLabel pushDown" title="{$RECORD->getName()}">
                            {foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
                                {assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
                                {if $FIELD_MODEL->getPermissions()}
                                    <span class="{$NAME_FIELD}">{trim($RECORD->get($NAME_FIELD))}</span>&nbsp;
                                {/if}
                            {/foreach}
                        </span>
                    </h4>
                </div>
                {include file="DetailViewHeaderFieldsView.tpl"|vtemplate_path:$MODULE}
            </div>
        </div>
    </div>
{/if}
{/strip}
