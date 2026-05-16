{* Contacts Detail Header Title: Sales hero (yellow avatar + name + map) | stock for other apps. *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
	<div class="mk-contact-detail-hero__left">
		<div class="mk-contact-detail-hero__identity clearfix">
			<div class="mk-contact-detail-hero__icon recordImage bgcontacts app-{(isset($SELECTED_MENU_CATEGORY)) ? $SELECTED_MENU_CATEGORY : ''}">
				{assign var=IMAGE_DETAILS value=$RECORD->getImageDetails()}
				{foreach key=ITER item=IMAGE_INFO from=$IMAGE_DETAILS}
					{if !empty($IMAGE_INFO.url)}
						<img src="{$IMAGE_INFO.url}" alt="{$IMAGE_INFO.orgname|escape:'html'}" title="{$IMAGE_INFO.orgname|escape:'html'}">
					{/if}
				{/foreach}
				{assign var=MK_HAS_CONTACT_PHOTO value=false}
				{foreach key=ITER item=IMAGE_INFO from=$IMAGE_DETAILS}
					{if !empty($IMAGE_INFO.url)}{assign var=MK_HAS_CONTACT_PHOTO value=true}{/if}
				{/foreach}
				{if !$MK_HAS_CONTACT_PHOTO}
					<span class="mk-contact-detail-hero__icon-glyph" aria-hidden="true"><strong>{$MODULE_MODEL->getModuleIcon()}</strong></span>
				{/if}
			</div>
			<div class="mk-contact-detail-hero__text recordBasicInfo">
				<div class="info-row mk-contact-detail-hero__name-row">
					<h1 class="mk-contact-detail-hero__title">
						<span class="recordLabel pushDown" title="{$RECORD->getDisplayValue('salutationtype')}&nbsp;{$RECORD->getName()}">
							{assign var=COUNTER value=0}
							{foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
								{assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
								{if $FIELD_MODEL->getPermissions()}
									<span class="{$NAME_FIELD}">
										{if $RECORD->getDisplayValue('salutationtype') && $FIELD_MODEL->getName() eq 'firstname'}
											{$RECORD->getDisplayValue('salutationtype')}&nbsp;
										{/if}
										{trim($RECORD->get($NAME_FIELD))}
									</span>
									{if $COUNTER eq 0 && ($RECORD->get($NAME_FIELD))}&nbsp;{assign var=COUNTER value=$COUNTER+1}{/if}
								{/if}
							{/foreach}
						</span>
					</h1>
				</div>
				<div class="mk-contact-detail-hero__map info-row">
					<i class="fa fa-map-marker" aria-hidden="true"></i>
					<a class="showMap" href="javascript:void(0);" onclick="Vtiger_Index_Js.showMap(this);" data-module="{$RECORD->getModule()->getName()}" data-record="{$RECORD->getId()}">{vtranslate('LBL_SHOW_MAP', $MODULE_NAME)}</a>
				</div>
			</div>
		</div>
	</div>
{else}
	{if $SELECTED_MENU_CATEGORY eq 'MARKETING'}
		<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Plans/resources/MarketingTheme.v2.css')}" />
	{/if}
	<div class="col-lg-6 col-md-6 col-sm-6">
		<div class="record-header clearfix">
			<div class="recordImage bgcontacts app-{$SELECTED_MENU_CATEGORY}">
				{assign var=IMAGE_DETAILS value=$RECORD->getImageDetails()}
				{foreach key=ITER item=IMAGE_INFO from=$IMAGE_DETAILS}
					{if !empty($IMAGE_INFO.url)}
						<img src="{$IMAGE_INFO.url}" alt="{$IMAGE_INFO.orgname}" title="{$IMAGE_INFO.orgname}" width="100%" height="100%" align="left"><br>
					{else}
						<img src="{vimage_path('summary_Contact.png')}" class="summaryImg"/>
					{/if}
				{/foreach}
				{if empty($IMAGE_DETAILS)}
					<div class="name"><span><strong>{$MODULE_MODEL->getModuleIcon()}</strong></span></div>
				{/if}
			</div>
			<div class="recordBasicInfo">
				<div class="info-row">
					<h4>
						<span class="recordLabel pushDown" title="{$RECORD->getDisplayValue('salutationtype')}&nbsp;{$RECORD->getName()}">
							{assign var=COUNTER value=0}
							{foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
								{assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
								{if $FIELD_MODEL->getPermissions()}
									<span class="{$NAME_FIELD}">
										{if $RECORD->getDisplayValue('salutationtype') && $FIELD_MODEL->getName() eq 'firstname'}
											{$RECORD->getDisplayValue('salutationtype')}&nbsp;
										{/if}
										{trim($RECORD->get($NAME_FIELD))}
									</span>
									{if $COUNTER eq 0 && ($RECORD->get($NAME_FIELD))}&nbsp;{assign var=COUNTER value=$COUNTER+1}{/if}
								{/if}
							{/foreach}
						</span>
					</h4>
				</div>
				{include file="DetailViewHeaderFieldsView.tpl"|vtemplate_path:$MODULE}
				<div class="info-row">
					<i class="fa fa-map-marker"></i>&nbsp;
					<a class="showMap" href="javascript:void(0);" onclick="Vtiger_Index_Js.showMap(this);" data-module="{$RECORD->getModule()->getName()}" data-record="{$RECORD->getId()}">{vtranslate('LBL_SHOW_MAP', $MODULE_NAME)}</a>
				</div>
			</div>
		</div>
	</div>
{/if}
{/strip}
