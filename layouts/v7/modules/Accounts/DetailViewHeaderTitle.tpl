{* Organizations Detail header title: modern hero (Sales + Marketing) | legacy elsewhere. *}
{strip}
{if !empty($MK_ACCOUNTS_MODERN_UI) || (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'MARKETING')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'MARKETING'))}
	<div class="mk-acc-detail-hero__left">
		<div class="mk-acc-detail-hero__identity clearfix">
			<div class="recordImage bgAccounts app-{$SELECTED_MENU_CATEGORY} mk-acc-detail-hero__avatar">
				{assign var=IMAGE_DETAILS value=$RECORD->getImageDetails()}
				{assign var=MK_HAS_ORG_PHOTO value=false}
				{foreach key=ITER item=IMAGE_INFO from=$IMAGE_DETAILS}
					{if !empty($IMAGE_INFO.url)}
						{assign var=MK_HAS_ORG_PHOTO value=true}
						<img src="{$IMAGE_INFO.url}" alt="{$IMAGE_INFO.orgname|escape:'html'}" title="{$IMAGE_INFO.orgname|escape:'html'}">
					{/if}
				{/foreach}
				{if !$MK_HAS_ORG_PHOTO}
					<span class="mk-acc-detail-hero__icon-glyph" aria-hidden="true">{include file="partials/AccountsDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='BUILDING'}</span>
				{/if}
			</div>
			<div class="mk-acc-detail-hero__text">
				<h1 class="mk-acc-detail-hero__title">
					<span class="recordLabel" title="{$RECORD->getName()}">
						{foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
							{assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
							{if $FIELD_MODEL->getPermissions()}
								<span class="{$NAME_FIELD}">{trim($RECORD->get($NAME_FIELD))}</span>&nbsp;
							{/if}
						{/foreach}
					</span>
				</h1>
				{include file="DetailViewHeaderFieldsView.tpl"|vtemplate_path:$MODULE}
				<div class="mk-acc-detail-hero__maplink">
					<span class="mk-acc-detail-hero__map-ic" aria-hidden="true">{include file="partials/AccountsDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='MAP'}</span>
					<a class="showMap" href="javascript:void(0);" onclick="Vtiger_Index_Js.showMap(this);" data-module="{$RECORD->getModule()->getName()}" data-record="{$RECORD->getId()}">{vtranslate('LBL_SHOW_MAP', $MODULE_NAME)}</a>
				</div>
			</div>
		</div>
	</div>
{else}
	{if $SELECTED_MENU_CATEGORY eq 'MARKETING'}
		<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Plans/resources/MarketingTheme.v2.css')}" />
	{/if}
	<div class="col-sm-6">
		<div class="clearfix record-header ">
			<div class="recordImage bgAccounts app-{$SELECTED_MENU_CATEGORY}">
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
				<div class="info-row" >
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
				<div class="info-row">
					<i class="fa fa-map-marker"></i>&nbsp;
					<a class="showMap" href="javascript:void(0);" onclick='Vtiger_Index_Js.showMap(this);' data-module='{$RECORD->getModule()->getName()}' data-record='{$RECORD->getId()}'>{vtranslate('LBL_SHOW_MAP', $MODULE_NAME)}</a>
				</div>
			</div>
		</div>
	</div>
{/if}
{/strip}
