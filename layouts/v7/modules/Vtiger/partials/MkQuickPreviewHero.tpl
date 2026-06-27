{* Universal Quick Preview header — no module SVG partials (avoids template load errors) *}
{strip}
{assign var=MK_QP_MODULE value=$MODULE_NAME|default:$MODULE}
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
		<div class="mk-quick-preview-hero__meta">
			{if $MK_QP_MODULE eq 'Potentials'}
				{assign var=MK_OPP_STAGE_LABEL value=$RECORD->getDisplayValue('sales_stage')}
				{assign var=MK_OPP_RELATED_TO value=$RECORD->getDisplayValue('related_to')}
				{assign var=MK_OPP_CLOSE_DATE value=$RECORD->getDisplayValue('closingdate')}
				{assign var=MK_OPP_AMOUNT value=$RECORD->getDisplayValue('amount')}
				{if !empty($MK_OPP_STAGE_LABEL)}<span class="mk-quick-preview-hero__pill">{$MK_OPP_STAGE_LABEL}</span>{/if}
				{if !empty($MK_OPP_RELATED_TO)}<span>{$MK_OPP_RELATED_TO}</span>{/if}
				{if !empty($MK_OPP_CLOSE_DATE)}<span><i class="fa fa-calendar-o"></i> {$MK_OPP_CLOSE_DATE}</span>{/if}
				{if !empty($MK_OPP_AMOUNT)}<span class="mk-quick-preview-hero__amount">{$MK_OPP_AMOUNT}</span>{/if}
			{elseif $MK_QP_MODULE eq 'ServiceContracts'}
				{assign var=MK_SC_STATUS value=$RECORD->getDisplayValue('contract_status')}
				{assign var=MK_SC_RELATED value=''}
				{assign var=REL_FIELD value=$MODULE_MODEL->getField('sc_related_to')}
				{if !$REL_FIELD}{assign var=REL_FIELD value=$MODULE_MODEL->getField('related_to')}{/if}
				{if $REL_FIELD && $REL_FIELD->getPermissions()}
					{assign var=MK_SC_RELATED value=$RECORD->getDisplayValue($REL_FIELD->getName())}
				{/if}
				{assign var=MK_SC_CONTRACT_NO value=$RECORD->getDisplayValue('contract_no')}
				{if !empty($MK_SC_STATUS)}<span class="mk-quick-preview-hero__pill">{$MK_SC_STATUS}</span>{/if}
				{if !empty($MK_SC_RELATED)}<span>{$MK_SC_RELATED}</span>{/if}
				{if !empty($MK_SC_CONTRACT_NO)}<span>#{$MK_SC_CONTRACT_NO}</span>{/if}
			{elseif $MK_QP_MODULE eq 'Project' || $MK_QP_MODULE eq 'ProjectTask'}
				{include file="DetailViewHeaderFieldsView.tpl"|vtemplate_path:$MK_QP_MODULE}
			{else}
				{include file="DetailViewHeaderFieldsView.tpl"|vtemplate_path:'Vtiger'}
			{/if}
		</div>
	</div>
</div>
{/strip}
