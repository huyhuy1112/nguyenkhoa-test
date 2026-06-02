{* Create Service Contract — dashboard shell + stock vtiger #EditView (all real fields). *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=ServiceContracts&view=List&app=SALES'}
{assign var=MK_IS_EDIT value=(!empty($RECORD_ID) && empty($IS_DUPLICATE))}
<div class="mk-sc-create{if $MK_IS_EDIT} mk-sc-create--edit{/if}" id="mkScCreateWorkspace" data-mk-sc-create="1">
	<header class="mk-sc-page-head">
		<nav class="mk-sc-page-head__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=Home&view=MainPage&app=SALES">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			<span aria-hidden="true">/</span>
			<a href="{$MK_LIST_URL}">{vtranslate($MODULE, $MODULE)}</a>
			<span aria-hidden="true">/</span>
			{if $MK_IS_EDIT}<span aria-current="page">{vtranslate('LBL_EDITING', $MODULE)}</span>{else}<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>{/if}
		</nav>
		<div class="mk-sc-page-head__row">
			<div>
				{if $MK_IS_EDIT}
					<h1 class="mk-sc-page-head__title">{vtranslate('LBL_EDITING', $MODULE)} {vtranslate('SINGLE_ServiceContracts', $MODULE)}</h1>
					{if !empty($RECORD_STRUCTURE_MODEL)}<p class="mk-sc-page-head__sub">{$RECORD_STRUCTURE_MODEL->getRecordName()|escape}</p>{else}<p class="mk-sc-page-head__sub">{vtranslate('LBL_SERVICE_CONTRACT_INFORMATION', $MODULE)}</p>{/if}
				{else}
					<h1 class="mk-sc-page-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_ServiceContracts', $MODULE)}</h1>
					<p class="mk-sc-page-head__sub">{vtranslate('LBL_SERVICE_CONTRACT_INFORMATION', $MODULE)}</p>
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
		{include file="layouts/v7/modules/Vtiger/EditView.tpl"}
	</div>
</div>
{/strip}
