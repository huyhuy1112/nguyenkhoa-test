{* Campaigns Detail header title — Marketing hero (Figma) *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}
	{assign var=MK_CAMP_TYPE value=$RECORD->getDisplayValue('campaigntype')}
	{assign var=MK_CAMP_CREATED value=$RECORD->getDisplayValue('createdtime')}
	<div class="mk-camp-detail-hero__left">
		<div class="mk-camp-detail-hero__identity clearfix">
			<div class="mk-camp-detail-hero__icon recordImage bg{$MODULE|lower} app-{(isset($SELECTED_MENU_CATEGORY)) ? $SELECTED_MENU_CATEGORY : ''}">
				<span class="mk-camp-detail-hero__icon-glyph" aria-hidden="true">{include file="partials/CampaignDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='MEGAPHONE'}</span>
			</div>
			<div class="mk-camp-detail-hero__text recordBasicInfo">
				<div class="info-row mk-camp-detail-hero__name-row">
					<h1 class="mk-camp-detail-hero__title">
						<span class="recordLabel pushDown" title="{$RECORD->getName()|escape:'html'}">
							{foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
								{assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
								{if $FIELD_MODEL->getPermissions()}
									<span class="{$NAME_FIELD}">{decode_html($RECORD->get($NAME_FIELD))}</span>&nbsp;
								{/if}
							{/foreach}
						</span>
					</h1>
				</div>
				<div class="mk-camp-detail-hero__meta">
					{if !empty($MK_CAMP_TYPE)}
						<span class="mk-camp-detail-hero__type-pill">{$MK_CAMP_TYPE}</span>
					{/if}
					{if !empty($MK_CAMP_CREATED)}
						<span class="mk-camp-detail-hero__created">Created at {$MK_CAMP_CREATED}</span>
					{/if}
				</div>
			</div>
		</div>
	</div>
{else}
	<div class="col-lg-6 col-md-6 col-sm-6">
		<div class="record-header clearfix mk-campaign-record-header">
			{if !$MODULE}
				{assign var=MODULE value=$MODULE_NAME}
			{/if}
			<div class="recordImage bg_{$MODULE} app-{(isset($SELECTED_MENU_CATEGORY)) ? $SELECTED_MENU_CATEGORY : ''}">
				<div class="name">
					<span class="mk-campaign-header-icon-wrap">
						<strong><i class="fa fa-bullhorn mk-campaign-header-icon" aria-hidden="true"></i></strong>
					</span>
				</div>
			</div>
			<div class="recordBasicInfo">
				<div class="info-row">
					<h4>
						<span class="recordLabel pushDown mk-campaign-record-label" title="{$RECORD->getName()}">
							{foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
								{assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
								{if $FIELD_MODEL->getPermissions()}
									<span class="{$NAME_FIELD}">{decode_html($RECORD->get($NAME_FIELD))}</span>&nbsp;
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
