{*<!--
/*********************************************************************************
** Potentials Detail Header Title: Sales hero matching Leads v4
** (avatar + name + plain meta + inline tags).
********************************************************************************/
-->*}
{strip}
{if isset($QUICK_PREVIEW) && $QUICK_PREVIEW}
	{assign var=MK_QP_MODULE value=$MODULE_NAME|default:$MODULE}
	{assign var=MK_OPP_RELATED_TO value=$RECORD->getDisplayValue('related_to')}
	{assign var=MK_OPP_AMOUNT value=$RECORD->getDisplayValue('amount')}
	{assign var=MK_OPP_STAGE_LABEL value=$RECORD->getDisplayValue('sales_stage')}
	{assign var=MK_OPP_CLOSE_DATE value=$RECORD->getDisplayValue('closingdate')}
	<div class="mk-quick-preview-hero mk-quick-preview-hero--{$MK_QP_MODULE|lower}">
		<div class="mk-quick-preview-hero__icon recordImage bg{$MK_QP_MODULE|lower}">
			<span class="name"><strong>{$MODULE_MODEL->getModuleIcon()}</strong></span>
		</div>
		<div class="mk-quick-preview-hero__body recordBasicInfo">
			<h4 class="mk-quick-preview-hero__title">
				<span class="recordLabel" title="{$RECORD->getName()}">
					{foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
						{assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
						{if $FIELD_MODEL->getPermissions()}
							<span class="{$NAME_FIELD}">{decode_html($RECORD->get($NAME_FIELD))}</span>&nbsp;
						{/if}
					{/foreach}
				</span>
			</h4>
			{if $MK_QP_MODULE eq 'Potentials'}
				<div class="mk-quick-preview-hero__meta">
					{if !empty($MK_OPP_STAGE_LABEL)}<span class="mk-quick-preview-hero__pill">{$MK_OPP_STAGE_LABEL}</span>{/if}
					{if !empty($MK_OPP_RELATED_TO)}<span>{$MK_OPP_RELATED_TO}</span>{/if}
					{if !empty($MK_OPP_CLOSE_DATE)}<span>{$MK_OPP_CLOSE_DATE}</span>{/if}
					{if !empty($MK_OPP_AMOUNT)}<span class="mk-quick-preview-hero__amount">{$MK_OPP_AMOUNT}</span>{/if}
				</div>
			{else}
				{include file="DetailViewHeaderFieldsView.tpl"|vtemplate_path:'Vtiger'}
			{/if}
		</div>
	</div>
{elseif (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
	{assign var=MK_OPP_RELATED_TO   value=$RECORD->getDisplayValue('related_to')|strip_tags|decode_html|trim}
	{assign var=MK_OPP_CLOSE_DATE   value=$RECORD->getDisplayValue('closingdate')|strip_tags|decode_html|trim}
	{assign var=MK_OPP_OWNER        value=$RECORD->getDisplayValue('assigned_user_id')|strip_tags|decode_html|trim}
	{assign var=MK_OPP_TITLE value=$RECORD->getName()|decode_html|trim}
	{assign var=MK_OPP_ADDR value=$MK_OPP_FULL_ADDRESS|default:''|strip_tags|decode_html|trim}
	<div class="mk-opportunity-detail-hero__left">
		<div class="mk-opportunity-detail-hero__identity clearfix">
			<div class="mk-opportunity-detail-hero__icon recordImage bg{$MODULE|lower} app-{(isset($SELECTED_MENU_CATEGORY)) ? $SELECTED_MENU_CATEGORY : ''}">
				<span class="mk-opportunity-detail-hero__icon-glyph" aria-hidden="true">{include file="partials/OpportunityDetailSvgIcon.tpl"|@vtemplate_path:'Potentials' ICON='OPPORTUNITY'}</span>
			</div>
			<div class="mk-opportunity-detail-hero__text recordBasicInfo">
				<div class="info-row mk-opportunity-detail-hero__name-row mk-opportunity-detail-hero__title-row">
					<h1 class="mk-opportunity-detail-hero__title">
						<span class="recordLabel pushDown" title="{$MK_OPP_TITLE|escape:'html'}">
							{foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
								{assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
								{if $FIELD_MODEL->getPermissions()}
									<span class="{$NAME_FIELD}">{$RECORD->get($NAME_FIELD)|decode_html|trim|escape:'html'}</span>&nbsp;
								{/if}
							{/foreach}
						</span>
					</h1>
					{if !empty($MK_OPP_RELATED_TO)}
						<p class="mk-opportunity-detail-hero__subtitle" title="{vtranslate('related_to', $MODULE)}">{$MK_OPP_RELATED_TO|escape:'html'}</p>
					{/if}
				</div>
				<div class="mk-opportunity-detail-hero__meta-row">
					<div class="mk-opportunity-detail-hero__meta">
						{if !empty($MK_OPP_ADDR)}
							<span class="mk-opportunity-detail-hero__meta-item mk-opportunity-detail-hero__meta-item--addr" title="{vtranslate('LBL_MK_OPP_ADDRESS', 'Potentials')}">
								<span class="mk-opportunity-detail-hero__meta-ic" aria-hidden="true">{include file="partials/OpportunityDetailSvgIcon.tpl"|@vtemplate_path:'Potentials' ICON='LOCATION'}</span>
								<span class="mk-opportunity-detail-hero__meta-text">{$MK_OPP_ADDR|escape:'html'}</span>
							</span>
						{/if}
						{if !empty($MK_OPP_CLOSE_DATE)}
							<span class="mk-opportunity-detail-hero__meta-item mk-opportunity-detail-hero__meta-item--date" title="{vtranslate('closingdate', $MODULE)}">
								<span class="mk-opportunity-detail-hero__meta-ic" aria-hidden="true">{include file="partials/OpportunityDetailSvgIcon.tpl"|@vtemplate_path:'Potentials' ICON='CALENDAR'}</span>
								<span class="mk-opportunity-detail-hero__meta-text">{$MK_OPP_CLOSE_DATE|escape:'html'}</span>
							</span>
						{/if}
						{if !empty($MK_OPP_OWNER)}
							<span class="mk-opportunity-detail-hero__meta-item mk-opportunity-detail-hero__meta-item--owner" title="{vtranslate('assigned_user_id', $MODULE)}">
								<span class="mk-opportunity-detail-hero__meta-ic" aria-hidden="true">{include file="partials/OpportunityDetailSvgIcon.tpl"|@vtemplate_path:'Potentials' ICON='USER'}</span>
								<span class="mk-opportunity-detail-hero__meta-text">{$MK_OPP_OWNER|escape:'html'}</span>
							</span>
						{/if}
					</div>
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
