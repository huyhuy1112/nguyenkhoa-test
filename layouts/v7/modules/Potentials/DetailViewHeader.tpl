{* Potentials Detail header: match Leads v4 flat hero + KPI strip. *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
	{assign var=MK_OPP_AMOUNT value=$RECORD->getDisplayValue('amount')|strip_tags|decode_html|trim}
	{assign var=MK_OPP_STAGE_LABEL value=$RECORD->getDisplayValue('sales_stage')|strip_tags|decode_html|trim}
	{assign var=MK_OPP_CLOSE_DATE value=$RECORD->getDisplayValue('closingdate')|strip_tags|decode_html|trim}
	{assign var=MK_OPP_LEADSOURCE value=$RECORD->getDisplayValue('leadsource')|strip_tags|decode_html|trim}
	{assign var=MK_OPP_AREA value=$MK_OPP_FULL_ADDRESS|default:''|strip_tags|decode_html|trim}
	{if empty($MK_OPP_AREA)}
		{assign var=MK_OPP_AREA value=$RECORD->getDisplayValue('related_to')|strip_tags|decode_html|trim}
	{/if}
	{if empty($MK_OPP_AMOUNT)}{assign var=MK_OPP_AMOUNT value='—'}{/if}
	{if empty($MK_OPP_STAGE_LABEL)}{assign var=MK_OPP_STAGE_LABEL value='—'}{/if}
	{if empty($MK_OPP_LEADSOURCE)}{assign var=MK_OPP_LEADSOURCE value='—'}{/if}
	{if empty($MK_OPP_AREA)}{assign var=MK_OPP_AREA value='—'}{/if}
<div class="detailview-header-block mk-opportunity-detail-hero-strip">
	<div class="detailview-header mk-opportunity-detail-hero mk-opportunity-detail-hero--flat">
		<div class="mk-opportunity-detail-hero__row mk-opportunity-detail-hero__main">
			{include file="DetailViewHeaderTitle.tpl"|vtemplate_path:$MODULE}
			{include file="DetailViewActions.tpl"|vtemplate_path:$MODULE}
		</div>
	</div>
	<div class="mk-opportunity-detail-kpi" aria-label="Chỉ số nhanh">
		<div class="mk-opportunity-detail-kpi__card">
			<span class="mk-opportunity-detail-kpi__label">Giai đoạn</span>
			<strong class="mk-opportunity-detail-kpi__value">{$MK_OPP_STAGE_LABEL|escape:'html'}</strong>
		</div>
		<div class="mk-opportunity-detail-kpi__card mk-opportunity-detail-kpi__card--value">
			<span class="mk-opportunity-detail-kpi__label">Giá trị</span>
			<strong class="mk-opportunity-detail-kpi__value">{$MK_OPP_AMOUNT|escape:'html'}</strong>
		</div>
		<div class="mk-opportunity-detail-kpi__card">
			<span class="mk-opportunity-detail-kpi__label">Nguồn</span>
			<strong class="mk-opportunity-detail-kpi__value">{$MK_OPP_LEADSOURCE|escape:'html'}</strong>
		</div>
		<div class="mk-opportunity-detail-kpi__card">
			<span class="mk-opportunity-detail-kpi__label">Khu vực</span>
			<strong class="mk-opportunity-detail-kpi__value" title="{$MK_OPP_AREA|escape:'html'}">{$MK_OPP_AREA|escape:'html'}</strong>
		</div>
	</div>
</div>
{else}
<div class=" detailview-header-block">
	<div class="detailview-header">
		<div class="row">
			{include file="DetailViewHeaderTitle.tpl"|vtemplate_path:$MODULE}
			{include file="DetailViewActions.tpl"|vtemplate_path:'Vtiger'}
		</div>
	</div>
</div>
{/if}
{/strip}
