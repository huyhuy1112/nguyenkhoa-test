{*<!--
/*********************************************************************************
** Potentials Detail Header Title: Sales hero (icon + name + org + amount + stage),
** falls back to stock Vtiger record-header for other apps.
********************************************************************************/
-->*}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
	{assign var=MK_OPP_RELATED_TO   value=$RECORD->getDisplayValue('related_to')}
	{assign var=MK_OPP_AMOUNT       value=$RECORD->getDisplayValue('amount')}
	{assign var=MK_OPP_STAGE_RAW    value=$RECORD->get('sales_stage')}
	{assign var=MK_OPP_STAGE_LABEL  value=$RECORD->getDisplayValue('sales_stage')}
	{assign var=MK_OPP_CLOSE_DATE   value=$RECORD->getDisplayValue('closingdate')}
	<div class="mk-opportunity-detail-hero__left">
		<div class="mk-opportunity-detail-hero__identity clearfix">
			<div class="mk-opportunity-detail-hero__icon recordImage bg{$MODULE|lower} app-{(isset($SELECTED_MENU_CATEGORY)) ? $SELECTED_MENU_CATEGORY : ''}">
				<span class="mk-opportunity-detail-hero__icon-glyph" aria-hidden="true">{include file="partials/OpportunityDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='OPPORTUNITY'}</span>
			</div>
			<div class="mk-opportunity-detail-hero__text recordBasicInfo">
				<div class="info-row mk-opportunity-detail-hero__name-row">
					<h1 class="mk-opportunity-detail-hero__title">
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
				{if !empty($MK_OPP_RELATED_TO)}
					<p class="mk-opportunity-detail-hero__subtitle" title="{vtranslate('related_to', $MODULE)}">{$MK_OPP_RELATED_TO}</p>
				{/if}
				<div class="mk-opportunity-detail-hero__meta">
					{if !empty($MK_OPP_AMOUNT)}
						<span class="mk-opportunity-detail-hero__meta-item mk-opportunity-detail-hero__meta-item--amount" title="{vtranslate('amount', $MODULE)}">
							<span class="mk-opportunity-detail-hero__meta-ic" aria-hidden="true">{include file="partials/OpportunityDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='AMOUNT'}</span>
							<span class="mk-opportunity-detail-hero__meta-text">{$MK_OPP_AMOUNT}</span>
						</span>
					{/if}
					{if !empty($MK_OPP_CLOSE_DATE)}
						<span class="mk-opportunity-detail-hero__meta-item mk-opportunity-detail-hero__meta-item--date" title="{vtranslate('closingdate', $MODULE)}">
							<span class="mk-opportunity-detail-hero__meta-ic" aria-hidden="true">{include file="partials/OpportunityDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='CALENDAR'}</span>
							<span class="mk-opportunity-detail-hero__meta-text">{$MK_OPP_CLOSE_DATE}</span>
						</span>
					{/if}
					{if !empty($MK_OPP_STAGE_LABEL)}
						{assign var=MK_OPP_STAGE_KEY value=$MK_OPP_STAGE_RAW|lower|regex_replace:"/[^a-z0-9]+/":"-"}
						<span class="mk-opportunity-detail-hero__stage mk-opportunity-stage-pill mk-opportunity-stage-pill--{$MK_OPP_STAGE_KEY}" data-stage="{$MK_OPP_STAGE_RAW}">
							<span class="mk-opportunity-stage-pill__dot" aria-hidden="true"></span>
							<span class="mk-opportunity-stage-pill__text">{$MK_OPP_STAGE_LABEL}</span>
						</span>
					{/if}
				</div>
			</div>
		</div>
	</div>
{else}
	<div class="col-sm-6 col-lg-6 col-md-6">
		<div class="record-header clearfix">
			<div class="recordImage bgpotentials app-{$SELECTED_MENU_CATEGORY}">
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
