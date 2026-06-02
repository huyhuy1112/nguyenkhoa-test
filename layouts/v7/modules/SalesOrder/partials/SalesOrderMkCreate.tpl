{* Create Sales Order — dashboard shell + stock Inventory #EditView (fields + line items). *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=SalesOrder&view=List&app=SALES'}
{assign var=MK_IS_EDIT value=(!empty($RECORD_ID) && empty($IS_DUPLICATE))}
<div class="mk-so-create{if $MK_IS_EDIT} mk-so-create--edit{/if}" id="mkSoCreateWorkspace" data-mk-sales-order-create="1">
	<header class="mk-so-page-head">
		<nav class="mk-so-page-head__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=Home&view=MainPage&app=SALES">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			<span aria-hidden="true">/</span>
			<a href="{$MK_LIST_URL}">{vtranslate('SalesOrder', $MODULE)}</a>
			<span aria-hidden="true">/</span>
			{if $MK_IS_EDIT}<span aria-current="page">{vtranslate('LBL_EDITING', $MODULE)}</span>{else}<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>{/if}
		</nav>
		<div class="mk-so-page-head__row">
			<div>
				{if $MK_IS_EDIT}
					<h1 class="mk-so-page-head__title">{vtranslate('LBL_EDITING', $MODULE)} {vtranslate('SINGLE_SalesOrder', $MODULE)}</h1>
					{if !empty($RECORD_STRUCTURE_MODEL)}<p class="mk-so-page-head__sub">{$RECORD_STRUCTURE_MODEL->getRecordName()|escape}</p>{else}<p class="mk-so-page-head__sub">{vtranslate('LBL_SO_INFORMATION', $MODULE)}</p>{/if}
				{else}
					<h1 class="mk-so-page-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_SalesOrder', $MODULE)}</h1>
					<p class="mk-so-page-head__sub">{vtranslate('LBL_SO_INFORMATION', $MODULE)}</p>
				{/if}
			</div>
			<div class="mk-so-page-head__actions">
				<a class="mk-so-btn mk-so-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				<button type="button" class="mk-so-btn mk-so-btn--primary" id="mkSoSaveTop" data-action="save">
					{vtranslate('LBL_SAVE', $MODULE)}
				</button>
			</div>
		</div>
	</header>

	<div class="mk-so-form-host" id="mkSoFormHost">
		{include file="partials/SalesOrderMkInventoryForm.tpl"|vtemplate_path:$MODULE}
	</div>
</div>
{/strip}
