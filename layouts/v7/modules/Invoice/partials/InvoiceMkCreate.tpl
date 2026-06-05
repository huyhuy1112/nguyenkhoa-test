{* Create Invoice — dashboard shell + stock Inventory #EditView (fields + line items). *}
{strip}
{assign var=MK_APP value=$SELECTED_MENU_CATEGORY|default:'TOOLS'}
{assign var=MK_LIST_URL value="index.php?module=Invoice&view=List&app=`$MK_APP`"}
{assign var=MK_IS_EDIT value=(!empty($RECORD_ID) && empty($IS_DUPLICATE))}
<div class="mk-inv-create{if $MK_IS_EDIT} mk-inv-create--edit{/if}" id="mkInvCreateWorkspace" data-mk-invoice-create="1">
	<header class="mk-inv-page-head">
		<nav class="mk-inv-page-head__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=Home&view=MainPage&app={$MK_APP}">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			<span aria-hidden="true">/</span>
			<a href="{$MK_LIST_URL}">{vtranslate('Invoice', $MODULE)}</a>
			<span aria-hidden="true">/</span>
			{if $MK_IS_EDIT}<span aria-current="page">{vtranslate('LBL_EDITING', $MODULE)}</span>{else}<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>{/if}
		</nav>
		<div class="mk-inv-page-head__panel">
			<div class="mk-inv-page-head__row">
				<div>
					<p class="mk-inv-page-head__eyebrow">{vtranslate('Invoice', $MODULE)}</p>
					{if $MK_IS_EDIT}
						<h1 class="mk-inv-page-head__title">{vtranslate('LBL_EDITING', $MODULE)} {vtranslate('SINGLE_Invoice', $MODULE)}</h1>
						{if !empty($RECORD_STRUCTURE_MODEL)}<p class="mk-inv-page-head__sub">{$RECORD_STRUCTURE_MODEL->getRecordName()|escape}</p>{else}<p class="mk-inv-page-head__sub">{vtranslate('LBL_INVOICE_INFORMATION', $MODULE)}</p>{/if}
					{else}
						<h1 class="mk-inv-page-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_Invoice', $MODULE)}</h1>
						<p class="mk-inv-page-head__sub">{vtranslate('LBL_INVOICE_INFORMATION', $MODULE)}</p>
					{/if}
				</div>
				<div class="mk-inv-page-head__actions">
					<a class="mk-inv-btn mk-inv-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
					<button type="button" class="mk-inv-btn mk-inv-btn--primary" id="mkInvSaveTop" data-action="save">
						{vtranslate('LBL_SAVE', $MODULE)}
					</button>
				</div>
			</div>
		</div>
	</header>

	<div class="mk-inv-form-host" id="mkInvFormHost">
		{include file="partials/InvoiceMkInventoryForm.tpl"|vtemplate_path:$MODULE}
	</div>
</div>
{/strip}
